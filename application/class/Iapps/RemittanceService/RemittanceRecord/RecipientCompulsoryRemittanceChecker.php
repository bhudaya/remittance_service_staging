<?php

namespace Iapps\RemittanceService\RemittanceRecord;

use Iapps\Common\Core\IappsBasicBaseService;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfig;
use Iapps\RemittanceService\RemittanceCompanyRecipient\RemittanceCompanyRecipientServiceFactory;
use Iapps\RemittanceService\RemittanceCompanyRecipient\RemittanceCompanyRecipient;
use Iapps\RemittanceService\RemittanceCompanyRecipient\RemittanceCompanyRecipientStatus;

class RecipientCompulsoryRemittanceChecker extends IappsBasicBaseService{
    
    protected $_passed = false;

    protected function setPass()
    {
        $this->_passed = true;
        return $this;
    }

    protected function setFail()
    {
        $this->_passed = false;
        return $this;
    }

    public function fails()
    {
        return ($this->_passed === false);
    }

    public function checkRequestEligible($user_profile_id, $recipient_id, RemittanceConfig $remittanceConfig)
    {//if remittance company requires face to face recipient
        $this->setFail();
        
        $remco = $remittanceConfig->getRemittanceCompany();
        
        //get remco recipient, create if did not find one, applicable to all regardless of f2f recipient setting
        $remcoRecipientServ = RemittanceCompanyRecipientServiceFactory::build();
        $remcoRecipientServ->setUpdatedBy($this->getUpdatedBy());
        $remcoRecipientServ->setIpAddress($this->getIpAddress());
        if( !$remcoRecipient = $remcoRecipientServ->getByCompanyAndRecipient($remco, $recipient_id) )
        {//create one
            $remcoRecipient = $remcoRecipientServ->createProfile($remco, $recipient_id, $user_profile_id);
        }
            
        if( $remco->getRequiredFaceToFaceRecipient() == 1 )
        {  
            if( $remcoRecipient instanceof RemittanceCompanyRecipient )
            {//as long as it's not failed, its eligible to request
                if( !($remcoRecipient->getRecipientStatus()->getCode() == RemittanceCompanyRecipientStatus::FAILED_VERIFY) )
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

    public function check($user_profile_id, $recipient_id, RemittanceConfig $remittanceConfig)
    {
        $this->setFail();
        
        $remco = $remittanceConfig->getRemittanceCompany();
        if( $remco->getRequiredFaceToFaceRecipient() == 1 )
        {
            //get remco recipient
            $remcoRecipientServ = RemittanceCompanyRecipientServiceFactory::build();
            if( $remcoRecipient = $remcoRecipientServ->getByCompanyAndRecipient($remco, $recipient_id) AND
                $remcoRecipient instanceof RemittanceCompanyRecipient )
            {//must be verified
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