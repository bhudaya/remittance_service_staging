<?php

use Iapps\RemittanceService\RemittanceConfig\AgentRemittanceConfigServiceFactory;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\Common\Core\IpAddress;

class Remittance_config_agent extends Agent_Base_Controller{

    function __construct()
    {
        parent::__construct();

        $this->_serv = AgentRemittanceConfigServiceFactory::build();
        $this->_serv->setIpAddress(IpAddress::fromString($this->_getIpAddress()));
    }

    public function getRemittanceChannelList()
    {
        if( !$agentId = $this->_getUserProfileId() )
            return false;

        $from_country = $this->input->get('from_country_code') ? $this->input->get('from_country_code') : NULL;

        $this->_serv->setUpdatedBy($agentId);
        if( $result = $this->_serv->getActiveChannel($from_country) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getCorpServicePaymentModeAndFeeByRemittanceConfigId()
    {
        if( !$agentId = $this->_getUserProfileId() )
            return false;

        if (!$this->is_required($this->input->get(), array('id'))) {
            return false;
        }

        $remittance_config_id = $this->input->get("id");

        if ($info = $this->_serv->getCorpServicePaymentModeAndFeeByRemittanceConfigId($remittance_config_id, false)) {

            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $info));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

}