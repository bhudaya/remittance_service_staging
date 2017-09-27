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
 * Description of PaymentModeCost
 *
 * @author lichao
 */
class PaymentModeCost extends IappsBaseEntity  {
    //put your code here
    
    protected $payment_mode_group_id = NULL;
    protected $service_provider_id = NULL;
    protected $service_provider_name = NULL;
    protected $role_id = NULL;
    protected $role_name = NULL;
    protected $is_percentage = NULL;
    protected $country_currency_code = NULL;
    protected $cost = NULL;
    
    public function __construct() {
        parent::__construct();
        
    }
    
    public function setPaymentModeGroupId($paymentModeGroupId)
    {
        $this->payment_mode_group_id = $paymentModeGroupId;
        return $this;
    }
    public function getPaymentModeGroupId()
    {
        return $this->payment_mode_group_id;
    }
    
    public function setServiceProviderId($serviceProviderId)
    {
        $this->service_provider_id = $serviceProviderId;
        return $this;
    }
    public function getServiceProviderId()
    {
        return $this->service_provider_id;
    }
    
    public function setServiceProviderName($serviceProviderName)
    {
        $this->service_provider_name = $serviceProviderName;
        return $this;
    }
    
    public function getServiceProviderName()
    {
        return $this->service_provider_name;
    }
    
    public function setRoleId($roleId)
    {
        $this->role_id = $roleId;
        return $this;
    }
    public function getRoleId()
    {
        return $this->role_id;
    }
    
    public function setRoleName($roleName)
    {
        $this->role_name = $roleName;
        return $this;
    }
    
    public function getRoleName()
    {
        return $this->role_name;
    }
    
    public function setIsPercentage($isPercentage)
    {
        $this->is_percentage = $isPercentage;
        return $this;
    }
    public function getIsPercentage()
    {
        return $this->is_percentage;
    }
    
    public function setCountryCurrencyCode($countryCurrencyCode)
    {
        $this->country_currency_code = $countryCurrencyCode;
        return $this;
    }
    public function getCountryCurrencyCode()
    {
        return $this->country_currency_code;
    }
    
    public function setCost($cost)
    {
        $this->cost = $cost;
        return $this;
    }
    public function getCost()
    {
        return $this->cost;
    }
    
    
    public function jsonSerialize() {
        
        $json = parent::jsonSerialize();
        
        $json['payment_mode_group_id'] = $this->getPaymentModeGroupId();
        $json['service_provider_id'] = $this->getServiceProviderId();
        $json['service_provider_name'] = $this->getServiceProviderName();
        $json['role_id'] = $this->getRoleId();
        $json['role_name'] = $this->getRoleName();
        $json['is_percentage'] = $this->getIsPercentage();
        $json['country_currency_code'] = $this->getCountryCurrencyCode();
        $json['cost'] = $this->getCost();
    
        return $json;
    }
    
}
