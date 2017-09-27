<?php

use Iapps\RemittanceService\RemittanceCompany\RemittanceCompanyServiceFactory;
use Iapps\Common\Core\IpAddress;
use Iapps\Common\Helper\RequestHeader;
use Iapps\Common\Helper\ResponseHeader;

class Remittance_company_system extends System_Base_Controller{

    function __construct()
    {
        parent::__construct();

        $this->_serv = RemittanceCompanyServiceFactory::build();
        $this->_serv->setIpAddress(IpAddress::fromString($this->_getIpAddress()));
    }

    public function get()
    {
        if( !$system_id = $this->_getUserProfileId() )
            return false;

        RequestHeader::set(ResponseHeader::FIELD_X_AUTHORIZATION, $this->clientToken);

        $this->_serv->setUpdatedBy($system_id);

        if( !$this->is_required($this->input->get(), array('service_provider_id')))
            return false;

        $service_provider_id = $this->input->get('service_provider_id');

        $this->_serv->setUpdatedBy($system_id);
        if( $result = $this->_serv->getByServiceProviderId($service_provider_id) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
}