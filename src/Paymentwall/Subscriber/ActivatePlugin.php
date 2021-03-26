<?php

namespace Paymentwall\Subscriber;

use Doctrine\DBAL\Connection;
use Enlight\Event\SubscriberInterface;
use Paymentwall\Paymentwall;
use Paymentwall\Components\Services\UtilService;

class ActivatePlugin implements SubscriberInterface
{
    protected $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatchSecure_Backend_PluginInstaller' => 'ActivatePaymentMethod',
        ];
    }

    public function ActivatePaymentMethod(\Enlight_Controller_ActionEventArgs $args)
    {
        if ($args->getRequest()->getActionName() !== 'activatePlugin') {
            return;
        }

        $request = $args->getSubject()->Request();
        $requestData = $request->getParams();

        if (strtolower($requestData['technicalName']) == Paymentwall::PAYMENT_NAME) {
            $paymentwallId = UtilService::getPaymentwallPaymentId();

            $this->connection->createQueryBuilder()
                ->update('s_core_paymentmeans')
                ->set('active', 1)
                ->where('id = ' . $paymentwallId)
                ->execute();
        }
    }
}
