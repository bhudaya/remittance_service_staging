<?php

namespace Iapps\RemittanceService\RemittanceRecord;

use Iapps\RemittanceService\RemittanceConfig\RemittanceConfig;
use Iapps\RemittanceService\RemittanceCompanyRecipient\RemittanceCompanyRecipientServiceFactory;
use Iapps\RemittanceService\RemittanceCompanyRecipient\RemittanceCompanyRecipient;
use Iapps\RemittanceService\RemittanceCompanyRecipient\RemittanceCompanyRecipientStatus;

class AgentRecipientCompulsoryRemittanceChecker extends RecipientCompulsoryRemittanceChecker{
    
    public function checkRequestEligible($user_profile_id, $recipient_id, RemittanceConfig $remittanceConfig)
    {//if remittance company requires face to face recipient
        $this->setFail();
               
        $remco = $remittanceConfig->getRemittanceCompany();
        if( $remco->getRequiredFaceToFaceRecipient() == 1 )
        {
            //get remco recipient, failed if none are found
            $remcoRecipientServ = RemittanceCompanyRecipientServiceFactory::build();
            if( $remcoRecipient = $remcoRecipientServ->getByCompanyAndRecipient($remco, $recipient_id) AND
                $remcoRecipient instanceof RemittanceCompanyRecipient )
            {//its eligible to request only if its verified
                if( $remcoRecipient->getRecipientStatus()->getCode() == RemittanceCompanyRecipientStatus::VERIFIED )
                    $this->setPass();
                else
                    $this->setFail();
                
                return $this;
            }            
        }
        else //dont care if remittance company dooesnt require
            $this->setPass();
        
        return $this;
    }
}