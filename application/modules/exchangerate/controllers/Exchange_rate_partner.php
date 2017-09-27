<?php

use Iapps\RemittanceService\ExchangeRate\ExchangeRate;
use Iapps\RemittanceService\ExchangeRate\PartnerExchangeRateService;
use Iapps\RemittanceService\ExchangeRate\ExchangeRateRepository;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\Common\Helper\InputValidator;
use Iapps\RemittanceService\Common\FunctionCode;
use Iapps\Common\Microservice\AccountService\AccessType;
use Iapps\Common\Core\IappsDateTime;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfig;

class Exchange_rate_partner extends Partner_Base_Controller{

    function __construct()
    {
        parent::__construct();

        $this->load->model('exchangerate/exchange_rate_model');
        $repo = new ExchangeRateRepository($this->exchange_rate_model);
        $this->_serv = new PartnerExchangeRateService($repo, $this->_getIpAddress());

        $this->_service_audit_log->setTableName('iafb_remittance.exchange_rate');
    }

    public function addRate()
    {
        if( !$admin_id =  $this->_getUserProfileId(FunctionCode::PARTNER_ADD_RATE, AccessType::WRITE) )
            return false;

        if( !$this->is_required($this->input->post(), array('remittance_config_id', 'rate')) )
            return false;

        $remittance_config_id = $this->input->post('remittance_config_id');
        $rates = $this->input->post('rate');

        if( $rates = json_decode($rates, true) )
        {
            $rateObj = new ExchangeRate();
            if( isset($rates[0]['corporate_service_id']) )
                $rateObj->setCorporateServiceId($rates[0]['corporate_service_id']);

            if( isset($rates[0]['exchange_rate']) )
                $rateObj->setExchangeRate($rates[0]['exchange_rate']);

            if( isset($rates[0]['margin']) )
                $rateObj->setMargin($rates[0]['margin']);
        }
        else
        {
            $errMsg = InputValidator::getInvalidParamMessage('rates');
            $this->_response(InputValidator::constructInvalidParamResponse($errMsg));
            return false;
        }

        $this->_serv->setUpdatedBy($admin_id);
        $this->_serv->setChannelCode($this->_getChannel());
        if( $this->_serv->addExchangeRate($remittance_config_id, $rateObj) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode());
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getRateListing()
    {
        if( !$admin_id =  $this->_getUserProfileId(FunctionCode::PARTNER_LIST_RATES, AccessType::READ) )
            return false;

        if( !$this->is_required($this->input->get(), array('remittance_config_id')) )
            return false;

        $statuses = array();
        if( $status = $this->input->get('status') )
            $statuses = explode('|', $status);

        $remittance_config_id = $this->input->get('remittance_config_id');

        $this->_serv->setUpdatedBy($admin_id);
        $this->_serv->setChannelCode($this->_getChannel());
        if( $result = $this->_serv->getRateListing($remittance_config_id, $statuses, $this->_getLimit(), $this->_getPage()) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result->result, 'total' => $result->total));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getEditableRate()
    {
        if( !$admin_id =  $this->_getUserProfileId(FunctionCode::PARTNER_LIST_RATES, AccessType::READ) )
            return false;

        if( !$this->is_required($this->input->get(), array('remittance_config_id')) )
            return false;

        $remittance_config_id = $this->input->get('remittance_config_id');

        $this->_serv->setUpdatedBy($admin_id);
        $this->_serv->setChannelCode($this->_getChannel());
        if( $result = $this->_serv->getEditableRates($remittance_config_id) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getPendingRate()
    {
        if( !$admin_id =  $this->_getUserProfileId(FunctionCode::PARTNER_VIEW_PENDING_RATE, AccessType::READ) )
            return false;

        if( !$this->is_required($this->input->get(), array('remittance_config_id')) )
            return false;

        $remittance_config_id = $this->input->get('remittance_config_id');

        $this->_serv->setUpdatedBy($admin_id);
        $this->_serv->setChannelCode($this->_getChannel());
        if( $result = $this->_serv->getPendingApprovalRate($remittance_config_id) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function updateRateStatus()
    {
        if( !$admin_id =  $this->_getUserProfileId(FunctionCode::PARTNER_UPDATE_RATE_STATUS, AccessType::WRITE) )
            return false;

        if( !$this->is_required($this->input->post(), array('remittance_config_id', 'rate_id', 'status')) )
            return false;

        $remittance_config_id = $this->input->post('remittance_config_id');
        $rate_id = $this->input->post('rate_id');
        $status = $this->input->post('status');
        $remark = $this->input->post('remark');

        $this->_serv->setUpdatedBy($admin_id);
        $this->_serv->setChannelCode($this->_getChannel());
        if( $result = $this->_serv->updateExchangeRateStatus($remittance_config_id, $rate_id, $status, $remark) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode());
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getRemittanceConfigListWithRates()
    {
        if( !$admin_id =  $this->_getUserProfileId(FunctionCode::PARTNER_LIST_RATES, AccessType::READ) )
            return false;

        $channel_id = $this->input->get('channel_id') ? $this->input->get('channel_id') : NULL;

        $filter = new RemittanceConfig();
        $filter->setChannelID($channel_id);
        $filter->setStatus(null);

        $this->_serv->setUpdatedBy($admin_id);
        $this->_serv->setChannelCode($this->_getChannel());
        if( $result = $this->_serv->getRemittanceConfigWithRates($filter, $this->_getLimit(), $this->_getPage()) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result->result, 'total' => $result->total));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getRemittanceConfigListWithPendingRates()
    {
        if( !$admin_id =  $this->_getUserProfileId(FunctionCode::PARTNER_LIST_CONFIG_WITH_PENDING_RATES, AccessType::READ) )
            return false;

        $this->_serv->setUpdatedBy($admin_id);
        $this->_serv->setChannelCode($this->_getChannel());
        if( $result = $this->_serv->getRemittanceConfigWithPendingRates($this->_getLimit(), $this->_getPage()) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result->result, 'total' => $result->total));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getTrendData()
    {
        if( !$admin_id =  $this->_getUserProfileId(FunctionCode::PARTNER_VIEW_RATES_TREND, AccessType::READ) )
            return false;

        if( !$this->is_required($this->input->get(), array('remittance_config_id', 'from_datetime', 'to_datetime')) )
            return false;

        $remittance_config_id = $this->input->get('remittance_config_id');
        $from_date = IappsDateTime::fromString($this->input->get('from_datetime'));
        $to_date = IappsDateTime::fromString($this->input->get('to_datetime'));

        if( $from_date->isNull() )
        {
            $errMsg = InputValidator::getInvalidParamMessage('from_datetime');
            $this->_response(InputValidator::constructInvalidParamResponse($errMsg));
            return false;
        }

        if( $to_date->isNull() )
        {
            $errMsg = InputValidator::getInvalidParamMessage('to_datetime');
            $this->_response(InputValidator::constructInvalidParamResponse($errMsg));
            return false;
        }

        $this->_serv->setUpdatedBy($admin_id);
        $this->_serv->setChannelCode($this->_getChannel());
        if( $result = $this->_serv->getTrendData($remittance_config_id, $from_date, $to_date) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
}