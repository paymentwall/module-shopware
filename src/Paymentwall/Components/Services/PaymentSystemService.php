<?php

namespace Paymentwall\Components\Services;

use Paymentwall_Config;
use Paymentwall_Signature_Widget;

class PaymentSystemService
{
    public function getLocalPaymentMethods()
    {
        $localPaymentMethods = [];

        if (empty($localPaymentMethods)) {
            $userCountry = UtilService::getCountryByIp(UtilService::getRealClientIP());
            $response = $this->getPaymentMethodFromApi($userCountry);
            $localPaymentMethods = $this->prepareLocalPayment($response);
        }
        return $localPaymentMethods;
    }

    protected function getPaymentMethodFromApi($userCountry)
    {
        if (empty($userCountry)) {
            return null;
        }

        $params = array(
            'key' => PaymentwallSettings::getProjectKey(),
            'country_code' => $userCountry,
            'sign_version' => 3,
            'currencyCode' =>  Shopware()->Container()->get('currency')->getShortName(),
        );

        $params['sign'] = (new Paymentwall_Signature_Widget())->calculate(
            $params,
            $params['sign_version']
        );

        $url = Paymentwall_Config::API_BASE_URL . '/payment-systems/?' . http_build_query($params);
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);
        if (curl_error($curl)) {
            return null;
        }

        return json_decode($response, true);
    }

    protected function prepareLocalPayment($payments)
    {
        $methods = [];
        if (!empty($payments)) {
            foreach ($payments as $payment) {
                if (!empty($payment['id']) && !empty($payment['name'])) {
                    $methods[] = [
                        'id' => $payment['id'],
                        'name' => $payment['name'],
                        'img_url' => !empty($payment['img_url']) ? $payment['img_url'] : ''
                    ];
                }
            }
        }
        return $methods;
    }
}
