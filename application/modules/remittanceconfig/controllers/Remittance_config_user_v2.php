<?php

use Iapps\RemittanceService\RemittanceConfig\RemittanceConfigServiceFactory;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\Common\Core\IpAddress;

class Remittance_config_user_v2 extends User_Base_Controller{

    public function getRemittanceChannelList()
    {
        if( !$user = $this->_getUserProfileId() )
            return false;

        if (!$this->is_required($this->input->get(), array('from_country_currency_code', 'to_country_currency_code'))
            AND !$this->is_required($this->input->post(), array('from_country_currency_code', 'to_country_currency_code')))
            return false;

        $from_country_currency_code = $this->input->get_post('from_country_currency_code');
        $to_country_currency_code = $this->input->get_post('to_country_currency_code');

        $service_provider_ids = $this->input->get_post('service_provider_id') ? json_decode($this->input->get_post('service_provider_id'), true) : NULL;
        $delivery_times = $this->input->get_post('delivery_time') ? json_decode($this->input->get_post('delivery_time'), true) : NULL;
        $payment_group_codes = $this->input->get_post('payment_group_code') ? json_decode($this->input->get_post('payment_group_code'), true) : NULL;
        $payment_attributes = $this->input->get_post('payment_attribute') ? json_decode($this->input->get_post('payment_attribute'), true) : NULL;

        $this->_serv = RemittanceConfigServiceFactory::build(2);    //build v2 service
        $this->_serv->setIpAddress(IpAddress::fromString($this->_getIpAddress()));
        $this->_serv->setUpdatedBy($user);
        if( $service_provider_ids )
            $this->_serv->setServiceProviderIdFilter($service_provider_ids);
        if( $delivery_times )
            $this->_serv->setDeliveryTimeFilter($delivery_times);
        if( $payment_group_codes )
            $this->_serv->setCollectionModeGroupFilter($payment_group_codes);
        if( $payment_attributes )
            $this->_serv->setCollectionModeAttributeFilter($payment_attributes);

        if( $result = $this->_serv->getActiveChannelV2($from_country_currency_code, $to_country_currency_code) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
}
