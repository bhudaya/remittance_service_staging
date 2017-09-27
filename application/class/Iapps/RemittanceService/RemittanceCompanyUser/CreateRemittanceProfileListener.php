<?php

namespace Iapps\RemittanceService\RemittanceCompanyUser;

use Iapps\Common\Helper\MessageBroker\BroadcastEventConsumer;
use Iapps\Common\Helper\MessageBroker\EventConsumer;
use Iapps\Common\Microservice\BaseMicroservice;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfigServiceFactory;
use Iapps\RemittanceService\RemittanceRecord\RemittanceRecord;
use Iapps\RemittanceService\RemittanceRecord\RemittanceRecordServiceFactory;

class CreateRemittanceProfileListener extends EventConsumer{

    protected function doTask($msg)
    {
        $data = json_decode($msg->body);

        try
        {
            $this->setForceAcknowledgement(false);
            BaseMicroservice::enableMemory(false);
            return $this->process($data->remittance_id);
        }
        catch(\Exception $e)
        {
            return false;
        }
    }

    public function listenEvent()
    {
        $this->listenWithoutDeclaration('remittance.queue.createRemittanceProfile');
    }

    protected function process($remittance_id)
    {
        //Get remittance transaction detail by transaction_id
        //only process if transaction is IN TRANSACTION, otherwise return true
        $remServ = RemittanceRecordServiceFactory::build();
        $remConfigServ = RemittanceConfigServiceFactory::build(2);

        $remServ->setUpdatedBy($this->getUpdatedBy());
        $remServ->setIpAddress($this->getIpAddress());
        if( $remittanceRecord = $remServ->retrieveRemittance($remittance_id) )
        {
            if( $remittanceRecord instanceof RemittanceRecord )
            {
                if( $remconfig = $remConfigServ->getRemittanceConfigById($remittanceRecord->getRemittanceConfigurationId()) )
                {
                    $remco = $remconfig->getRemittanceCompany();

                    if( $remco->getId() != NULL )
                    {
                        $user = $remittanceRecord->getSender();
                        //Get remittance_company_user by user_profile_id and service_provider_id
                        $remcoUserServ = RemittanceCompanyUserServiceFactory::build();
                        $remcoUserServ->setUpdatedBy($this->getUpdatedBy());
                        $remcoUserServ->setIpAddress($this->getIpAddress());
                        if( !$remcoUserServ->getByCompanyAndUser($remco, $user->getId()) )
                        {//create a remittance_company_user of user_profile_id and service_provider_id with
                            return $remcoUserServ->createProfile($remco, $user);
                        }
                        else //If remittance_company_user exists, do nothing and return true
                            return true;
                    }
                    else //if this does not belong to a remittance company, do nothing and return true
                        return true;
                }
            }

            return false;
        }

        return true;
    }
}