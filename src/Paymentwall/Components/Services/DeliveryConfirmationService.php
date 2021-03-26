<?php

namespace Paymentwall\Components\Services;

use Paymentwall_GenerericApiObject;
use Shopware\Models\Order\Order;

class DeliveryConfirmationService
{
    const PRODUCT_PHYSICAL = 'physical';
    const PRODUCT_DIGITAL = 'digital';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_ORDER_PLACED = 'order_placed';
    const STATUS_ORDER_SHIPPED = 'order_shipped';

    /**
     * @var Order $order
     * @param $customer
     * @param $status
     * @param null $trackingData
     * @return array
     */
    public function prepareDeliveryData($order, $status, $trackingData = null)
    {
        $shipping = $order->getShipping();
        $data = [
            'payment_id' => $order->getTransactionId(),
            'merchant_reference_id' => $order->getId(),
            'type' => ($order->getEsd()) ? self::PRODUCT_DIGITAL : self::PRODUCT_PHYSICAL,
            'status' => $status,
            'estimated_delivery_datetime' => date('Y/m/d H:i:s'),
            'estimated_update_datetime' => date('Y/m/d H:i:s'),
            'refundable' => 'yes',
            'details' => 'Order status has been updated on ' . date('Y/m/d H:i:s'),
            'product_description' => '',
            'shipping_address[country]' => $shipping->getCountry()->getIso(),
            'shipping_address[city]' =>   $shipping->getCountry(),
            'shipping_address[zip]' => $shipping->getZipCode(),
            'shipping_address[state]' => !empty($shipping->getState()) ? $shipping->getState() : 'N/A',
            'shipping_address[street]' => $shipping->getStreet(),
            'shipping_address[phone]' => !empty($shipping->getPhone()) ? $shipping->getPhone() : 'N/A',
            'shipping_address[firstname]' => $shipping->getFirstName(),
            'shipping_address[lastname]' =>  $shipping->getLastName(),
            'shipping_address[email]' => $order->getCustomer()->getEmail(),
            'reason' => 'none',
            'attachments' => null,
            'is_test' => PaymentwallSettings::getTestMode(),
        ];

        if (!empty($trackingData)) {
            return array_merge($data, $trackingData);
        }
        return $data;
    }

    public function sendDeliveryData($dataPrepared)
    {
        if (empty($dataPrepared)) {
            return;
        }

        $delivery = new Paymentwall_GenerericApiObject('delivery');
        $delivery->post($dataPrepared);
    }
}
