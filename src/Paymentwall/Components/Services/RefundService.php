<?php

namespace Paymentwall\Components\Services;

use Paymentwall_GenerericApiObject;
use Paymentwall_Signature_Widget;

class RefundService
{
    const TYPE_FULL_REFUND = 1;

    public function prepareData($ref, $uid, $host)
    {
        $params =  [
            'key' => PaymentwallSettings::getProjectKey(),
            'ref' => $ref,
            'uid' => $uid,
            'sign_version' => 3,
            'type' => self::TYPE_FULL_REFUND,
            'message' => 'Shopware: website ' . strtoupper($host) . ' request full refund',
            'test_mode' => (int)PaymentwallSettings::getTestMode(),
        ];

        $params['sign'] = (new Paymentwall_Signature_Widget())->calculate($params, $params['sign_version']);
        return $params;
    }

    public function sendCancellation($dataPrepared)
    {
        $delivery = new Paymentwall_GenerericApiObject('ticket');
        return $delivery->post($dataPrepared);
    }
}
