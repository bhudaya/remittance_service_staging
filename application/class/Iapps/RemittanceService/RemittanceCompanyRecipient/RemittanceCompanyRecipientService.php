<?php

namespace Iapps\RemittanceService\RemittanceCompanyRecipient;

use Iapps\Common\Core\IappsBaseService;
use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\Common\Microservice\AccountService\AgentAccountServiceFactory;
use Iapps\Common\Microservice\AccountService\User;
use Iapps\Common\Microservice\CountryService\CountryServiceFactory;
use Iapps\Common\Microservice\UserCreditService\IUserCreditGetterInterface;
use Iapps\Common\Microservice\UserCreditService\WorldCheck;
use Iapps\Common\Microservice\UserCreditService\OFACStatus;
use Iapps\Common\Microservice\UserCreditService\WorldCheckStatus;
use Iapps\Common\Microservice\UserCreditService\SystemUserCreditServiceFactory;
use Iapps\RemittanceService\Common\IncrementIDAttribute;
use Iapps\RemittanceService\Common\IncrementIDServiceFactory;
use Iapps\RemittanceService\Common\MessageCode;
use Iapps\RemittanceService\Common\SystemCodeServiceFactory;
use Iapps\RemittanceService\Recipient\Recipient;
use Iapps\RemittanceService\Recipient\RecipientServiceFactory;
use Iapps\RemittanceService\RemittanceCompany\RemittanceCompany;
use Iapps\RemittanceService\RemittanceCompany\RemittanceCompanyServiceFactory;

class RemittanceCompanyRecipientService extends IappsBaseService{

    public function findById($id)
    {
        return $this->getRepository()->findById($id);
    }

    /*
     * Create a remittance recipient
     */

    public function createProfile(RemittanceCompany $remittanceCompany, $recipient_id, $user_profile_id)
    {
        $user = new User();
        $user->setId($user_profile_id);
        if( !$recipient = $this->_getRecipient($recipient_id) OR
            !$recipient->belongsTo($user) )
        {
            $this->setResponseCode(MessageCode::CODE_RECIPIENT_NOT_FOUND);
            return false;
        }        
        
		if( $remittanceProfile = RemittanceCompanyRecipient::create($remittanceCompany, $recipient) )
		{
			$this->_extractRecipientStatus($remittanceProfile);

            $remittanceProfile->setCreatedBy($this->getUpdatedBy());            
            if ($this->getRepository()->insert($remittanceProfile)) {
                $this->setResponseCode(MessageCode::CODE_ADD_RECIPIENT_SUCCESS);
                
                //publish 
                RemittanceCompanyRecipientEventProducer::publishRecipientCreated($recipient_id, $remittanceCompany->getServiceProviderId());
                return $remittanceProfile;
            }
        }

        $this->setResponseCode(MessageCode::CODE_ADD_RECIPIENT_FAIL);
        return false;
    }

    public function checkProfilesStatus($recipient_id)
    {
        if( !$recipient = $this->_getRecipient($recipient_id) )
            return false;

        if( $remRecProfiles = $this->listByRecipient($recipient_id, 1, MAX_VALUE, false) )
        {
            $update_success = false;
            foreach($remRecProfiles AS $remRecProfile)
            {
                if( $remRecProfile instanceof RemittanceCompanyRecipient )
                {
                    $this->checkProfileStatus($remRecProfile);                    
                }
            }
        }

        return true;
    }
    
    public function checkProfileStatus(RemittanceCompanyRecipient $remittanceProfile)
    {
        //get UC status
        list($ucStat, $ucLastUpdatedAt) = $this->_getRecipientUserCredit($remittanceProfile);
        
        $statusToBe = null;
        if( $ucStat == 'fail' )
            $statusToBe = RemittanceCompanyRecipientStatus::FAILED_VERIFY;
        elseif( $ucStat == 'pending' )
            $statusToBe = RemittanceCompanyRecipientStatus::PENDING_VERIFY;
        elseif( $ucStat == 'pass' )
        {   
            $last_edited = MAX_VALUE;
            if( $recipient = $this->_getRecipient($remittanceProfile->getRecipient()->getId()) )
                //if its already verified after the uc statuses, last edited, then no changed needed
                $last_edited = $recipient->getLastEditedAt()->isNull() ? 0 : $recipient->getLastEditedAt()->getUnix();
            
            if( $remittanceProfile->isFaceToFaceVerified() AND
                $remittanceProfile->getFaceToFaceVerifiedAt()->getUnix() > $last_edited )
                $statusToBe = RemittanceCompanyRecipientStatus::VERIFIED;   //no changed
            else
                $statusToBe = RemittanceCompanyRecipientStatus::PENDING_VERIFY;
        }

        if( !is_null($statusToBe) AND
            $statusToBe != $remittanceProfile->getRecipientStatus()->getCode() )
        {//this is either pending or failed verify
            $remittanceProfile->getRecipientStatus()->setCode($statusToBe);
            $remittanceProfile->setFaceToFaceVerifiedBy(NULL);
            $remittanceProfile->setFaceToFaceVerifiedAt((new IappsDateTime()));
            
            $this->_extractRecipientStatus($remittanceProfile);
            //update profile            
            $remittanceProfile->setUpdatedBy($this->getUpdatedBy());
            if($this->getRepository()->update($remittanceProfile, false))
            {
                return $remittanceProfile;
            }
        }
        
        return true;
    }

