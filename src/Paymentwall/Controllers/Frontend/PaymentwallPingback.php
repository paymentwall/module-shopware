<?php

use Paymentwall\Components\Services\DeliveryConfirmationService;
use Paymentwall\Components\Services\UtilService;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;

class Shopware_Controllers_Frontend_PaymentwallPingback extends Shopware_Controllers_Frontend_Payment
{
    protected $deliveryService;
    protected $paymentService;
    protected $orderService;
    protected $pingbackService;

    public function preDispatch()
    {
        $this->deliveryService = $this->get('paymentwall.delivery_service');
        $this->paymentService = $this->get('paymentwall.payment_service');
        $this->orderService = $this->get('paymentwall.order_service');
        $this->pingbackService = $this->get('paymentwall.pingback_service');
    }

    public function indexAction()
    {
        $pingbackParams = $this->Request()->getParams();
        unset($pingbackParams['module'], $pingbackParams['controller'], $pingbackParams['action']);

        $this->pingbackService->loadData($pingbackParams);
        $orderId = $pingbackParams['goodsid'];
        $orderPaymentId = $this->orderService->getPaymentIdByOrderId($orderId);

        if ($orderPaymentId != UtilService::getPaymentwallPaymentId()) {
            die('wrong payment method');
        }

        if ($this->isOrderWasPaid($orderId) && $this->pingbackService->isPingbackDeliverable()) {
            die('Order was paid');
        }

        if ($this->pingbackService->verifyPingback()) {
            $sOrder = Shopware()->Modules()->Order();

            if ($this->pingbackService->isPingbackDeliverable()) {
                $sOrder->setOrderStatus($orderId, Status::ORDER_STATE_IN_PROCESS);
                $sOrder->setPaymentStatus($orderId, Status::PAYMENT_STATE_COMPLETELY_PAID);
                $this->orderService->updateTransactionId($pingbackParams['ref'], $orderId);
                $this->sendDeiliveryStatus($orderId);
            }

            if ($this->pingbackService->isPingbackCancelable()) {
                $sOrder->setPaymentStatus($orderId, Status::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED);
            }
            die('OK');
        } else {
            die($this->pingbackService->getErrorSummary());
        }
    }

    protected function sendDeiliveryStatus($orderId)
    {

        $order = $this->orderService->loadOrderRepositoryById($orderId);

        if (!($order instanceof Order)) {
            return;
        }

        $deliveryStatus = !empty($order->getEsd())
            ? DeliveryConfirmationService::STATUS_DELIVERED
            : DeliveryConfirmationService::STATUS_ORDER_PLACED;

        $deliveryDataPrepared = $this->deliveryService->prepareDeliveryData(
            $order,
            $deliveryStatus
        );
        $this->deliveryService->sendDeliveryData($deliveryDataPrepared);
    }

    protected function isOrderWasPaid($orderId)
    {
        $order = $this->orderService->loadOrderRepositoryById($orderId);

        if (!($order instanceof Order)) {
            exit('Order is not loaded');
        }

        if ($this->orderService->isOrderHistoryHasPaidStatus($orderId)
            && $order->getTransactionId() != $order->getTemporaryId()
        ) {
            return true;
        }
        return false;
    }
}
