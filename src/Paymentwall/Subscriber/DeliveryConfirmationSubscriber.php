<?php

namespace Paymentwall\Subscriber;

use Enlight\Event\SubscriberInterface;
use Paymentwall\Paymentwall;
use Paymentwall\Components\Services\DeliveryConfirmationService;
use Paymentwall\Components\Services\OrderService;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;

class DeliveryConfirmationSubscriber implements SubscriberInterface
{
    protected $deliveryConfirmationService;
    protected $orderService;

    public function __construct(
        DeliveryConfirmationService $deliveryConfirmationService,
        OrderService $orderService
    ) {
        $this->deliveryConfirmationService = $deliveryConfirmationService;
        $this->orderService = $orderService;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PreDispatch_Backend_Order' => 'sendDelivery',
        ];
    }

    public function sendDelivery(\Enlight_Controller_ActionEventArgs $args)
    {
        if ($args->getRequest()->getActionName() !== 'save') {
            return;
        }

        $request = $args->getSubject()->Request();
        $requestData = $request->getParams();

        $id = $requestData['id'];
        if (
            empty($id)
            || $requestData['payment'][0]['name'] != Paymentwall::PAYMENT_NAME
        ) {
            return;
        }

        $order = $this->orderService->loadOrderRepositoryById($id);

        if (!($order instanceof Order)) {
            return;
        }

        $oldTrackingCode = $order->getTrackingCode();
        $newTrackingCode = $requestData['trackingCode'];
        $deliveryDataPrepared = [];

        if (empty($oldTrackingCode) && !empty($newTrackingCode) || ($oldTrackingCode != $newTrackingCode)) {

            $trackingData = [
                'carrier_tracking_id' => $newTrackingCode,
                'carrier_type' =>$order->getDispatch()->getName()
            ];

            $deliveryDataPrepared = $this->deliveryConfirmationService->prepareDeliveryData(
                $order,
                DeliveryConfirmationService::STATUS_ORDER_SHIPPED,
                $trackingData
            );
        }

        $paymentStatus = $requestData['status'];
        $currentStatus = $this->orderService->getOrderStatus($id);

        if ($paymentStatus == Status::ORDER_STATE_COMPLETELY_DELIVERED
             && $currentStatus != Status::ORDER_STATE_COMPLETELY_DELIVERED
        ) {
            $deliveryDataPrepared = $this->deliveryConfirmationService->prepareDeliveryData(
                $order,
                DeliveryConfirmationService::STATUS_DELIVERED
            );
        }

        if (!empty($deliveryDataPrepared)) {
            $this->deliveryConfirmationService->sendDeliveryData($deliveryDataPrepared);
        }
    }
}
