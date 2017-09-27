<?php

namespace Iapps\RemittanceService\RemittanceRecord;

use Iapps\Common\Helper\MessageBroker\BroadcastEventConsumer;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransactionEventType;

class CompleteEwalletCashoutListener extends BroadcastEventConsumer{

    protected function doTask($msg)
    {
        $data = json_decode($msg->body);

        try
        {
            $serv = new CompleteEwalletCashoutService($this->getIpAddress()->getString(), $this->getUpdatedBy());
            $this->setForceAcknowledgement(false);

            return $serv->process($data->transaction_id);
        }
        catch(\Exception $e)
        {
            return false;
        }
    }

    public function listenEvent()
    {
        $this->listen(RemittanceTransactionEventType::REMITTANCE_TRANSACTION_USER_CONVERTED, NULL, 'remittance.queue.completeEwalletCashOut');
    }
}