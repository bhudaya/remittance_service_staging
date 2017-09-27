<?php

namespace Iapps\RemittanceService\SearchRemcoRecipient;

use Iapps\Common\Core\IappsBaseService;
use Iapps\RemittanceService\RemittanceCompanyRecipient\RemittanceCompanyRecipient;
use Iapps\RemittanceService\RemittanceCompanyRecipient\RemittanceCompanyRecipientCollection;
use Iapps\RemittanceService\Recipient\Recipient;
use Iapps\RemittanceService\Recipient\RecipientCollection;
use Iapps\RemittanceService\Attribute\RecipientAttribute;
use Iapps\RemittanceService\Attribute\RecipientAttributeCollection;
use Iapps\Common\Core\IappsBaseRepository;
use Iapps\RemittanceService\RemittanceCompany\RemittanceCompany;
use Iapps\RemittanceService\RemittanceCompany\RemittanceCompanyServiceFactory;
use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\RemittanceService\Common\MessageCode;
use Iapps\RemittanceService\Attribute\RecipientAttributeServiceFactory;
use Iapps\Common\Microservice\UserCreditService\SystemUserCreditServiceFactory;
use Iapps\Common\Core\IappsBaseEntityCollection;
use Iapps\Common\Microservice\UserCreditService\WorldCheckStatus;

class SearchRemcoRecipientService extends IappsBaseService{
    
    protected $attributeFilter = NULL;
    protected $remittanceCompany = NULL;
    protected $recipientStatuses = array();
    protected $accountID = NULL;
    protected $userprofileIds = array();
    protected $mobileNumbers = array();


    public function __construct(IappsBaseRepository $rp, $ipAddress = '127.0.0.1', $updatedBy = NULL) {
        parent::__construct($rp, $ipAddress, $updatedBy);
        
        $this->attributeFilter = new RecipientAttributeCollection();
        $this->remittanceCompany = new RemittanceCompany(); 
    }
    
    public function addAttributeFilter($code, $value)
    {
        if( $attr = $this->attributeFilter->getByCode($code) )
        {
            $attr->getValue(false)->setValue($code);
        }
        else
        {
            $attr = new RecipientAttribute();
            $attr->getAttribute()->setCode($code);
            $attr->getValue(false)->setValue($value);
            $this->attributeFilter->addData($attr); 
        }
            
        return $this;
    }
    
    public function setServiceProviderId($service_provider_id)
    {
        $remcoServ = RemittanceCompanyServiceFactory::build();
        if( $remco = $remcoServ->getByServiceProviderId($service_provider_id) )
        {
            $this->remittanceCompany = $remco;
        }
        
        return $this;
    }
    
    public function setStatus(array $statuses)
    {
        $this->recipientStatuses = $statuses;
        return $this;
    }
    
    public function setMobileNumber(array $mobile_number)
    {
        $this->mobileNumbers = $mobile_number;
        return $this;
    }
    
    public function setAccountID($accountID)
    {
        $this->accountID = $accountID;
        return $this;
    }

    public function search($page, $limit)
    {
        //if account ID is given
        $accServ = AccountServiceFactory::build();
        if( $this->accountID )
        {            
            if( !$user = $accServ->getUser($this->accountID) )
            {
                $this->setResponseCode(MessageCode::CODE_RECIPIENT_LIST_NOT_FOUND);
                return false;
            }
            
            $this->userprofileIds[] = $user->getId();
        }
        
        $filters = $this->_constructFilters();
        $recipientFilter = $this->_constructRecipientFilters();
        if( $info = $this->getRepository()->findByRecipientsAttributes($filters, $recipientFilter, $this->attributeFilter) )
        {
            $paginatedResult = $info->getResult()->pagination($limit, $page);
            
            if( count($paginatedResult->getResult()) > 0 )
            {                
                $this->setResponseCode(MessageCode::CODE_RECIPIENT_LIST_FOUND);
                
                $recipientIds = $paginatedResult->getResult()->getFieldValues('recipient');
                $recipientList = $paginatedResult->getResult()->getRecipientList();
                $recipientAttrService = RecipientAttributeServiceFactory::build();
                $attributeInfo = $recipientAttrService->getByRecipientIds($recipientIds);                    
               
                $ucServ = SystemUserCreditServiceFactory::build();
                $ucServ->setServiceProviderId($this->remittanceCompany->getServiceProviderId());                
                $acuityList = $ucServ->getWorldCheckStatusList(array(), $recipientIds);
                $watchlistList = $ucServ->getWatchlistStatusList(array(), $recipientIds);
                
                if( $userIds = $recipientList->getFieldValues('user_profile_id') AND 
                    $userList = $accServ->getUsers($userIds) )
                {
                    $recipientList->joinUser($userList);
                }
                    

                $result = array();
                foreach( $paginatedResult->getResult() AS $remcoRecipient )
                {
                    $temp = $remcoRecipient->jsonSerialize();
                    
                    if( $attributeInfo )
                    {
                        foreach($attributeInfo AS $attribute)
                        {
                            if( $attribute instanceof RecipientAttribute )
                            {
                                if( $attribute->belongsTo($remcoRecipient->getRecipient()) )
                                    $remcoRecipient->getRecipient()->getAttributes()->addData($attribute);
                            }
                        }
                    }
                    
                    $temp['recipient'] = $remcoRecipient->getRecipient()->jsonSerialize();
                    $temp['recipient']['user'] = $remcoRecipient->getRecipient()->getUser()->getSelectedField(array('id', 'accountID', 'name'));
                    $temp['recipient']['attributes'] = $remcoRecipient->getRecipient()->getAttributes()->getSelectedField(array('attribute_code', 'attribute_name', 'value'));
                    $temp['acuity_status'] = $this->_getAcuityStatusFromList($remcoRecipient->getRecipient(), $acuityList);
                    $temp['watchlist_status'] = $this->_getWatchlistStatusFromList($remcoRecipient->getRecipient(), $watchlistList);
                    
                    $result[] = $temp;
                }
                
                $paginatedResult->setResult($result);
                return $paginatedResult;
            }            
        }
        
        $this->setResponseCode(MessageCode::CODE_RECIPIENT_LIST_NOT_FOUND);
        return false;
    }
    
    protected function _constructFilters()
    {
        $filters = new RemittanceCompanyRecipientCollection();
        
        if( $this->remittanceCompany->getId() )
        {
            $filter = new RemittanceCompanyRecipient();
            $filter->setRemittanceCompany($this->remittanceCompany);
            $filters->addData($filter);
        }
        
        //statuses
        foreach( $this->recipientStatuses AS $status )
        {
            $filter = new RemittanceCompanyRecipient();
            $filter->getRecipientStatus()->setCode($status);
            $filters->addData($filter);            
        }
        
        return $filters;
    }
    
    protected function _constructRecipientFilters()
    {
        $filters = new RecipientCollection();
        
        foreach( $this->userprofileIds AS $userProfileId )
        {
            $filter = new Recipient();
            $filter->setUserProfileId($userProfileId);
            $filters->addData($filter);
        }
        
        //statuses
        foreach( $this->mobileNumbers AS $mobileNumber )
        {
            $filter = new Recipient();
            $filter->getRecipientMobileNumber()->setValue($mobileNumber);
            $filters->addData($filter);            
        }
        
        return $filters;
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
}