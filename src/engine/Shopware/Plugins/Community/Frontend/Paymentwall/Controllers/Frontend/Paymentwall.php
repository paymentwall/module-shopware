<?php
if (!class_exists('Paymentwall_Config'))
    require_once(Shopware()->OldPath() . "engine/Library/paymentwall/lib/paymentwall.php");

define('PW_BASE_URL', Shopware()->Shop()->getBaseUrl());

class Shopware_Controllers_Frontend_Paymentwall extends Shopware_Controllers_Frontend_Payment
{
    private $config;
    const ORDER_OPEN = 0;
    const ORDER_PROCESS = 1;
    const ORDER_COMPLETED = 2;
    const ORDER_CANCELED = 8;
    const PAYMENT_COMPLETELY_PAID = 12;
    const PAYMENT_CANCELED = 20;

    public $_unsetParams = array();

    public function init()
    {
        $this->config = Shopware()->Plugins()->Frontend()->Paymentwall()->Config();

        Paymentwall_Config::getInstance()->set(array(
            'api_type' => Paymentwall_Config::API_GOODS,
            'public_key' => trim($this->config->get("projectKey")), // available in your Paymentwall merchant area
            'private_key' => trim($this->config->get("secretKey")) // available in your Paymentwall merchant area
        ));
        $this->_unsetParams = array(
            'controller',
            'action'
        );
    }

    /**
     * Load payment method of Paymentwall
     *
     */
    public function indexAction()
    {
        if (!empty(Shopware()->Session()->pwLocal)) {
            unset(Shopware()->Session()->pwLocal);
            $this->redirect(PW_BASE_URL);
        } else {
            try {
                $orderNumber = $this->saveOrder(
                    $this->createPaymentUniqueId(),
                    md5($this->createPaymentUniqueId()),
                    self::ORDER_OPEN
                );
                $orderId = $this->getOrderIdByOrderNumber($orderNumber);
                $params = array(
                    'orderId' => $orderId,
                    'orderNumber' => $orderNumber,
                    'amount' => $this->getAmount(),
                    'currency' => $this->getCurrencyShortName(),
                    'user' => $this->getUser(),
                );
                Shopware()->Session()->pwLocal = $params;
                $this->View()->orderId = $orderId;
                $this->View()->iframe = $this->getWidget($params);
            } catch (Exception $e) {
                $this->redirect(PW_BASE_URL);
            }
        }
    }

    /**
     * Get iframe widget pwlocal
     *
     * @param array $params
     */
    private function getWidget($params)
    {
        $widget = new Paymentwall_Widget(
            !empty($params['user']['additional']['user']['customerId']) ?
                $params['user']['additional']['user']['customerId'] :
                $_SERVER['REMOTE_ADDR'], // id of the end-user
            trim($this->config->get("widgetCode")),
            array(
                new Paymentwall_Product(
                    $params['orderId'],
                    $params['amount'],
                    $params['currency'],
                    'Order #' . $params['orderNumber'],
                    Paymentwall_Product::TYPE_FIXED,
                    0,
                    null,
                    false
                )
            ),
            // additional parameters
            array_merge(
                array(
                    'integration_module' => 'shopware',
                    'test_mode' => trim($this->config->get("testMode")),
                    'success_url' => (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . PW_BASE_URL . '/checkout/finish'
                ),
                $this->getUserProfileData($params['user'])
            )
        );

        return $widget->getHtmlCode(array('height' => 400, 'width' => '100%'));
    }

    /**
     * Build User Profile Data
     *
     * @param array $params
     */
    protected function getUserProfileData($params)
    {
        $billing = $params['billingaddress'];

        return array(
            'customer[city]' => $billing['city'],
            'customer[state]' => $billing['stateID'],
            'customer[address]' => $billing['street'],
            'customer[country]' => $billing['country'],
            'customer[zip]' => $billing['zipcode'],
            'customer[firstname]' => $billing['firstname'],
            'customer[lastname]' => $billing['lastname'],
            'email' => $params['additional']['user']['email']
        );
    }

    public function getRequestParams()
    {
        $params = $this->Request()->getParams();
        foreach ($this->_unsetParams AS $un) {
            unset($params[$un]);
        }
        return $params;
    }

    /**
     * Pingback update Order, Payment status
     *
     * @param array $_GET
     */
    public function pingbackAction()
    {
        $getData = $this->getRequestParams();

        $pingback = new Paymentwall_Pingback($getData, $_SERVER['REMOTE_ADDR']);
        $order = Shopware()->Modules()->Order();
        $orderId = (int) $pingback->getProductId();
        $sendMail = true;

        if ($pingback->validate()) {
            if ($pingback->isDeliverable()) {
                $order->setOrderStatus($orderId, self::ORDER_PROCESS);
                $order->setPaymentStatus($orderId, self::PAYMENT_COMPLETELY_PAID, $sendMail);
                $this->updateOrderReferer($orderId, $pingback->getReferenceId());
            } elseif ($pingback->isCancelable()) {
                $order->setOrderStatus($orderId, self::ORDER_CANCELED);
                $order->setPaymentStatus($orderId, self::PAYMENT_CANCELED, $sendMail);
            }            
            echo "OK";
        } else {
            echo $pingback->getErrorSummary();
        }
        die;
    }

    private function getOrderIdByOrderNumber($orderNumber)
    {
        return Shopware()->Db()->fetchOne("SELECT id FROM s_order WHERE ordernumber = ?", array($orderNumber));
    }

    private function getOrderStatusByOrderId($orderId)
    {
        return Shopware()->Db()->fetchOne("SELECT status FROM s_order WHERE id = ?", array($orderId));
    }


    private function updateOrderReferer($orderId, $referer)
    {
        Shopware()->Db()->update('s_order', array('referer' => $referer), array('id='.$orderId));
    }

    /**
     * Check order status, redirect to thank you page
     *
     * @param integer $orderId
     */
    public function redirectAction()
    {
        $orderId = $this->Request()->getParam("orderId");
        $status = 0;
        if (!empty($orderId)) {
            $status = $this->getOrderStatusByOrderId($orderId);
        }
        echo $status;
        die;
    }
}

