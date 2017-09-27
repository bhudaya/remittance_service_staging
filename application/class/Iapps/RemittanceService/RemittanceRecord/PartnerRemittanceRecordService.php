<?php

namespace Iapps\RemittanceService\RemittanceRecord;

use Iapps\Common\Microservice\PaymentService\PaymentServiceFactory;

use Iapps\RemittanceService\Common\ChannelTypeValidator;
use Iapps\Common\Microservice\AccountService\AccountService;
use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\RemittanceService\Common\MessageCode;
use Iapps\Common\Core\IappsBaseRepository;
use Iapps\RemittanceService\Common\SystemCodeServiceFactory;
use Iapps\RemittanceService\Recipient\RecipientServiceFactory;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransactionServiceFactory;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfigServiceFactory;
use Iapps\RemittanceService\Common\CorporateServServiceFactory;

use Iapps\Common\Microservice\UserCreditService\PrelimCheckServiceFactory;
use Iapps\RemittanceService\RemittanceCompanyUser\RemittanceCompanyUserServiceFactory;
use Iapps\RemittanceService\RemittanceCompanyRecipient\RemittanceCompanyRecipientServiceFactory;
use Iapps\RemittanceService\RemittanceCompany\RemittanceCompanyServiceFactory;
use Iapps\Common\Microservice\AccountService\PartnerAccountServiceFactory;
use Iapps\RemittanceService\SearchRemittanceRecord\SearchRemittanceRecordServiceFactory;
use Iapps\Common\Core\IappsDateTime;
use Iapps\RemittanceService\Attribute\AttributeCode;
use Iapps\RemittanceService\RemittanceRecord\RemittanceStatus;

class PartnerRemittanceRecordService extends RemittanceRecordService{

    protected $paymentInterface;
    protected $channel;
    protected $calculation_direction = 'to';
    protected $send_amount = NULL;

    protected $_accountService;
    protected $_paymentService;

    protected function _extractStatusId(RemittanceRecord $remittance)
    {
        //extract status id
        $system_code_serv = SystemCodeServiceFactory::build();
        if( !$status = $system_code_serv->getByCode($remittance->getStatus()->getCode(), RemittanceStatus::getSystemGroupCode()))
        {
            $this->setResponseCode(MessageCode::CODE_REMITTANCE_RECORD_NOT_FOUND);
            return false;
        }
        $remittance->setStatus($status);

        return $remittance;
    }


    public function setAccountService(AccountService $accountService)
    {
        $this->_accountService = $accountService;
    }

    public function getAccountService()
    {
        if( !$this->_accountService )
        {
            $this->_accountService = AccountServiceFactory::build();
        }

        return $this->_accountService;
    }

    public function setChannelCode($code)
    {
        if( $channel = ChannelTypeValidator::validate($code) )
        {
            $this->channel = $channel;
            return $this;
        }

        return false;
    }

    public function getChannel()
    {
        if( !$this->channel )
        {
            $this->setResponseCode(MessageCode::CODE_INVALID_CHANNEL);
            return false;
        }

        return $this->channel;
    }

    public function setCalcDirection($calcDir)
    {
        $this->calculation_direction = $calcDir;

        return $this;
    }

    public function getCalcDirection()
    {
        return $this->calculation_direction;
    }

    public function setSendAmount($sendAmount)
    {
        $this->send_amount = $sendAmount;

        return $this;
    }

    public function getSendAmount()
    {
        return $this->send_amount;
    }

    function __construct(IappsBaseRepository $rp, $ipAddress='127.0.0.1', $updatedBy=NULL, RemittancePaymentInterface $interface = NULL)
    {
        parent::__construct($rp, $ipAddress, $updatedBy);
        if( $interface == NULL )
            $this->paymentInterface = new UserRemittancePayment();
        else
            $this->paymentInterface = $interface;
    }

    protected function _isRequestForPayment(RemittanceFeeCalculator $calculator)
    {
        return false;
    }

    protected function _completeAction(RemittanceRecord $remittanceRecord)
    {
        return false;
    }
    
