<?php

namespace Paymentwall\Components\Services;

use Doctrine\DBAL\Connection;
use Paymentwall\Paymentwall;
use Paymentwall_Config;

class UtilService
{
    const USER_ID_GEOLOCATION = 'user101';

    public static function getRealClientIP()
    {
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } else {
            $headers = $_SERVER;
        }

        //Get the forwarded IP if it exists
        if (array_key_exists('X-Forwarded-For', $headers) && filter_var($headers['X-Forwarded-For'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $the_ip = $headers['X-Forwarded-For'];
        } elseif (array_key_exists('HTTP_X_FORWARDED_FOR', $headers) && filter_var($headers['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $the_ip = $headers['HTTP_X_FORWARDED_FOR'];
        } elseif (array_key_exists('Cf-Connecting-Ip', $headers)) {
            $the_ip = $headers['Cf-Connecting-Ip'];
        } else {
            $the_ip = filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
        }

        return $the_ip;
    }

    public static function getCountryByIp($ip)
    {
        if (!empty($ip)) {
            $params = array(
                'key' => PaymentwallSettings::getProjectKey(),
                'uid' => self::USER_ID_GEOLOCATION,
                'user_ip' => $ip
            );

            $url = Paymentwall_Config::API_BASE_URL . '/rest/country?' . http_build_query($params);
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($curl);

            if (curl_error($curl)) {
                return null;
            }
            $response = json_decode($response, true);

            if (!empty($response['code'])) {
                return $response['code'];
            }
        }
        return null;
    }

    public static function getPaymentMethodActiveFlag()
    {
        /** @var Connection $connection */
        $connection = Shopware()->Container()->get('dbal_connection');

        return (bool) $connection->fetchColumn(
            'SELECT active FROM s_core_paymentmeans WHERE name = :paymentName',
            [':paymentName' => Paymentwall::PAYMENT_NAME]
        );
    }

    public static function getPaymentwallPaymentId()
    {
        /** @var Connection $connection */
        $connection = Shopware()->Container()->get('dbal_connection');

        return (int) $connection->fetchColumn(
            'SELECT id FROM s_core_paymentmeans WHERE name = :paymentName',
            [':paymentName' => Paymentwall::PAYMENT_NAME,]
        );
    }
}
