<?php defined('BASEPATH') OR exit('No direct script access allowed');

use Iapps\RemittanceService\RemittanceServiceConfig\RemittanceServiceConfigServiceFactory;
use Iapps\RemittanceService\Common\FunctionCode;
use Iapps\Common\Microservice\AccountService\AccessType;
use Iapps\Common\Core\IpAddress;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\Common\Helper\InputValidator;
use Iapps\Common\Core\IappsDateTime;

class Remittance_service_admin extends Admin_Base_Controller {

    protected $_remittance_service_config_service;
    function __construct()
    {
        parent::__construct();

        $this->_serv = RemittanceServiceConfigServiceFactory::build();
        $this->_serv->setIpAddress(IpAddress::fromString($this->_getIpAddress()));
        
        $this->_service_audit_log->setTableName('iafb_remittance.remittance_service_config');
    }

    public function getAllRemittanceServiceConfig()
    {
        if( !$adminId = $this->_getUserProfileId(FunctionCode::ADMIN_LIST_CHANNEL, AccessType::READ) )
            return false;

        $limit = $this->_getLimit();
        $page = $this->_getPage();

        $this->_serv->setUpdatedBy($adminId);
        if( $result = $this->_serv->getAllRemittanceServiceConfig($limit, $page) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result->getResult(), 'total' => $result->getTotal()));
            return false;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getAllRemittanceServiceConfigByFromCountryCode()
    {
        if( !$adminId = $this->_getUserProfileId(FunctionCode::ADMIN_LIST_CHANNEL, AccessType::READ) )
            return false;

        if( !$this->is_required($this->input->get(), array('from_country_code')) )
            return false;

        $from_country_code = $this->input->get("from_country_code");

        $this->_serv->setUpdatedBy($adminId);
        if( $result_array = $this->_serv->getAllRemittanceServiceConfigByFromCountryCode($from_country_code) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result_array, 'total' => count($result_array)));
            return false;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getRemittanceServiceConfigBySearchFilter()
    {
        if( !$adminId = $this->_getUserProfileId(FunctionCode::ADMIN_LIST_CHANNEL, AccessType::READ) )
            return false;

        if( !$this->is_required($this->input->get(), array('from_country_currency_code', 'to_country_currency_code')) )
            return false;

        $from_country_currency_code = $this->input->get("from_country_currency_code");
        $to_country_currency_code = $this->input->get("to_country_currency_code");

        $this->_serv->setUpdatedBy($adminId);
        if( $info = $this->_serv->getRemittanceServiceConfigInfoByFromAndTo($from_country_currency_code, $to_country_currency_code) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $info));
            return false;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getRemittanceServiceConfigInfo()
    {
        if( !$adminId = $this->_getUserProfileId(FunctionCode::ADMIN_LIST_CHANNEL, AccessType::READ) )
            return false;

        if( !$this->is_required($this->input->get(), array('id')) )
            return false;

        $id = $this->input->get("id");

        $this->_serv->setUpdatedBy($adminId);
        if( $info = $this->_serv->getRemittanceServiceConfigInfo($id) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $info));
            return false;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function addRemittanceServiceConfig()
    {
        if( !$adminId = $this->_getUserProfileId(FunctionCode::ADMIN_ADD_CHANNEL, AccessType::WRITE) )
            return false;

        if( !$this->is_required($this->input->post(), array('from_country_currency_code', 'to_country_currency_code')) )
            return false;

        $from_country_currency_code = $this->input->post("from_country_currency_code");
        $to_country_currency_code = $this->input->post("to_country_currency_code");

        $config = new \Iapps\RemittanceService\RemittanceServiceConfig\RemittanceServiceConfig();
        $config->setFromCountryCurrencyCode($from_country_currency_code);
        $config->setToCountryCurrencyCode($to_country_currency_code);

        $this->_serv->setUpdatedBy($adminId);
        if( $config = $this->_serv->addRemittanceServiceConfig($config) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $config));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function editRemittanceServiceConfig()
    {
        $this->_returnObsoleteFunction();
        return false;
    }

    public function editRemittanceServiceConfigRates()
    {
        $this->_returnObsoleteFunction();
        return false;
    }
}