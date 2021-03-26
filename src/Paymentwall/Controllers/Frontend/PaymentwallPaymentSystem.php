<?php

class Shopware_Controllers_Frontend_PaymentwallPaymentSystem extends Enlight_Controller_Action
{
    public function savePaymentsystemAction()
    {
        if (!$this->Request()->isPost() && empty($this->Request()->getPost('psId'))) {
            die;
        }

        $payment = [
            'id' => $this->Request()->getPost('psId'),
            'name' => !empty($this->Request()->getPost('psName')) ? $this->Request()->getPost('psName') : '',
        ];
        $session = $this->container->get('session');
        $session->offsetSet('paymentwall-localpayment', $payment);
        die($payment['id']);
    }
}
