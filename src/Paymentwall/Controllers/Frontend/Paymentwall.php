<?php

use Paymentwall\Components\Services\PaymentwallSettings;
use Shopware\Models\Order\Status;
use Paymentwall\Paymentwall;

class Shopware_Controllers_Frontend_Paymentwall extends Shopware_Controllers_Frontend_Payment
{
    protected $paymentService;
    protected $orderService;
    protected $session;

    public function preDispatch()
    {
        /** @var \Shopware\Components\Plugin $plugin */
        $plugin = $this->get('kernel')->getPlugins()['Paymentwall'];
        $this->get('template')->addTemplateDir($plugin->getPath() . '/Resources/views/');
        $this->paymentService = $this->get('paymentwall.payment_service');
        $this->orderService = $this->get('paymentwall.order_service');
        $this->session = $this->get('session');
    }

    public function indexAction()
    {
        /**
         * Check if one of the payment methods is selected. Else return to default controller.
         */

        if ($this->getPaymentShortName() == Paymentwall::PAYMENT_NAME) {
            if (PaymentwallSettings::getRedirectPayment()) {
                return $this->redirect(['action' => 'direct', 'forceSecure' => true]);
            }
            return $this->redirect(['action' => 'gateway', 'forceSecure' => true]);
        }
        return $this->redirect(['controller' => 'checkout']);
    }

    public function gatewayAction()
    {
        if (empty($this->getBasket())) {
            $this->redirect(['controller' => 'index', 'action' => 'index']);
        }

        $this->View()->assign('gatewayUrl', $this->generateWidgetUrl());
    }

    public function directAction()
    {
        $providerUrl = $this->generateWidgetUrl();
        if (!empty($providerUrl)) {
            $this->redirect($providerUrl);
        }
    }

    private function generateWidgetUrl()
    {
        $user = $this->getUser();
        $orderId = $this->prepareTemporaryOrder();
        $widget = $this->paymentService->prepareWidget($user['additional']['user'], $this->getAmount(), $this->getCurrencyShortName(), $orderId);
        return $widget->getUrl();
    }

    private function prepareTemporaryOrder()
    {
        $orderId = $this->createOrder();
        $this->session->offsetSet('paymentwall_neworderid', $orderId);

        $this->orderService->setOrderStatus($orderId, Status::ORDER_STATE_CANCELLED);

        if (!empty($orderId)) {
            $this->clearBasketData();
        }

        return $orderId;
    }

    public function successAction()
    {
        try {
            $orderId = $this->session->offsetGet('paymentwall_neworderid');
            $currentStatus = $this->orderService->getOrderStatus($orderId);

            if ($currentStatus == Status::ORDER_STATE_CANCELLED
                && $this->orderService->isOrderHistoryEmpty($orderId)
            ) {
                $this->orderService->setOrderStatus($orderId, Status::ORDER_STATE_OPEN);
                $this->session->offsetUnset('paymentwall_neworderid');
            }
        } catch (Exception $e) {

        }
        $this->redirect(['controller' => 'checkout', 'action' => 'finish']);
    }

    private function clearBasketData()
    {
        $this->session->offsetUnset('sBasketAmount');
        $this->session->offsetUnset('sBasketQuantity');
    }

    private function createOrder()
    {
        $paymentUniqueId = $this->createPaymentUniqueId();
        $this->saveOrder($paymentUniqueId, $paymentUniqueId, Status::PAYMENT_STATE_OPEN);
        $orderId = $this->paymentService->getOrderIdByPaymentUniqueId($paymentUniqueId);

        return $orderId;
    }
}
