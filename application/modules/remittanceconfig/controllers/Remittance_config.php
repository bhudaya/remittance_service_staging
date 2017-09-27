<?php

use Iapps\RemittanceService\RemittanceConfig\RemittanceConfig;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfigRepository;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfigService;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\Common\Core\IappsDateTime;

class Remittance_config extends Base_Controller {

    protected $_remittance_config_service;

    function __construct() {
        parent::__construct();

        $this->load->model('remittanceconfig/Remittance_config_model');
        $repo = new RemittanceConfigRepository($this->Remittance_config_model);
        $this->_remittance_config_service = new RemittanceConfigService($repo);

        $this->_service_audit_log->setTableName('iafb_remittance.remittance_configuration');
    }

    public function addRemittanceConfig() {
        $user_profile_id = $this->_getUserProfileId();
        if ($user_profile_id === false) {
            return false;
        }

        if (!$this->is_required($this->input->post(), array(
                    'from_country_currency_code',
                    'to_country_currency_code',
                    'from_country_partner_id',
                    'to_country_partner_id',
                    'rates_setter',
                    'min_limit',
                    'max_limit'
                ))
        ) {
            return false;
        }


        $from_country_currency_code = $this->input->post('from_country_currency_code');
        $to_country_currency_code = $this->input->post('to_country_currency_code');
        $from_country_partner_id = $this->input->post('from_country_partner_id');
        $to_country_partner_id = $this->input->post('to_country_partner_id');
        $intermediary_currency = $this->input->post('intermediary_currency');
        $min_limit = $this->input->post('min_limit');
        $max_limit = $this->input->post('max_limit');
        $rates_setter = $this->input->post('rates_setter');

//        $user_profile_id = 'e3da6335-8907-4324-a3bf-28d9984471fc';

        $object = new RemittanceConfig();

        $object->setFromCountryCurrencyCode($from_country_currency_code);
        $object->setToCountryCurrencyCode($to_country_currency_code);
        $object->setFromCountryPartnerID($from_country_partner_id);
        $object->setToCountryPartnerID($to_country_partner_id);
        $object->setIntermediaryCurrency($intermediary_currency);
        $object->setRatesSetter($rates_setter);
        $object->setMaxLimit($max_limit);
        $object->setMinLimit($min_limit);
        $object->setCreatedBy($user_profile_id);
        $object->setCreatedAt(IappsDateTime::now());

        if ($object = $this->_remittance_config_service->addRemittanceConfig($object)) {
            $this->_respondWithSuccessCode($this->_remittance_config_service->getResponseCode(), array('result' => $object));
            return true;
        }

        $this->_respondWithCode($this->_remittance_config_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getRemittanceConfigInfo() {

        $user_profile_id = $this->_getUserProfileId();
        if ($user_profile_id === false) {
            return false;
        }

        if (!$this->is_required($this->input->get(), array('id'
                ))) {
            return false;
        }

        if ($info = $this->_remittance_config_service->getRemittanceConfigById($this->input->get("id"))) {

            $this->_respondWithSuccessCode($this->_remittance_config_service->getResponseCode(), array('result' => $info));
            return true;
        }

        $this->_respondWithCode($this->_remittance_config_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getAllRemittanceConfig() {
        $user_profile_id = $this->_getUserProfileId();
        if ($user_profile_id === false) {
            return false;
        }


        $limit = $this->_getLimit();
        $page = $this->_getPage();

        if ($object = $this->_remittance_config_service->getAllRemittanceConfig($limit, $page)) {

            $result_array = $object->result != null ? $object->result->toArray() : null;

            $this->_respondWithSuccessCode($this->_remittance_config_service->getResponseCode(), array('result' => $result_array, 'total' => $object->total));
            return true;
        }

        $this->_respondWithCode($this->_remittance_config_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getRemittanceConfigInfoBySearchFilter() {

        $user_profile_id = $this->_getUserProfileId();
        if ($user_profile_id === false) {
            return false;
        }


        $limit = $this->_getLimit();
        $page = $this->_getPage();

        $remittance_service_id = $this->input->get("remittance_service_id");
        $id = $this->input->get("id");
        $status = $this->input->get("status");


        $config = new RemittanceConfig();
        $config->setId($id);
        $config->setRemittanceServiceId($remittance_service_id);
        $config->setStatus($status);
        if ($object = $this->_remittance_config_service->getRemittanceConfigBySearchFilter($config, $limit, $page)) {
            $result_array = $object->result != null ? $object->result->toArray() : null;
            $this->_respondWithSuccessCode($this->_remittance_config_service->getResponseCode(), array('result' => $result_array, 'total' => $object->total));
            return true;
        }

        $this->_respondWithCode($this->_remittance_config_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function updateRemittanceConfigStatus() {

//        $user_profile_id = $this->_getUserProfileId();
//        if ($user_profile_id === false) {
//            return false;
//        }

        if (!$this->is_required($this->input->post(), array('remittance_configuration_id', 'status'))) {
            return false;
        }

        $remittanceConfigurationId = $this->input->post('remittance_configuration_id');
        $status = $this->input->post('status');
        $remarks = $this->input->post('remarks');

        if ($config = $this->_remittance_config_service->updateRemittanceConfigStatus($remittanceConfigurationId, $status, $remarks)) {

            $this->_respondWithSuccessCode($this->_remittance_config_service->getResponseCode(), array('result' => $config));
            return true;
        }

        $this->_respondWithCode($this->_remittance_config_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

}
