<?php

namespace Iapps\RemittanceService\RemittanceRecord\Mode;

use Iapps\RemittanceService\RemittanceRecord\RemittancePaymentModeOptionInterface;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransaction;

class DefaultPaymentModeOption implements RemittancePaymentModeOptionInterface{

    protected $transaction;
    protected $sender_id;
    protected $recipient_id;


    function __construct(RemittanceTransaction $transaction, $sender_id, $recipient_id)
    {
        $this->transaction = $transaction;
        $this->sender_id = $sender_id;
        $this->recipient_id = $recipient_id;
    }

    public function getOption(array $paymentInfo)
    {
        return $paymentInfo;
    }

    public function saveResponse()
    {
        return true;
    }

    public function getFormattedResponseMessage($response)
    {
        return $response;
    }
}