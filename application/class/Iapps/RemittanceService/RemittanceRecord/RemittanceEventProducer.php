<?php

namespace Iapps\RemittanceService\RemittanceRecord;

use Iapps\Common\Helper\MessageBroker\BroadcastEventProducer;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransactionEventType;

class RemittanceEventProducer extends BroadcastEventProducer{

    protected $remittance_id;

    public function setRemittanceId($remittance_id)
    {
        $this->remittance_id = $remittance_id;
        return $this;
    }

    public function getRemittanceId()
    {
        return $this->remittance_id;
    }

    public function getMessage()
    {
        $temp['remittance_id'] = $this->getRemittanceId();
        return json_encode($temp);
    }

    public static function publishStatusChanged($remittance_id, $status)
    {
        $e = new RemittanceEventWithKeyProducer();

        return $e->publishStatusChanged($remittance_id, $status);
    }

    public static function publishApprovalRequiredChanged($remittance_id)
    {
        $e = new RemittanceEventProducer();

        $e->setRemittanceId($remittance_id);
        return $e->trigger(RemittanceTransactionEventType::REMITTANCE_APPROVAL_REQUIRED_CHANGED, NULL, $e->getMessage());
    }
/*
    public static function publishRemittanceCompleted($remittance_id)
    {
        $e = new RemittanceEventProducer();

        $e->setRemittanceId($remittance_id);
        return $e->trigger(RemittanceTransactionEventType::REMITTANCE_COMPLETED, NULL, $e->getMessage());
    }
*/
}