    public function getServiceProviderId($admin_id)
    {
        // get user profile
        $serviceAccount = $this->getAccountService();
        if( $structure = $serviceAccount->getUplineStructure($admin_id) )
        {
            $upline = $structure->first_upline;
            if( $upline )
            {
                $user = $upline->getUser();
                $user_id = $user->getId();
                return $user_id;
            }
        }
        
        $this->setResponseCode(MessageCode::CODE_INVALID_SERVICE_PROVIDER);
        return false;
    }
    
    public function getRemittanceTransactionDetail($id)
    {
        if( $detail = parent::getRemittanceTransactionDetail($id) )
        {
            $result = $detail->jsonSerialize();
            if( $remco = $this->_getRemittanceCompany() )
            {
                //get services
                $remcoUserServ = RemittanceCompanyUserServiceFactory::build();
                $remcoRecipientServ = RemittanceCompanyRecipientServiceFactory::build();

                //get remco
                $remcoUser = $remcoUserServ->getByCompanyAndUser($remco, $detail->getSender()->getId());
                $remcoRecipient = $remcoRecipientServ->getByCompanyAndRecipient($remco, $detail->getRecipient()->getId());
                
                $result['remco_user'] = $remcoUser->getSelectedField(array('id','customerID','user_status'));
                $result['remco_recipient'] = $remcoRecipient->getSelectedField(array('id','recipient_status'));
            }                    

            return $result;
        }
        
        return false;
    }
    
