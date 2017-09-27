<?php

namespace Iapps\RemittanceService\RemittanceRecord;

use Iapps\Common\Helper\MessageBroker\EventProducer;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransactionEventType;

class RemittanceEventWithKeyProducer extends EventProducer{

    protected $remittance_id;
    protected $status;

    public function setRemittanceId($remittance_id)
    {
        $this->remittance_id = $remittance_id;
        return $this;
    }

    public function getRemittanceId()
    {
        return $this->remittance_id;
    }

    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getMessage()
    {
        $temp['remittance_id'] = $this->getRemittanceId();
        $temp['status'] = $this->getStatus();
        return json_encode($temp);
    }

    public static function publishStatusChanged($remittance_id, $status)
    {
        $e = new RemittanceEventWithKeyProducer();

        $e->setRemittanceId($remittance_id);
        $e->setStatus($status);

        //publish one with and without routing key
        $e->trigger(RemittanceTransactionEventType::REMITTANCE_STATUS_CHANGED, NULL, $e->getMessage());
        return $e->trigger(RemittanceTransactionEventType::REMITTANCE_STATUS_CHANGED, $status, $e->getMessage());
    }
}