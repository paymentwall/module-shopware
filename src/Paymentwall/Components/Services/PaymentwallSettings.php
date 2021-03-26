<?php

namespace Paymentwall\Components\Services;

class PaymentwallSettings
{
    protected static function getConfig()
    {
        return Shopware()->Container()->get('shopware.plugin.cached_config_reader')->getByPluginName('paymentwall');
    }

    public static function getTestMode()
    {
        $config = self::getConfig();
        return $config['pwTestMode'];
    }

    public static function getWidgetCode()
    {
        $config = self::getConfig();
        return $config['pwWidget'];
    }

    public static function getProjectKey()
    {
        $config = self::getConfig();
        return $config['pwProjectKey'];
    }

    public static function getSecretKey()
    {
        $config = self::getConfig();
        return $config['pwWipwSecretKey'];
    }

    public static function getRefundState()
    {
        $config = self::getConfig();
        return $config['pwRefundState'];
    }

    public static function getRedirectPayment()
    {
        $config = self::getConfig();
        return $config['pwRedirectPayment'];
    }
}
