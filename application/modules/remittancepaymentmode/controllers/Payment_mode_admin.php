<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeService;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeRepository;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentMode;
use Iapps\Common\Helper\GuidGenerator;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeFeeGroup;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeFee;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeFeeCollection;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeCostGroup;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeCost;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeCostCollection;
use Iapps\RemittanceService\Common\SystemCodeServiceFactory;
use Iapps\RemittanceService\Common\FeeType;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeFeeGroupType;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeFeeGroupStatus;
use Iapps\RemittanceService\Common\MessageCode;

use Iapps\RemittanceService\RemittanceConfig\RemittanceConfigServiceFactory;
use Iapps\RemittanceService\Common\CorporateServServiceFactory;
use Iapps\RemittanceService\Common\FunctionCode;
use Iapps\Common\Microservice\AccountService\AccessType;

/**
 * Description of Payment_mode_fee
 *
 * @author lichao
 */
class Payment_mode_admin extends Admin_Base_Controller {
    //put your code here
    
    private $isLogin = false;
    private $loginUserProfileId = null;
    private $_service_payment_mode;
    
    function __construct() {
        parent::__construct();
        
        $this->load->model('remittancepaymentmode/Payment_mode_model');
        $repo = new PaymentModeRepository($this->Payment_mode_model);
        $this->_service_payment_mode = new PaymentModeService($repo);
        
        $this->_service_audit_log->setTableName('iafb_remittance.corporate_service_payment_mode');
    }
    
