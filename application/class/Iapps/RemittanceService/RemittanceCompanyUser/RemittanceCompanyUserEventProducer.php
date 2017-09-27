<?php

namespace Iapps\RemittanceService\RemittanceCompanyUser;

use Iapps\Common\Helper\MessageBroker\EventProducer;

class RemittanceCompanyUserEventProducer extends EventProducer{

    protected $user_profile_id;
    protected $service_provider_id;
    protected $status;

    public function setUserProfileId($user_profile_id)
    {
        $this->user_profile_id = $user_profile_id;
        return $this;
    }

    public function getUserProfileId()
    {
        return $this->user_profile_id;
    }

    public function setServiceProviderId($service_provider_id)
    {
        $this->service_provider_id = $service_provider_id;
        return $this;
    }

    public function getServiceProviderId()
    {
        return $this->service_provider_id;
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
        $temp['user_profile_id'] = $this->getUserProfileId();
        $temp['service_provider_id'] = $this->getServiceProviderId();
        $temp['status'] = $this->getStatus();
        return json_encode($temp);
    }

    public static function publishStatusChanged(RemittanceCompanyUser $user)
    {
        $e = new RemittanceCompanyUserEventProducer();

        $e->setUserProfileId($user->getUser()->getId());
        $e->setServiceProviderId($user->getRemittanceCompany()->getServiceProviderId());
        $e->setStatus($user->getUserStatus()->getCode());

        //publish one with and without routing key
        return $e->trigger(RemittanceCompanyUserEventType::REMCO_PROFILE_STATUS_CHANGED, $e->getStatus(), $e->getMessage());
    }
}