    public function verifyProfile(Recipient $recipient, RemittanceCompany $remittanceCompany)
    {
    	if( !$remittanceProfile = $this->getByCompanyAndRecipient($remittanceCompany, $recipient->getId()) )
        {
        	$this->setResponseCode(MessageCode::CODE_RECIPIENT_NOT_FOUND);
            return false;
		}
		
        if( $remittanceProfile instanceof RemittanceCompanyRecipient )
        {
            $remittanceProfile->setRecipient($recipient);
            if( $remittanceProfile->getRecipientStatus()->getCode() == RemittanceCompanyRecipientStatus::PENDING_VERIFY )
            {
            	//todo validate with user credit statuses
                list($ucStat, $ucLastUpdatedAt) = $this->_getRecipientUserCredit($remittanceProfile);                
                if( $ucStat == 'pass' )
                {
                    $admin = new User();
                    $admin->setId($this->getUpdatedBy());
                    $remittanceProfile->faceToFaceVerify($admin);
                    $this->_extractRecipientStatus($remittanceProfile);

                    //update
                    $remittanceProfile->setUpdatedBy($this->getUpdatedBy());
                    if( $this->getRepository()->update($remittanceProfile) )
                    {
                        $this->setResponseCode(MessageCode::CODE_VERIFY_RECIPIENT_SUCCESS);
                        return true;
                    }
                }            	
            }
        }

        $this->setResponseCode(MessageCode::CODE_VERIFY_RECIPIENT_FAILED);
        return false;
    }

