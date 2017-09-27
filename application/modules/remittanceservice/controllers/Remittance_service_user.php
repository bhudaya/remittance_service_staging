<?php

use Iapps\RemittanceService\RemittanceServiceConfig\RemittanceServiceConfigServiceFactory;
use Iapps\RemittanceService\RemittanceServiceConfig\RemittanceServiceConfig;
use Iapps\Common\Core\IpAddress;
use Iapps\Common\Helper\ResponseHeader;

class Remittance_service_user extends User_Base_Controller{

    function __construct()
    {
        parent::__construct();

        $this->_serv = RemittanceServiceConfigServiceFactory::build();
        $this->_serv->setIpAddress(IpAddress::fromString($this->_getIpAddress()));
    }

    public function getList()
    {
        if( !$userId = $this->_getUserProfileId() )
            return false;

        $page = 1;
        $limit = MAX_VALUE;

        $filter = new RemittanceServiceConfig();
        if( $this->input->get("from_country_currency_code"))
            $filter->setFromCountryCurrencyCode($this->input->get("from_country_currency_code"));
        if( $this->input->get("to_country_currency_code"))
            $filter->setToCountryCurrencyCode($this->input->get("to_country_currency_code"));

        if( $forInternational = $this->input->get("international") ? $this->input->get("international") : 'true' )
        {
            if( $forInternational == 'false' )
                $forInternational = false;
            else
                $forInternational = true;
        }


        $this->_serv->setUpdatedBy($userId);
        if( $result = $this->_serv->getAllRemittanceServiceConfig($limit, $page, $filter, $forInternational) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result));
            return false;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
}