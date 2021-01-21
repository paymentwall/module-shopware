<?php
namespace Paymentwall;

use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\UpdateContext;
use Symfony\Component\DependencyInjection\ContainerBuilder;

require_once(dirname(__FILE__) . '/Components/Libs/Paymentwall/lib/paymentwall.php');

class Paymentwall extends Plugin
{
    const PAYMENT_NAME = 'paymentwall';

    public function build(ContainerBuilder $container)
    {
        $container->setParameter('paymentwall.plugin_dir', $this->getPath());
        parent::build($container);
    }

    public function install(InstallContext $context)
    {
        /** @var \Shopware\Components\Plugin\PaymentInstaller $installer */
        $installer = $this->container->get('shopware.plugin_payment_installer');

        $options = [
            'name' => self::PAYMENT_NAME,
            'description' => 'Paymentwall',
            'action' => 'Paymentwall',
            'active' => 0,
            'position' => -99999,
            'additionalDescription' =>
                'offer more than 150 local payment methods, including e-wallets, bank transfers, prepaid cards, and cash options'
        ];
        $installer->createOrUpdate($context->getPlugin(), $options);
    }

    public function uninstall(UninstallContext $context)
    {
        $this->setActiveFlag($context->getPlugin()->getPayments(), false);
        $context->scheduleClearCache(UpdateContext::CACHE_LIST_ALL);
    }

    /**
     * @param DeactivateContext $context
     */
    public function deactivate(DeactivateContext $context)
    {
        $this->setActiveFlag($context->getPlugin()->getPayments(), false);
        $context->scheduleClearCache(UpdateContext::CACHE_LIST_ALL);
    }

    /**
     * @param ActivateContext $context
     */
    public function activate(ActivateContext $context)
    {
        $this->setActiveFlag($context->getPlugin()->getPayments(), true);
        $context->scheduleClearCache(UpdateContext::CACHE_LIST_ALL);
    }

    private function setActiveFlag($payments, $active)
    {
        $em = $this->container->get('models');

        foreach ($payments as $payment) {
            $payment->setActive($active);
        }
        $em->flush();
    }
}
