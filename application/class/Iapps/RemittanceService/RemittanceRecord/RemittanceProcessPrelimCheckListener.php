<?php

namespace Iapps\RemittanceService\RemittanceRecord;

use Iapps\Common\Helper\MessageBroker\EventConsumer;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransactionEventType;

class RemittanceProcessPrelimCheckListener extends EventConsumer{

    public function doTask($msg)
    {
        $this->setForceAcknowledgement(false);

        $data = json_decode($msg->body);

        try{
            if( isset($data->remittance_id) )
            {
                $remittanceRecordServ = RemittanceRecordServiceFactory::build();
                $remittanceRecordServ->setUpdatedBy($this->getUpdatedBy());
                $remittanceRecordServ->setIpAddress($this->getIpAddress());
                return $remittanceRecordServ->prelimCheck($data->remittance_id);
            }

            //ignore if unknown payload
            return true;
        } catch (\Exception $e){
            return false;
        }
    }

    public function listenEvent()
    {
        $this->listen(RemittanceTransactionEventType::REMITTANCE_STATUS_CHANGED, RemittanceStatus::PROCESSING, 'remittance.queue.processPrelimCheck');
    }
}