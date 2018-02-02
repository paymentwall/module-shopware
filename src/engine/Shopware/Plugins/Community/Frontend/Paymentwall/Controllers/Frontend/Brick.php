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
    const ORDER_CANCELED = 4;
    const PAYMENT_COMPLETELY_PAID = 12;
    const PAYMENT_CANCELED = 35;
    const PAYMENT_NO_CREDIT_APPROVED = 30;
    const PAYMENT_REVIEW_NECESSARY = 21;

    public function init()
    {
        $this->config = Shopware()->Plugins()->Frontend()->Paymentwall()->Config();

        Paymentwall_Config::getInstance()->set(array(
            'api_type' => Paymentwall_Config::API_GOODS,
            'public_key' => trim($this->config->get("publicKey")), // available in your Paymentwall merchant area
            'private_key' => trim($this->config->get("privateKey"))// available in your Paymentwall merchant area
        ));
    }

    public function indexAction()
    {
        if (!empty(Shopware()->Session()->brick)) {
            unset(Shopware()->Session()->brick);
            $this->redirect(PW_BASE_URL);
        } else {
            try {
                $customerDetails = $this->getUser();
                $customerFirstname = $customerDetails['billingaddress']['firstname'];
                $customerLastname = $customerDetails['billingaddress']['lastname'];
                $customerID = $customerDetails['additional']['user']['id'];
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
                    'currency' => $this->getCurrencyShortName(),
                    'customerLastname' => $customerLastname,
                    'customerFirstname' => $customerFirstname,
                    'customerId' => $customerID
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
            'uid' => $orderData['customerId'],
            'email' => $parameters['email'],
            'plan' => $orderData['orderId'],
            'amount' => $orderData['amount'],
            'currency' => $orderData['currency'],
            'token' => $parameters['brick_token'],
            'fingerprint' => $parameters['brick_fingerprint'],
            'description' => 'Order #' . $orderData['orderNumber'],
            'customer[lastname]' => $orderData['customerLastname'],
            'customer[firstname]' => $orderData['customerFirstname']
        );
        
        if (isset($parameters['brick_charge_id']) AND isset($parameters['brick_secure_token'])) {
            $cardInfo['charge_id'] = $parameters['brick_charge_id'];
            $cardInfo['secure_token'] = $parameters['brick_secure_token'];
        }
       
        $charge = new Paymentwall_Charge();
        $charge->create($cardInfo);
        $responseData = json_decode($charge->getRawResponseData(),true);
        $response = $charge->getPublicData();

        if ($charge->isSuccessful() AND empty($responseData['secure'])) {
            $order = Shopware()->Modules()->Order();
            $orderId = $orderData['orderId'];
            $sendMail = true;

            if ($charge->isCaptured()) {
                $order->setOrderStatus($orderId, self::ORDER_PROCESS);
                $order->setPaymentStatus($orderId, self::PAYMENT_COMPLETELY_PAID, $sendMail);
            } elseif ($charge->isUnderReview()) {
                $order->setOrderStatus($orderId, self::ORDER_OPEN);
                $order->setPaymentStatus($orderId, self::PAYMENT_REVIEW_NECESSARY, $sendMail);
            }
            $this->updateOrderReferer($orderId, $charge->getId());
            unset(Shopware()->Session()->brick);
        } elseif (!empty($responseData['secure'])) {
            $response = json_encode(array('secure' => $responseData['secure']));
        } else {
            $errors = json_decode($response, true);
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