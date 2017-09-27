<?php

use Iapps\RemittanceService\Common\FunctionCode;
use Iapps\Common\Microservice\AccountService\AccessType;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\RemittanceService\Attribute\AttributeCode;
use Iapps\RemittanceService\SearchRemcoRecipient\SearchRemcoRecipientServiceFactory;
use Iapps\Common\Core\IpAddress;

class Remittance_company_recipient_partner extends Partner_Base_Controller{
    
    function __construct()
    {
        parent::__construct();

        $this->_serv = SearchRemcoRecipientServiceFactory::build();        
        $this->_serv->setIpAddress(IpAddress::fromString($this->_getIpAddress()));
    }
    
    public function getList()
    {
        // access control
        if( !$adminId = $this->_getUserProfileId(FunctionCode::PARTNER_GET_USER, AccessType::READ) )
            return false;
        
        if( !$mainAgent = $this->_getMainAgent() )
            return false;
        
        $page = $this->_getPage();
        $limit = $this->_getLimit();
        
        $recipient_statuses = $this->input->get('recipient_status') ? explode('|', $this->input->get('recipient_status')) : array();
        $country_code = $this->input->get('country_code') ? $this->input->get('country_code') : NULL;
        $full_name = $this->input->get('full_name') ? $this->input->get('full_name') : NULL;
        $mobile_no = $this->input->get('mobile_no') ? explode('|', $this->input->get('mobile_no')) : array();                
        $accountID = $this->input->get('accountID') ? $this->input->get('accountID') : NULL;
                        
        $this->_serv->setUpdatedBy($adminId);
        $this->_serv->setServiceProviderId($mainAgent->getId());
        $this->_serv->setStatus($recipient_statuses);
        $this->_serv->setMobileNumber($mobile_no);
        $this->_serv->setAccountID($accountID);
        
        //full name filter
        if( !is_null($full_name) )
            $this->_serv->addAttributeFilter(AttributeCode::FULL_NAME, $full_name);
                
        //country code filter
        if( !is_null($country_code) )
            $this->_serv->addAttributeFilter(AttributeCode::RESIDENTIAL_COUNTRY, $country_code);
                
        if( $result = $this->_serv->search($page, $limit) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result->getResult(), 'total' => $result->getTotal()));
            return true;
        }
        
        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }        
}
