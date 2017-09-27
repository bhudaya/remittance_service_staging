<?php

use Iapps\Common\Helper\ResponseHeader;
use Iapps\Common\Core\IpAddress;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfigServiceFactory;

class Microservice_exchange_rate extends Base_Controller{

    public function getRateByServiceProviderId()
    {
        if( !$admin_id = $this->_getUserProfileId() )
            return false;

        if( !$this->is_required($this->input->post(), array('service_provider_id','from_country_currency_code','to_country_currency_code')) )
            return false;

        $service_provider_id = $this->input->post('service_provider_id');
        $from_country_currency_code = $this->input->get_post('from_country_currency_code');
        $to_country_currency_code = $this->input->get_post('to_country_currency_code');

        $this->_serv = RemittanceConfigServiceFactory::build(2);
        $this->_serv->setIpAddress(IpAddress::fromString($this->_getIpAddress()));
        $this->_serv->setUpdatedBy($admin_id);
        $this->_serv->setServiceProviderIdFilter(array($service_provider_id));

        if( $result = $this->_serv->getActiveChannelV2($from_country_currency_code, $to_country_currency_code) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
}