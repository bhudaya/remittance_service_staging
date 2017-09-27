<?php

namespace Iapps\RemittanceService\Reports\NFFReport;

use Iapps\Common\Core\IappsDateTime;
use Iapps\RemittanceService\Common\MessageCode;
use Iapps\RemittanceService\Reports\ReportBaseService;
use Iapps\RemittanceService\RemittanceCompany\RemittanceCompanyServiceFactory;
use Iapps\RemittanceService\RemittanceCompanyUser\RemittanceCompanyUserServiceFactory;
use Iapps\RemittanceService\RemittanceCompanyUser\RemittanceCompanyUserCollection;
use Iapps\RemittanceService\RemittanceRecord\RemittanceRecord;
use Iapps\RemittanceService\RemittanceRecord\RemittanceRecordCollection;
use Iapps\RemittanceService\SearchRemittanceRecord\SearchRemittanceRecordServiceFactory;
use Iapps\RemittanceService\RemittanceRecord\RemittanceStatus;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfigServiceFactory;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfigCollection;
use Iapps\RemittanceService\RemittanceRecord\RemittanceApprovalStatus;
use Iapps\RemittanceService\Attribute\AttributeCode;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransaction;
use Iapps\RemittanceService\Common\Logger;

//requires RemittanceRecordRepository
class NFFReportService extends ReportBaseService{
    
    public function getData()
    {
        Logger::debug('NFF Report: Started');
        $filters = new RemittanceRecordCollection();        
        
        $startTime = $this->getOption('start_time');
        $endTime = $this->getOption('end_time');
        
        if( !($startTime instanceof IappsDateTime OR 
            !($endTime instanceof IappsDateTime) OR 
            $startTime->getUnix() >= $endTime->getUnix()) )
        {
            $this->setResponseCode(MessageCode::CODE_GET_DATA_FAILED);
            return false;
        }
                  
        //get customer if given
        if($customerID = $this->getOption('customerID') )
        {
            $remcoUserServ = RemittanceCompanyUserServiceFactory::build();
            if( !$remcoCustomer = $remcoUserServ->getByCustomerID($customerID) )
            {
                $this->setResponseCode(MessageCode::CODE_GET_DATA_FAILED);
                return false;                
            }
        }
        
        $rConfigList = $this->_getRemittanceConfigurations();
                
        //construct filters
        foreach($this->_getStatuses() AS $status)
        {
            foreach( $rConfigList->getIds() AS $rConfigId )
            {
                $filter = new RemittanceRecord();
                $filter->getStatus()->setCode($status);
                $filter->setIsNFF(1);
                $filter->getRemittanceConfiguration()->setId($rConfigId);
                if( isset($remcoCustomer) )
                    $filter->setSender ($remcoCustomer->getUser());
                                
                $filters->addData($filter);                    
            }
            
        }
        
        if( count($filter) <= 0 )
        {//must be at least one filter
            $this->setResponseCode(MessageCode::CODE_GET_DATA_FAILED);
            return false;
        }
        
        //get data
        Logger::debug('NFF Report: searching data...');
        $searchRemittanceServ = SearchRemittanceRecordServiceFactory::build();
        if( $datas = $searchRemittanceServ->findByFilters($filters, $startTime, $endTime) )
        {            
            //parse the data into report format
            $result = array();
            
            //get remco users
            $user_profile_ids = $datas->getResult()->getFieldValues('sender_user_profile_id');
            $remco_users = $this->_getRemittanceCompanyUsers($user_profile_ids);                    
                       
            foreach($datas->getResult() AS $data)
            {
                $from_country = NULL;
                $from_currency = NULL;
                $to_country = NULL;
                $to_currency = NULL;
                if($rConfig = $rConfigList->getById($data->getRemittanceConfiguration()->getId()) )
                {
                    $from_country = substr($rConfig->getRemittanceService()->getFromCountryCurrencyCode(),0,2);
                    $from_currency = substr($rConfig->getRemittanceService()->getFromCountryCurrencyCode(),3,3);
                    $to_country = substr($rConfig->getRemittanceService()->getToCountryCurrencyCode(),0,2);
                    $to_currency = substr($rConfig->getRemittanceService()->getToCountryCurrencyCode(),3,3);
                }
                
                
                
                $temp = $this->_getEmptyHolder();
                $temp['Sending Country'] = $from_country;
                $temp['Destination Country'] = $to_country;
                $temp['Date'] = !$data->getCreatedAt()->isNull() ? $data->getCreatedAt()->addHour(8)->getString() : NULL;
                $temp['Receipt No.'] = $data->getRemittanceID();
                $temp['Account ID'] = $data->getSender()->getAccountID();
                $temp['Sender Full Name'] = $data->getSender()->getFullName();
                
                if( $remco_users instanceof RemittanceCompanyUserCollection AND
                    ($remco_user = $remco_users->getByUserProfileId($data->getSenderUserProfileId())) )
                {
                    $temp['Remittance Customer ID'] = $remco_user->getCustomerID();
                }
                
                $temp['Recipient Full Name'] = $data->getRecipient()->getAttributes()->hasAttribute(AttributeCode::FULL_NAME);
                $temp['Sending Currency'] =  $from_currency;
                $temp['Sending Amount'] = $data->getFromAmount();
                $temp['Exchange Rate'] = $data->getDisplayRate();
                $temp['Destination Currency'] = $to_currency;
                $temp['Destination Amount'] = $data->getToAmount();
                $temp['Remittance Status'] = $data->getStatus()->getDisplayName();
                                
                if( $data->getApprovalRequired() == 1 AND 
                    $data->getApprovalStatus() == RemittanceApprovalStatus::APPROVED AND
                    !$data->getApprovedRejectedAt()->isNull())
                {
                    $temp['Approved On'] = $data->getApprovedRejectedAt()->addHour(8)->getString();
                    $temp['Approved By'] = $data->getApprovedRejectedByUser()->getName() . ', ' .  $data->getApprovedRejectedByUser()->getAccountID();
                }
                
                if( $data->getInTransaction() instanceof RemittanceTransaction )
                {
                    $temp['Description'] = $data->getInTransaction()->getDescription();
                    $temp['Payment Mode'] = $data->getInTransaction()->getConfirmPaymentCode();                
                    $temp['Payment Fee'] = $data->getInTransaction()->getItems()->getPaymentFeeItem()->getNetAmount();
                    $temp['Service Fee'] = $data->getInTransaction()->getItems()->getServiceFeeItem()->getNetAmount();
                    $temp['Total Payable'] = $data->getInTransaction()->getItems()->getTotalAmount();
                }
                
                if( $data->getOutTransaction() instanceof RemittanceTransaction )
                {
                    $temp['Collection Mode'] = $data->getOutTransaction()->getConfirmPaymentCode();
                }
                                
                $result[] = $temp;
            }  
            
            Logger::debug('NFF Report: data count ' . count($result));
            $this->setResponseCode(MessageCode::CODE_GET_DATA_SUCCESS);
            return $result;
        }
        
        Logger::debug('NFF Report: No data found');
        $this->setResponseCode(MessageCode::CODE_GET_DATA_FAILED);
        return false;
    } 
        
