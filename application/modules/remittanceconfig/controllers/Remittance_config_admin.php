<?php

use Iapps\RemittanceService\RemittanceConfig\RemittanceConfig;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfigRepository;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfigService;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\Common\Core\IappsDateTime;
use Iapps\RemittanceService\Common\FunctionCode;
use Iapps\Common\Microservice\AccountService\AccessType;
use Iapps\Common\Core\IpAddress;
use Iapps\Common\Helper\InputValidator;

class Remittance_config_admin extends Admin_Base_Controller {

    protected $_remittance_config_service;
    private $user_profile_id = FALSE;

    function __construct() {
        parent::__construct();

        $this->load->model('remittanceconfig/Remittance_config_model');
        $repo = new RemittanceConfigRepository($this->Remittance_config_model);
        $this->_remittance_config_service = new RemittanceConfigService($repo);
        $this->_remittance_config_service->setIpAddress(IpAddress::fromString($this->_getIpAddress()));

        $this->_service_audit_log->setTableName('iafb_remittance.remittance_configuration');
    }

    public function addRemittanceConfig() {
        
        if( !$this->user_profile_id = $this->_getUserProfileId(FunctionCode::ADMIN_ADD_CHANNEL, AccessType::WRITE) )
            return false;
        
        $this->_remittance_config_service->setUpdatedBy($this->user_profile_id);

        if (!$this->is_required($this->input->post(), array(
                    'from_country_currency_code',
                    'to_country_currency_code',
                    'from_country_partner_id',
                    'to_country_partner_id',
                    'rates_setter',
                    'min_limit',
                    'max_limit'
                ),false)
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
        if(!empty($min_limit))
            $min_limit = str_replace (',', '', $min_limit);
        if(!empty($max_limit))
            $max_limit = str_replace (',', '', $max_limit);

        $require_face_to_face_trans = $this->input->post('require_face_to_face_trans') ? $this->input->post('require_face_to_face_trans') : TRUE;
        $require_face_to_face_recipient = $this->input->post('require_face_to_face_recipient') ? $this->input->post('require_face_to_face_recipient') : TRUE;
        $home_collection_enabled = $this->input->post('home_collection_enabled') ? $this->input->post('home_collection_enabled') : FALSE;
        $cashin_expiry_period = $this->input->post('cashin_expiry_period') ? $this->input->post('cashin_expiry_period') : 10080;

        $object = new RemittanceConfig();

        $object->setFromCountryCurrencyCode($from_country_currency_code);
        $object->setToCountryCurrencyCode($to_country_currency_code);
        $object->setFromCountryPartnerID($from_country_partner_id);
        $object->setToCountryPartnerID($to_country_partner_id);
        $object->setIntermediaryCurrency($intermediary_currency);
        $object->setRatesSetter($rates_setter);
        $object->setMaxLimit($max_limit);
        $object->setMinLimit($min_limit);
        $object->setRequireFaceToFaceTrans($this->convertStringToBooleanInt($require_face_to_face_trans));
        $object->setRequireFaceToFaceRecipient($this->convertStringToBooleanInt($require_face_to_face_recipient));
        $object->setHomeCollectionEnabled($this->convertStringToBooleanInt($home_collection_enabled));
        $object->setCashinExpiryPeriod($cashin_expiry_period);
        $object->setCreatedBy($this->user_profile_id);
        $object->setCreatedAt(IappsDateTime::now());

        if ($object = $this->_remittance_config_service->addRemittanceConfig($object)) {
            $this->_respondWithSuccessCode($this->_remittance_config_service->getResponseCode(), array('result' => $object));
            return true;
        }

        $this->_respondWithCode($this->_remittance_config_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getRemittanceConfigInfo() {

        if( !$this->user_profile_id = $this->_getUserProfileId(FunctionCode::ADMIN_LIST_CHANNEL, AccessType::READ) )
            return false;

        if (!$this->is_required($this->input->get(), array('id'))) {
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
        
        if( !$this->user_profile_id = $this->_getUserProfileId(FunctionCode::ADMIN_LIST_CHANNEL, AccessType::READ) )
            return false;

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

        if( !$this->user_profile_id = $this->_getUserProfileId(FunctionCode::ADMIN_LIST_CHANNEL, AccessType::READ) )
            return false;

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

    public function editRemittanceConfigOption()
    {
        if( !$admin_id = $this->_getUserProfileId(FunctionCode::ADMIN_UPDATE_CHANNEL, AccessType::WRITE) )
            return false;

        $this->_remittance_config_service->setUpdatedBy($admin_id);
        if (!$this->is_required($this->input->post(), array('remittance_configuration_id'))) {
            return false;
        }

        $remittance_config_id = $this->input->post('remittance_configuration_id');
        $home_collection_enabled = $this->input->post('home_collection_enabled') ? $this->convertStringToBooleanInt($this->input->post('home_collection_enabled')) : NULL;
        $cashin_expiry_period = ($this->input->post('cashin_expiry_period') !== NULL) ? $this->input->post('cashin_expiry_period') : NULL;

        //input validation
        if( is_null($home_collection_enabled) AND is_null($cashin_expiry_period) )
        {//must be at least one given
            $this->_response(InputValidator::constructInvalidParamResponse('At least one option to be given'));
            return false;
        }

        if( $cashin_expiry_period )
        {
            if( !is_numeric($cashin_expiry_period) OR $cashin_expiry_period < 0 )
            {//cash in period error
                $this->_response(InputValidator::constructInvalidParamResponse('Invalid cashin_expiry_period'));
                return false;
            }
        }

        $remittanceConfig = new RemittanceConfig();
        $remittanceConfig->setId($remittance_config_id);        
        $remittanceConfig->setHomeCollectionEnabled($home_collection_enabled);
        $remittanceConfig->setCashinExpiryPeriod($cashin_expiry_period);

        //edit option
        if( $this->_remittance_config_service->editRemittanceConfigOption($remittanceConfig) )
        {
            $this->_respondWithSuccessCode($this->_remittance_config_service->getResponseCode());
            return true;
        }

        $this->_respondWithCode($this->_remittance_config_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function updateRemittanceConfigStatus() {

        if( !$this->user_profile_id = $this->_getUserProfileId(FunctionCode::ADMIN_UPDATE_CHANNEL_STATUS, AccessType::WRITE) )
            return false;
        
        $this->_remittance_config_service->setUpdatedBy($this->user_profile_id);

        if (!$this->is_required($this->input->post(), array('remittance_configuration_id', 'status'))) {
            return false;
        }

        $remittanceConfigurationId = $this->input->post('remittance_configuration_id');
        $status = $this->input->post('status');
        $remarks = $this->input->post('approve_reject_remark');

        if ($config = $this->_remittance_config_service->updateRemittanceConfigStatus($remittanceConfigurationId, $status, $remarks)) {

            $this->_respondWithSuccessCode($this->_remittance_config_service->getResponseCode(), array('result' => $config));
            return true;
        }

        $this->_respondWithCode($this->_remittance_config_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getExistsRemittanceConfigList()
    {
        if( !$this->user_profile_id = $this->_getUserProfileId(FunctionCode::ADMIN_LIST_CHANNEL, AccessType::READ) )
            return false;
        
        $limit = $this->_getLimit();
        $page = $this->_getPage();

        $remittance_config_id = $this->input->post('remittance_config_id') ? $this->input->post('remittance_config_id') : NULL;
        $from_country_currency_code = $this->input->post('from_country_currency_code') ? $this->input->post('from_country_currency_code') : NULL;
        $from_country_partner_id = $this->input->post('from_country_partner_id') ? $this->input->post('from_country_partner_id') : NULL;
        $to_country_currency_code = $this->input->post('to_country_currency_code') ? $this->input->post('to_country_currency_code') : NULL;
        $to_country_partner_id = $this->input->post('to_country_partner_id') ? $this->input->post('to_country_partner_id') : NULL;
        $status = $this->input->post('status') ? $this->input->post('status') : NULL;
        if(!empty($status))
            $status = array($status);
        else
            $status = NULL;

        if ($object = $this->_remittance_config_service->getExistsRemittanceConfigList($limit, $page, 
                $remittance_config_id,
                $from_country_currency_code,$to_country_currency_code,
                $from_country_partner_id,$to_country_partner_id,
                $status)) {

            $result_array = $object->result != null ? $object->result->toArray() : null;

            $this->_respondWithSuccessCode($this->_remittance_config_service->getResponseCode(), array('result' => $result_array, 'total' => $object->total));
            return true;
        }

        $this->_respondWithCode($this->_remittance_config_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
        
    }
    
    
}
