<?php

namespace Paymentwall\Subscriber;

use Enlight\Event\SubscriberInterface;
use Paymentwall\Components\Services\PaymentwallSettings;
use Paymentwall\Paymentwall;
use Paymentwall_Config;
use Shopware\Models\Order\Status;
use Paymentwall\Components\Services\RefundService;

class RefundSubsciber implements SubscriberInterface
{
    protected $refundService;

    public function __construct(RefundService $refundService)
    {
        $this->refundService = $refundService;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Order' => 'refundOrder',
        ];
    }

    public function refundOrder(\Enlight_Controller_ActionEventArgs $args)
    {
        if ($args->getRequest()->getActionName() !== 'save') {
            return;
        }

        $requestParam = $args->getSubject()->Request()->getParams();

        $isRefundEnabled = PaymentwallSettings::getRefundState();

        if ($requestParam['status'] == Status::ORDER_STATE_CANCELLED_REJECTED
            && $requestParam['payment'][0]['name'] == Paymentwall::PAYMENT_NAME
            && !empty($requestParam['transactionId'])
            && $isRefundEnabled
        ) {
            $shop = '';
            if (Shopware()->Container()->initialized('shop')) {
                $shop = Shopware()->Container()->get('shop');
            }

            if (!$shop) {
                $shop = Shopware()->Container()->get('models')->getRepository(\Shopware\Models\Shop\Shop::class)->getActiveDefault();
            }

            $uid = !empty($requestParam['customerId']) ? $requestParam['customerId'] : $requestParam['customerEmail'];

            Paymentwall_Config::getInstance()->set(array(
                'api_base_url' => 'https://api.paymentwall.com/developers/api',
                'private_key' => PaymentwallSettings::getSecretKey()
            ));
            $preparedData = $this->refundService->prepareData($requestParam['transactionId'], $uid, $shop->getHost());
            $this->refundService->sendCancellation($preparedData);
        }
    }
}
