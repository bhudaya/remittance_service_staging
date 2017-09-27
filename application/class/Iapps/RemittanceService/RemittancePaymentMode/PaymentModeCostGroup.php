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
 * Description of PaymentModeCostGroup
 *
 * @author lichao
 */
class PaymentModeCostGroup extends IappsBaseEntity {
    //put your code here
    
    private $corporate_service_payment_mode_id;
    private $status;
    private $approve_reject_remark;
    private $approve_reject_at;
    private $approve_reject_by;
    private $is_active;
    private $no_cost;
    
    protected $paymentModeCostItems = NULL;
    
    public function __construct() {
//        parent::__construct();
        
        $this->status = new SystemCode();
        $this->approve_reject_at = new IappsDateTime();
        
        $this->paymentModeCostItems = new PaymentModeCostCollection();
    }
    
    public function setCorporateServicePaymentModeId($corporate_service_payment_mode_id)
    {
        $this->corporate_service_payment_mode_id = $corporate_service_payment_mode_id;
        return $this;
    }
    public function getCorporateServicePaymentModeId()
    {
        return $this->corporate_service_payment_mode_id;
    }
    
    public function setStatus(SystemCode $status)
    {
        $this->status = $status;
        return $this;
    }
    public function getStatus()
    {
        return $this->status;
    }
    
    public function setApproveRejectRemark($approve_reject_remark)
    {
        $this->approve_reject_remark = $approve_reject_remark;
        return $this;
    }
    public function getApproveRejectRemark()
    {
        return $this->approve_reject_remark;
    }
    
    public function setApproveRejectAt(IappsDateTime $approve_reject_at)
    {
        $this->approve_reject_at = $approve_reject_at;
        return $this;
    }
    public function getApproveRejectAt()
    {
        return $this->approve_reject_at;
    }
    
    public function setApproveRejectBy($approve_reject_by)
    {
        $this->approve_reject_by = $approve_reject_by;
        return $this;
    }
    public function getApproveRejectBy()
    {
        return $this->approve_reject_by;
    }
    
    public function setIsActive($is_active)
    {
        $this->is_active = $is_active;
        return $this;
    }
    public function getIsActive()
    {
        return $this->is_active;
    }
    
    public function setNoCost($no_cost)
    {
        $this->no_cost = $no_cost;
        return $this;
    }
    public function getNoCost()
    {
        return $this->no_cost;
    }
    
    public function getPaymentModeCostItems(){
        return $this->paymentModeCostItems;
    }
    public function setPaymentModeCostItems(PaymentModeCostCollection $paymentModeCostItems){
        $this->paymentModeCostItems = $paymentModeCostItems;
        return $this;
    }
    public function addPaymentModeCostItems(PaymentModeCost $itemEntity){
        $this->paymentModeCostItems->addData($itemEntity);
        return $this;
    }
    
    public function jsonSerialize() {
        
        $json = parent::jsonSerialize();
        
        $json['corporate_service_payment_mode_id'] = $this->getCorporateServicePaymentModeId();
        $json['status'] = $this->getStatus()->getCode();
        $json['approve_reject_remark'] = $this->getApproveRejectRemark();
        $json['approve_reject_at'] = $this->getApproveRejectAt()->getString();
        $json['approve_reject_by'] = $this->getApproveRejectBy();
        $json['is_active'] = $this->getIsActive();
        $json['no_cost'] = $this->getNoCost();
    
        return $json;
    }
    
}
