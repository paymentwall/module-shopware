<?php

namespace Paymentwall\Components\Services;

use Paymentwall_Product;
use Paymentwall_Widget;
use Shopware\Models\Order\Order;

class PaymentService
{
    public function prepareWidget($user, $totalAmount, $currencyCode, $orderId)
    {
        $session = Shopware()->Container()->get('session');
        $selectedPaymentMethod = $session->offsetGet('paymentwall-localpayment');

        return new Paymentwall_Widget(
            !empty($user['userID']) ? $user['userID'] : $user['email'],
            PaymentwallSettings::getWidgetCode(),
            [
                new Paymentwall_Product(
                    $orderId,
                    $totalAmount,
                    $currencyCode,
                    Paymentwall_Product::TYPE_FIXED
                )
            ],
            [
                'integration_module' => 'shopware',
                'ps' => !empty($selectedPaymentMethod['id']) ? $selectedPaymentMethod['id'] : 'all',
                'test_mode' => PaymentwallSettings::getTestMode(),
                'success_url' => $this->getSuccessUrl()
            ]
        );
    }

    protected function getSuccessUrl()
    {
        $router = Shopware()->Front()->Router();
        return  $router->assemble(['controller' => 'Paymentwall', 'action' => 'success']);
    }

    public function getOrderIdByPaymentUniqueId($paymentUniqueId)
    {
        $repository = Shopware()->Models()->getRepository(Order::class);
        $order = $repository->findOneBy([
            'temporaryId' => $paymentUniqueId,
            'transactionId' => $paymentUniqueId
        ]);
        return $order->getId();
    }
}
