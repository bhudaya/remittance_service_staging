<?php

namespace Iapps\RemittanceService\RemittanceCompanyRecipient;

use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\RemittanceService\Common\MessageCode;
use Iapps\Common\Microservice\AccountService\User;
use Iapps\Common\Core\PaginatedResult;
use Iapps\RemittanceService\RemittanceCompanyRecipient\RemittanceCompanyRecipientStatus;
use Iapps\RemittanceService\Recipient\RecipientServiceFactory;
use Iapps\Common\Microservice\UserCreditService\SystemUserCreditServiceFactory;
use Iapps\Common\Core\IappsBaseEntityCollection;
use Iapps\Common\Microservice\UserCreditService\WorldCheckStatus;
use Iapps\RemittanceService\Recipient\Recipient;
use Iapps\RemittanceService\Attribute\AttributeCode;

class AgentRemittanceCompanyRecipientService extends RemittanceCompanyRecipientService{

	public function addRecipient($recipient_id, $user_profile_id)
	{		
		//get agent and upline
        $service_provider_id = NULL;
        if( $mainAgent = $this->_getMainAgent() )
		{
        	$service_provider_id = $mainAgent->getId();
        } else {
            $this->setResponseCode(MessageCode::CODE_REMCO_NOT_FOUND);
            return false;
        }
		
		if( $remittanceCompany = $this->_getRemittanceCompany($service_provider_id) AND
			!$existingRemcoRecipient = $this->getByCompanyAndRecipient($remittanceCompany, $recipient_id) )
		{
			return $this->createProfile($remittanceCompany, $recipient_id, $user_profile_id);
		}
		
        $this->setResponseCode(MessageCode::CODE_ADD_RECIPIENT_FAIL);
		return false;
	}
	