    protected function _getStatuses()
    {
        if(!$statuses = $this->getOption('statuses') )
        {
            $statuses = array(
                RemittanceStatus::PROCESSING,
                RemittanceStatus::REJECTED,
                RemittanceStatus::DELIVERING,
                RemittanceStatus::COLLECTED,
                RemittanceStatus::FAILED,
                RemittanceStatus::EXPIRED,
            );
        }
        
        return $statuses;
    } 
    
    protected function _getRemittanceConfigurations()
    {
        $service_provider_id = $this->getOption('service_provider_id');
        
        $rConfigServ = RemittanceConfigServiceFactory::build(2);
        if( $rConfigList = $rConfigServ->getExistsRemittanceConfigList(MAX_VALUE, 1, NULL, NULL, NULL, $service_provider_id, NULL) )
        {
            return $rConfigList->getResult();
        }
        
        return new RemittanceConfigCollection();
    }
    
    protected function _getRemittanceCompanyUsers(array $user_profile_ids)
    {
        if( $service_provider_id = $this->getOption('service_provider_id') )
        {
            $remcoUserServ = RemittanceCompanyUserServiceFactory::build();
            $remcoServ = RemittanceCompanyServiceFactory::build();
        
            if( $remco = $remcoServ->getByServiceProviderId($service_provider_id) AND
                $info = $remcoUserServ->getByCompanyAndUsers($remco, $user_profile_ids) )
            {
                return $info;
            }
        }
        
        return false;
    }


    protected function _getEmptyHolder()
    {
        $temp = array();
    
        $temp['Sending Country'] = NULL;
        $temp['Destination Country'] = NULL;
        $temp['Date'] = NULL;
        $temp['Receipt No.'] = NULL;
        $temp['Account ID'] = NULL;
        $temp['Sender Full Name'] = NULL;
        $temp['Remittance Customer ID'] = NULL;
        $temp['Recipient Full Name'] = NULL;
        $temp['Sending Currency'] = NULL;
        $temp['Sending Amount'] = NULL;
        $temp['Exchange Rate'] = NULL;
        $temp['Destination Currency'] = NULL;
        $temp['Destination Amount'] = NULL;
        $temp['Description'] = NULL;
        $temp['Collection Mode'] = NULL;
        $temp['Payment Mode'] = NULL;
        $temp['Payment Fee'] = NULL;
        $temp['Service Fee'] = NULL;
        $temp['Total Payable'] = NULL;
        $temp['Remittance Status'] = NULL;
        $temp['Approved On'] = NULL;
        $temp['Approved By'] = NULL;
        
        return $temp;
    }
}


