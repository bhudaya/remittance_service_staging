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
 * Description of PaymentMode
 *
 * @author lichao
 */
class PaymentMode extends IappsBaseEntity {
    //put your code here

    protected $direction = NULL;
    protected $corporate_service_id = NULL;
    protected $is_default = NULL;
    protected $payment_code = NULL;
    protected $is_active = NULL;

    public function setDirection($direction) {
        $this->direction = $direction;
        return true;
    }

    public function getDirection() {
        return $this->direction;
    }

    public function setCorporateServiceId($CorporateServiceId) {
        $this->corporate_service_id = $CorporateServiceId;
        return true;
    }

    public function getCorporateServiceId() {
        return $this->corporate_service_id;
    }

    public function setIsDefault($IsDefault) {
        $this->is_default = $IsDefault;
        return true;
    }

    public function getIsDefault() {
        return $this->is_default;
    }

    public function setPaymentCode($PaymentCode) {
        $this->payment_code = $PaymentCode;
        return true;
    }

    public function getPaymentCode() {
        return $this->payment_code;
    }
    
    public function setIsActive($isActive)
    {
        $this->is_active = $isActive;
        return $this;
    }
    
    public function getIsActive()
    {
        return $this->is_active;
    }

    public function jsonSerialize() {
        
        $json = parent::jsonSerialize();
        
        $json['corporate_service_id'] = $this->getCorporateServiceId();
        $json['direction'] = $this->getDirection();
        $json['is_default'] = (bool) $this->getIsDefault();
        $json['payment_code'] = $this->getPaymentCode();
        $json['is_active'] = $this->getIsActive();

        return $json;
    }

}