	public function getList($user_profile_id, $status)
	{
		//get agent and upline
        $service_provider_id = NULL;
        if( $mainAgent = $this->_getMainAgent() )
		{
        	$service_provider_id = $mainAgent->getId();
        } else {
            $this->setResponseCode(MessageCode::CODE_REMCO_NOT_FOUND);
            return false;
        }
		
		if( $remittanceCompany = $this->_getRemittanceCompany($service_provider_id) )
		{
            $recipientServ = RecipientServiceFactory::build(true, '2');
            if( !$list = $recipientServ->getBasicRecipientList($user_profile_id) )
            {
                $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_RECIPIENT_FAILED);
                return false;
            }
            
            $remcoRecipientList = new RemittanceCompanyRecipientCollection();
            $filterRemcoRecipientList = new RemittanceCompanyRecipientCollection();
            $acuityList = NULL;
            $watchlistList = NULL;
			if( $info = $this->listByCompanyAndUser($remittanceCompany, $user_profile_id) )
			{
                $remcoRecipientList = $info->result;                
                $filterRemcoRecipientList = $remcoRecipientList->getByStatus($status);
                
                $ucServ = SystemUserCreditServiceFactory::build();
                $ucServ->setServiceProviderId($remittanceCompany->getServiceProviderId());
                $recipientIds = $filterRemcoRecipientList->getRecipientIds();
                $acuityList = $ucServ->getWorldCheckStatusList(array(), $recipientIds);
                $watchlistList = $ucServ->getWatchlistStatusList(array(), $recipientIds);
            }
                
            $result = array();
            foreach($list AS $recipient)
            {                                        
                if( $country_code = $recipient->getAttributes()->hasAttribute(AttributeCode::RESIDENTIAL_COUNTRY) )
                    $recipient->setRecipientResidentialCountry($country_code);

                if( $remcoRecipient = $filterRemcoRecipientList->getByRecipient($recipient) )
                {//add to result
                    $temp = $recipient->jsonSerialize();

                    $temp['is_remco_recipient'] = true;
                    $temp['remco_recipient'] = $remcoRecipient;
                    $temp['acuity_status'] = $this->_getAcuityStatusFromList($recipient, $acuityList);
                    $temp['watchlist_status'] = $this->_getWatchlistStatusFromList($recipient, $watchlistList);

                    $result[] = $temp;
                }
                elseif( $status == RemittanceCompanyRecipientStatus::PENDING_VERIFY AND 
                        !$remcoRecipientList->getByRecipient($recipient))
                {//include none remco recipient 
                    $temp = $recipient->jsonSerialize();
                    $temp['is_remco_recipient'] = false;
                    $temp['remco_recipient'] = null;
                    $temp['acuity_status'] = null;
                    $temp['watchlist_status'] = null;

                    $result[] = $temp;
                }
            }

            if( count($result) > 0 )
            {                                                            
                $list = new PaginatedResult();
                $list->setResult($result);
                $list->setTotal(count($result));

                $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_RECIPIENT_SUCCESS);
                return $list;	
            }
		}
		
		$this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_RECIPIENT_FAILED);
		return false;
	}
    
    protected function _getAcuityStatusFromList(Recipient $recipient, $acuityList)
    {
        if( $acuityList instanceof IappsBaseEntityCollection )
        {
            foreach($acuityList AS $acuity)
            {
                if( $acuity->getRecipientId() == $recipient->getId() )
                {
                    switch( $acuity->getStatus() )
                    {
                        case WorldCheckStatus::PASS:
                            return 'pass';
                        case WorldCheckStatus::PENDING:
                            return 'pending';
                        case WorldCheckStatus::FAIL:    
                            return 'fail';
                        default:
                            return 'pending';
                    }                     
                }
            }
        }        
        
        return 'pending';
    }
    
    protected function _getWatchlistStatusFromList(Recipient $recipient, $watchList)
    {
        if( is_array($watchList) )
        {
            foreach($watchList AS $watch)
            {
                if( isset($watch->recipient_id) AND 
                    isset($watch->status->watchlist->status) AND
                    $watch->recipient_id == $recipient->getId() )
                {
                    switch($watch->status->watchlist->status)
                    {
                        case 'pass':
                            return 'pass';
                        case 'pending':
                            return 'pending';
                        case 'fail':
                            return 'fail';
                        default:
                            return 'pending';
                    }
                }
            }
        }
        
        return 'pending';
    }

    public function verifyFaceToFaceRecipient($recipient_id, $user_profile_id)
    {
    	$user = new User();
		$user->setId($user_profile_id);
		
        //get recipient
        if( !$recipient = $this->_getRecipient($recipient_id) OR
        	!$recipient->belongsTo($user) )
		{
			$this->setResponseCode(MessageCode::CODE_INVALID_RECIPIENT);
			return false;
		}		            

        //get agent and upline
        $service_provider_id = NULL;
        if( $mainAgent = $this->_getMainAgent() )
        {
            $service_provider_id = $mainAgent->getId();
        } else {
            $this->setResponseCode(MessageCode::CODE_REMCO_NOT_FOUND);
            return false;
        }

        if( $remittanceCompany = $this->_getRemittanceCompany($service_provider_id) )
        {
            $serv = RemittanceCompanyRecipientServiceFactory::build();
            $serv->setIpAddress($this->getIpAddress());
            $serv->setUpdatedBy($this->getUpdatedBy());

            if( $serv->verifyProfile($recipient, $remittanceCompany) ) {
                $this->setResponseCode(MessageCode::CODE_VERIFY_RECIPIENT_SUCCESS);
                return true;
            } else {
                $this->setResponseCode($serv->getResponseCode());
                return false;
            }
        }

        $this->setResponseCode(MessageCode::CODE_VERIFY_RECIPIENT_FAILED);
        return false;
    }
  
    protected function _getMainAgent()
    {
        $acc_serv = AccountServiceFactory::build();
        if( $structure = $acc_serv->getAgentUplineStructure() )
        {
            if( $upline = $structure->first_upline )
            {
                if( $upline->getRoles()->hasRole(array('main_agent')) )
                {
                    return $upline->getUser();
                }
            }

            if( $upline = $structure->second_upline )
            {
                if( $upline->getRoles()->hasRole(array('main_agent')) )
                {
                    return $upline->getUser();
                }
            }
        }

        return false;
    }


}