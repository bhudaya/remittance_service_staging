<?php

namespace Iapps\RemittanceService\RemittanceCompanyRecipient;

use Iapps\Common\Helper\MessageBroker\BroadcastEventConsumer;
use Iapps\RemittanceService\RemittanceCompany\RemittanceCompanyServiceFactory;
use Iapps\Common\Microservice\BaseMicroservice;
use Iapps\RemittanceService\Common\Logger;

class RecheckRemcoRecipientStatusListener extends BroadcastEventConsumer{
    
    protected function doTask($msg)
    {
        $this->setForceAcknowledgement(false);
        BaseMicroservice::enableMemory(false);

        $data = json_decode($msg->body);

        try {
            $remcoRecipientService = RemittanceCompanyRecipientServiceFactory::build();
            $remcoRecipientService->setUpdatedBy($this->getUpdatedBy());
            $remcoRecipientService->setIpAddress($this->getIpAddress());
                
            if (isset($data->recipient_id) AND isset($data->service_provider_id) )
            {//if both given, only check the profile under this remco
                Logger::debug('processing recipient: ' . $data->recipient_id . ', ' . $data->service_provider_id);
                
                //get remco
                $remcoServ = RemittanceCompanyServiceFactory::build();
                if( $remco = $remcoServ->getByServiceProviderId($data->service_provider_id) AND
                    $remcoProfile = $remcoRecipientService->getByCompanyAndRecipient($remco, $data->recipient_id) )                        
                {
                    $remcoProfile->setRemittanceCompany($remco);
                    if( $remcoRecipientService->checkProfileStatus($remcoProfile) )
                        return true;

                    return false;
                }
            }
            elseif( isset($data->recipient_id) )
            {//else check for all remco
                Logger::debug('processing recipient: ' . $data->recipient_id);
                
                if( $remcoRecipientService->checkProfilesStatus($data->recipient_id) )
                    return true;
                
                return false;
            }

            //drop the rest of messages
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function listenEvent()
    {
        $this->listenWithoutDeclaration('remittance.queue.recheckRecipientStatus');
    }
}