    public function getByServiceProviderIdAndRecipient($service_provider_id, $recipient_id)
    {
        $remcoServ = RemittanceCompanyServiceFactory::build();
        if( $remco = $remcoServ->getByServiceProviderId($service_provider_id) )
        {
            return $this->getByCompanyAndRecipient($remco, $recipient_id);
        }
        
        $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_RECIPIENT_FAILED);
        return false;
    }
    
    public function getByCompanyAndRecipient(RemittanceCompany $company, $recipient_id)
    {
        if( $info = $this->getRepository()->findByRecipientIdAndCompanyId($recipient_id, $company->getId()) )
        {
            $info->result->rewind();
            $result = $info->result->current();
            $result->setRemittanceCompany($company);
            return $result;
        }

        $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_RECIPIENT_FAILED);
        return false;
    }
	
	public function listByCompanyAndUser(RemittanceCompany $company, $user_profile_id)
	{
		//get recipient list by user
		$recipientServ = RecipientServiceFactory::build(true, '2');
		if( $list = $recipientServ->getBasicRecipientList($user_profile_id) )
		{
			$recipientIds = $list->getIds();
			
			$filters = new RemittanceCompanyRecipientCollection();
			foreach($recipientIds AS $recipientId)
			{
				$filter = new RemittanceCompanyRecipient();
				$filter->getRecipient()->setId($recipientId);
                $filter->setRemittanceCompany($company);
				$filters->addData($filter);
			}
        		
			if ($info = $this->getRepository()->findByFilters($filters)) 
			{
				$this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_RECIPIENT_SUCCESS);
				return $info;
			}									
		}
		
		$this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_RECIPIENT_FAILED);
        return false;
	}

    public function listByRecipient($recipient_id, $page = 1, $limit = MAX_VALUE, $isArray = true)
    {
        if ($info = $this->getRepository()->findByRecipientId($recipient_id)) {
            $remRecList = $info->getResult();
            //get company
            $companyServ = RemittanceCompanyServiceFactory::build();
            if ($companyList = $companyServ->getList(1, MAX_VALUE, false))
                $remRecList->joinRemittanceCompany($companyList);


            $remRecList = $remRecList->pagination($limit, $page)->getResult();
            $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_RECIPIENT_SUCCESS);
            if ($isArray) {
                $result = array();

                foreach ($remRecList AS $remRec) {
                    $temp = $remRec->getSelectedField(array('id', 'recipient_status', 'face_to_face_verified_at', 'face_to_face_verified_by'));
                    $temp['remittance_company'] = $remRec->getRemittanceCompany()->getSelectedField(array('service_provider_id', 'uen', 'mas_license_no'));
                    $temp['remittance_company']['name'] = $remRec->getRemittanceCompany()->getCompanyInfo()->getName();
                    $temp['remittance_company']['logo'] = $remRec->getRemittanceCompany()->getCompanyInfo()->getProfileImageUrl();

                    $result[] = $temp;
                }

                return $result;
            } else
                return $remRecList;
        }


        $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_RECIPIENT_FAILED);
        return false;
    }

    public function listByRemittanceCompany(RemittanceCompany $company, RemittanceCompanyRecipientCollection $filters)
    {
        if( count($filters) <= 0 )
        {
            $filter = new RemittanceCompanyRecipient();
            $filters->addData($filter); //to make sure at least one filter
        }

        $filters->setRemittanceCompany($company);

        if( $info = $this->getRepository()->findByFilters($filters) )
        {
            $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_RECIPIENT_SUCCESS);
            return $info;
        }

        $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_RECIPIENT_FAILED);
        return false;
    }
    
    //[ (pass, fail, pending), (last updated at) ]
    protected function _getRecipientUserCredit(RemittanceCompanyRecipient $recipient)
    {        
        $usercreditServ = SystemUserCreditServiceFactory::build();
        $usercreditServ->setServiceProviderId($recipient->getRemittanceCompany()->getServiceProviderId());
        
        //get statuses
        $watchListStatus = $usercreditServ->getWatchlistStatus(NULL, $recipient->getRecipient()->getId());
        $acuityStatus = $usercreditServ->getWorldCheckStatus(NULL, $recipient->getRecipient()->getId());        
        if( $recipient->getRemittanceCompany()->getRequiredAcuityCheck() )
        {                            
            if( $acuityStatus instanceof WorldCheck )
            {
                $last_updated = 0;
                if( !$acuityStatus->getUpdatedAt()->isNull() )
                    $last_updated = $acuityStatus->getUpdatedAt()->getUnix();
                else
                    $last_updated = $acuityStatus->getCreatedAt()->getUnix();
                                    
                if( $acuityStatus->getStatus() == WorldCheckStatus::PASS )
                    return array('pass', $last_updated);
                elseif( $acuityStatus->getStatus() == WorldCheckStatus::PENDING )
                    return array('pending', $last_updated);
                elseif( $acuityStatus->getStatus() == WorldCheckStatus::FAIL )
                    return array('fail', $last_updated);
            }
            
            return array('pending', 0);   //consider as pending if can't get            
        }
        else
        {
            if( isset($watchListStatus->watchlist->status) )
            {
                $last_updated = 0;
                if( isset($watchListStatus->watchlist->last_updated_at) )
                    $last_updated = IappsDateTime::fromString($watchListStatus->watchlist->last_updated_at)->getUnix();                    
                
                if( $watchListStatus->watchlist->status == 'pass' )
                    return array('pass', $last_updated);
                elseif( $watchListStatus->watchlist->status == 'pending' )
                    return array('pending', $last_updated);
                elseif( $watchListStatus->watchlist->status == 'fail' )
                    return array('fail', $last_updated);
            }
            
            return array('pending', 0);
        }
    }

    protected function _getCountryInfo($country_code)
    {
        $country_serv = CountryServiceFactory::build();
        return $country_serv->getCountryInfo($country_code);
    }

    protected function _getRemittanceCompany($service_provider_id)
    {
        $rcServ = RemittanceCompanyServiceFactory::build();
        return $rcServ->getByServiceProviderId($service_provider_id);
    }

    protected function _getRecipient($recipient_id)
    {
        $recipient_serv = RecipientServiceFactory::build(true, 2);
        if( !$recipient = $recipient_serv->getBasicRecipientDetail($recipient_id) )
        {
            $this->setResponseCode(MessageCode::CODE_INVALID_RECIPIENT);
            return false;
        }

        if( $user_id = $recipient->getRecipientUserProfileId() )
        {
            $accServ =  AccountServiceFactory::build();
            if ($user = $accServ->getUser(NULL, $user_id))
            {
                $recipient->setRecipientUser($user);
            }
        }

        return $recipient;
    }

    protected function _extractRecipientStatus(RemittanceCompanyRecipient $recipient)
    {
        $sys_serv = SystemCodeServiceFactory::build();
        if( !$recipientstatus = $sys_serv->getByCode($recipient->getRecipientStatus()->getCode(), RemittanceCompanyRecipientStatus::getSystemGroupCode()) )
        {
            $this->setResponseCode(MessageCode::CODE_INVALID_SYSTEM_CODE);
            return false;
        }

        $recipient->setRecipientStatus($recipientstatus);
        return true;
    }

}