<?php
if (!class_exists('Paymentwall_Config'))
    require_once(Shopware()->OldPath() . "engine/Library/paymentwall/lib/paymentwall.php");

define('PW_BASE_URL', Shopware()->Shop()->getBaseUrl());

class Shopware_Controllers_Frontend_Brick extends Shopware_Controllers_Frontend_Payment
{
    private $config;
    const ORDER_OPEN = 0;
    const ORDER_PROCESS = 1;
    const ORDER_COMPLETED = 2;
    const ORDER_CANCELED = 8;
    const PAYMENT_COMPLETELY_PAID = 12;
    const PAYMENT_CANCELED = 20;

    public function init()
    {
        $this->config = Shopware()->Plugins()->Frontend()->Paymentwall()->Config();

        Paymentwall_Config::getInstance()->set(array(
            'api_type' => Paymentwall_Config::API_GOODS,
            'public_key' => trim($this->config->get("publicKey")), // available in your Paymentwall merchant area
            'private_key' => trim($this->config->get("privateKey")) // available in your Paymentwall merchant area
        ));
    }

    public function indexAction()
    {
        if (!empty(Shopware()->Session()->brick)) {
            unset(Shopware()->Session()->brick);
            $this->redirect(PW_BASE_URL);
        } else {
            try {
                $orderNumber = $this->saveOrder(
                    $this->createPaymentUniqueId(),
                    md5($this->createPaymentUniqueId()),
                    self::ORDER_OPEN
                );
                $orderId = $this->getOrderIdByOrderNumber($orderNumber);
                $order = array(
                    'orderId' => $orderId,
                    'orderNumber' => $orderNumber,
                    'amount' => $this->getAmount(),
                    'currency' => $this->getCurrencyShortName()
                );
                Shopware()->Session()->brick = $order;
                $this->View()->order = $order;
                $this->View()->public_key = trim($this->config->get("publicKey"));
                $this->View()->merchant_name = trim($this->config->get("merchantName"));
            } catch (Exception $e) {
                $this->redirect(PW_BASE_URL);
            }
        }
    }

    public function payAction()
    {
        if (empty(Shopware()->Session()->brick)) {
            $result = array(
                'success' => 0,
                'error' => array(
                    'code' => '404',
                    'message' => 'Order data not found',
                )
            );
            echo json_encode($result);
            die;
        }

        $orderData = Shopware()->Session()->brick;

        $parameters = $_POST;
        $cardInfo = array(
            'email' => $parameters['email'],
            'amount' => $orderData['amount'],
            'currency' => $orderData['currency'],
            'token' => $parameters['brick_token'],
            'fingerprint' => $parameters['brick_fingerprint'],
            'description' => 'Order #' . $orderData['orderNumber']
        );

        $charge = new Paymentwall_Charge();
        $charge->create($cardInfo);

        $response = $charge->getPublicData();

        if ($charge->isSuccessful()) {
            $order = Shopware()->Modules()->Order();
            $orderId = $orderData['orderId'];
            $sendMail = true;

            if ($charge->isCaptured()) {
                $order->setOrderStatus($orderId, self::ORDER_PROCESS);
                $order->setPaymentStatus($orderId, self::PAYMENT_COMPLETELY_PAID, $sendMail);
            } elseif ($charge->isUnderReview()) {
                $order->setOrderStatus($orderId, self::ORDER_CANCELED);
                $order->setPaymentStatus($orderId, self::PAYMENT_CANCELED, $sendMail);
            }
            $this->updateOrderReferer($orderId, $charge->getId());
            unset(Shopware()->Session()->brick);
        }

        echo $response;
        die;
    }

    private function getOrderIdByOrderNumber($orderNumber)
    {
        return Shopware()->Db()->fetchOne("SELECT id FROM s_order WHERE ordernumber = ?", array($orderNumber));
    }

    private function updateOrderReferer($orderId, $referer)
    {
        Shopware()->Db()->update('s_order', array('referer' => $referer), array('id='.$orderId));
    }
}