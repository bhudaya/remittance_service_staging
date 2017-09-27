<?php

namespace Iapps\RemittanceService\RemittanceCompanyUser;

use Iapps\Common\Helper\MessageBroker\BroadcastEventConsumer;
use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\Common\Microservice\BaseMicroservice;
use Iapps\RemittanceService\Common\Logger;

class ProcessProfileEditedListener extends BroadcastEventConsumer{

    protected function doTask($msg)
    {
        $this->setForceAcknowledgement(false);
        BaseMicroservice::enableMemory(false);

        $data = json_decode($msg->body);

        try {
            if (isset($data->user_profile_id))
            {
                Logger::debug('processing for edited profile: ' . $data->user_profile_id);
                $remittanceService = RemittanceCompanyUserServiceFactory::build();
                $remittanceService->setUpdatedBy($this->getUpdatedBy());
                $remittanceService->setIpAddress($this->getIpAddress());
                $remittanceService->recheckStatusAfterProfileEdited($data->user_profile_id);
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function listenEvent()
    {
        $this->listen('account.user.profileEdited', NULL, 'remittance.queue.processProfileEdited');
    }
}