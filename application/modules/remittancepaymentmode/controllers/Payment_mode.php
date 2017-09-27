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

/**
 * Description of Payment_mode_fee
 *
 * @author lichao
 */
class Payment_mode extends Base_Controller {
    //put your code here
    
    private $isLogin = false;
    private $loginUserProfileId = null;
    private $_service_payment_mode;
    
    function __construct() {
        parent::__construct();
        
        $this->load->model('remittancepaymentmode/Payment_mode_model');
        $repo = new PaymentModeRepository($this->Payment_mode_model);
        $this->_service_payment_mode = new PaymentModeService($repo);
        
        $this->loginUserProfileId = $this->_getUserProfileId();
        if($this->loginUserProfileId)
            $this->isLogin = true;
        $this->_service_payment_mode->setUpdatedBy($this->loginUserProfileId);
        
        $this->_service_audit_log->setTableName('iafb_remittance.corporate_service_payment_mode');
    }
    
    public function getPricingFeeListing()
    {
        if( !$this->is_required($this->input->post(), array('status')))
        {
            return false;
        }
        
        $remittanceConfigurationId = $this->input->post('remittance_configuration_id');
        $type = $this->input->post('type');
        $fromCountryCurrency = $this->input->post('from_country_currency');
        $fromCountryPartner = $this->input->post('from_country_partner');
        $toCountryCurrency = $this->input->post('to_country_currency');
        $toCountryPartner = $this->input->post('to_country_partner');
        $status = $this->input->post('status');
        
        $limit = $this->_getLimit();
        $page = $this->_getPage();
        
        if( $result = $this->_service_payment_mode->getPricingFeeListing($limit, $page, $remittanceConfigurationId, $type, 
            $fromCountryCurrency, $fromCountryPartner, 
            $toCountryCurrency, $toCountryPartner, 
            $status) )
        {
            $this->_respondWithSuccessCode($this->_service_payment_mode->getResponseCode(), array('result' => $result->result, 'total' => $result->total));
            return true;
        }
        
        $this->_respondWithCode($this->_service_payment_mode->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    
    public function getPricingCostListing()
    {
        if( !$this->is_required($this->input->post(), array('status')))
        {
            return false;
        }
        
        $remittanceConfigurationId = $this->input->post('remittance_configuration_id');
        $type = $this->input->post('type');
        $fromCountryCurrency = $this->input->post('from_country_currency');
        $fromCountryPartner = $this->input->post('from_country_partner');
        $toCountryCurrency = $this->input->post('to_country_currency');
        $toCountryPartner = $this->input->post('to_country_partner');
        $status = $this->input->post('status');
        
        $limit = $this->_getLimit();
        $page = $this->_getPage();
        
        if( $result = $this->_service_payment_mode->getPricingCostListing($limit, $page, $remittanceConfigurationId, $type, 
            $fromCountryCurrency, $fromCountryPartner, 
            $toCountryCurrency, $toCountryPartner, 
            $status) )
        {
            $this->_respondWithSuccessCode($this->_service_payment_mode->getResponseCode(), array('result' => $result->result, 'total' => $result->total));
            return true;
        }
        
        $this->_respondWithCode($this->_service_payment_mode->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    
    public function viewPricingFee()
    {
        if( !$this->is_required($this->input->post(), array('corporate_service_payment_mode_group_id')))
        {
            return false;
        }
        
        $corporateServicePaymentModeGroupId = $this->input->post('corporate_service_payment_mode_group_id');
        
        $limit = $this->_getLimit();
        $page = $this->_getPage();
        
        if( $result = $this->_service_payment_mode->viewPricingFee($corporateServicePaymentModeGroupId) )
        {
            $this->_respondWithSuccessCode($this->_service_payment_mode->getResponseCode(), array('result' => $result->result, 'total' => $result->total));
            return true;
        }
        
        $this->_respondWithCode($this->_service_payment_mode->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    
    public function viewPricingCost()
    {
        if( !$this->is_required($this->input->post(), array('payment_mode_cost_group_id')))
        {
            return false;
        }
        
        $paymentModeCostGroupId = $this->input->post('payment_mode_cost_group_id');
        
        $limit = $this->_getLimit();
        $page = $this->_getPage();
        
        if( $result = $this->_service_payment_mode->viewPricingCost($paymentModeCostGroupId) )
        {
            $this->_respondWithSuccessCode($this->_service_payment_mode->getResponseCode(), array('result' => $result->result, 'total' => $result->total));
            return true;
        }
        
        $this->_respondWithCode($this->_service_payment_mode->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    
    
    public function getPaymentModeListing()
    {
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
    
    public function getCollectionModeListing()
    {
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
    
    public function addPaymentMode()
    {
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
    
    public function addCollectionMode()
    {
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
    
    public function updatePaymentModeActive()
    {
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
    
    public function addPaymentModeFee()
    {
        if( !$this->is_required($this->input->post(), array('remittance_configuration_id','corporate_service_payment_mode_id','fees')))
        {
            return false;
        }
        
        $remittanceConfigurationId = $this->input->post('remittance_configuration_id');
        $corporateServicePaymentModeId = $this->input->post('corporate_service_payment_mode_id');
        $name = $this->input->post('name');
        $fees = $this->input->post('fees');
        $fees = json_decode($fees);
        
        if( $this->_service_payment_mode->addPaymentModeFee($remittanceConfigurationId, $corporateServicePaymentModeId, $name, $fees) )
        {
            $this->_respondWithSuccessCode($this->_service_payment_mode->getResponseCode());
            return true;
        }
        $this->_respondWithCode($this->_service_payment_mode->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    
    public function addCollectionModeFee()
    {
        if( !$this->is_required($this->input->post(), array('remittance_configuration_id','corporate_service_payment_mode_id','fees')))
        {
            return false;
        }
        
        $remittanceConfigurationId = $this->input->post('remittance_configuration_id');
        $corporateServicePaymentModeId = $this->input->post('corporate_service_payment_mode_id');
        $name = $this->input->post('name');
        $fees = $this->input->post('fees');
        $fees = json_decode($fees);
        
        if( $this->_service_payment_mode->addCollectionModeFee($remittanceConfigurationId, $corporateServicePaymentModeId, $name, $fees) )
        {
            $this->_respondWithSuccessCode($this->_service_payment_mode->getResponseCode());
            return true;
        }
        
        $this->_respondWithCode($this->_service_payment_mode->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    
    public function getPaymentModeFeeGroupInfo()
    {
        if( !$this->is_required($this->input->post(), array('corporate_service_payment_mode_id')))
        {
            return false;
        }
        $corporateServicePaymentModeId = $this->input->post('corporate_service_payment_mode_id');
        
        if( $result = $this->_service_payment_mode->getPaymentModeFeeGroupInfo($corporateServicePaymentModeId, NULL, NULL) )
        {
            $this->_respondWithSuccessCode($this->_service_payment_mode->getResponseCode(), array('result' => $result));
            return true;
        }
        $this->_respondWithCode($this->_service_payment_mode->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    
    public function getPaymentModeFeeGroupInfoByGroupId()
    {
        
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
    
    public function getPaymentModeFeeListing()
    {
        if( !$this->is_required($this->input->post(), array('corporate_service_payment_mode_id')))
        {
            return false;
        }
//        $remittanceConfigurationId = $this->input->post('remittance_configuration_id');
        $corporateServicePaymentModeId = $this->input->post('corporate_service_payment_mode_id');
        $status = $this->input->post('status') ? $this->input->post('status') : NULL;
        
        $limit = $this->_getLimit();
        $page = $this->_getPage();
        
        
        if( $result = $this->_service_payment_mode->getPaymentModeFeeListing($limit, $page, $corporateServicePaymentModeId, NULL, $status) )
        {
            $this->_respondWithSuccessCode($this->_service_payment_mode->getResponseCode(), array('result' => $result->result, 'total' => $result->total));
            return true;
        }
        $this->_respondWithCode($this->_service_payment_mode->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    
    public function getPaymentModeFeeListingView()
    {
        if( !$this->is_required($this->input->post(), array('corporate_service_payment_mode_fee_group_id')))
        {
            return false;
        }
        $corporateServicePaymentModeFeeGroupId = $this->input->post('corporate_service_payment_mode_fee_group_id');
        
        $limit = $this->_getLimit();
        $page = $this->_getPage();
        
        
        if( $result = $this->_service_payment_mode->getPaymentModeFeeListingView($limit, $page, $corporateServicePaymentModeFeeGroupId) )
        {
            $this->_respondWithSuccessCode($this->_service_payment_mode->getResponseCode(), array('result' => $result->result, 'total' => $result->total));
            return true;
        }
        $this->_respondWithCode($this->_service_payment_mode->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    
    public function updatePaymentModeFeeGroupStatus()
    {
        if( !$this->is_required($this->input->post(), array('corporate_service_payment_mode_fee_group_id','status')))
        {
            return false;
        }
        $corporateServicePaymentModeFeeGroupId = $this->input->post('corporate_service_payment_mode_fee_group_id');
        $status = $this->input->post('status');
        $remarks = $this->input->post('remarks');
        
        if( $result = $this->_service_payment_mode->updatePaymentModeFeeStatus($corporateServicePaymentModeFeeGroupId, $status, $remarks) )
        {
            $this->_respondWithSuccessCode($this->_service_payment_mode->getResponseCode());
            return true;
        }
        $this->_respondWithCode($this->_service_payment_mode->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    
    public function updatePaymentModeFee()
    {
        if( !$this->is_required($this->input->post(), array('remittance_configuration_id','corporate_service_payment_mode_id', 'corporate_service_payment_mode_fee_group_id', 'fees')))
        {
            return false;
        }
        
        $remittanceConfigurationId = $this->input->post('remittance_configuration_id');
        $corporateServicePaymentModeId = $this->input->post('corporate_service_payment_mode_id');
        $corporateServicePaymentModeFeeGroupId = $this->input->post('corporate_service_payment_mode_fee_group_id');
        $fees = $this->input->post('fees');
        $fees = json_decode($fees);
        
        if( $result = $this->_service_payment_mode->updatePaymentModeFee($remittanceConfigurationId, $corporateServicePaymentModeId, $corporateServicePaymentModeFeeGroupId, $fees) )
        {
            $this->_respondWithSuccessCode($this->_service_payment_mode->getResponseCode(), array('result' => $result->result, 'total' => $result->total));
            return true;
        }
        
        $this->_respondWithCode($this->_service_payment_mode->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    
    
    public function addPaymentModeCost()
    {
        if( !$this->is_required($this->input->post(), array('remittance_configuration_id','corporate_service_payment_mode_id','costs')))
        {
            return false;
        }
        
        $remittanceConfigurationId = $this->input->post('remittance_configuration_id');
        $corporateServicePaymentModeId = $this->input->post('corporate_service_payment_mode_id');
        $costs = $this->input->post('costs');
        $costs = json_decode($costs);
        
        if( $this->_service_payment_mode->addPaymentModeCost($remittanceConfigurationId, $corporateServicePaymentModeId, $costs) )
        {
            $this->_respondWithSuccessCode($this->_service_payment_mode->getResponseCode());
            return true;
        }
        
        $this->_respondWithCode($this->_service_payment_mode->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    
    public function addCollectionModeCost()
    {
        if( !$this->is_required($this->input->post(), array('remittance_configuration_id','corporate_service_payment_mode_id','name','fees')))
        {
            return false;
        }
        
        $remittanceConfigurationId = $this->input->post('remittance_configuration_id');
        $corporateServicePaymentModeId = $this->input->post('corporate_service_payment_mode_id');
        $name = $this->input->post('name');
        $fees = $this->input->post('fees');
        $fees = json_decode($fees);
        
        if( $this->_service_payment_mode->addCollectionModeFee($remittanceConfigurationId, $corporateServicePaymentModeId, $name, $fees) )
        {
            $this->_respondWithSuccessCode($this->_service_payment_mode->getResponseCode());
            return true;
        }
        
        $this->_respondWithCode($this->_service_payment_mode->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    
    public function getPaymentModeCostGroupInfo()
    {
        if( !$this->is_required($this->input->post(), array('corporate_service_payment_mode_id')))
        {
            return false;
        }
        $corporateServicePaymentModeId = $this->input->post('corporate_service_payment_mode_id');
        
        if( $result = $this->_service_payment_mode->getPaymentModeCostGroupInfo($corporateServicePaymentModeId, NULL, NULL) )
        {
            $this->_respondWithSuccessCode($this->_service_payment_mode->getResponseCode(), array('result' => $result));
            return true;
        }
        $this->_respondWithCode($this->_service_payment_mode->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    
    public function getPaymentModeCostGroupInfoByGroupId()
    {
        
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
    
    public function getPaymentModeCostListing()
    {
//        if( !$this->is_required($this->input->post(), array('corporate_service_payment_mode_id')))
//        {
//            return false;
//        }
//        $remittanceConfigurationId = $this->input->post('remittance_configuration_id');
//        $corporateServicePaymentModeId = $this->input->post('corporate_service_payment_mode_id');
        
        $status = $this->input->post('status') ? $this->input->post('status') : NULL;
        
        $limit = $this->_getLimit();
        $page = $this->_getPage();
        
        
        if( $result = $this->_service_payment_mode->getPaymentModeCostListing($limit, $page, NULL, NULL, $status) )
        {
            $this->_respondWithSuccessCode($this->_service_payment_mode->getResponseCode(), array('result' => $result->result, 'total' => $result->total));
            return true;
        }
        $this->_respondWithCode($this->_service_payment_mode->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    
    public function getPaymentModeCostListingView()
    {
        if( !$this->is_required($this->input->post(), array('payment_mode_cost_group_id')))
        {
            return false;
        }
        $paymentModeCostGroupId = $this->input->post('payment_mode_cost_group_id');
        
        $limit = $this->_getLimit();
        $page = $this->_getPage();
        
        
        if( $result = $this->_service_payment_mode->getPaymentModeCostListingView($limit, $page, $paymentModeCostGroupId) )
        {
            $this->_respondWithSuccessCode($this->_service_payment_mode->getResponseCode(), array('result' => $result->result, 'total' => $result->total));
            return true;
        }
        $this->_respondWithCode($this->_service_payment_mode->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    
    public function updatePaymentModeCostGroupStatus()
    {
        if( !$this->is_required($this->input->post(), array('payment_mode_cost_group_id','status')))
        {
            return false;
        }
        
        $paymentModeCostGroupId = $this->input->post('payment_mode_cost_group_id');
        $status = $this->input->post('status') ? $this->input->post('status') : NULL;
        $remarks = $this->input->post('remarks');
        
        if( $result = $this->_service_payment_mode->updatePaymentModeCostStatus($paymentModeCostGroupId, $status, $remarks) )
        {
            $this->_respondWithSuccessCode($this->_service_payment_mode->getResponseCode());
            return true;
        }
        $this->_respondWithCode($this->_service_payment_mode->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    
    public function updatePaymentModeCost()
    {
        if( !$this->is_required($this->input->post(), array('remittance_configuration_id','corporate_service_payment_mode_id','payment_mode_cost_group_id','costs')))
        {
            return false;
        }
        
        $remittanceConfigurationId = $this->input->post('remittance_configuration_id');
        $corporateServicePaymentModeId = $this->input->post('corporate_service_payment_mode_id');
        $paymentModeCostGroupId = $this->input->post('payment_mode_cost_group_id');
        $costs = $this->input->post('costs');
        $costs = json_decode($costs);
        
        if( $result = $this->_service_payment_mode->updatePaymentModeCost($remittanceConfigurationId, $corporateServicePaymentModeId, $paymentModeCostGroupId, $costs) )
        {
            $this->_respondWithSuccessCode($this->_service_payment_mode->getResponseCode(), array('result' => $result->result, 'total' => $result->total));
            return true;
        }
        
        $this->_respondWithCode($this->_service_payment_mode->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    
}