    public function getCollectionModeList()
    {
        if( !$this->loginUserProfileId = $this->_getUserProfileId(FunctionCode::ADMIN_LIST_COLLECTION_MODE, AccessType::READ) )
            return false;
        $this->_service_payment_mode->setUpdatedBy($this->loginUserProfileId);
        
        if( !$this->is_required($this->input->post(), array('remittance_configuration_id')))
        {
            return false;
        }
        $remittanceConfigurationId = $this->input->post('remittance_configuration_id');
        
        $limit = $this->_getLimit();
        $page = $this->_getPage();
        
        if( $result = $this->_service_payment_mode->getCollectionModeListing($limit, $page, $remittanceConfigurationId) )
        {
            $this->_respondWithSuccessCode($this->_service_payment_mode->getResponseCode(), array('result' => $result->result, 'total' => $result->total));
            return true;
        }
        $this->_respondWithCode($this->_service_payment_mode->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    
    public function addCollectionMode()
    {
        if( !$this->loginUserProfileId = $this->_getUserProfileId(FunctionCode::ADMIN_ADD_COLLECTION_MODE, AccessType::WRITE) )
            return false;
        $this->_service_payment_mode->setUpdatedBy($this->loginUserProfileId);
        
        if( !$this->is_required($this->input->post(), array('remittance_configuration_id','payment_code','is_default')))
        {
            return false;
        }
        
        $corporate_service_id = $this->input->post('remittance_configuration_id');
        $payment_code = $this->input->post('payment_code');
        $is_default = ($this->input->post("is_default") === "true") ? 1 : 0;
        
        if( $object = $this->_service_payment_mode->addCollectionMode($corporate_service_id, $payment_code, $is_default) )
        {
            $this->_respondWithSuccessCode($this->_service_payment_mode->getResponseCode(), array("result" => $object));
            return true;
        }
        $this->_respondWithCode($this->_service_payment_mode->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    
    public function getPaymentModeList()
    {
        if( !$this->loginUserProfileId = $this->_getUserProfileId(FunctionCode::ADMIN_LIST_PAYMENT_MODE, AccessType::READ) )
            return false;
        $this->_service_payment_mode->setUpdatedBy($this->loginUserProfileId);
        
        if( !$this->is_required($this->input->post(), array('remittance_configuration_id')))
        {
            return false;
        }
        $remittanceConfigurationId = $this->input->post('remittance_configuration_id');
        
        $limit = $this->_getLimit();
        $page = $this->_getPage();
        
        if( $result = $this->_service_payment_mode->getPaymentModeListing($limit, $page, $remittanceConfigurationId) )
        {
            $this->_respondWithSuccessCode($this->_service_payment_mode->getResponseCode(), array('result' => $result->result, 'total' => $result->total));
            return true;
        }
        $this->_respondWithCode($this->_service_payment_mode->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    
    public function addPaymentMode()
    {
        if( !$this->loginUserProfileId = $this->_getUserProfileId(FunctionCode::ADMIN_ADD_PAYMENT_MODE, AccessType::WRITE) )
            return false;
        $this->_service_payment_mode->setUpdatedBy($this->loginUserProfileId);
        
        if( !$this->is_required($this->input->post(), array('remittance_configuration_id','payment_code','is_default')))
        {
            return false;
        }
        
        $corporate_service_id = $this->input->post('remittance_configuration_id');
        $payment_code = $this->input->post('payment_code');
        $is_default = ($this->input->post("is_default") === "true") ? 1 : 0;
        
        if( $object = $this->_service_payment_mode->addPaymentMode($corporate_service_id, $payment_code, $is_default) )
        {
            $this->_respondWithSuccessCode($this->_service_payment_mode->getResponseCode(), array("result" => $object));
            return true;
        }
        $this->_respondWithCode($this->_service_payment_mode->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    
    public function addCollectionModeFeeGroup()
    {
        if( !$this->loginUserProfileId = $this->_getUserProfileId(FunctionCode::ADMIN_ADD_COLLECTION_MODE_FEE, AccessType::WRITE) )
            return false;
        $this->_service_payment_mode->setUpdatedBy($this->loginUserProfileId);
        
        if( !$this->is_required($this->input->post(), array('remittance_configuration_id','corporate_service_payment_mode_id','fees')))
        {
            return false;
        }
        
        $remittanceConfigurationId = $this->input->post('remittance_configuration_id');
        $corporateServicePaymentModeId = $this->input->post('corporate_service_payment_mode_id');
        $name = $this->input->post('name');
        $fees = $this->input->post('fees');
        $fees = json_decode($fees);
        
        if( $this->_service_payment_mode->addCollectionModeFeeGroup($remittanceConfigurationId, $corporateServicePaymentModeId, $name, $fees) )
        {
            $this->_respondWithSuccessCode($this->_service_payment_mode->getResponseCode());
            return true;
        }
        
        $this->_respondWithCode($this->_service_payment_mode->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    
    public function addPaymentModeFeeGroup()
    {
        if( !$this->loginUserProfileId = $this->_getUserProfileId(FunctionCode::ADMIN_ADD_PAYMENT_MODE_FEE, AccessType::WRITE) )
            return false;
        $this->_service_payment_mode->setUpdatedBy($this->loginUserProfileId);
        
        if( !$this->is_required($this->input->post(), array('remittance_configuration_id','corporate_service_payment_mode_id','fees')))
        {
            return false;
        }
        
        $remittanceConfigurationId = $this->input->post('remittance_configuration_id');
        $corporateServicePaymentModeId = $this->input->post('corporate_service_payment_mode_id');
        $name = $this->input->post('name');
        $fees = $this->input->post('fees');
        $fees = json_decode($fees);
        
        if( $this->_service_payment_mode->addPaymentModeFeeGroup($remittanceConfigurationId, $corporateServicePaymentModeId, $name, $fees) )
        {
            $this->_respondWithSuccessCode($this->_service_payment_mode->getResponseCode());
            return true;
        }
        $this->_respondWithCode($this->_service_payment_mode->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    
    public function getCollectionModeFeeGroupInfo()
    {
        return $this->getPaymentModeFeeGroupInfo();
    }
    
    public function getPaymentModeFeeGroupInfo()
    {
        if( !$this->loginUserProfileId = $this->_getUserProfileId(FunctionCode::ADMIN_LIST_PAYMENT_MODE_FEE_GROUP, AccessType::READ) )
            return false;
        $this->_service_payment_mode->setUpdatedBy($this->loginUserProfileId);
        
        
        if( !$this->is_required($this->input->post(), array('corporate_service_payment_mode_id','status')))
        {
            return false;
        }
        $corporateServicePaymentModeId = $this->input->post('corporate_service_payment_mode_id');
        $status = $this->input->post('status');
        
        if( $result = $this->_service_payment_mode->getPaymentModeFeeGroupInfo($corporateServicePaymentModeId, NULL, $status) )
        {
            $this->_respondWithSuccessCode($this->_service_payment_mode->getResponseCode(), array('result' => $result));
            return true;
        }
        $this->_respondWithCode($this->_service_payment_mode->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    
//    public function getCollectionModeFeeListing()
//    {
//        return $this->getPaymentModeFeeListing();
//    }
//    
//    public function getPaymentModeFeeListing()
//    {
//        if( !$this->loginUserProfileId = $this->_getUserProfileId(FunctionCode::ADMIN_LIST_PAYMENT_MODE_FEE, AccessType::READ) )
//            return false;
//        $this->_service_payment_mode->setUpdatedBy($this->loginUserProfileId);
//        
//        if( !$this->is_required($this->input->post(), array('corporate_service_payment_mode_id','status')))
//        {
//            return false;
//        }
////        $remittanceConfigurationId = $this->input->post('remittance_configuration_id');
//        $corporateServicePaymentModeId = $this->input->post('corporate_service_payment_mode_id');
//        $status = $this->input->post('status') ? $this->input->post('status') : NULL;
//        
//        $limit = $this->_getLimit();
//        $page = $this->_getPage();
//        
//        
//        if( $result = $this->_service_payment_mode->getPaymentModeFeeListing($limit, $page, $corporateServicePaymentModeId, NULL, $status) )
//        {
//            $this->_respondWithSuccessCode($this->_service_payment_mode->getResponseCode(), array('result' => $result->result, 'total' => $result->total));
//            return true;
//        }
//        $this->_respondWithCode($this->_service_payment_mode->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
//        return false;
//    }
    
    
    public function getCollectionModeFeeListingByGroupId()
    {
        return $this->getPaymentModeFeeListingByGroupId();
    }
    
    public function getPaymentModeFeeListingByGroupId()
    {
        if( !$this->loginUserProfileId = $this->_getUserProfileId(FunctionCode::ADMIN_LIST_PAYMENT_MODE_FEE, AccessType::READ) )
            return false;
        $this->_service_payment_mode->setUpdatedBy($this->loginUserProfileId);
        
        if( !$this->is_required($this->input->post(), array('corporate_service_payment_mode_fee_group_id')))
        {
            return false;
        }
        
        $corporateServicePaymentModeFeeGroupId = $this->input->post('corporate_service_payment_mode_fee_group_id');
        
        $limit = $this->_getLimit();
        $page = $this->_getPage();
        
        
        if( $result = $this->_service_payment_mode->getPaymentModeFeeListingByGroupId($limit, $page, $corporateServicePaymentModeFeeGroupId) )
        {
            $this->_respondWithSuccessCode($this->_service_payment_mode->getResponseCode(), array('result' => $result->result, 'total' => $result->total));
            return true;
        }
        $this->_respondWithCode($this->_service_payment_mode->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    
    
    public function addCollectionModeCostGroup()
    {
        if( !$this->loginUserProfileId = $this->_getUserProfileId(FunctionCode::ADMIN_ADD_COLLECTION_MODE_COST, AccessType::WRITE) )
            return false;
        $this->_service_payment_mode->setUpdatedBy($this->loginUserProfileId);
        
        if( !$this->is_required($this->input->post(), array('remittance_configuration_id','corporate_service_payment_mode_id','no_cost'), false))
        {
            return false;
        }
        
        $remittanceConfigurationId = $this->input->post('remittance_configuration_id');
        $corporateServicePaymentModeId = $this->input->post('corporate_service_payment_mode_id');
        $no_cost = $this->input->post('no_cost') ? $this->input->post('no_cost') : false;
        $costs = $this->input->post('costs') ? $this->input->post('costs') : NULL;
        if(!empty($costs))
            $costs = json_decode($costs);
        
        if( $this->_service_payment_mode->addPaymentModeCostGroup($remittanceConfigurationId, $corporateServicePaymentModeId, $costs, $no_cost) )
        {
            $this->_respondWithSuccessCode($this->_service_payment_mode->getResponseCode());
            return true;
        }
        
        $this->_respondWithCode($this->_service_payment_mode->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    
    public function addPaymentModeCostGroup()
    {
        if( !$this->loginUserProfileId = $this->_getUserProfileId(FunctionCode::ADMIN_ADD_PAYMENT_MODE_COST, AccessType::WRITE) )
            return false;
        $this->_service_payment_mode->setUpdatedBy($this->loginUserProfileId);
        
        if( !$this->is_required($this->input->post(), array('remittance_configuration_id','corporate_service_payment_mode_id','no_cost'), false))
        {
            return false;
        }
        
        $remittanceConfigurationId = $this->input->post('remittance_configuration_id');
        $corporateServicePaymentModeId = $this->input->post('corporate_service_payment_mode_id');
        $no_cost = $this->input->post('no_cost') ? $this->input->post('no_cost') : false;
        $costs = $this->input->post('costs');
        if(!empty($costs))
            $costs = json_decode($costs);
        
        if( $this->_service_payment_mode->addPaymentModeCostGroup($remittanceConfigurationId, $corporateServicePaymentModeId, $costs, $no_cost) )
        {
            $this->_respondWithSuccessCode($this->_service_payment_mode->getResponseCode());
            return true;
        }
        
        $this->_respondWithCode($this->_service_payment_mode->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    
    public function getCollectionModeCostGroupInfo()
    {
        if( !$this->loginUserProfileId = $this->_getUserProfileId(FunctionCode::ADMIN_LIST_PAYMENT_MODE_COST_GROUP, AccessType::READ) )
            return false;
        $this->_service_payment_mode->setUpdatedBy($this->loginUserProfileId);
        
        
        if( !$this->is_required($this->input->post(), array('corporate_service_payment_mode_id','status')))
        {
            return false;
        }
        $corporateServicePaymentModeId = $this->input->post('corporate_service_payment_mode_id');
        $status = $this->input->post('status');
        
        if( $result = $this->_service_payment_mode->getPaymentModeCostGroupInfo($corporateServicePaymentModeId, NULL, $status) )
        {
            $this->_respondWithSuccessCode($this->_service_payment_mode->getResponseCode(), array('result' => $result));
            return true;
        }
        $this->_respondWithCode($this->_service_payment_mode->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    
    public function getPaymentModeCostGroupInfo()
    {
        if( !$this->loginUserProfileId = $this->_getUserProfileId(FunctionCode::ADMIN_LIST_PAYMENT_MODE_COST_GROUP, AccessType::READ) )
            return false;
        $this->_service_payment_mode->setUpdatedBy($this->loginUserProfileId);
        
        
        if( !$this->is_required($this->input->post(), array('corporate_service_payment_mode_id','status')))
        {
            return false;
        }
        $corporateServicePaymentModeId = $this->input->post('corporate_service_payment_mode_id');
        $status = $this->input->post('status');
        
        if( $result = $this->_service_payment_mode->getPaymentModeCostGroupInfo($corporateServicePaymentModeId, NULL, $status) )
        {
            $this->_respondWithSuccessCode($this->_service_payment_mode->getResponseCode(), array('result' => $result));
            return true;
        }
        $this->_respondWithCode($this->_service_payment_mode->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    
    
    public function getCollectionModeCostListingByGroupId()
    {
        if( !$this->loginUserProfileId = $this->_getUserProfileId(FunctionCode::ADMIN_LIST_PAYMENT_MODE_COST, AccessType::READ) )
            return false;
        $this->_service_payment_mode->setUpdatedBy($this->loginUserProfileId);
        
        if( !$this->is_required($this->input->post(), array('payment_mode_cost_group_id')))
        {
            return false;
        }
        $paymentModeCostGroupId = $this->input->post('payment_mode_cost_group_id');
        
        $limit = $this->_getLimit();
        $page = $this->_getPage();
        
        if( $result = $this->_service_payment_mode->getPaymentModeCostListingByGroupId($limit, $page, $paymentModeCostGroupId) )
        {
            $this->_respondWithSuccessCode($this->_service_payment_mode->getResponseCode(), array('result' => $result->result, 'total' => $result->total));
            return true;
        }
        $this->_respondWithCode($this->_service_payment_mode->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    
    
    
    public function getApprovalPricingFeeListing()
    {
        if( !$this->loginUserProfileId = $this->_getUserProfileId(FunctionCode::ADMIN_LIST_PRICING_FEE, AccessType::READ) )
            return false;
        $this->_service_payment_mode->setUpdatedBy($this->loginUserProfileId);
        
        if( !$this->is_required($this->input->post(), array('status')))
        {
            return false;
        }
        
        $type = $this->input->post('type');
        $remittance_config_id = $this->input->post('remittance_config_id') ? $this->input->post('remittance_config_id') : NULL;
        $from_country_currency_code = $this->input->post('from_country_currency_code') ? $this->input->post('from_country_currency_code') : NULL;
        $from_country_partner_id = $this->input->post('from_country_partner_id') ? $this->input->post('from_country_partner_id') : NULL;
        $to_country_currency_code = $this->input->post('to_country_currency_code') ? $this->input->post('to_country_currency_code') : NULL;
        $to_country_partner_id = $this->input->post('to_country_partner_id') ? $this->input->post('to_country_partner_id') : NULL;
        $status = $this->input->post('status') ? $this->input->post('status') : NULL;
        
        $limit = $this->_getLimit();
        $page = $this->_getPage();
        
        if( $result = $this->_service_payment_mode->getApprovalPricingFeeListing($limit, $page, $remittance_config_id, $type, 
            $from_country_currency_code, $from_country_partner_id, 
            $to_country_currency_code, $to_country_partner_id, 
            $status) )
        {
            $this->_respondWithSuccessCode($this->_service_payment_mode->getResponseCode(), array('result' => $result->result, 'total' => $result->total));
            return true;
        }
        
        $this->_respondWithCode($this->_service_payment_mode->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    
    public function getApprovalPricingCostListing()
    {
        if( !$this->loginUserProfileId = $this->_getUserProfileId(FunctionCode::ADMIN_LIST_PRICING_COST, AccessType::READ) )
            return false;
        $this->_service_payment_mode->setUpdatedBy($this->loginUserProfileId);
        
        if( !$this->is_required($this->input->post(), array('status')))
        {
            return false;
        }
        
        $type = $this->input->post('type');
        $remittance_config_id = $this->input->post('remittance_config_id') ? $this->input->post('remittance_config_id') : NULL;
        $from_country_currency_code = $this->input->post('from_country_currency_code') ? $this->input->post('from_country_currency_code') : NULL;
        $from_country_partner_id = $this->input->post('from_country_partner_id') ? $this->input->post('from_country_partner_id') : NULL;
        $to_country_currency_code = $this->input->post('to_country_currency_code') ? $this->input->post('to_country_currency_code') : NULL;
        $to_country_partner_id = $this->input->post('to_country_partner_id') ? $this->input->post('to_country_partner_id') : NULL;
        $status = $this->input->post('status') ? $this->input->post('status') : NULL;
        
        $limit = $this->_getLimit();
        $page = $this->_getPage();
        
        if( $result = $this->_service_payment_mode->getApprovalPricingCostListing($limit, $page, $remittance_config_id, $type, 
            $from_country_currency_code, $from_country_partner_id, 
            $to_country_currency_code, $to_country_partner_id, 
            $status) )
        {
            $this->_respondWithSuccessCode($this->_service_payment_mode->getResponseCode(), array('result' => $result->result, 'total' => $result->total));
            return true;
        }
        
        $this->_respondWithCode($this->_service_payment_mode->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    
    public function getPaymentModeFeeGroupInfoByGroupId()
    {
        if( !$this->loginUserProfileId = $this->_getUserProfileId(FunctionCode::ADMIN_LIST_PAYMENT_MODE_FEE_GROUP, AccessType::READ) )
            return false;
        $this->_service_payment_mode->setUpdatedBy($this->loginUserProfileId);
        
        if( !$this->is_required($this->input->post(), array('corporate_service_payment_mode_fee_group_id')))
        {
            return false;
        }
        $corporateServicePaymentModeFeeGroupId = $this->input->post('corporate_service_payment_mode_fee_group_id');
        
        if( $result = $this->_service_payment_mode->getPaymentModeFeeGroupInfoByGroupId($corporateServicePaymentModeFeeGroupId, NULL, NULL) )
        {
            $this->_respondWithSuccessCode($this->_service_payment_mode->getResponseCode(), array('result' => $result));
            return true;
        }
        $this->_respondWithCode($this->_service_payment_mode->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    
    public function getPaymentModeCostGroupInfoByGroupId()
    {
        if( !$this->loginUserProfileId = $this->_getUserProfileId(FunctionCode::ADMIN_LIST_PAYMENT_MODE_COST_GROUP, AccessType::READ) )
            return false;
        $this->_service_payment_mode->setUpdatedBy($this->loginUserProfileId);
        
        
        if( !$this->is_required($this->input->post(), array('payment_mode_cost_group_id')))
        {
            return false;
        }
        $payment_mode_cost_group_id = $this->input->post('payment_mode_cost_group_id');
        
        if( $result = $this->_service_payment_mode->getPaymentModeCostGroupInfoByGroupId($payment_mode_cost_group_id, NULL, NULL) )
        {
            $this->_respondWithSuccessCode($this->_service_payment_mode->getResponseCode(), array('result' => $result));
            return true;
        }
        $this->_respondWithCode($this->_service_payment_mode->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    
    public function viewApprovalPricingFee()
    {
        if( !$this->loginUserProfileId = $this->_getUserProfileId(FunctionCode::ADMIN_LIST_PRICING_FEE, AccessType::READ) )
            return false;
        $this->_service_payment_mode->setUpdatedBy($this->loginUserProfileId);
        
        if( !$this->is_required($this->input->post(), array('corporate_service_payment_mode_fee_group_id')))
        {
            return false;
        }
        
        $corporateServicePaymentModeGroupId = $this->input->post('corporate_service_payment_mode_fee_group_id');
        
        $limit = $this->_getLimit();
        $page = $this->_getPage();
        
        if( $result = $this->_service_payment_mode->viewApprovalPricingFee($limit, $page, $corporateServicePaymentModeGroupId) )
        {
            $this->_respondWithSuccessCode($this->_service_payment_mode->getResponseCode(), array('result' => $result->result, 'total' => $result->total));
            return true;
        }
        
        $this->_respondWithCode($this->_service_payment_mode->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    
    public function viewApprovalPricingCost()
    {
        if( !$this->loginUserProfileId = $this->_getUserProfileId(FunctionCode::ADMIN_LIST_PRICING_COST, AccessType::READ) )
            return false;
        $this->_service_payment_mode->setUpdatedBy($this->loginUserProfileId);
        
        if( !$this->is_required($this->input->post(), array('payment_mode_cost_group_id')))
        {
            return false;
        }
        
        $paymentModeCostGroupId = $this->input->post('payment_mode_cost_group_id');
        
        $limit = $this->_getLimit();
        $page = $this->_getPage();
        
        if( $result = $this->_service_payment_mode->viewApprovalPricingCost($limit, $page, $paymentModeCostGroupId) )
        {
            $this->_respondWithSuccessCode($this->_service_payment_mode->getResponseCode(), array('result' => $result->result, 'total' => $result->total));
            return true;
        }
        
        $this->_respondWithCode($this->_service_payment_mode->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    
    
    public function approvalPaymentModeFeeGroup()
    {
        if( !$this->loginUserProfileId = $this->_getUserProfileId(FunctionCode::ADMIN_UPDATE_PAYMENT_MODE_FEE_GROUP_STATUS, AccessType::WRITE) )
            return false;
        $this->_service_payment_mode->setUpdatedBy($this->loginUserProfileId);
        
        if( !$this->is_required($this->input->post(), array('corporate_service_payment_mode_fee_group_id','status')))
        {
            return false;
        }
        $corporateServicePaymentModeFeeGroupId = $this->input->post('corporate_service_payment_mode_fee_group_id');
        $status = $this->input->post('status');
        $remarks = $this->input->post('remarks');
        
        if( $result = $this->_service_payment_mode->approvalPaymentModeFee($corporateServicePaymentModeFeeGroupId, $status, $remarks) )
        {
            $this->_respondWithSuccessCode($this->_service_payment_mode->getResponseCode());
            return true;
        }
        $this->_respondWithCode($this->_service_payment_mode->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    
    
    
    public function approvalPaymentModeCostGroup()
    {
        if( !$this->loginUserProfileId = $this->_getUserProfileId(FunctionCode::ADMIN_UPDATE_PAYMENT_MODE_COST_GROUP_STATUS, AccessType::WRITE) )
            return false;
        $this->_service_payment_mode->setUpdatedBy($this->loginUserProfileId);
        
        if( !$this->is_required($this->input->post(), array('payment_mode_cost_group_id','status')))
        {
            return false;
        }
        
        $paymentModeCostGroupId = $this->input->post('payment_mode_cost_group_id');
        $status = $this->input->post('status') ? $this->input->post('status') : NULL;
        $remarks = $this->input->post('remarks');
        
        if( $result = $this->_service_payment_mode->approvalPaymentModeCost($paymentModeCostGroupId, $status, $remarks) )
        {
            $this->_respondWithSuccessCode($this->_service_payment_mode->getResponseCode());
            return true;
        }
        $this->_respondWithCode($this->_service_payment_mode->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    
    
    public function activeCollectionMode()
    {
        return $this->activePaymentMode();
    }
    
    public function activePaymentMode()
    {
        if( !$this->loginUserProfileId = $this->_getUserProfileId(FunctionCode::ADMIN_UPDATE_PAYMENT_MODE_STATUS, AccessType::WRITE) )
            return false;
        $this->_service_payment_mode->setUpdatedBy($this->loginUserProfileId);
        
        if( !$this->is_required($this->input->post(), array('corporate_service_payment_mode_id','is_active')))
        {
            return false;
        }
        
        $paymentModeId = $this->input->post('corporate_service_payment_mode_id');
        $isActive = $this->input->post('is_active');
        
        if($isActive === "true")
            $isActive = 1;
        else
            $isActive = 0;
        
        if( $this->_service_payment_mode->activePaymentMode($paymentModeId, $isActive) )
        {
            $this->_respondWithSuccessCode($this->_service_payment_mode->getResponseCode());
            return true;
        }
        $this->_respondWithCode($this->_service_payment_mode->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    
}
