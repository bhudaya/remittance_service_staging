<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Iapps\RemittanceService\RemittancePaymentMode;

use Iapps\Common\Core\IappsBaseEntity;
use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\SystemCode\SystemCode;

/**
 * Description of PaymentModeFeeGroup
 *
 * @author lichao
 */
class PaymentModeFeeGroup extends IappsBaseEntity {

    //put your code here

    protected $corporate_service_payment_mode_id = NULL;
    protected $fee_type = NULL;
    protected $name = NULL;
    protected $status = NULL;
    protected $approve_reject_remark = NULL;
    protected $approve_reject_at = NULL;
    protected $approve_reject_by = NULL;
    protected $is_active = NULL;
    
    protected $paymentModeFeeItems = NULL;

    function __construct() {
        parent::__construct();

        $this->approve_reject_at = new IappsDateTime();
        $this->fee_type = new SystemCode();
        $this->status = new SystemCode();
        
        $this->paymentModeFeeItems = new PaymentModeFeeCollection();
    }

    public function setCorporateServicePaymentModeId($corporate_service_payment_mode_id) {
        $this->corporate_service_payment_mode_id = $corporate_service_payment_mode_id;
        return true;
    }

    public function getCorporateServicePaymentModeId() {
        return $this->corporate_service_payment_mode_id;
    }

    public function setFeeType(SystemCode $fee_type) {
        $this->fee_type = $fee_type;
        return $this;
    }

    public function getFeeType() {
        return $this->fee_type;
    }

//    public function setFeeTypeId($fee_type_id){
//        $this->fee_type_id = $fee_type_id;
//        return true;
//    }
//
//    public function getFeeTypeId() {
//        return $this->fee_type_id;
//    }

    public function setName($Name) {
        $this->name = $Name;
        return true;
    }

    public function getName() {
        return $this->name;
    }

    public function setStatus(SystemCode $status) {
        $this->status = $status;
        return $this;
    }

    public function getStatus() {
        return $this->status;
    }

    public function setApproveRejectRemark($approve_reject_remark) {
        $this->approve_reject_remark = $approve_reject_remark;
        return $this;
    }

    public function getApproveRejectRemark() {
        return $this->approve_reject_remark;
    }

    public function setApproveRejectAt(IappsDateTime $approve_reject_at) {
        $this->approve_reject_at = $approve_reject_at;
        return $this;
    }

    public function getApproveRejectAt() {
        return $this->approve_reject_at;
    }

    public function setApproveRejectBy($approve_reject_by) {
        $this->approve_reject_by = $approve_reject_by;
        return $this;
    }

    public function getApproveRejectBy() {
        return $this->approve_reject_by;
    }

    public function setIsActive($is_active) {
        $this->is_active = $is_active;
        return $this;
    }

    public function getIsActive() {
        return $this->is_active;
    }

    public function getPaymentModeFeeItems(){
        return $this->paymentModeFeeItems;
    }
    public function setPaymentModeFeeItems(PaymentModeFeeCollection $paymentModeFeeItems){
        $this->paymentModeFeeItems = $paymentModeFeeItems;
        return $this;
    }
    public function addPaymentModeFeeItems(PaymentModeFee $itemEntity){
        $this->paymentModeFeeItems->addData($itemEntity);
        return $this;
    }
    
    public function jsonSerialize() {
        
        $json = parent::jsonSerialize();

        $json['corporate_service_payment_mode_id'] = $this->getCorporateServicePaymentModeId();
        $json['fee_type'] = $this->getFeeType()->getCode();
        $json['fee_type_id'] = $this->getFeeType()->getId();
        $json['name'] = $this->getName();
        $json['status'] = $this->getStatus()->getCode();
        $json['approve_reject_remark'] = $this->getApproveRejectRemark();
        $json['approve_reject_at'] = $this->getApproveRejectAt()->getString();
        $json['approve_reject_by'] = $this->getApproveRejectBy();
        $json['is_active '] = $this->getIsActive();

        return $json;
    }
}
