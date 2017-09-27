<?php

namespace Iapps\RemittanceService\RemittanceTransaction;

use Iapps\Common\Helper\MessageBroker\BroadcastEventProducer;

class RemittanceTransactionEventProducer extends BroadcastEventProducer{

    protected $transaction_id;

    public function setTransactionId($transaction_id)
    {
        $this->transaction_id = $transaction_id;
        return $this;
    }

    public function getTransactionId()
    {
        return $this->transaction_id;
    }

    public function getMessage()
    {
        $temp['transaction_id'] = $this->getTransactionId();
        return json_encode($temp);
    }

    public static function publishTransactionCreated($transaction_id)
    {
        $e = new RemittanceTransactionEventProducer();

        $e->setTransactionId($transaction_id);
        return $e->trigger(RemittanceTransactionEventType::REMITTANCE_TRANSACTION_CREATED, NULL, $e->getMessage());
    }

    public static function publishTransactionUserConverted($transaction_id)
    {
        $e = new RemittanceTransactionEventProducer();

        $e->setTransactionId($transaction_id);
        return $e->trigger(RemittanceTransactionEventType::REMITTANCE_TRANSACTION_USER_CONVERTED, NULL, $e->getMessage());
    }
}