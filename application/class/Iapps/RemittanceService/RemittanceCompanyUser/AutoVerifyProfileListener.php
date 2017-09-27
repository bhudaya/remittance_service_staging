<?php

namespace Iapps\RemittanceService\RemittanceCompanyUser;

use Iapps\Common\Helper\MessageBroker\BroadcastEventConsumer;
use Iapps\Common\Helper\MessageBroker\EventConsumer;
use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\Common\Microservice\BaseMicroservice;
use Iapps\RemittanceService\RemittanceCompany\RemittanceCompanyServiceFactory;

class AutoVerifyProfileListener extends EventConsumer{

    //overwrite this
    protected $ttl = NULL; //10s

    protected function doTask($msg)
    {
        $data = json_decode($msg->body);

        try
        {
            $this->setForceAcknowledgement(false);
            BaseMicroservice::enableMemory(false);  //disable using memory

            if( isset($data->user_profile_id) AND isset($data->service_provider_id) )
            {
                $this->process($data->user_profile_id, $data->service_provider_id);
                return true;
            }

            //ignore
            return true;
        }
        catch(\Exception $e)
        {
            return false;
        }
    }

    public function listenEvent()
    {//this don't care about the binding, just read from the queue.
        $this->listenWithoutDeclaration('remittance.queue.autoVerifyRemittanceProfile');
    }

    protected function process($user_profile_id, $service_provider_id)
    {
        //get remittance company
        $remcoServ = RemittanceCompanyServiceFactory::build();
        if( !$remco = $remcoServ->getByServiceProviderId($service_provider_id) )
            return false;

        //get user
        $accServ = AccountServiceFactory::build();
        if( !$user = $accServ->getUser(NULL, $user_profile_id) )
            return false;

        //get remittance profile
        $remcoUserServ = RemittanceCompanyUserServiceFactory::build();
        return $remcoUserServ->verifyProfile($user, $remco);
    }
}