<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeFeeGroupService;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeFeeGroupRepository;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeFeeGroup;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeFeeGroupStatus;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeFee;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeFeeCollection;
use Iapps\RemittanceService\Common\SystemCodeServiceFactory;
use Iapps\Common\Helper\ResponseHeader;

/**
 * Description of Payment_mode_fee_group
 *
 * @author lichao
 */
class Payment_mode_fee_group extends Base_Controller {

    //put your code here

    private $isLogin = false;
    private $loginUserProfileId = null;
    private $_service_payment_mode_fee_group;

    function __construct() {
        parent::__construct();

        $this->load->model('remittancepaymentmode/Payment_mode_fee_group_model');
        $repo = new PaymentModeFeeGroupRepository($this->Payment_mode_fee_group_model);
        $this->_service_payment_mode_fee_group = new PaymentModeFeeGroupService($repo);

        $this->_service_audit_log->setTableName('iafb_remittance.corporate_service_payment_mode_fee_group');
    }
    
    private function _getServiceSystemCodeService()
    {
        $service = SystemCodeServiceFactory::build();
        $service->setUpdatedBy($this->_service_payment_mode_fee_group->getUpdatedBy());
        $service->setIpAddress($this->_service_payment_mode_fee_group->getIpAddress());
        return $service;
    }

    public function getListByCorporateServiceIds() {
        if (!$this->is_required($this->input->post(), array('corporate_service_ids'))) {
            return false;
        }

        $limit = $this->_getLimit();
        $page = $this->_getPage();
        $corporate_service_id = $this->input->post('corporate_service_ids');

        $corporate_service_ids = explode(',', $corporate_service_id);

        if ($object = $this->_service_payment_mode_fee_group->getListByCorporrateServicePaymentModeIds($limit, $page, $corporate_service_ids)) {
            $this->_respondWithSuccessCode($this->_service_payment_mode_fee_group->getResponseCode(), array('result' => $object->result, 'total' => $object->total));
            return true;
        }

        $this->_respondWithCode($this->_service_payment_mode_fee_group->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function addPaymentModeFeeGroup() {
        
        if( !$this->is_required($this->input->post(), array('corporate_service_payment_mode_id', 'fee_type_id', 'name')) )
        {
            return false;
        }
        
        $corporate_service_payment_mode_id = $this->input->post('corporate_service_payment_mode_id');
        $fee_type_id = $this->input->post('fee_type_id');
        $name = $this->input->post('name');
        
        
        $serviceSystemCode = $this->_getServiceSystemCodeService();
        $fee_type = $serviceSystemCode->getById($fee_type_id);
        if( $fee_type == false )
        {
            $this->_respondWithCode($this->_service_payment_mode_fee_group->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
            return false;
        }
        $status = $serviceSystemCode->getByCode(PaymentModeFeeGroupStatus::CODE_PENDING, PaymentModeFeeGroupStatus::getSystemGroupCode());
        if( $status == false )
        {
            $this->_respondWithCode($this->_service_payment_mode_fee_group->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
            return false;
        }

        $entity = new PaymentModeFeeGroup();

        $id = Iapps\Common\Helper\GuidGenerator::generate();
        $entity->setId($id);
        $entity->setCorporateServicePaymentModeId($corporate_service_payment_mode_id);
        $entity->setFeeType($fee_type);
        $entity->setName($name);
        $entity->setStatus($status);
        
        // payment_mode_fee list
        
        if( $this->_service_payment_mode_fee_group->savePaymentModeFeeGroup($entity) )
        {
            $this->_respondWithSuccessCode($this->_service_payment_mode_fee_group->getResponseCode());
            return true;
        }
        
        $this->_respondWithCode($this->_service_payment_mode_fee_group->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

}