    protected function _getMainAgent()
    {
        $acc_serv = PartnerAccountServiceFactory::build();
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

    protected function _getRemittanceCompany()
    {
        if( $mainAgent = $this->_getMainAgent() )
        {
            $rcServ = RemittanceCompanyServiceFactory::build();
            return $rcServ->getByServiceProviderId($mainAgent->getId());
        }

        $this->setResponseCode(MessageCode::CODE_REMCO_NOT_FOUND);
        return false;
    }

    public function getListRemittanceTransactionWithServiceProvider($limit, $page, $service_provider_id, $start_time = null, $end_time = null, $approval_required = null, $status = null, $remittanceID = null, $remittance_status,$for_international = null, 
                                                                    $accountID = NULL, $is_nff = NULL)
    {      
        if ($remittance_status != NULL) {            
            if( !RemittanceStatus::exists($remittance_status) )
            {
                $this->setResponseCode(MessageCode::CODE_LIST_REMITTANCE_TRANSACTION_FAILED);
                return false;
            }
        }

        $channelFilter = array();
        //get all remittance_configuration_ids with service_provider_id
        $corporate_service_ids = array();
        $serviceCorporateServ = CorporateServServiceFactory::build();
        if( $collectionCorporateService = $serviceCorporateServ->findByServiceProviderIds(array($service_provider_id)) )
        {
            $corporate_service_ids = $collectionCorporateService->getIds();
            $corporate_service_ids = array_unique($corporate_service_ids);
            $serviceRemittanceConfiguration = RemittanceConfigServiceFactory::build();
            if( $collectionRemittanceConfiguration = $serviceRemittanceConfiguration->findByCorporateServiceIds($corporate_service_ids, NULL, NULL, MAX_VALUE, 1) )
            {
                $channelFilter = $collectionRemittanceConfiguration->result->getIds();
            }
        }
        
        //get sender
        $sender_id = NULL;
        if( $accountID )
        {
            $accountServ = AccountServiceFactory::build();
            if( $user = $accountServ->getUser($accountID) )
            {
                $sender_id = $user->getId();
            }
            else
            {
                $this->setResponseCode(MessageCode::CODE_LIST_REMITTANCE_TRANSACTION_FAILED);
                return false;
            }
        }
               
        //rconfigs, recipients, statuses, remittanceID        
        $filters = $this->_constructFilter($channelFilter, array(), array($remittance_status), array($remittanceID), array($status), array($sender_id), $is_nff, $approval_required);
        
        $searchServ = SearchRemittanceRecordServiceFactory::build();
        if( $paginatedResult = $searchServ->findByFilters($filters, IappsDateTime::fromUnix($start_time), IappsDateTime::fromUnix($end_time), $limit, $page) )
        {                        
            $paymentServ = PaymentServiceFactory::build();
            $allCountryCurrency = $paymentServ->getAllCountryCurrency();
            if(!empty($allCountryCurrency))
            {
                foreach ($paginatedResult->getResult() as $res)
                {     
                    $res->setInTransactionIDString($res->getInTransaction()->getTransactionID());
                    $res->setOutTransactionIDString($res->getOutTransaction()->getTransactionID());

                    $res->setInCountryCurrencyCode($res->getInTransaction()->getCountryCurrencyCode());
                    $res->setOutCountryCurrencyCode($res->getOutTransaction()->getCountryCurrencyCode());
                    foreach ($allCountryCurrency as $country)
                    {
                        if ($country->getCode() == $res->getInCountryCurrencyCode())
                        {
                            $res->setFromCountryCode($country->getCountryCode());
                            $res->setFromCurrencyCode($country->getCurrencyCode());
                        }
                        if ($country->getCode() == $res->getOutCountryCurrencyCode())
                        {
                            $res->setToCountryCode($country->getCountryCode());
                            $res->setToCurrencyCode($country->getCurrencyCode());
                        }
                    }
                                        
                    //obsolete this field
                    $res->setPrelimCheckStatus("N/A");
                    
                    $res->setRecipientUserName($res->getRecipient()->getAttributes()->hasAttribute(AttributeCode::FULL_NAME));  
                    $res->setSenderUserName($res->getSender()->getFullName());
                }
            }
                    
            $this->setResponseCode(MessageCode::CODE_LIST_REMITTANCE_TRANSACTION_SUCCESS);
            return $paginatedResult;
                        
        }
        $this->setResponseCode(MessageCode::CODE_LIST_REMITTANCE_TRANSACTION_FAILED);
        return false;
    }

    protected function _constructFilter(array $remittanceConfigIds, array $recipientIds, array $statuses, array $remittanceIDs, array $approval_statuses, array $sender_ids, $is_nff = NULL, $approval_required = NULL)
    {
        $filters = new RemittanceRecordCollection();
                        
        foreach( $remittanceConfigIds AS $remittanceConfigurationId )
        {
            $filter = new RemittanceRecord();
            
            if( !is_null($is_nff) )
                $filter->setIsNFF($is_nff);
            
            if( !is_null($approval_required) )
                $filter->setApprovalRequired ($approval_required);
            
            $filter->getRemittanceConfiguration()->setId($remittanceConfigurationId);
            
            $filters->addData($filter);                        
        }        
                
        $newFilters = new RemittanceRecordCollection();
        foreach( $recipientIds AS $recipient_id)
        {
            foreach( $filters AS $filter )
            {
                //deep clone
                $newfilter = unserialize(serialize($filter));
                $newfilter->getRecipient()->setId($recipient_id);                                    
                
                $newFilters->addData($newfilter);
            }            
        }
        if(count($newFilters)>0)
            $filters = clone($newFilters);
        
        $newFilters = new RemittanceRecordCollection();
        foreach( $statuses AS $status)
        {
            foreach( $filters AS $filter )
            {
                //deep clone
                $newfilter = unserialize(serialize($filter));
                $newfilter->getStatus()->setCode($status);
                
                $newFilters->addData($newfilter);
            }
        }
        if(count($newFilters)>0)
            $filters = clone($newFilters);
        
        $newFilters = new RemittanceRecordCollection();
        foreach( $remittanceIDs AS $remittanceID)
        {
            foreach( $filters AS $filter )
            {
                //deep clone
                $newfilter = unserialize(serialize($filter));
                $newfilter->setRemittanceID($remittanceID);                    
                
                $newFilters->addData($newfilter);
            }            
        }
        if(count($newFilters)>0)
            $filters = clone($newFilters);
        
        $newFilters = new RemittanceRecordCollection();
        foreach( $approval_statuses AS $status)
        {
            foreach( $filters AS $filter )
            {
                //deep clone
                $newfilter = unserialize(serialize($filter));
                $newfilter->setApprovalStatus($status);                    
                
                $newFilters->addData($newfilter);
            }            
        }
        if(count($newFilters)>0)
            $filters = clone($newFilters);
        
        $newFilters = new RemittanceRecordCollection();
        foreach( $sender_ids AS $sender_id)
        {
            foreach( $filters AS $filter )
            {
                //deep clone
                $newfilter = unserialize(serialize($filter));
                $newfilter->getSender()->setId($sender_id);                    
                
                $newFilters->addData($newfilter);
            }            
        }
        if(count($newFilters)>0)
            $filters = clone($newFilters);
        
        return $filters;
    }
}