<?php

use Iapps\RemittanceService\RemittanceConfig\RemittanceConfigRepository;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfigService;
use Iapps\Common\Helper\ResponseHeader;

class Remittance_config_user extends User_Base_Controller{

    function __construct()
    {
        parent::__construct();

        $this->load->model('remittanceconfig/Remittance_config_model');
        $repo = new RemittanceConfigRepository($this->Remittance_config_model);
        $this->_remittance_config_service = new RemittanceConfigService($repo);

        $this->_service_audit_log->setTableName('iafb_remittance.remittance_configuration');
    }

    public function getRemittanceChannelList()
    {
        if( !$user = $this->_getUserProfileId() )
            return false;

        $from_country = $this->input->get('from_country_code') ? $this->input->get('from_country_code') : NULL;

        $this->_remittance_config_service->setUpdatedBy($user);
        if( $result = $this->_remittance_config_service->getActiveChannel($from_country) )
        {
            $this->_respondWithSuccessCode($this->_remittance_config_service->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_remittance_config_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getCorpServicePaymentModeAndFeeByRemittanceConfigId()
    {
        if( !$user_id = $this->_getUserProfileId() )
        {
            return false;
        }

        if (!$this->is_required($this->input->get(), array('id'))) {
            return false;
        }

        $remittance_config_id = $this->input->get("id");

        if ($info = $this->_remittance_config_service->getCorpServicePaymentModeAndFeeByRemittanceConfigId($remittance_config_id, true)) {

            $this->_respondWithSuccessCode($this->_remittance_config_service->getResponseCode(), array('result' => $info));
            return true;
        }

        $this->_respondWithCode($this->_remittance_config_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
}