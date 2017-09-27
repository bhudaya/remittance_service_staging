<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Iapps\RemittanceService\RemittancePaymentMode;

use Iapps\Common\Core\IappsBaseEntity;
use Iapps\Common\Core\IappsDateTime;

/**
 * Description of PaymentModeFee
 *
 * @author lichao
 */
class PaymentModeFee extends IappsBaseEntity {

    //put your code here

    protected $corporate_service_payment_mode_fee_group_id = NULL;
    protected $multitier_type = NULL;
    protected $reference_value1 = NULL;
    protected $reference_value2 = NULL;
    protected $is_percentage = NULL;
    protected $fee = NULL;

    function __construct() {
        parent::__construct();

        
    }

    public function setCorporateServicePaymentModeFeeGroupId($corporate_service_payment_mode_fee_group_id) {
        $this->corporate_service_payment_mode_fee_group_id = $corporate_service_payment_mode_fee_group_id;
        return true;
    }

    public function getCorporateServicePaymentModeFeeGroupId() {
        return $this->corporate_service_payment_mode_fee_group_id;
    }
    
    public function setMultitierType($multitier_type) {
        $this->multitier_type = $multitier_type;
        return true;
    }

    public function getMultitierType() {
        return $this->multitier_type;
    }

    public function setReferenceValue1($reference_value1) {
        $this->reference_value1 = $reference_value1;
        return true;
    }

    public function getReferenceValue1() {
        return $this->reference_value1;
    }

    public function setReferenceValue2($reference_value2) {
        $this->reference_value2 = $reference_value2;
        return true;
    }

    public function getReferenceValue2() {
        return $this->reference_value2;
    }

    public function setIsPercentage($IsPercentage) {
        $this->is_percentage = $IsPercentage;
        return true;
    }

    public function getIsPercentage() {
        return $this->is_percentage;
    }

    public function setFee($Fee) {
        $this->fee = $Fee;
        return true;
    }

    public function getFee() {
        return $this->fee;
    }

    public function jsonSerialize() {
        
        $json = parent::jsonSerialize();
        
        $json['corporate_service_payment_mode_fee_group_id'] = $this->getCorporateServicePaymentModeFeeGroupId();
        $json['multitier_type'] = $this->getMultitierType();
        $json['reference_value1'] = $this->getReferenceValue1();
        $json['reference_value2'] = $this->getReferenceValue2();
        $json['is_percentage'] = $this->getIsPercentage();
        $json['fee'] = $this->getFee();
        
        return $json;
    }

}
