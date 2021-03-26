<?php

namespace Paymentwall\Subscriber;

use Doctrine\Common\Collections\ArrayCollection;
use Enlight\Event\SubscriberInterface;
use Enlight_Controller_ActionEventArgs as ActionEventArgs;
use Enlight_Template_Manager;
use Paymentwall\Components\Services\PaymentSystemService;
use Paymentwall\Components\Services\UtilService;
use Paymentwall\Paymentwall;

class FrontendCheckout implements SubscriberInterface
{
    protected $pluginDir;
    protected $template;
    protected $connection;
    protected $paymentSystemService;

    public function __construct(
        $pluginDir,
        Enlight_Template_Manager $template,
        PaymentSystemService $paymentSystemService
    ) {
        $this->pluginDir = $pluginDir;
        $this->template = $template;
        $this->paymentSystemService = $paymentSystemService;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Theme_Compiler_Collect_Plugin_Javascript' => 'onCollectJavascript',
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_Checkout' => 'onPostDispatchSecure',
        ];
    }

    public function onPostDispatchSecure(ActionEventArgs $args)
    {
        $paymentwallActive = UtilService::getPaymentMethodActiveFlag();
        if (!$paymentwallActive) {
            return;
        }

        if ($args->getRequest()->getActionName() == 'shippingPayment') {

            $localPaymentMethods = $this->paymentSystemService->getLocalPaymentMethods();

            if (!empty($localPaymentMethods)) {
                $paymentwallId = UtilService::getPaymentwallPaymentId();
                $view = $args->getSubject()->View();
                $view->addTemplateDir($this->pluginDir . '/Resources/views/');
                $view->extendsTemplate('frontend/paymentwall/checkout/change_payment.tpl');
                $view->assign('localPaymentMethods', $localPaymentMethods);
                $view->assign('paymentwallId', $paymentwallId);
            } else {
                $session = Shopware()->Container()->get('session');
                $session->offsetUnset('paymentwall-localpayment');
            }
        }

        if ($args->getRequest()->getActionName() == 'confirm') {

            $view = $args->getSubject()->View();
            $user = $view->getAssign('sUserData');
            $payment = $user['additional']['payment'];

            if ($payment['name'] == Paymentwall::PAYMENT_NAME) {
                $session = Shopware()->Container()->get('session');
                $selectedPaymentMethod = $session->offsetGet('paymentwall-localpayment');

                if (!empty($selectedPaymentMethod['id'])) {
                    $view->addTemplateDir($this->pluginDir . '/Resources/views/');
                    $view->extendsTemplate('frontend/paymentwall/checkout/confirm.tpl');
                    $paymentMethodName = $selectedPaymentMethod['name'];
                    $view->assign('localPaymentSelected', $paymentMethodName);
                }
            }
        }
    }

    public function onCollectJavascript()
    {
        $jsPath = [
            $this->pluginDir . '/Resources/views/frontend/_public/src/js/jquery.paymentwall.custom-shipping-payment.js',
        ];

        return new ArrayCollection($jsPath);
    }
}
