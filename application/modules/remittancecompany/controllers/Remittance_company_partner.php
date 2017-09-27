<?php

use Iapps\RemittanceService\RemittanceCompany\RemittanceCompanyServiceFactory;
use Iapps\Common\Core\IpAddress;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\RemittanceService\Common\FunctionCode;
use Iapps\Common\Microservice\AccountService\AccessType;
use Iapps\RemittanceService\RemittanceCompany\RemittanceCompany;

class Remittance_company_partner extends Partner_Base_Controller{

    function __construct()
    {
        parent::__construct();

        $this->_serv = RemittanceCompanyServiceFactory::build();
        $this->_serv->setIpAddress(IpAddress::fromString($this->_getIpAddress()));
    }

    public function getCompany()
    {

        if( !$user_id = $this->_getUserProfileId(FunctionCode::PARTNER_GET_COMPANY_INFO, AccessType::READ) )
            return false;

        $this->_serv->setUpdatedBy($user_id);

        if( !$service_provider_id = $this->_serv->getServiceProviderId() )
        {
            $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
            return false;
        }

        if( $result = $this->_serv->getByServiceProviderId($service_provider_id) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function editCompany()
    {
        
        if( !$user_id = $this->_getUserProfileId(FunctionCode::PARTNER_EDIT_COMPANY_INFO, AccessType::WRITE) )
            return false;

        if( !$this->is_required($this->input->post(), array('receipt_format')) )
            return false;

        $receipt_format = $this->input->post('receipt_format');

        $this->_serv->setUpdatedBy($user_id);

        if( !$service_provider_id = $this->_serv->getServiceProviderId() )
        {
            $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
            return false;
        }

        $entity = new RemittanceCompany();
        $entity->setServiceProviderId($service_provider_id);
        $entity->setReceiptFormat($receipt_format);

        if( $result = $this->_serv->editByServiceProviderId($entity) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
}