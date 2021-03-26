<?php

namespace Paymentwall\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Controller_ActionEventArgs as ActionEventArgs;
use Paymentwall_Config;

class PaymentwallInit implements SubscriberInterface
{
    protected static $allowedController = ['checkout', 'paymentwallpingback', 'order', 'paymentwallpaymentsystem', 'paymentwall'];

    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PreDispatch' => 'onInitPwSetting',
        ];
    }

    public function onInitPwSetting(ActionEventArgs $args)
    {
        $controller = strtolower($args->getRequest()->getControllerName());
        if (!in_array(strtolower($controller), self::$allowedController)) {
            return;
        }

        $config = Shopware()->Container()->get('shopware.plugin.cached_config_reader')->getByPluginName('paymentwall');
        Paymentwall_Config::getInstance()->set(array(
            'api_type' => Paymentwall_Config::API_GOODS,
            'public_key' => $config['pwProjectKey'],
            'private_key' => $config['pwSecretKey']
        ));
    }
}
