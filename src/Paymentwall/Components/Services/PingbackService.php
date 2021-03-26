<?php

namespace Paymentwall\Components\Services;

use Paymentwall\Components\PaymentMethodProvider;
use Paymentwall_Pingback;

class PingbackService
{
    protected $pingback;

    public function loadData($pingbackParams, $ipAddress = null)
    {
        $this->pingback = new Paymentwall_Pingback($pingbackParams, $ipAddress);
    }

    public function verifyPingback()
    {
        if ($this->pingback->validate(true)) {
            return true;
        }

        return false;
    }

    public function isPingbackCancelable()
    {
        return $this->pingback->isCancelable();
    }

    public function isPingbackDeliverable()
    {
        return $this->pingback->isDeliverable();
    }

    public function isPingbackUnderReview()
    {
        return $this->pingback->isUnderReview();
    }

    public function getErrorSummary()
    {
        return $this->pingback->getErrorSummary();
    }
}
