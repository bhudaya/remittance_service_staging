<?php

use Iapps\RemittanceService\RemittanceCompany\RemittanceCompanyServiceFactory;
use Iapps\Common\Core\IpAddress;
use Iapps\Common\Helper\RequestHeader;
use Iapps\Common\Helper\ResponseHeader;

class Microservice_Remittance_company extends Base_Controller{

    function __construct()
    {
        parent::__construct();

        $this->_serv = RemittanceCompanyServiceFactory::build();
        $this->_serv->setIpAddress(IpAddress::fromString($this->_getIpAddress()));
    }

    public function getByCompanycode()
    {
        if( !$user_id = $this->_getUserProfileId() )
            return false;

        $this->_serv->setUpdatedBy($user_id);

        if( !$this->is_required($this->input->get(), array('company_code')))
            return false;

        $company_code = $this->input->get('company_code');

        if( $result = $this->_serv->getByCompanyCode($company_code) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
}