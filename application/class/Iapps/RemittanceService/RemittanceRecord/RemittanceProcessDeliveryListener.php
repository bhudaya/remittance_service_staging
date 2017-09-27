<?php

namespace Iapps\RemittanceService\RemittanceRecord;

use Iapps\Common\Helper\MessageBroker\EventConsumer;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransactionEventType;

class RemittanceProcessDeliveryListener extends EventConsumer
{
    protected function doTask($msg)
    {
        $data = json_decode($msg->body);

        try {
            if (isset($data->remittance_id))
            {
                $remittanceService = RemittanceRecordServiceFactory::build();
                $remittanceService->setUpdatedBy($this->getUpdatedBy());
                $remittanceService->setIpAddress($this->getIpAddress());
                return $remittanceService->deliver($data->remittance_id);
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function listenEvent()
    {
        $this->listen(RemittanceTransactionEventType::REMITTANCE_STATUS_CHANGED, RemittanceStatus::DELIVERING, 'remittance.queue.processDelivery');
    }
}