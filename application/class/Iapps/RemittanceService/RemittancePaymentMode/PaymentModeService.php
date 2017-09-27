<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Iapps\RemittanceService\RemittancePaymentMode;

use Iapps\Common\Core\IappsBaseService;
use Iapps\Common\Core\IappsBaseEntity;
use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\Helper\GuidGenerator;
use Iapps\Common\SystemCode\SystemCode;
use Iapps\RemittanceService\Common\MessageCode;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfigServiceFactory;
use Iapps\RemittanceService\Common\CorporateServServiceFactory;
use Iapps\Common\Microservice\PaymentService\PaymentServiceFactory;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeFeeGroupServiceFactory;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeFeeServiceFactory;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeCostGroupServiceFactory;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeCostServiceFactory;
use Iapps\RemittanceService\Common\PaymentDirection;
use Iapps\Common\AuditLog\AuditLogAction;
use Iapps\RemittanceService\Common\SystemCodeServiceFactory;
use Iapps\Common\CorporateService\FeeType;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeCostGroupStatus;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeCostGroup;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeCost;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeCostCollection;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfig;
use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfigCollection;

/**
 * Description of PaymentModeService
 *
 * @author lichao
 */
class PaymentModeService extends IappsBaseService {

    //put your code here
    private $_serviceRemittanceConfig;
    private $_serviceAccount;
    
    private function _getServiceRemittanceConfig() {
        if( !$this->_serviceRemittanceConfig )
        {
            $this->_serviceRemittanceConfig = RemittanceConfigServiceFactory::build();
        }
        
        $this->_serviceRemittanceConfig->setUpdatedBy($this->getUpdatedBy());
        $this->_serviceRemittanceConfig->setIpAddress($this->getIpAddress());
        
        return $this->_serviceRemittanceConfig;
    }
    
    private function _getServiceAccount() {
        if( !$this->_serviceAccount )
        {
            $this->_serviceAccount = AccountServiceFactory::build();
        }
        
        return $this->_serviceAccount;
    }
    
    private function _getServiceCorporateService() {
        $service = CorporateServServiceFactory::build();
        $service->setUpdatedBy($this->getUpdatedBy());
        $service->setIpAddress($this->getIpAddress());
        return $service;
    }
    
    private function _getServiceSystemCodeService() {
        $service = SystemCodeServiceFactory::build();
        $service->setUpdatedBy($this->getUpdatedBy());
        $service->setIpAddress($this->getIpAddress());
        return $service;
    }
    
    private function _getServicePaymentModeMicroService() {
        $service = PaymentServiceFactory::build();
//        $service->setUpdatedBy($this->getUpdatedBy());
//        $service->setIpAddress($this->getIpAddress());
        return $service;
    }

    private function _getServicePaymentModeFeeGroupService() {
        $service = PaymentModeFeeGroupServiceFactory::build();
        $service->setUpdatedBy($this->getUpdatedBy());
        $service->setIpAddress($this->getIpAddress());
        return $service;
    }

    private function _getServicePaymentModeFeeService() {
        $service = PaymentModeFeeServiceFactory::build();
        $service->setUpdatedBy($this->getUpdatedBy());
        $service->setIpAddress($this->getIpAddress());
        return $service;
    }
    
    private function _getServicePaymentModeCostGroupService() {
        $service = PaymentModeCostGroupServiceFactory::build();
        $service->setUpdatedBy($this->getUpdatedBy());
        $service->setIpAddress($this->getIpAddress());
        return $service;
    }

    private function _getServicePaymentModeCostService() {
        $service = PaymentModeCostServiceFactory::build();
        $service->setUpdatedBy($this->getUpdatedBy());
        $service->setIpAddress($this->getIpAddress());
        return $service;
    }
    
    
    
    public function addCollectionMode($remittanceConfigurationId, $paymentCode, $isDefault)
    {
        // 1. Get remittance_service by remittance_conguration_id
        $serviceRemittanceConfig = $this->_getServiceRemittanceConfig();
        $entityRemittanceConfig = $serviceRemittanceConfig->getRemittanceConfigById($remittanceConfigurationId);
        if($entityRemittanceConfig == false)
        {
            $this->setResponseCode($serviceRemittanceConfig->getResponseCode());
            return false;
        }
        $cashInCorporateServiceId = $entityRemittanceConfig->getCashInCorporateServiceId();
        $cashOutCorporateServiceId = $entityRemittanceConfig->getCashOutCorporateServiceId();
        
        /**
         * already doing in $serviceRemittanceConfig->getRemittanceConfigById function
         */
        // 2.Get and combine in/out corporate_service  by remittance_configuration.in_corporate_service_id, remittance_configuration.out_corporate_service_id
        
        // 3. Validate payment_code from payment service
        $servicePaymentMode = $this->_getServicePaymentModeMicroService();
        $paymentInfo = $servicePaymentMode->getPaymentInfo($paymentCode);
        if($paymentInfo == false)
        {
            $this->setResponseCode(MessageCode::CODE_ADD_CORPORATE_PAYMENT_MODE_FAIL);
            return false;
        }
        
        $entityPaymentMode = new PaymentMode();
        $entityPaymentMode->setId(GuidGenerator::generate());
        $entityPaymentMode->setDirection(PaymentDirection::OUT);
        $entityPaymentMode->setCorporateServiceId($cashOutCorporateServiceId);
        $entityPaymentMode->setIsDefault($isDefault);
        $entityPaymentMode->setPaymentCode($paymentCode);
        $entityPaymentMode->setCreatedBy($this->getUpdatedBy());
        
        // 4. Validate if payment_code does not exist in corporate_service_payment_mode by out corporate_service_id
        // 5. If is_default = 1, update is_default = 0 for other corporate_service_payment_mode by out corporate_service_id
        // 6. Insert into corporate_service_payment_mode
        // - corporate_service_id = out corporate_service_id
        // - direction = 'OUT'
        // - is_default = is_default
        // - payment_code = payment_code
        // - is_active = 0

        return $this->savePaymentMode($entityPaymentMode);
    }
    
    public function getCollectionModeListing($limit, $page, $remittanceConfigurationId) 
    {
        
        $service_remittance_config = $this->_getServiceRemittanceConfig();
        $entityRemittanceConfig = $service_remittance_config->getRemittanceConfigById($remittanceConfigurationId);
        
        // 1. Get remittance_service by remittance_conguration_id
        if($entityRemittanceConfig == false)
        {
            $this->setResponseCode($service_remittance_config->getResponseCode());
            return false;
        }
        
        $cashInCorporateServiceId = $entityRemittanceConfig->getCashInCorporateServiceId();
        $cashOutCorporateServiceId = $entityRemittanceConfig->getCashOutCorporateServiceId();
        
        /**
         * already doing in $serviceRemittanceConfig->getRemittanceConfigById function
         */
//        // 2. Get and combine in/out corporate_service  by remittance_configuration.in_corporate_service_id, remittance_configuration.out_corporate_service_id
//        $service_corprate_service = $this->_getServiceCorporateService();
//        // 2.1. get cash in corporate service
//        $entityCashInCorporateService = $service_corprate_service->getCorporateService($cashInCorporateServiceId);
//        if($entityCashInCorporateService == false)
//        {
//            $this->setResponseCode($service_corprate_service->getResponseCode());
//            return false;
//        }
//        
//        // 2.2 get cash out corporate service
//        $entityCashOutCorporateService = $service_corprate_service->getCorporateService($cashOutCorporateServiceId);
//        if($entityCashInCorporateService == false)
//        {
//            $this->setResponseCode($service_corprate_service->getResponseCode());
//            return false;
//        }
        
        // 3. Get Collection Mode listing from corporate_service_payment_mode by out corporate_service_id
        if ($listPaymentModeObject = $this->getRepository()->findListByCorporeateServiceId($limit, $page, array($cashOutCorporateServiceId), NULL, NULL, NULL))
        {
            // get payment_mode_name (all payment mode list from microservice)
            $servicePaymentModeMicro = $this->_getServicePaymentModeMicroService();
            $listPaymentModeMicro = $servicePaymentModeMicro->getAllPaymentModes();
            
            $servicePaymentModeFeeGroup = $this->_getServicePaymentModeFeeGroupService();
            $servicePaymentModeFee = $this->_getServicePaymentModeFeeService();
            $servicePaymentModeCostGroup = $this->_getServicePaymentModeCostGroupService();
            $servicePaymentModeCost = $this->_getServicePaymentModeCostService();
            
            // all approved payment fee/cost group ids
            $approvedPaymentModeFeeGroupIds = array();
            $approvedPaymentModeCostGroupIds = array();
//            // all pending payment fee/cost group ids
//            $pendingPaymentModeFeeGroupIds = array();
//            $pendingPaymentModeCostGroupIds = array();
            
            $approvedPaymentModeFeeGroupObject = new PaymentModeFeeGroupCollection();
            $approvedPaymentModeCostGroupObject = new PaymentModeCostGroupCollection();
//            $pendingPaymentModeFeeGroupObject = new PaymentModeFeeGroupCollection();
//            $pendingPaymentModeCostGroupObject = new PaymentModeCostGroupCollection();
            
            foreach ($listPaymentModeObject->result as $entityPaymentMode) {
                $_paymentModeIds = array($entityPaymentMode->getId());
                
                // get last approved fee group by paymentModeId
                if( $entityPaymentModeFeeGroup = $servicePaymentModeFeeGroup->getLastFeeGroupInfo($_paymentModeIds, PaymentModeFeeGroupStatus::CODE_APPROVED) )
                {
                    array_push($approvedPaymentModeFeeGroupIds, $entityPaymentModeFeeGroup->getId());
                    $approvedPaymentModeFeeGroupObject->addData($entityPaymentModeFeeGroup);
                }
                // get last approved cost group by paymentModeId
                if( $entityPaymentModeCostGroup = $servicePaymentModeCostGroup->getLastCostGroupInfo($_paymentModeIds, PaymentModeCostGroupStatus::CODE_APPROVED) )
                {
                    array_push($approvedPaymentModeCostGroupIds, $entityPaymentModeCostGroup->getId());
                    $approvedPaymentModeCostGroupObject->addData($entityPaymentModeCostGroup);
                }
                
//                // get last updated fee group by paymentModeId
//                if( $entityPaymentModeFeeGroup = $servicePaymentModeFeeGroup->getLastFeeGroupInfo($_paymentModeIds) )
//                {
//                    array_push($pendingPaymentModeFeeGroupIds, $entityPaymentModeFeeGroup->getId());
//                    $pendingPaymentModeFeeGroupObject->addData($entityPaymentModeFeeGroup);
//                }
//                // get last updated cost group by paymentModeId
//                if( $entityPaymentModeCostGroup = $servicePaymentModeCostGroup->getLastCostGroupInfo($_paymentModeIds) )
//                {
//                    array_push($pendingPaymentModeCostGroupIds, $entityPaymentModeCostGroup->getId());
//                    $pendingPaymentModeCostGroupObject->addData($entityPaymentModeCostGroup);
//                }
            }
            
            // all approved payment fee list
            if( count($approvedPaymentModeFeeGroupIds) > 0 && $listApprovedPaymentModeFeeObject = $servicePaymentModeFee->getListByGroupIds(10000, 1, $approvedPaymentModeFeeGroupIds) )
            {
                $approvedPaymentModeFeeGroupObject->joinPaymentModeFeeItems($listApprovedPaymentModeFeeObject->result);
                $approvedPaymentModeFeeGroupObject->rewind();
            }
            
            // all approved payment cost list
            if( count($approvedPaymentModeCostGroupIds) > 0 && $listApprovedPaymentModeCostObject = $servicePaymentModeCost->getListByGroupIds(1000, 1, $approvedPaymentModeCostGroupIds) )
            {
                $approvedPaymentModeCostGroupObject->joinPaymentModeCostItems($listApprovedPaymentModeCostObject->result);
                $approvedPaymentModeCostGroupObject->rewind();
            }
            
//            // all pending payment fee list
//            if( count($pendingPaymentModeFeeGroupIds) > 0 && $listPendingPaymentModeFeeObject = $servicePaymentModeFee->getListByGroupIds(10000, 1, $pendingPaymentModeFeeGroupIds) )
//            {
//                $pendingPaymentModeFeeGroupObject->joinPaymentModeFeeItems($listPendingPaymentModeFeeObject->result);
//                $pendingPaymentModeFeeGroupObject->rewind();
//            }
//            
//            // all pending payment cost list
//            if( count($pendingPaymentModeCostGroupIds) > 0 && $listPendingPaymentModeCostObject = $servicePaymentModeCost->getListByGroupIds(1000, 1, $pendingPaymentModeCostGroupIds) )
//            {
//                $pendingPaymentModeCostGroupObject->joinPaymentModeCostItems($listPendingPaymentModeCostObject->result);
//                $pendingPaymentModeCostGroupObject->rewind();
//            }
            
            $result = array();
            
            foreach ($listPaymentModeObject->result as $entityPaymentMode) {
                
                $tempArr = $entityPaymentMode->jsonSerialize();
                $tempArr['payment_mode_name'] = NULL;
                $tempArr['payment_mode_fee'] = NULL;
                $tempArr['payment_mode_cost'] = NULL;
                $tempArr['payment_mode_last_updated_at'] = NULL;
                
                // get payment mode name
                if( $entityPaymentModeMicro = $this->_getPaymentModeInfo($entityPaymentMode->getPaymentCode(), $listPaymentModeMicro) )
                {
//                    $tempArr['payment_mode_id'] = $entityPaymentModeMicro->id;
                    $tempArr['payment_mode_name'] = $entityPaymentModeMicro->name;
                }
                
                $entityApprovedPaymentModeFeeGroupInfo = $this->_getPaymentModeGroupInfo($entityPaymentMode->getId(), $approvedPaymentModeFeeGroupObject);
                $entityApprovedPaymentModeCostGroupInfo = $this->_getPaymentModeGroupInfo($entityPaymentMode->getId(), $approvedPaymentModeCostGroupObject);
//                $entityPendingPaymentModeFeeGroupInfo = $this->_getPaymentModeGroupInfo($entityPaymentMode->getId(), $pendingPaymentModeFeeGroupObject);
//                $entityPendingPaymentModeCostGroupInfo = $this->_getPaymentModeGroupInfo($entityPaymentMode->getId(), $pendingPaymentModeCostGroupObject);
                
                // get max fee from approved list
//                $tempArr['payment_mode_fee_group_id'] = $entityApprovedPaymentModeFeeGroupInfo ? $entityApprovedPaymentModeFeeGroupInfo->getId() : NULL;
//                $tempArr['payment_mode_fee_group_is_active'] = $entityApprovedPaymentModeFeeGroupInfo ? $entityApprovedPaymentModeFeeGroupInfo->getIsActive() : NULL;
//                $tempArr['payment_mode_fee_group_status'] = $entityApprovedPaymentModeFeeGroupInfo ? ($entityApprovedPaymentModeFeeGroupInfo->getStatus() ? $entityApprovedPaymentModeFeeGroupInfo->getStatus()->getCode() : NULL) : NULL;

                $lastUpdateds = array();
                
                if($entityApprovedPaymentModeFeeGroupInfo)
                {
                    $items = $entityApprovedPaymentModeFeeGroupInfo->getPaymentModeFeeItems();
                    if($items)
                    {
                        $maxFee = 0;
                        foreach ($items as $value) {
                            if($value->getFee() > $maxFee)
                                $maxFee = $value->getFee();
                        }
                        $tempArr['payment_mode_fee'] = $maxFee;
                    }
                    if($entityApprovedPaymentModeFeeGroupInfo->getApproveRejectAt() != NULL)
                        $lastUpdateds[] = $entityApprovedPaymentModeFeeGroupInfo->getApproveRejectAt()->getUnix();
                }
                
                // get max cost from approved list
//                $tempArr['payment_mode_cost_group_id'] = $entityApprovedPaymentModeCostGroupInfo ? $entityApprovedPaymentModeCostGroupInfo->getId() : NULL;
//                $tempArr['payment_mode_cost_group_is_active'] = $entityApprovedPaymentModeCostGroupInfo ? $entityApprovedPaymentModeCostGroupInfo->getIsActive() : NULL;
//                $tempArr['payment_mode_cost_group_status'] = $entityApprovedPaymentModeCostGroupInfo ? ($entityApprovedPaymentModeCostGroupInfo->getStatus() ? $entityApprovedPaymentModeCostGroupInfo->getStatus()->getCode() : NULL) : NULL;
                if($entityApprovedPaymentModeCostGroupInfo)
                {
                    $items = $entityApprovedPaymentModeCostGroupInfo->getPaymentModeCostItems();
                    if($items)
                    {
                        $maxCost = 0;
                        foreach ($items as $value) {
                            if($value->getCost() > $maxCost)
                                $maxCost = $value->getCost();
                        }
                        $tempArr['payment_mode_cost'] = $maxCost;
                    }
                    if($entityApprovedPaymentModeCostGroupInfo->getApproveRejectAt() != NULL)
                        $lastUpdateds[] = $entityApprovedPaymentModeCostGroupInfo->getApproveRejectAt()->getUnix();
                }
                
//                if($entityPendingPaymentModeFeeGroupInfo != NULL && $entityPendingPaymentModeFeeGroupInfo->getCreatedAt() != NULL)
//                {
//                    $lastUpdateds[] = $entityPendingPaymentModeFeeGroupInfo->getCreatedAt()->getUnix();
//                }
//                
//                if($entityPendingPaymentModeCostGroupInfo != NULL && $entityPendingPaymentModeCostGroupInfo->getCreatedAt() != NULL)
//                {
//                    $lastUpdateds[] = $entityPendingPaymentModeCostGroupInfo->getCreatedAt()->getUnix();
//                }

                if(count($lastUpdateds) > 0)
                {
                    rsort($lastUpdateds);
                    $tempArr['payment_mode_last_updated_at'] = IappsDateTime::fromUnix($lastUpdateds[0])->getString();
                }
                
                $result[] = $tempArr;
            }
            
            $listPaymentModeObject->result = $result;
            
            $this->setResponseCode(MessageCode::CODE_LIST_CORPORATE_PAYMENT_MODE_SUCCESS);
            return $listPaymentModeObject;
        }

        $this->setResponseCode(MessageCode::CODE_LIST_CORPORATE_PAYMENT_MODE_FAIL);
        return false;
    }
    
    public function addPaymentMode($remittanceConfigurationId, $paymentCode, $isDefault)
    {
        // 1. Get remittance_service by remittance_conguration_id
        $serviceRemittanceConfig = $this->_getServiceRemittanceConfig();
        $entityRemittanceConfig = $serviceRemittanceConfig->getRemittanceConfigById($remittanceConfigurationId);
        if($entityRemittanceConfig == false)
        {
            $this->setResponseCode($serviceRemittanceConfig->getResponseCode());
            return false;
        }
        $cashInCorporateServiceId = $entityRemittanceConfig->getCashInCorporateServiceId();
        $cashOutCorporateServiceId = $entityRemittanceConfig->getCashOutCorporateServiceId();
        
        /**
         * already doing in $serviceRemittanceConfig->getRemittanceConfigById function
         */
        // 2.Get and combine in/out corporate_service  by remittance_configuration.in_corporate_service_id, remittance_configuration.out_corporate_service_id
        
        // 3. Validate payment_code from payment service
        $servicePaymentMode = $this->_getServicePaymentModeMicroService();
        $paymentInfo = $servicePaymentMode->getPaymentInfo($paymentCode);
        if($paymentInfo == false)
        {
            $this->setResponseCode(MessageCode::CODE_ADD_CORPORATE_PAYMENT_MODE_FAIL);
            return false;
        }
        
        $entityPaymentMode = new PaymentMode();
        $entityPaymentMode->setId(GuidGenerator::generate());
        $entityPaymentMode->setDirection(PaymentDirection::IN);
        $entityPaymentMode->setCorporateServiceId($cashInCorporateServiceId);
        $entityPaymentMode->setIsDefault($isDefault);
        $entityPaymentMode->setPaymentCode($paymentCode);
        $entityPaymentMode->setCreatedBy($this->getUpdatedBy());
        
        // 4. Validate if payment_code does not exist in corporate_service_payment_mode by in corporate_service_id
        // 5. If is_default = 1, update is_default = 0 for other corporate_service_payment_mode by in corporate_service_id
        // 6. Insert into corporate_service_payment_mode
        // - corporate_service_id = in corporate_service_id
        // - direction = 'IN'
        // - is_default = is_default
        // - payment_code = payment_code
        // - is_active = 0

        return $this->savePaymentMode($entityPaymentMode);
    }
    
    public function getPaymentModeListing($limit, $page, $remittanceConfigurationId) 
    {
        $service_remittance_config = $this->_getServiceRemittanceConfig();
        $entityRemittanceConfig = $service_remittance_config->getRemittanceConfigById($remittanceConfigurationId);
        
        // 1. Get remittance_service by remittance_conguration_id
        if($entityRemittanceConfig == false)
        {
            $this->setResponseCode($service_remittance_config->getResponseCode());
            return false;
        }
        
        $cashInCorporateServiceId = $entityRemittanceConfig->getCashInCorporateServiceId();
        $cashOutCorporateServiceId = $entityRemittanceConfig->getCashOutCorporateServiceId();
        
        /**
         * already doing in $serviceRemittanceConfig->getRemittanceConfigById function
         */
//        // 2. Get and combine in/out corporate_service  by remittance_configuration.in_corporate_service_id, remittance_configuration.out_corporate_service_id
//        $service_corprate_service = $this->_getServiceCorporateService();
//        // 2.1. get cash in corporate service
//        $entityCashInCorporateService = $service_corprate_service->getCorporateService($cashInCorporateServiceId);
//        if($entityCashInCorporateService == false)
//        {
//            $this->setResponseCode($service_corprate_service->getResponseCode());
//            return false;
//        }
//        
//        // 2.2 get cash out corporate service
//        $entityCashOutCorporateService = $service_corprate_service->getCorporateService($cashOutCorporateServiceId);
//        if($entityCashInCorporateService == false)
//        {
//            $this->setResponseCode($service_corprate_service->getResponseCode());
//            return false;
//        }
        
        // 3. Get Collection Mode listing from corporate_service_payment_mode by out corporate_service_id
        if ($listPaymentModeObject = $this->getRepository()->findListByCorporeateServiceId($limit, $page, array($cashInCorporateServiceId), NULL, NULL, NULL))
        {
            // get payment_mode_name (all payment mode list from microservice)
            $servicePaymentModeMicro = $this->_getServicePaymentModeMicroService();
            $listPaymentModeMicro = $servicePaymentModeMicro->getAllPaymentModes();
            
            $servicePaymentModeFeeGroup = $this->_getServicePaymentModeFeeGroupService();
            $servicePaymentModeFee = $this->_getServicePaymentModeFeeService();
            $servicePaymentModeCostGroup = $this->_getServicePaymentModeCostGroupService();
            $servicePaymentModeCost = $this->_getServicePaymentModeCostService();
            
            // all approved payment fee/cost group ids
            $approvedPaymentModeFeeGroupIds = array();
            $approvedPaymentModeCostGroupIds = array();
//            // all pending payment fee/cost group ids
//            $pendingPaymentModeFeeGroupIds = array();
//            $pendingPaymentModeCostGroupIds = array();
            
            $approvedPaymentModeFeeGroupObject = new PaymentModeFeeGroupCollection();
            $approvedPaymentModeCostGroupObject = new PaymentModeCostGroupCollection();
//            $pendingPaymentModeFeeGroupObject = new PaymentModeFeeGroupCollection();
//            $pendingPaymentModeCostGroupObject = new PaymentModeCostGroupCollection();
            
            foreach ($listPaymentModeObject->result as $entityPaymentMode) {
                $_paymentModeIds = array($entityPaymentMode->getId());
                
                // get last approved fee group by paymentModeId
                if( $entityPaymentModeFeeGroup = $servicePaymentModeFeeGroup->getLastFeeGroupInfo($_paymentModeIds, PaymentModeFeeGroupStatus::CODE_APPROVED) )
                {
                    array_push($approvedPaymentModeFeeGroupIds, $entityPaymentModeFeeGroup->getId());
                    $approvedPaymentModeFeeGroupObject->addData($entityPaymentModeFeeGroup);
                }
                // get last approved cost group by paymentModeId
                if( $entityPaymentModeCostGroup = $servicePaymentModeCostGroup->getLastCostGroupInfo($_paymentModeIds, PaymentModeCostGroupStatus::CODE_APPROVED) )
                {
                    array_push($approvedPaymentModeCostGroupIds, $entityPaymentModeCostGroup->getId());
                    $approvedPaymentModeCostGroupObject->addData($entityPaymentModeCostGroup);
                }
                
//                // get last updated fee group by paymentModeId
//                if( $entityPaymentModeFeeGroup = $servicePaymentModeFeeGroup->getLastFeeGroupInfo($_paymentModeIds) )
//                {
//                    array_push($pendingPaymentModeFeeGroupIds, $entityPaymentModeFeeGroup->getId());
//                    $pendingPaymentModeFeeGroupObject->addData($entityPaymentModeFeeGroup);
//                }
//                // get last updated cost group by paymentModeId
//                if( $entityPaymentModeCostGroup = $servicePaymentModeCostGroup->getLastCostGroupInfo($_paymentModeIds) )
//                {
//                    array_push($pendingPaymentModeCostGroupIds, $entityPaymentModeCostGroup->getId());
//                    $pendingPaymentModeCostGroupObject->addData($entityPaymentModeCostGroup);
//                }
            }
            
            // all approved payment fee list
            if( count($approvedPaymentModeFeeGroupIds) > 0 && $listApprovedPaymentModeFeeObject = $servicePaymentModeFee->getListByGroupIds(10000, 1, $approvedPaymentModeFeeGroupIds) )
            {
                $approvedPaymentModeFeeGroupObject->joinPaymentModeFeeItems($listApprovedPaymentModeFeeObject->result);
                $approvedPaymentModeFeeGroupObject->rewind();
            }
            
            // all approved payment cost list
            if( count($approvedPaymentModeCostGroupIds) > 0 && $listApprovedPaymentModeCostObject = $servicePaymentModeCost->getListByGroupIds(1000, 1, $approvedPaymentModeCostGroupIds) )
            {
                $approvedPaymentModeCostGroupObject->joinPaymentModeCostItems($listApprovedPaymentModeCostObject->result);
                $approvedPaymentModeCostGroupObject->rewind();
            }
            
//            // all pending payment fee list
//            if( count($pendingPaymentModeFeeGroupIds) > 0 && $listPendingPaymentModeFeeObject = $servicePaymentModeFee->getListByGroupIds(10000, 1, $pendingPaymentModeFeeGroupIds) )
//            {
//                $pendingPaymentModeFeeGroupObject->joinPaymentModeFeeItems($listPendingPaymentModeFeeObject->result);
//                $pendingPaymentModeFeeGroupObject->rewind();
//            }
//            
//            // all pending payment cost list
//            if( count($pendingPaymentModeCostGroupIds) > 0 && $listPendingPaymentModeCostObject = $servicePaymentModeCost->getListByGroupIds(1000, 1, $pendingPaymentModeCostGroupIds) )
//            {
//                $pendingPaymentModeCostGroupObject->joinPaymentModeCostItems($listPendingPaymentModeCostObject->result);
//                $pendingPaymentModeCostGroupObject->rewind();
//            }
            
            $result = array();
            
            foreach ($listPaymentModeObject->result as $entityPaymentMode) {
                
                $tempArr = $entityPaymentMode->jsonSerialize();
                $tempArr['payment_mode_name'] = NULL;
                $tempArr['payment_mode_fee'] = NULL;
                $tempArr['payment_mode_cost'] = NULL;
                $tempArr['payment_mode_last_updated_at'] = NULL;
                
                // get payment mode name
                if( $entityPaymentModeMicro = $this->_getPaymentModeInfo($entityPaymentMode->getPaymentCode(), $listPaymentModeMicro) )
                {
//                    $tempArr['payment_mode_id'] = $entityPaymentModeMicro->id;
                    $tempArr['payment_mode_name'] = $entityPaymentModeMicro->name;
                }
                
                $entityApprovedPaymentModeFeeGroupInfo = $this->_getPaymentModeGroupInfo($entityPaymentMode->getId(), $approvedPaymentModeFeeGroupObject);
                $entityApprovedPaymentModeCostGroupInfo = $this->_getPaymentModeGroupInfo($entityPaymentMode->getId(), $approvedPaymentModeCostGroupObject);
//                $entityPendingPaymentModeFeeGroupInfo = $this->_getPaymentModeGroupInfo($entityPaymentMode->getId(), $pendingPaymentModeFeeGroupObject);
//                $entityPendingPaymentModeCostGroupInfo = $this->_getPaymentModeGroupInfo($entityPaymentMode->getId(), $pendingPaymentModeCostGroupObject);
                
                // get max fee from approved list
//                $tempArr['payment_mode_fee_group_id'] = $entityApprovedPaymentModeFeeGroupInfo ? $entityApprovedPaymentModeFeeGroupInfo->getId() : NULL;
//                $tempArr['payment_mode_fee_group_is_active'] = $entityApprovedPaymentModeFeeGroupInfo ? $entityApprovedPaymentModeFeeGroupInfo->getIsActive() : NULL;
//                $tempArr['payment_mode_fee_group_status'] = $entityApprovedPaymentModeFeeGroupInfo ? ($entityApprovedPaymentModeFeeGroupInfo->getStatus() ? $entityApprovedPaymentModeFeeGroupInfo->getStatus()->getCode() : NULL) : NULL;

                $lastUpdateds = array();
                
                if($entityApprovedPaymentModeFeeGroupInfo)
                {
                    $items = $entityApprovedPaymentModeFeeGroupInfo->getPaymentModeFeeItems();
                    if($items)
                    {
                        $maxFee = 0;
                        foreach ($items as $value) {
                            if($value->getFee() > $maxFee)
                                $maxFee = $value->getFee();
                        }
                        $tempArr['payment_mode_fee'] = $maxFee;
                    }
                    
                    if($entityApprovedPaymentModeFeeGroupInfo->getApproveRejectAt() != NULL)
                        $lastUpdateds[] = $entityApprovedPaymentModeFeeGroupInfo->getApproveRejectAt()->getUnix();
                }
                
                // get max cost from approved list
//                $tempArr['payment_mode_cost_group_id'] = $entityApprovedPaymentModeCostGroupInfo ? $entityApprovedPaymentModeCostGroupInfo->getId() : NULL;
//                $tempArr['payment_mode_cost_group_is_active'] = $entityApprovedPaymentModeCostGroupInfo ? $entityApprovedPaymentModeCostGroupInfo->getIsActive() : NULL;
//                $tempArr['payment_mode_cost_group_status'] = $entityApprovedPaymentModeCostGroupInfo ? ($entityApprovedPaymentModeCostGroupInfo->getStatus() ? $entityApprovedPaymentModeCostGroupInfo->getStatus()->getCode() : NULL) : NULL;
                if($entityApprovedPaymentModeCostGroupInfo)
                {
                    $items = $entityApprovedPaymentModeCostGroupInfo->getPaymentModeCostItems();
                    if($items)
                    {
                        $maxCost = 0;
                        foreach ($items as $value) {
                            if($value->getCost() > $maxCost)
                                $maxCost = $value->getCost();
                        }
                        $tempArr['payment_mode_cost'] = $maxCost;
                    }
                    
                    if($entityApprovedPaymentModeCostGroupInfo->getApproveRejectAt() != NULL)
                        $lastUpdateds[] = $entityApprovedPaymentModeCostGroupInfo->getApproveRejectAt()->getUnix();
                }
                
                
//                if($entityPendingPaymentModeFeeGroupInfo != NULL && $entityPendingPaymentModeFeeGroupInfo->getCreatedAt() != NULL)
//                {
//                    $lastUpdateds[] = $entityPendingPaymentModeFeeGroupInfo->getCreatedAt()->getUnix();
//                }
//                
//                if($entityPendingPaymentModeCostGroupInfo != NULL && $entityPendingPaymentModeCostGroupInfo->getCreatedAt() != NULL)
//                {
//                    $lastUpdateds[] = $entityPendingPaymentModeCostGroupInfo->getCreatedAt()->getUnix();
//                }

                if(count($lastUpdateds) > 0)
                {
                    rsort($lastUpdateds);
                    $tempArr['payment_mode_last_updated_at'] = IappsDateTime::fromUnix($lastUpdateds[0])->getString();
                }
                
                $result[] = $tempArr;
            }
            
            $listPaymentModeObject->result = $result;
            
            $this->setResponseCode(MessageCode::CODE_LIST_CORPORATE_PAYMENT_MODE_SUCCESS);
            return $listPaymentModeObject;
        }
    }
    
    public function addCollectionModeFeeGroup($remittanceConfigurationId, $corporateServicePaymentModeId, $name, array $fees)
    {
        // 1. Get corporate_service_payment_mode by corporate_service_payment_mode_id
        $entityPaymentMode = $this->getRepository()->findById($corporateServicePaymentModeId);
        if($entityPaymentMode == false)
        {
            $this->setResponseCode(MessageCode::CODE_ADD_CORPORATE_PAYMENT_MODE_FEE_FAIL);
            return false;
        }
        
        $serviceRemittanceConfig = $this->_getServiceRemittanceConfig();
        $entityRemittanceConfig = $serviceRemittanceConfig->getRemittanceConfigById($remittanceConfigurationId);
        // 2. Get remittance_service by remittance_conguration_id
        if($entityRemittanceConfig == false)
        {
            $this->setResponseCode($serviceRemittanceConfig->getResponseCode());
            return false;
        }
        
        $corporateServiceId = $entityPaymentMode->getCorporateServiceId();
        
        $cashInCorporateServiceId = $entityRemittanceConfig->getCashInCorporateServiceId();
        $cashOutCorporateServiceId = $entityRemittanceConfig->getCashOutCorporateServiceId();
        
        /**
         * already doing in $serviceRemittanceConfig->getRemittanceConfigById function
         */
//        // 3. Get and combine in/out corporate_service  by remittance_configuration.in_corporate_service_id, remittance_configuration.out_corporate_service_id
//        $serviceCorprateService = $this->_getServiceCorporateService();
//        // 3.1. get cash in corporate service
//        $entityCashInCorporateService = $serviceCorprateService->getCorporateService($cashInCorporateServiceId);
//        if($entityCashInCorporateService == false)
//        {
//            $this->setResponseCode($serviceCorprateService->getResponseCode());
//            return false;
//        }
//        
//        // 3.2 get cash out corporate service
//        $entityCashOutCorporateService = $serviceCorprateService->getCorporateService($cashOutCorporateServiceId);
//        if($entityCashInCorporateService == false)
//        {
//            $this->setResponseCode($serviceCorprateService->getResponseCode());
//            return false;
//        }
        
        //4. Validate if corporate_service_payment_mode is child of out corporate_service_id
        if($corporateServiceId != $cashOutCorporateServiceId)
        {
            $this->setResponseCode(MessageCode::CODE_ADD_CORPORATE_PAYMENT_MODE_FEE_FAIL);
            return false;
        }
        
        //5. Insert into corporate_service_payment_mode_fee
        //- corporate_service_payment_mode_id = corporate_service_payment_mode
        //- name = name
        //- fee_type_id = corporate_service_fee
        //- status = pending
        //- is_active = 0
        
        //payment_fee(payment mode)
        //corporate_service(collection mode)
        $serviceSystemCode = $this->_getServiceSystemCodeService();
        $fee_type = $serviceSystemCode->getByCode(FeeType::SERVICE_FEE,  FeeType::getSystemGroupCode());
        if( $fee_type == false )
        {
            $this->setResponseCode(MessageCode::CODE_ADD_CORPORATE_PAYMENT_MODE_FEE_GROUP_FAIL);
            return false;
        }
        $status = $serviceSystemCode->getByCode(PaymentModeFeeGroupStatus::CODE_PENDING, PaymentModeFeeGroupStatus::getSystemGroupCode());
        if( $status == false )
        {
            $this->setResponseCode(MessageCode::CODE_ADD_CORPORATE_PAYMENT_MODE_FEE_GROUP_FAIL);
            return false;
        }
        
        $paymentModeFeeGroupId = GuidGenerator::generate();
        $entityPaymentModeFeeGroup = new PaymentModeFeeGroup();
        $entityPaymentModeFeeGroup->setId($paymentModeFeeGroupId);
        $entityPaymentModeFeeGroup->setCorporateServicePaymentModeId($corporateServicePaymentModeId);
        $entityPaymentModeFeeGroup->setFeeType($fee_type);
        $entityPaymentModeFeeGroup->setName($name);
        $entityPaymentModeFeeGroup->setStatus($status);
        
        $collectionPaymentModeFee = new PaymentModeFeeCollection();
        
        
        $isFlat = false;
        $isLessThan = false;
        $isRange = false;
        $isGreaterThan = false;
        $flatNum = $lessNum = $rangeNum = $greaterNum = 0;
        
        $lastAmount = false;
        
        foreach ($fees as $value) {
            
//            $mutitier_type = $value['multitier_type'];
//            $ref_value1 = $value['ref_value1'];
//            $ref_value2 = $value['ref_value2'];
//            $fee = $value['fee'];
            
            $multitier_type = $value->multitier_type;
            $ref_value1 = $value->ref_value1;
            $ref_value2 = $value->ref_value2;
            $fee = $value->fee;
            
            //need validate multitier_type ?
            
            if(empty($fee) && $fee !== 0)
            {
                $this->setResponseCode(MessageCode::CODE_INVALID_CORPORATE_PAYMENT_MODE_FEE_AMOUNT);
                return false;
            }
            
            if($multitier_type == PaymentModeFeeMultitierType::CODE_FLAT)
            {
                $isFlat = true;
                $flatNum++;
                
//                if(empty($ref_value1) && $ref_value1 !== 0)
//                {
//                    $this->setResponseCode(MessageCode::CODE_INVALID_CORPORATE_PAYMENT_MODE_FEE_AMOUNT);
//                    return false;
//                }
            }
            else if($multitier_type == PaymentModeFeeMultitierType::CODE_LESSTHAN)
            {
                if(empty($ref_value1) && $ref_value1 !== 0)
                {
                    $this->setResponseCode(MessageCode::CODE_INVALID_CORPORATE_PAYMENT_MODE_FEE_AMOUNT);
                    return false;
                }
                
                $isLessThan = true;
                $lessNum++;
                $lastAmount = $ref_value1;
            }
            else if($multitier_type == PaymentModeFeeMultitierType::CODE_RANGE)
            {
                if($ref_value1 != $lastAmount)
                {
                    $this->setResponseCode(MessageCode::CODE_INVALID_CORPORATE_PAYMENT_MODE_FEE_AMOUNT);
                    return false;
                }
                
                if((empty($ref_value1) && $ref_value1 !== 0) || (empty($ref_value2) && $ref_value2 !== 0))
                {
                    $this->setResponseCode(MessageCode::CODE_INVALID_CORPORATE_PAYMENT_MODE_FEE_AMOUNT);
                    return false;
                }
                
                $isRange = true;
                $rangeNum++;
                $lastAmount = $ref_value2;
            }
            else if($multitier_type == PaymentModeFeeMultitierType::CODE_GREATERTHAN)
            {
                if($ref_value1 != $lastAmount)
                {
                    $this->setResponseCode(MessageCode::CODE_INVALID_CORPORATE_PAYMENT_MODE_FEE_AMOUNT);
                    return false;
                }
                
                if(empty($ref_value1) && $ref_value1 !== 0)
                {
                    $this->setResponseCode(MessageCode::CODE_INVALID_CORPORATE_PAYMENT_MODE_FEE_AMOUNT);
                    return false;
                }
                
                $isGreaterThan = true;
                $greaterNum++;
                $lastAmount = $ref_value1;
            }
            
            $entityPaymentModeFee = new PaymentModeFee();
            $entityPaymentModeFee->setId(GuidGenerator::generate());
            $entityPaymentModeFee->setCorporateServicePaymentModeFeeGroupId($paymentModeFeeGroupId);
            $entityPaymentModeFee->setMultitierType($multitier_type);
            if($ref_value1 !== "")
                $entityPaymentModeFee->setReferenceValue1($ref_value1);
            if($ref_value2 !== "")
                $entityPaymentModeFee->setReferenceValue2($ref_value2);
            $entityPaymentModeFee->setIsPercentage(0);
            if($fee !== "")
                $entityPaymentModeFee->setFee($fee);
            
            $collectionPaymentModeFee->addData($entityPaymentModeFee);
        }
        
        if($flatNum > 0)
        {
            if($isLessThan || $isRange || $isGreaterThan)
            {
                $this->setResponseCode(MessageCode::CODE_INVALID_CORPORATE_PAYMENT_MODE_FEE_MULTITIER_TYPE);
                return false;
            }
        }
        else
        {
            if(($isLessThan && $lessNum > 1) || ($isGreaterThan && $greaterNum > 1))
            {
                $this->setResponseCode(MessageCode::CODE_INVALID_CORPORATE_PAYMENT_MODE_FEE_MULTITIER_TYPE);
                return false;
            }

        }
        
        $entityPaymentModeFeeGroup->setPaymentModeFeeItems($collectionPaymentModeFee);
        
        return $this->saveFeeGroup($entityPaymentModeFeeGroup);
    }
    
    public function addPaymentModeFeeGroup($remittanceConfigurationId, $corporateServicePaymentModeId, $name, array $fees)
    {
        // 1. Get corporate_service_payment_mode by corporate_service_payment_mode_id
        $entityPaymentMode = $this->getRepository()->findById($corporateServicePaymentModeId);
        if($entityPaymentMode == false)
        {
            $this->setResponseCode(MessageCode::CODE_ADD_CORPORATE_PAYMENT_MODE_FEE_FAIL);
            return false;
        }
        
        $serviceRemittanceConfig = $this->_getServiceRemittanceConfig();
        $entityRemittanceConfig = $serviceRemittanceConfig->getRemittanceConfigById($remittanceConfigurationId);
        // 2. Get remittance_service by remittance_conguration_id
        if($entityRemittanceConfig == false)
        {
            $this->setResponseCode($serviceRemittanceConfig->getResponseCode());
            return false;
        }
        
        $corporateServiceId = $entityPaymentMode->getCorporateServiceId();
        
        $cashInCorporateServiceId = $entityRemittanceConfig->getCashInCorporateServiceId();
        $cashOutCorporateServiceId = $entityRemittanceConfig->getCashOutCorporateServiceId();
        
        /**
         * already doing in $serviceRemittanceConfig->getRemittanceConfigById function
         */
//        // 3. Get and combine in/out corporate_service  by remittance_configuration.in_corporate_service_id, remittance_configuration.out_corporate_service_id
//        $serviceCorprateService = $this->_getServiceCorporateService();
//        // 3.1. get cash in corporate service
//        $entityCashInCorporateService = $serviceCorprateService->getCorporateService($cashInCorporateServiceId);
//        if($entityCashInCorporateService == false)
//        {
//            $this->setResponseCode($serviceCorprateService->getResponseCode());
//            return false;
//        }
//        
//        // 3.2 get cash out corporate service
//        $entityCashOutCorporateService = $serviceCorprateService->getCorporateService($cashOutCorporateServiceId);
//        if($entityCashInCorporateService == false)
//        {
//            $this->setResponseCode($serviceCorprateService->getResponseCode());
//            return false;
//        }
        
        //4. Validate if corporate_service_payment_mode is child of in corporate_service_id
        if($corporateServiceId != $cashInCorporateServiceId)
        {
            $this->setResponseCode(MessageCode::CODE_ADD_CORPORATE_PAYMENT_MODE_FEE_FAIL);
            return false;
        }
        
        //5. Insert into corporate_service_payment_mode_fee
        //- corporate_service_payment_mode_id = corporate_service_payment_mode
        //- name = name
        //- fee_type_id = payment_fee
        //- status = pending
        //- is_active = 0
        
        //payment_fee(payment mode)
        //corporate_service(collection mode)
        $serviceSystemCode = $this->_getServiceSystemCodeService();
        $fee_type = $serviceSystemCode->getByCode(FeeType::PAYMENT_MODE_FEE,  FeeType::getSystemGroupCode());
        if( $fee_type == false )
        {
            $this->setResponseCode(MessageCode::CODE_ADD_CORPORATE_PAYMENT_MODE_FEE_GROUP_FAIL);
            return false;
        }
        $status = $serviceSystemCode->getByCode(PaymentModeFeeGroupStatus::CODE_PENDING, PaymentModeFeeGroupStatus::getSystemGroupCode());
        if( $status == false )
        {
            $this->setResponseCode(MessageCode::CODE_ADD_CORPORATE_PAYMENT_MODE_FEE_GROUP_FAIL);
            return false;
        }
        
        $paymentModeFeeGroupId = GuidGenerator::generate();
        $entityPaymentModeFeeGroup = new PaymentModeFeeGroup();
        $entityPaymentModeFeeGroup->setId($paymentModeFeeGroupId);
        $entityPaymentModeFeeGroup->setCorporateServicePaymentModeId($corporateServicePaymentModeId);
        $entityPaymentModeFeeGroup->setFeeType($fee_type);
        $entityPaymentModeFeeGroup->setName($name);
        $entityPaymentModeFeeGroup->setStatus($status);
        
        $collectionPaymentModeFee = new PaymentModeFeeCollection();
        
        $isFlat = false;
        $isLessThan = false;
        $isRange = false;
        $isGreaterThan = false;
        $flatNum = $lessNum = $rangeNum = $greaterNum = 0;
        
        $lastAmount = false;
        
        foreach ($fees as $value) {
            
//            $mutitier_type = $value['multitier_type'];
//            $ref_value1 = $value['ref_value1'];
//            $ref_value2 = $value['ref_value2'];
//            $fee = $value['fee'];
            
            $multitier_type = $value->multitier_type;
            $ref_value1 = $value->ref_value1;
            $ref_value2 = $value->ref_value2;
            $fee = $value->fee;
            
            //need validate multitier_type ?
            
            if(empty($fee) && $fee !== 0)
            {
                $this->setResponseCode(MessageCode::CODE_INVALID_CORPORATE_PAYMENT_MODE_FEE_AMOUNT);
                return false;
            }
            
            if($multitier_type == PaymentModeFeeMultitierType::CODE_FLAT)
            {
                $isFlat = true;
                $flatNum++;
                
//                if(empty($ref_value1) && $ref_value1 !== 0)
//                {
//                    $this->setResponseCode(MessageCode::CODE_INVALID_CORPORATE_PAYMENT_MODE_FEE_AMOUNT);
//                    return false;
//                }
            }
            else if($multitier_type == PaymentModeFeeMultitierType::CODE_LESSTHAN)
            {
                if(empty($ref_value1) && $ref_value1 !== 0)
                {
                    $this->setResponseCode(MessageCode::CODE_INVALID_CORPORATE_PAYMENT_MODE_FEE_AMOUNT);
                    return false;
                }
                
                $isLessThan = true;
                $lessNum++;
                $lastAmount = $ref_value1;
            }
            else if($multitier_type == PaymentModeFeeMultitierType::CODE_RANGE)
            {
                if($ref_value1 != $lastAmount)
                {
                    $this->setResponseCode(MessageCode::CODE_INVALID_CORPORATE_PAYMENT_MODE_FEE_AMOUNT);
                    return false;
                }
                
                if((empty($ref_value1) && $ref_value1 !== 0) || (empty($ref_value2) && $ref_value2 !== 0))
                {
                    $this->setResponseCode(MessageCode::CODE_INVALID_CORPORATE_PAYMENT_MODE_FEE_AMOUNT);
                    return false;
                }
                
                $isRange = true;
                $rangeNum++;
                $lastAmount = $ref_value2;
            }
            else if($multitier_type == PaymentModeFeeMultitierType::CODE_GREATERTHAN)
            {
                if($ref_value1 != $lastAmount)
                {
                    $this->setResponseCode(MessageCode::CODE_INVALID_CORPORATE_PAYMENT_MODE_FEE_AMOUNT);
                    return false;
                }
                
                if(empty($ref_value1) && $ref_value1 !== 0)
                {
                    $this->setResponseCode(MessageCode::CODE_INVALID_CORPORATE_PAYMENT_MODE_FEE_AMOUNT);
                    return false;
                }
                
                $isGreaterThan = true;
                $greaterNum++;
                $lastAmount = $ref_value1;
            }
            
            $entityPaymentModeFee = new PaymentModeFee();
            $entityPaymentModeFee->setId(GuidGenerator::generate());
            $entityPaymentModeFee->setCorporateServicePaymentModeFeeGroupId($paymentModeFeeGroupId);
            $entityPaymentModeFee->setMultitierType($multitier_type);
            if($ref_value1 !== "")
                $entityPaymentModeFee->setReferenceValue1($ref_value1);
            if($ref_value2 !== "")
                $entityPaymentModeFee->setReferenceValue2($ref_value2);
            $entityPaymentModeFee->setIsPercentage(0);
            if($fee !== "")
                $entityPaymentModeFee->setFee($fee);
            
            $collectionPaymentModeFee->addData($entityPaymentModeFee);
        }
        
        $entityPaymentModeFeeGroup->setPaymentModeFeeItems($collectionPaymentModeFee);
        
        return $this->saveFeeGroup($entityPaymentModeFeeGroup);
    }
    
    // get payment/collection mode fee group info ( for maker use 'approved', checker use 'pending')
    public function getPaymentModeFeeGroupInfo($corporateServicePaymentModeId, $isActive = NULL,  $status = NULL)
    {
        $entityPaymentMode = $this->getRepository()->findById($corporateServicePaymentModeId);
        if( $entityPaymentMode == false )
        {
            $this->setResponseCode(MessageCode::CODE_CORPORATE_PAYMENT_MODE_NOT_FOUND);
            return false;
        }
        
        $tempArr = array();
        
        $payment_type = NULL;
        $payment_mode_code = NULL;
        $payment_mode_name = NULL;
        
        if($entityPaymentMode->getDirection() == PaymentDirection::IN)
            $payment_type = "Payment";
        else if($entityPaymentMode->getDirection() == PaymentDirection::OUT)
            $payment_type = "Collection";
        
        $payment_mode_code = $entityPaymentMode->getPaymentCode();
        
        // get payment_mode_name (all payment mode list from microservice)
        $servicePaymentModeMicro = $this->_getServicePaymentModeMicroService();
        $listPaymentModeMicro = $servicePaymentModeMicro->getAllPaymentModes();
        if( $entityPaymentModeMicro = $this->_getPaymentModeInfo($entityPaymentMode->getPaymentCode(), $listPaymentModeMicro) )
        {
            $payment_mode_name = $entityPaymentModeMicro->name;
        }
        
        $servicePaymentModeFeeGroup = $this->_getServicePaymentModeFeeGroupService();
        //PaymentModeFeeGroupStatus::CODE_APPROVED || PaymentModeFeeGroupStatus::CODE_PENDING
        if( $entityPaymentModeFeeGroup = $servicePaymentModeFeeGroup->getLastFeeGroupInfo( array($entityPaymentMode->getId() ), $status) )
        {
            $tempArr = $entityPaymentModeFeeGroup->jsonSerialize();
            
            $tempArr['payment_mode_name'] = NULL;
            $tempArr['updated_by_name'] = NULL;
            $tempArr['approve_reject_by_name'] = NULL;
            
            $userIds = array();
            array_push($userIds, $entityPaymentModeFeeGroup->getUpdatedBy());
            array_push($userIds, $entityPaymentModeFeeGroup->getCreatedBy());
            array_push($userIds, $entityPaymentModeFeeGroup->getApproveRejectBy());

            $serviceAccount = $this->_getServiceAccount();
            if( $collectionUsers = $serviceAccount->getUsers( array_unique($userIds) ) )
            {
                foreach ($collectionUsers as $entityUser) {
                    if( $entityUser->getId() == $entityPaymentModeFeeGroup->getCreatedBy() )
                    {
                        $tempArr['created_by_name'] = $entityUser->getName();
                    }
                    if($entityUser->getId() == $entityPaymentModeFeeGroup->getUpdatedBy())
                    {
                        $tempArr['updated_by_name'] = $entityUser->getName();
                    }
                    if($entityUser->getId() == $entityPaymentModeFeeGroup->getApproveRejectBy())
                    {
                        $tempArr['approve_reject_by_name'] = $entityUser->getName();
                    }
                }
            }
        }
        
        $tempArr['payment_type'] = $payment_type;
        $tempArr['payment_mode_code'] = $payment_mode_code;
        $tempArr['payment_mode_name'] = $payment_mode_name;
        
        //always return data
        $this->setResponseCode(MessageCode::CODE_GET_CORPORATE_PAYMENT_MODE_FEE_GROUP_SUCCESS);
        return $tempArr;
    }
    
//    // get payment/collection mode fee listing ( for maker use 'approved', checker use 'pending')
//    public function getPaymentModeFeeListing($limit, $page, $corporateServicePaymentModeId, $isActive = NULL,  $status = NULL)
//    {
//        //1. Get corporate_service_payment_mode by corporate_service_payment_mode_id
//        $entityPaymentMode = $this->getRepository()->findById($corporateServicePaymentModeId);
//        if($entityPaymentMode == false)
//        {
//            $this->setResponseCode(MessageCode::CODE_LIST_CORPORATE_PAYMENT_MODE_FEE_FAIL);
//            return false;
//        }
//        
//        //2. Get corporate_service_payment_mode_fee_group by corporate_service_payment_mode_id where status (if given)
//        $servicePaymentModeFee = $this->_getServicePaymentModeFeeService();
//        if( $listPaymentModeFee = $servicePaymentModeFee->getListByCorporrateServicePaymentModeIds($limit, $page, array($corporateServicePaymentModeId), NULL, NULL, $status) )
//        {
//            $this->setResponseCode(MessageCode::CODE_LIST_CORPORATE_PAYMENT_MODE_FEE_SUCCESS);
//            return $listPaymentModeFee;
//        }
//        
//        $this->setResponseCode(MessageCode::CODE_LIST_CORPORATE_PAYMENT_MODE_FEE_FAIL);
//        return false;
//    }
    
    // get payment/collection mode fee listing ( for maker use 'approved', checker use 'pending')
    public function getPaymentModeFeeListingByGroupId($limit, $page, $paymentModeFeeGroupId)
    {
        $servicePaymentModeFee = $this->_getServicePaymentModeFeeService();
        if( $listPaymentModeFee = $servicePaymentModeFee->getListByGroupIds($limit, $page, array($paymentModeFeeGroupId)) )
        {
            $this->setResponseCode(MessageCode::CODE_LIST_CORPORATE_PAYMENT_MODE_FEE_SUCCESS);
            return $listPaymentModeFee;
        }
        
        $this->setResponseCode(MessageCode::CODE_LIST_CORPORATE_PAYMENT_MODE_FEE_FAIL);
        return false;
    }
    
    // add payment/collection mode cost
    public function addPaymentModeCostGroup($remittanceConfigurationId, $corporateServicePaymentModeId, array $costs, $no_cost = false)
    {
        // 1. Get corporate_service_payment_mode by corporate_service_payment_mode_id
        $entityPaymentMode = $this->getRepository()->findById($corporateServicePaymentModeId);
        if($entityPaymentMode == false)
        {
            $this->setResponseCode(MessageCode::CODE_CORPORATE_PAYMENT_MODE_NOT_FOUND);
            return false;
        }
        
        // 2. Validate there is no pending payment_mode_cost_group record by corporate_service_payment_mode_id
        $servicePaymentModeCostGroup = $this->_getServicePaymentModeCostGroupService();
        if( $listPaymentModeCostGroup = $servicePaymentModeCostGroup->getListByCorporrateServicePaymentModeIds(10,1,array($corporateServicePaymentModeId), NULL, NULL, PaymentModeCostGroupStatus::CODE_PENDING, NULL) )
        {
            $this->setResponseCode(MessageCode::CODE_CORPORATE_PAYMENT_MODE_COST_GROUP_ALREADY_EXISTS_PENDING);
            return false;
        }
        
        // 3. Insert into payment_mode_cost_group
        // - corporate_service_payment_mode_id = corporate_service_payment_mode_id
        // - status = pending
        // - is_active = 0
        
        $serviceSystemCode = $this->_getServiceSystemCodeService();
        $status = $serviceSystemCode->getByCode(PaymentModeCostGroupStatus::CODE_PENDING, PaymentModeCostGroupStatus::getSystemGroupCode());
        if( $status == false )
        {
            $this->setResponseCode(MessageCode::CODE_ADD_CORPORATE_PAYMENT_MODE_COST_GROUP_FAIL);
            return false;
        }
        
        $paymentModeCostGroupId = GuidGenerator::generate();
        $entityPaymentModeCostGroup = new PaymentModeCostGroup();
        $entityPaymentModeCostGroup->setId($paymentModeCostGroupId);
        $entityPaymentModeCostGroup->setCorporateServicePaymentModeId($corporateServicePaymentModeId);
        $entityPaymentModeCostGroup->setStatus($status);
        $entityPaymentModeCostGroup->setIsActive(0);
        if($no_cost === 1 || $no_cost == "1")
        {
            $entityPaymentModeCostGroup->setNoCost(1);
        }
        else
        {
            $entityPaymentModeCostGroup->setNoCost(0);
        
            // 4. Insert into payment_mode_cost (for each cost item)
            // - payment_mode_cost_group_id = payment_mode_cost_group_id
            // - service_provider_id = service_provider_id
            // - is_percentage = is_percentage
            // - country_currency_code = country_currency_code
            // - cost = cost

            $collectionPaymentModeCost = new PaymentModeCostCollection();

            foreach ($costs as $value) {

                $service_provider_id = isset($value->service_provider_id) ? $value->service_provider_id : null;
                $role_id = isset($value->role_id) ? $value->role_id : null;
                $is_percentage = $value->is_percentage;
                $country_currency_code = $value->country_currency_code;
                $cost = $value->cost;

                $entityPaymentModeCost = new PaymentModeCost();
                $entityPaymentModeCost->setId(GuidGenerator::generate());
                $entityPaymentModeCost->setPaymentModeGroupId($paymentModeCostGroupId);
                if(!empty($service_provider_id))
                    $entityPaymentModeCost->setServiceProviderId($service_provider_id);
                if(!empty($role_id))
                    $entityPaymentModeCost->setRoleId($role_id);
                $entityPaymentModeCost->setIsPercentage(0);
                $entityPaymentModeCost->setCountryCurrencyCode($country_currency_code);
                $entityPaymentModeCost->setCost($cost);

                $collectionPaymentModeCost->addData($entityPaymentModeCost);
            }
            
            $entityPaymentModeCostGroup->setPaymentModeCostItems($collectionPaymentModeCost);
        }
        
        return $this->saveCostGroup($entityPaymentModeCostGroup);
    }
    
    public function getPaymentModeCostGroupInfo($corporateServicePaymentModeId, $isActive = NULL,  $status = NULL)
    {
        $entityPaymentMode = $this->getRepository()->findById($corporateServicePaymentModeId);
        if( $entityPaymentMode == false )
        {
            $this->setResponseCode(MessageCode::CODE_CORPORATE_PAYMENT_MODE_NOT_FOUND);
            return false;
        }
        
        $tempArr = array();
        
        $payment_type = NULL;
        $payment_mode_code = NULL;
        $payment_mode_name = NULL;
        
        if($entityPaymentMode->getDirection() == PaymentDirection::IN)
            $payment_type = "Payment";
        else if($entityPaymentMode->getDirection() == PaymentDirection::OUT)
            $payment_type = "Collection";
        
        $payment_mode_code = $entityPaymentMode->getPaymentCode();
        
        // get payment_mode_name (all payment mode list from microservice)
        $servicePaymentModeMicro = $this->_getServicePaymentModeMicroService();
        $listPaymentModeMicro = $servicePaymentModeMicro->getAllPaymentModes();
        if( $entityPaymentModeMicro = $this->_getPaymentModeInfo($entityPaymentMode->getPaymentCode(), $listPaymentModeMicro) )
        {
            $payment_mode_name = $entityPaymentModeMicro->name;
        }
        
        $servicePaymentModeCostGroup = $this->_getServicePaymentModeCostGroupService();
        //PaymentModeCostGroupStatus::CODE_APPROVED || PaymentModeCostGroupStatus::CODE_PENDING
        if( $entityPaymentModeCostGroup = $servicePaymentModeCostGroup->getLastCostGroupInfo(array($entityPaymentMode->getId()), $status) )
        {
            $tempArr = $entityPaymentModeCostGroup->jsonSerialize();
            
            $tempArr['payment_mode_name'] = NULL;
            $tempArr['updated_by_name'] = NULL;
            $tempArr['approve_reject_by_name'] = NULL;
            
            $userIds = array();
            array_push($userIds, $entityPaymentModeCostGroup->getUpdatedBy());
            array_push($userIds, $entityPaymentModeCostGroup->getCreatedBy());
            array_push($userIds, $entityPaymentModeCostGroup->getApproveRejectBy());

            $serviceAccount = $this->_getServiceAccount();
            if( $collectionUsers = $serviceAccount->getUsers( array_unique($userIds) ) )
            {
                foreach ($collectionUsers as $entityUser) {
                    if( $entityUser->getId() == $entityPaymentModeCostGroup->getCreatedBy() )
                    {
                        $tempArr['created_by_name'] = $entityUser->getName();
                    }
                    if($entityUser->getId() == $entityPaymentModeCostGroup->getUpdatedBy())
                    {
                        $tempArr['updated_by_name'] = $entityUser->getName();
                    }
                    if($entityUser->getId() == $entityPaymentModeCostGroup->getApproveRejectBy())
                    {
                        $tempArr['approve_reject_by_name'] = $entityUser->getName();
                    }
                }
            }
        }
        
        $tempArr['payment_type'] = $payment_type;
        $tempArr['payment_mode_code'] = $payment_mode_code;
        $tempArr['payment_mode_name'] = $payment_mode_name;
        
        //always return data
        $this->setResponseCode(MessageCode::CODE_GET_CORPORATE_PAYMENT_MODE_COST_GROUP_SUCCESS);
        return $tempArr;
    }
    
    public function getPaymentModeCostListingByGroupId($limit, $page, $paymentModeCostGroupId)
    {
        // 1. Get records from payment_mode_cost_group table by status (if given)
        $servicePaymentModeCost = $this->_getServicePaymentModeCostService();
        if( $listPaymentModeCost = $servicePaymentModeCost->getListByGroupIds($limit, $page, array($paymentModeCostGroupId)) )
        {
            
            $this->_extractPaymentModeCostCollection($listPaymentModeCost->result);
            
            $this->setResponseCode(MessageCode::CODE_LIST_CORPORATE_PAYMENT_MODE_FEE_SUCCESS);
            return $listPaymentModeCost;
        }
        
        $this->setResponseCode(MessageCode::CODE_LIST_CORPORATE_PAYMENT_MODE_FEE_FAIL);
        return false;
    }
    
    
    public function getApprovalPricingFeeListing($limit, $page, $remittanceConfigurationId = NULL, $type = NULL, 
            $fromCountryCurrency = NULL, $fromCountryPartner = NULL, 
            $toCountryCurrency = NULL, $toCountryPartner = NULL, 
            $status = NULL)
    {
        
        $paymentModeIds = NULL;
        $paymentModeFeeGroupIds = NULL;
        $listPaymentModeObject = NULL;
        $listRemittanceConfigObject = NULL;
        
        $serviceRemittanceConfig = $this->_getServiceRemittanceConfig();
        
        if(!empty($remittanceConfigurationId) 
                || !empty($fromCountryCurrency) || !empty($fromCountryPartner) 
                || !empty($toCountryCurrency) || !empty($toCountryPartner)
            )
        {
            if(!empty($status))
                $channel_status = array($status);
            else
                $channel_status = NULL;
        
            // if remitance configuration id is not null, get remittance configuratioin by id
            if( !$collectionRemittanceConfig = $serviceRemittanceConfig->getExistsRemittanceConfigList(100, 1, $remittanceConfigurationId, $fromCountryCurrency, $toCountryCurrency, $fromCountryPartner, $toCountryPartner, NULL) )
            {
                $this->setResponseCode(MessageCode::CODE_PRICING_CONFIG_LISTING_NOT_FOUND);
                return false;
            }
            
            $corporateServiceIds = array();
            foreach ($collectionRemittanceConfig->result as $entityRemittanceConfig) {
                array_push($corporateServiceIds, $entityRemittanceConfig->getCashInCorporateServiceId());
                array_push($corporateServiceIds, $entityRemittanceConfig->getCashOutCorporateServiceId());
            }
            $corporateServiceIds = array_unique($corporateServiceIds);
            
            // if $type is not null then get all (collection or payment) payment mode of current remittance configurationId
            $listPaymentMode = $this->getRepository()->findListByCorporeateServiceId(1000, 1, $corporateServiceIds);
            if($listPaymentMode == false)
            {
                $this->setResponseCode(MessageCode::CODE_PRICING_CONFIG_LISTING_NOT_FOUND);
                return false;
            }
            
            if($listPaymentMode != null && count($listPaymentMode->result) > 0)
            {
                foreach ($listPaymentMode->result as $value) {
                    if($paymentModeIds == null)
                        $paymentModeIds = array();
                    array_push($paymentModeIds, $value->getId());
                }
            }            
        }
        
        $servicePaymentModeFeeGroup = $this->_getServicePaymentModeFeeGroupService();
        if( $listPaymentModeGroupObject = $servicePaymentModeFeeGroup->getListByCorporrateServicePaymentModeIds($limit, $page, $paymentModeIds, NULL, NULL, $status) )
        {
            // get payment_mode_name (all payment mode list from microservice)
            $servicePaymentModeMicro = $this->_getServicePaymentModeMicroService();
            $listPaymentModeMicro = $servicePaymentModeMicro->getAllPaymentModes();
                    
            foreach ($listPaymentModeGroupObject->result as $entityPaymentModeGroup) {
                if($paymentModeIds == NULL)
                    $paymentModeIds = array();
                array_push($paymentModeIds, $entityPaymentModeGroup->getCorporateServicePaymentModeId());
            }
            if($paymentModeIds !== NULL)
                $paymentModeIds = array_unique($paymentModeIds);
            
            // get all payment mode by payment mode ids
            $cashInCorporateServiceIds = NULL;
            $cashOutCorporateServiceIds = NULL;
            if( $paymentModeIds != NULL) 
            {
                if ($listPaymentModeObject = $this->getRepository()->findListByCorporeateServiceId(100, 1, NULL, $paymentModeIds, NULL, NULL))
                {
                    foreach ($listPaymentModeObject->result as $entityPaymentMode) {
                        if($entityPaymentMode->getDirection() == PaymentDirection::IN)
                        {
                            if($cashInCorporateServiceIds == NULL)
                                $cashInCorporateServiceIds = array();
                            array_push($cashInCorporateServiceIds, $entityPaymentMode->getCorporateServiceid());
                        }
                        else if($entityPaymentMode->getDirection() == PaymentDirection::OUT)
                        {
                            if($cashOutCorporateServiceIds == NULL)
                                $cashOutCorporateServiceIds = array();
                            array_push($cashOutCorporateServiceIds, $entityPaymentMode->getCorporateServiceId());
                        }
                    }
                    
                    if($cashInCorporateServiceIds != NULL)
                        $cashInCorporateServiceIds = array_unique($cashInCorporateServiceIds);
                    if($cashOutCorporateServiceIds != NULL)
                        $cashOutCorporateServiceIds = array_unique($cashOutCorporateServiceIds);
                    
                }
            }
            
            if($cashInCorporateServiceIds != NULL || $cashOutCorporateServiceIds != NULL)
            {
                $listRemittanceConfigObject = $serviceRemittanceConfig->findByCorporateServiceIds($cashInCorporateServiceIds, $cashOutCorporateServiceIds, NULL, 1000, 1);
            }
            
            $result = array();
            
            foreach ($listPaymentModeGroupObject->result as $entityPaymentModeGroup) {
                $tempArr = $entityPaymentModeGroup->jsonSerialize();
                
                $tempArr['remittance_configuration_id'] = "";
                $tempArr['channel_id'] = "";
                $tempArr['payment_mode_name'] = "";
                $tempArr['payment_type'] = "";
                $tempArr['from_country_currency_code'] = "";
                $tempArr['from_country_partener_name'] = "";
                $tempArr['to_country_currency_code'] = "";
                $tempArr['to_country_partener_name'] = "";
                
                $corporateServiceId = NULL;
                
                // get payment mode
                $paymentModeFeeGroupId = $entityPaymentModeGroup->getId();
                $paymentModeId = $entityPaymentModeGroup->getCorporateServicePaymentModeId();
                if( $entityPaymentMode = $this->_getPaymentModeByPaymentModeId($paymentModeId, $listPaymentModeObject) )
                {
                    if($paymentModeInfo = $this->_getPaymentModeInfo($entityPaymentMode->getPaymentCode(), $listPaymentModeMicro))
                    {
                        $tempArr['payment_mode_name']  = $paymentModeInfo->name;
                    }
                    
                    if($entityPaymentMode->getDirection() == PaymentDirection::IN)
                    {
                        $tempArr['payment_type'] = "Payment";
                    }
                    else if($entityPaymentMode->getDirection() == PaymentDirection::OUT)
                    {
                        $tempArr['payment_type'] = "Collection";
                    }
                    
                    $corporateServiceId = $entityPaymentMode->getCorporateServiceId();
                }
                
//                $paymentModeFeeGroupInfo = $this->_getPaymentModeFeeGroup($paymentModeFeeGroupId, $listPaymentModeGroupObject ? $listPaymentModeGroupObject->result : $listPaymentModeGroupObject);
//                $paymentModeCostGroupInfo = $this->_getPaymentModeCostGroup($paymentModeCostGroupId, $paymentModeCostGroupList ? $paymentModeCostGroupList->result : $paymentModeCostGroupList);

                // get from country_currency and to country_currency by corporate_service_id
                if($corporateServiceId != NULL)
                {
                    if( $remittanceConfigInfo = $this->_getRemittanceConfigByCroporateServiceId($corporateServiceId, $listRemittanceConfigObject) )
                    {
                        $tempArr['remittance_configuration_id'] = $remittanceConfigInfo->getId();
                        $tempArr['channel_id'] = $remittanceConfigInfo->getChannelID();
                        
                        $tempArr['from_country_currency_code'] = $remittanceConfigInfo->getInCorporateService()->getCountryCurrencyCode();
                        $tempArr['from_country_partener_name'] = $remittanceConfigInfo->getFromCountryPartnerName();
                        
                        $tempArr['to_country_currency_code'] = $remittanceConfigInfo->getOutCorporateService()->getCountryCurrencyCode();
                        $tempArr['to_country_partener_name'] = $remittanceConfigInfo->getToCountryPartnerName();
                    }
                }
                
                $tempArr['payment_mode_last_updated_at'] = NULL;
                if($entityPaymentModeGroup->getCreatedAt() != NULL && $entityPaymentModeGroup->getCreatedAt()->getUnix() != NULL)
                {
                    $tempArr['payment_mode_last_updated_at'] = $entityPaymentModeGroup->getCreatedAt()->getString();
                }
                
                $result[] = $tempArr;
            }

            $listPaymentModeGroupObject->result = $result;

            $this->setResponseCode(MessageCode::CODE_PRICING_CONFIG_LISTING_SUCCESS);
            return $listPaymentModeGroupObject;
        }
        
        $this->setResponseCode(MessageCode::CODE_PRICING_CONFIG_LISTING_NOT_FOUND);
        return false;
    }

    public function getApprovalPricingCostListing($limit, $page, $remittanceConfigurationId = NULL, $type = NULL, 
            $fromCountryCurrency = NULL, $fromCountryPartner = NULL, 
            $toCountryCurrency = NULL, $toCountryPartner = NULL, 
            $status = NULL)
    {
        $paymentModeIds = NULL;
        $paymentModeCostGroupIds = NULL;
        $listPaymentModeObject = NULL;
        $listRemittanceConfigObject = NULL;
        
        $serviceRemittanceConfig = $this->_getServiceRemittanceConfig();
        
        if(!empty($remittanceConfigurationId) 
                || !empty($fromCountryCurrency) || !empty($fromCountryPartner) 
                || !empty($toCountryCurrency) || !empty($toCountryPartner)
            )
        {
            if(!empty($status))
                $channel_status = array($status);
            else
                $channel_status = NULL;
        
            // if remitance configuration id is not null, get remittance configuratioin by id
            if( !$collectionRemittanceConfig = $serviceRemittanceConfig->getExistsRemittanceConfigList(100, 1, $remittanceConfigurationId, $fromCountryCurrency, $toCountryCurrency, $fromCountryPartner, $toCountryPartner, NULL) )
            {
                $this->setResponseCode(MessageCode::CODE_PRICING_CONFIG_LISTING_NOT_FOUND);
                return false;
            }
            
            $corporateServiceIds = array();
            foreach ($collectionRemittanceConfig->result as $entityRemittanceConfig) {
                array_push($corporateServiceIds, $entityRemittanceConfig->getCashInCorporateServiceId());
                array_push($corporateServiceIds, $entityRemittanceConfig->getCashOutCorporateServiceId());
            }
            $corporateServiceIds = array_unique($corporateServiceIds);
            
            // if $type is not null then get all (collection or payment) payment mode of current remittance configurationId
            $listPaymentMode = $this->getRepository()->findListByCorporeateServiceId(1000, 1, $corporateServiceIds);
            if($listPaymentMode == false)
            {
                $this->setResponseCode(MessageCode::CODE_PRICING_CONFIG_LISTING_NOT_FOUND);
                return false;
            }
            
            if($listPaymentMode != null && count($listPaymentMode->result) > 0)
            {
                foreach ($listPaymentMode->result as $value) {
                    if($paymentModeIds == null)
                        $paymentModeIds = array();
                    array_push($paymentModeIds, $value->getId());
                }
            }
            
        }
        
        // get all payment mode cost group list by status
        $servicePaymentModeCostGroup = $this->_getServicePaymentModeCostGroupService();
        if( $listPaymentModeGroupObject = $servicePaymentModeCostGroup->getListByCorporrateServicePaymentModeIds($limit, $page, $paymentModeIds, NULL, NULL, $status) )
        {
            // get payment_mode_name (all payment mode list from microservice)
            $servicePaymentModeMicro = $this->_getServicePaymentModeMicroService();
            $listPaymentModeMicro = $servicePaymentModeMicro->getAllPaymentModes();
            
            foreach ($listPaymentModeGroupObject->result as $value) {
                if($paymentModeIds == NULL)
                    $paymentModeIds = array();
                array_push($paymentModeIds, $value->getCorporateServicePaymentModeId());
            }
            if($paymentModeIds !== NULL)
                $paymentModeIds = array_unique($paymentModeIds);
            
            // get all payment mode by payment mode ids
            $cashInCorporateServiceIds = NULL;
            $cashOutCorporateServiceIds = NULL;
            if( $paymentModeIds != NULL) 
            {
                if( $listPaymentModeObject = $this->getRepository()->findListByCorporeateServiceId(100, 1, NULL, $paymentModeIds))
                {
                    foreach ($listPaymentModeObject->result as $entityPaymentMode) {
                        if($entityPaymentMode->getDirection() == PaymentDirection::IN)
                        {
                            if($cashInCorporateServiceIds == NULL)
                                $cashInCorporateServiceIds = array();
                            array_push($cashInCorporateServiceIds, $entityPaymentMode->getCorporateServiceId());
                        }
                        else if($entityPaymentMode->getDirection() == PaymentDirection::OUT)
                        {
                            if($cashOutCorporateServiceIds == NULL)
                                $cashOutCorporateServiceIds = array();
                            array_push($cashOutCorporateServiceIds, $entityPaymentMode->getCorporateServiceId());
                        }
                    }
                    
                    if($cashInCorporateServiceIds != NULL)
                        $cashInCorporateServiceIds = array_unique($cashInCorporateServiceIds);
                    if($cashOutCorporateServiceIds != NULL)
                        $cashOutCorporateServiceIds = array_unique($cashOutCorporateServiceIds);
                    
                }
            }
            
            if($cashInCorporateServiceIds != NULL || $cashOutCorporateServiceIds != NULL)
            {
                $listRemittanceConfigObject = $serviceRemittanceConfig->findByCorporateServiceIds($cashInCorporateServiceIds, $cashOutCorporateServiceIds, NULL, 1000, 1);
            }
            
            $result = array();
            
            foreach ($listPaymentModeGroupObject->result as $entityPaymentModeGroup) {
                $tempArr = $entityPaymentModeGroup->jsonSerialize();
                
                $tempArr['remittance_configuration_id'] = "";
                $tempArr['channel_id'] = "";
                $tempArr['payment_mode_name'] = "";
                $tempArr['payment_type'] = "";
                $tempArr['from_country_currency_code'] = "";
                $tempArr['from_country_partener_name'] = "";
                $tempArr['to_country_currency_code'] = "";
                $tempArr['to_country_partener_name'] = "";
                
                $corporateServiceId = NULL;
                
                // get payment mode
                $paymentModeCostGroupId = $entityPaymentModeGroup->getId();
                $paymentModeId = $entityPaymentModeGroup->getCorporateServicePaymentModeId();
                
                if( $entityPaymentMode = $this->_getPaymentModeByPaymentModeId($paymentModeId, $listPaymentModeObject) )
                {
                    if($paymentModeInfo = $this->_getPaymentModeInfo($entityPaymentMode->getPaymentCode(), $listPaymentModeMicro))
                    {
                        $tempArr['payment_mode_name']  = $paymentModeInfo->name;
                    }
                    
                    if($entityPaymentMode->getDirection() == PaymentDirection::IN)
                    {
                        $tempArr['payment_type'] = "Payment";
                    }
                    else if($entityPaymentMode->getDirection() == PaymentDirection::OUT)
                    {
                        $tempArr['payment_type'] = "Collection";
                    }
                    
                    $corporateServiceId = $entityPaymentMode->getCorporateServiceId();
                }
                
                // get from country_currency and to country_currency by corporate_service_id
                if($corporateServiceId != NULL)
                {
                    if( $remittanceConfigInfo = $this->_getRemittanceConfigByCroporateServiceId($corporateServiceId, $listRemittanceConfigObject) )
                    {
                        $tempArr['remittance_configuration_id'] = $remittanceConfigInfo->getId();
                        $tempArr['channel_id'] = $remittanceConfigInfo->getChannelID();
                        
                        $tempArr['from_country_currency_code'] = $remittanceConfigInfo->getInCorporateService()->getCountryCurrencyCode();
                        $tempArr['from_country_partener_name'] = $remittanceConfigInfo->getFromCountryPartnerName();
                        
                        $tempArr['to_country_currency_code'] = $remittanceConfigInfo->getOutCorporateService()->getCountryCurrencyCode();
                        $tempArr['to_country_partener_name'] = $remittanceConfigInfo->getToCountryPartnerName();
                    }
                }
                
                $tempArr['payment_mode_last_updated_at'] = "";
                if( $entityPaymentModeGroup->getCreatedAt() != NULL && $entityPaymentModeGroup->getCreatedAt()->getUnix() != NULL)
                {
                    $tempArr['payment_mode_last_updated_at'] = $entityPaymentModeGroup->getCreatedAt()->getString();
                }
                
                $result[] = $tempArr;
            }

            $listPaymentModeGroupObject->result = $result;

            $this->setResponseCode(MessageCode::CODE_PRICING_CONFIG_LISTING_SUCCESS);
            return $listPaymentModeGroupObject;
        }
        
        $this->setResponseCode(MessageCode::CODE_PRICING_CONFIG_LISTING_NOT_FOUND);
        return false;
    }
    
    
    
    
    public function viewApprovalPricingFee($limit, $page, $paymentModeFeeGroupId)
    {
        //1. Get corporate_service_payment_mode_fee_group by corporate_service_payment_mode_fee_group_id
        $servicePaymentModeFeeGroup = $this->_getServicePaymentModeFeeGroupService();
        $entityPaymentModeFeeGroup = $servicePaymentModeFeeGroup->getPaymentModeFeeGroupById($paymentModeFeeGroupId);
        if($entityPaymentModeFeeGroup == false)
        {
            $this->setResponseCode(MessageCode::CODE_GET_CORPORATE_PAYMENT_MODE_FEE_GROUP_FAIL);
            return false;
        }
        
        //2. Get corporate_service_payment_mode_fee by corporate_service_payment_mode_fee_group_id
        $servicePaymentModeFee = $this->_getServicePaymentModeFeeService();
        if( $listPaymentModeFee = $servicePaymentModeFee->getListByCorporrateServicePaymentModeIds($limit, $page, NULL, array($paymentModeFeeGroupId), NULL, NULL) )
        {
            $this->setResponseCode(MessageCode::CODE_LIST_CORPORATE_PAYMENT_MODE_FEE_SUCCESS);
            return $listPaymentModeFee;
        }

        $this->setResponseCode(MessageCode::CODE_LIST_CORPORATE_PAYMENT_MODE_FEE_FAIL);
        return false;
    }
    
    public function viewApprovalPricingCost($limit, $page, $paymentModeCostGroupId)
    {
        //1. Get payment_mode_cost_group by payment_mode_cost_group_id
        $servicePaymentModeCostGroup = $this->_getServicePaymentModeCostGroupService();
        $entityPaymentModeCostGroup = $servicePaymentModeCostGroup->getPaymentModeCostGroupById($paymentModeCostGroupId);
        if($entityPaymentModeCostGroup == false)
        {
            $this->setResponseCode(MessageCode::CODE_GET_CORPORATE_PAYMENT_MODE_COST_GROUP_FAIL);
            return false;
        }
        
        //2. Get payment_mode_cost by payment_mode_cost_group_id
        $servicePaymentModeCost = $this->_getServicePaymentModeCostService();
        if( $listPaymentModeCost = $servicePaymentModeCost->getListByCorporrateServicePaymentModeIds($limit, $page, NULL, array($paymentModeCostGroupId), NULL, NULL) )
        {
            $this->_extractPaymentModeCostCollection($listPaymentModeCost->result);
            
            $this->setResponseCode(MessageCode::CODE_LIST_CORPORATE_PAYMENT_MODE_COST_SUCCESS);
            return $listPaymentModeCost;
        }

        $this->setResponseCode(MessageCode::CODE_LIST_CORPORATE_PAYMENT_MODE_COST_FAIL);
        return false;
    }
    
    public function approvalPaymentModeFee($corporateServicePaymentModeFeeGroupId, $status, $remarks)
    {
        //1. Get corporate_service_payment_mode_fee_group by corporate_service_payment_mode_fee_group_id
        $servicePaymentModeFeeGroup = $this->_getServicePaymentModeFeeGroupService();
        $entityPaymentModeFeeGroup = $servicePaymentModeFeeGroup->getPaymentModeFeeGroupById($corporateServicePaymentModeFeeGroupId);
        if($entityPaymentModeFeeGroup == false)
        {
            $this->setResponseCode(MessageCode::CODE_EDIT_CORPORATE_PAYMENT_MODE_FEE_GROUP_FAIL);
            return false;
        }
        
        //2. Get corporate_service_payment_mode_fee by corporate_service_payment_mode_fee_group_id
        $servicePaymentModeFee = $this->_getServicePaymentModeFeeService();
        $listPaymentModeFee = $servicePaymentModeFee->getListByCorporrateServicePaymentModeIds(10, 1, NULL, array($corporateServicePaymentModeFeeGroupId), NULL, NULL);
        if( $listPaymentModeFee == false )
        {
            $this->setResponseCode(MessageCode::CODE_LIST_CORPORATE_PAYMENT_MODE_FEE_FAIL);
            return false;
        }
        
        //3. Validate if corporate_service_payment_mode_fee_group.status = pending
        if($entityPaymentModeFeeGroup->getStatus()->getCode() != PaymentModeFeeGroupStatus::CODE_PENDING)
        {
            $this->setResponseCode(MessageCode::CODE_EDIT_CORPORATE_PAYMENT_MODE_FEE_GROUP_FAIL);
            return false;
        }
        
        //4. If status = approved, go to 5. Otherwise, go to 7
        if($status == PaymentModeFeeGroupStatus::CODE_APPROVED)
        {
            //5. If Update previous active corporate_service_payment_mode_fee_group, is_active = 0
            if( $entityPaymentModeFeeGroupPrevious = $servicePaymentModeFeeGroup->getLastFeeGroupInfo(array($entityPaymentModeFeeGroup->getCorporateServicePaymentModeId()), $status) )
            {
                $entityPaymentModeFeeGroupPrevious->setIsActive(0);
//                $entityPaymentModeFeeGroupPrevious->setApproveRejectBy($this->getUpdatedBy());
//                $entityPaymentModeFeeGroupPrevious->setApproveRejectAt(IappsDateTime::now());
                    
                if($servicePaymentModeFeeGroup->updatePaymentModeFeeGroupStatus($entityPaymentModeFeeGroupPrevious) == false)
                {
                    $this->setResponseCode(MessageCode::CODE_EDIT_CORPORATE_PAYMENT_MODE_FEE_GROUP_FAIL);
                    return false;
                }
            }

            //6. Update corporate_service_payment_mode_fee_group’s status, remark, approve_reject_by, approve_reject_at, is_active = 1
            
            $entityPaymentModeFeeGroup->getStatus()->setCode($status);
            $entityPaymentModeFeeGroup->setIsActive(1);
            $entityPaymentModeFeeGroup->setApproveRejectRemark($remarks);
            $entityPaymentModeFeeGroup->setApproveRejectBy($this->getUpdatedBy());
            $entityPaymentModeFeeGroup->setApproveRejectAt(IappsDateTime::now());
            
        }
        else
        {
            //7. Update corporate_service_payment_mode_fee_group’s status, remark, approve_reject_by, approve_reject_at, is_active = 0
            
            $entityPaymentModeFeeGroup->getStatus()->setCode($status);
            $entityPaymentModeFeeGroup->setIsActive(0);
            $entityPaymentModeFeeGroup->setApproveRejectRemark($remarks);
            $entityPaymentModeFeeGroup->setApproveRejectBy($this->getUpdatedBy());
            $entityPaymentModeFeeGroup->setApproveRejectAt(IappsDateTime::now());
            
        }
        
        if($servicePaymentModeFeeGroup->updatePaymentModeFeeGroupStatus($entityPaymentModeFeeGroup))
        {
//            $this->setResponseCode(MessageCode::CODE_EDIT_CORPORATE_PAYMENT_MODE_FEE_GROUP_SUCCESS);
//            return $entityPaymentModeFeeGroup;
            
//            if( $listPaymentModeFee = $servicePaymentModeFee->getListByCorporrateServicePaymentModeIds(10, 1, NULL, array($corporateServicePaymentModeFeeGroupId), NULL, NULL) )
//            {
//                $this->setResponseCode(MessageCode::CODE_EDIT_CORPORATE_PAYMENT_MODE_FEE_GROUP_SUCCESS);
//                return $listPaymentModeFee;
//            }
            $this->setResponseCode(MessageCode::CODE_EDIT_CORPORATE_PAYMENT_MODE_FEE_GROUP_SUCCESS);
            return true;
        }
        $this->setResponseCode(MessageCode::CODE_EDIT_CORPORATE_PAYMENT_MODE_FEE_GROUP_FAIL);
        return false;

    }
    
    public function approvalPaymentModeCost($paymentModeCostGroupId, $status, $remarks)
    {
        //1. Get payment_mode_cost_group by payment_mode_cost_group_id
        $servicePaymentModeCostGroup = $this->_getServicePaymentModeCostGroupService();
        $entityPaymentModeCostGroup = $servicePaymentModeCostGroup->getPaymentModeCostGroupById($paymentModeCostGroupId);
        if($entityPaymentModeCostGroup == false)
        {
            $this->setResponseCode(MessageCode::CODE_EDIT_CORPORATE_PAYMENT_MODE_COST_GROUP_FAIL);
            return false;
        }
        
        //2. Validate if payment_mode_cost_group.status is pending
        if($entityPaymentModeCostGroup->getStatus()->getCode() != PaymentModeCostGroupStatus::CODE_PENDING)
        {
            $this->setResponseCode(MessageCode::CODE_EDIT_CORPORATE_PAYMENT_MODE_COST_GROUP_FAIL);
            return false;
        }
        
        //3. If status = approved, go to 4. Otherwise go to 6.
        if($status == PaymentModeFeeGroupStatus::CODE_APPROVED)
        {
            //4. Update previous active payment_mode_cost_group, is_active = 0
            if( $entityPaymentModeCostGroupPrevious = $servicePaymentModeCostGroup->getLastCostGroupInfo(array($entityPaymentModeCostGroup->getCorporateServicePaymentModeId()), $status) )
            {                
                $entityPaymentModeCostGroupPrevious->setIsActive(0);
//                $entityPaymentModeCostGroupPrevious->setApproveRejectBy($this->getUpdatedBy());
//                $entityPaymentModeCostGroupPrevious->setApproveRejectAt(IappsDateTime::now());
                    
                if($servicePaymentModeCostGroup->updatePaymentModeCostGroupStatus($entityPaymentModeCostGroupPrevious) == false)
                {
                    $this->setResponseCode(MessageCode::CODE_EDIT_CORPORATE_PAYMENT_MODE_COST_GROUP_FAIL);
                    return false;
                }
            }

            //5. Update payment_mode_cost_group’s status, remark, approve_reject_by, approve_reject_at, is_active = 1
            
            $entityPaymentModeCostGroup->getStatus()->setCode($status);
            $entityPaymentModeCostGroup->setIsActive(1);
            $entityPaymentModeCostGroup->setApproveRejectRemark($remarks);
            $entityPaymentModeCostGroup->setApproveRejectBy($this->getUpdatedBy());
            $entityPaymentModeCostGroup->setApproveRejectAt(IappsDateTime::now());
            
        }
        else
        {
            //6. Update payment_mode_cost_group’s status, remark, approve_reject_by, approve_reject_at, is_active = 0
            
            $entityPaymentModeCostGroup->getStatus()->setCode($status);
            $entityPaymentModeCostGroup->setIsActive(0);
            $entityPaymentModeCostGroup->setApproveRejectRemark($remarks);
            $entityPaymentModeCostGroup->setApproveRejectBy($this->getUpdatedBy());
            $entityPaymentModeCostGroup->setApproveRejectAt(IappsDateTime::now());
            
        }
        
        if($servicePaymentModeCostGroup->updatePaymentModeCostGroupStatus($entityPaymentModeCostGroup))
        {
            // response 
//            if( $listPaymentModeCost = $servicePaymentModeCostGroup->getListByCorporrateServicePaymentModeIds(10, 1, NULL, array($paymentModeCostGroupId), NULL, NULL) )
//            {
//                $this->setResponseCode(MessageCode::CODE_EDIT_CORPORATE_PAYMENT_MODE_COST_GROUP_SUCCESS);
//                return $listPaymentModeCost;
//            }
            $this->setResponseCode(MessageCode::CODE_EDIT_CORPORATE_PAYMENT_MODE_COST_GROUP_SUCCESS);
            return true;
        }
        $this->setResponseCode(MessageCode::CODE_EDIT_CORPORATE_PAYMENT_MODE_COST_GROUP_FAIL);
        return false;

    }
    
    // active collection/payment mode
    public function activePaymentMode($paymentModeId, $isActive = 1)
    {
        //1. Get corporate_service_payment_mode by corporate_service_payment_mode_id
        $entityOld = $this->getRepository()->findById($paymentModeId);
        if( $entityOld == false )
        {
            $this->setResponseCode(MessageCode::CODE_EDIT_CORPORATE_PAYMENT_MODE_FAIL);
            return false;
        }
        
        if($isActive === 1)
        {
            //2. Validate if corporate_service_payment_mode has an active payment_mode_cost_group (is_active = 1).
            $servicePaymentModeCostGroup = $this->_getServicePaymentModeCostGroupService();
            $listPaymentModeCostGroup = $servicePaymentModeCostGroup->getListByCorporrateServicePaymentModeIds(10,1,array($paymentModeId), NULL, TRUE);
            if($listPaymentModeCostGroup === false || count($listPaymentModeCostGroup->result) !== 1)
            {
                $this->setResponseCode(MessageCode::CODE_EDIT_CORPORATE_PAYMENT_MODE_FAIL);
                return false;
            }

            //3. Validate if corporate_service_payment_mode has an active corporate_service_payment_mode_fee_group (is_active = 1)
            $servicePaymentModeFeeGroup = $this->_getServicePaymentModeFeeGroupService();
            $listPaymentModeFeeGroup = $servicePaymentModeFeeGroup->getListByCorporrateServicePaymentModeIds(10,1,array($paymentModeId), NULL, TRUE);
            if($listPaymentModeFeeGroup === false || count($listPaymentModeFeeGroup->result) !== 1)
            {
                $this->setResponseCode(MessageCode::CODE_EDIT_CORPORATE_PAYMENT_MODE_FAIL);
                return false;
            }
        }
        
        //4. Update corporate_service_payment_mode’s is_active = 1
        $entity = clone $entityOld;
        $entity->setIsActive($isActive);
        $entity->setUpdatedBy($this->getUpdatedBy());

        if( $this->updatePaymentMode($entity) )
        {
            $this->fireLogEvent('iafb_remittance.corporate_service_payment_mode', AuditLogAction::UPDATE, $paymentModeId, $entityOld);

            $this->setResponseCode(MessageCode::CODE_EDIT_ACTIVE_CORPORATE_PAYMENT_MODE_SUCCESS);
            return true;
        }
        $this->setResponseCode(MessageCode::CODE_EDIT_ACTIVE_CORPORATE_PAYMENT_MODE_FAIL);
        return false;
    }
    
    public function getPaymentModeFeeGroupInfoByGroupId($groupId, $isActive = NULL,  $status = NULL)
    {
        // get payment mode name
        $service_payment_mode = $this->_getServicePaymentModeMicroService();
        $paymentModeList = $service_payment_mode->getAllPaymentModes(null);
        
        $paymentModeName = "";
        
        //1. Get 1 corporate_service_payment_mode_fee_group by corporate_service_payment_mode_fee_group_id
        $servicePaymentModeFeeGroup = $this->_getServicePaymentModeFeeGroupService();
        $entityPaymentModeFeeGroup = $servicePaymentModeFeeGroup->getPaymentModeFeeGroupById($groupId);
        if($entityPaymentModeFeeGroup != false)
        {
            $tempArr = $entityPaymentModeFeeGroup->jsonSerialize();
            $tempArr['payment_mode_name'] = $paymentModeName;
            $tempArr['payment_type'] = "";
            $tempArr['updated_by_name'] = "";
            $tempArr['approve_reject_by_name'] = "";

            $userIds = array();
            array_push($userIds, $entityPaymentModeFeeGroup->getUpdatedBy());
            array_push($userIds, $entityPaymentModeFeeGroup->getCreatedBy());
            array_push($userIds, $entityPaymentModeFeeGroup->getApproveRejectBy());

             $serviceAccount = $this->_getServiceAccount();
            if( $collectionUsers = $serviceAccount->getUsers( array_unique($userIds) ) )
            {
                foreach ($collectionUsers as $entityUser) {
                    if( $entityUser->getId() == $entityPaymentModeFeeGroup->getCreatedBy() )
                    {
                        $tempArr['created_by_name'] = $entityUser->getName();
                    }
                    if($entityUser->getId() == $entityPaymentModeFeeGroup->getUpdatedBy())
                    {
                        $tempArr['updated_by_name'] = $entityUser->getName();
                    }
                    if($entityUser->getId() == $entityPaymentModeFeeGroup->getApproveRejectBy())
                    {
                        $tempArr['approve_reject_by_name'] = $entityUser->getName();
                    }
                }
            }
            
            $corporateServicePaymentModeId = $entityPaymentModeFeeGroup->getCorporateServicePaymentModeId();
            if( $entityPaymentModeInfo = $this->getRepository()->findById($corporateServicePaymentModeId) )
            {
                if( $paymentModeInfo = $this->_getPaymentModeInfo($entityPaymentModeInfo->getPaymentCode(), $paymentModeList) )
                {
                    $paymentModeName = $paymentModeInfo->name;
                }
                
                if($entityPaymentModeInfo->getDirection() == PaymentDirection::IN)
                    $tempArr['payment_type'] = "Payment";
                else if($entityPaymentModeInfo->getDirection() == PaymentDirection::OUT)
                    $tempArr['payment_type'] = "Collection";
            }

//            $entityPaymentModeFeeGroup->payment_mode_name = $paymentModeName;
            $tempArr['payment_mode_name'] = $paymentModeName;
            $this->setResponseCode(MessageCode::CODE_GET_CORPORATE_PAYMENT_MODE_FEE_GROUP_SUCCESS);
//            return $entityPaymentModeFeeGroup;
            return $tempArr;
        }
        
        $this->setResponseCode(MessageCode::CODE_GET_CORPORATE_PAYMENT_MODE_FEE_GROUP_FAIL);
        return false;
    }
    
    public function getPaymentModeListByCorporateServiceId($limit, $page, array $corporateServiceIds = NULL, array $paymentModeIds = NULL, $isActive = NULL, $direction = NULL) {
        
        if ($collectionPaymentMode = $this->getRepository()->findListByCorporeateServiceId($limit, $page, $corporateServiceIds, $paymentModeIds, $isActive, $direction)) {
            
            $this->setResponseCode(MessageCode::CODE_LIST_CORPORATE_PAYMENT_MODE_SUCCESS);
            return $collectionPaymentMode;
        }
        $this->setResponseCode(MessageCode::CODE_LIST_CORPORATE_PAYMENT_MODE_FAIL);
        return false;
    }
    
    private function savePaymentMode(PaymentMode $entity)
    {
        // check exists by corporate_service_id & direction & payment_code
        if($object = $this->getRepository()->exists($entity))
        {
            $this->setResponseCode(MessageCode::CODE_EXISTS_COROPORATE_PAYMENT_MODE);
            return false;
        }
        
        if($this->getRepository()->insert($entity))
        {
            // if $entity->getIsDefault() === 1 then update is_default = 0 for other payment_mode
            if($entity->getIsDefault() === 1)
            {
                $otherEntity = clone $entity;
                $otherEntity->setIsDefault(0);
                $updateFields = array(
                    'is_default'
                );
                $whereFields = array(
                    '!id' => $entity->getId(),
                    'corporate_service_id' => $entity->getCorporateServiceId(),
                    'direction' => $entity->getDirection()
                );

                if( $this->getRepository()->updateFields($otherEntity, $updateFields, $whereFields) )
                {

                }
            }
            
            $this->fireLogEvent('iafb_remittance.corporate_service_payment_mode', AuditLogAction::CREATE, $entity->getId());

            $this->setResponseCode(MessageCode::CODE_ADD_CORPORATE_PAYMENT_MODE_SUCCESS);
            return $entity;
        }
        
        $this->setResponseCode(MessageCode::CODE_ADD_CORPORATE_PAYMENT_MODE_FAIL);
        return false;
    }
        
    private function _getPaymentModeInfo($paymentCode, $paymentModeList) {

        if ($paymentModeList != null && count($paymentModeList) > 0) {
            foreach ($paymentModeList as $value) {
                if ($value->code == $paymentCode) {
                    return $value;
                }
            }
        }

        return false;
    }
    
    private function _getPaymentModeGroupInfo($paymentModeId, $listPaymentModeFeeGroupObject){
        if($listPaymentModeFeeGroupObject)
        {
            foreach ($listPaymentModeFeeGroupObject as $value) {
                if($value->getCorporateServicePaymentModeId() == $paymentModeId)
                {
                    return $value;
                }
            }
        }
        return false;
    }
    
    private function saveFeeGroup(PaymentModeFeeGroup $entityPaymentModeFeeGroup)
    {
        $servicePaymentModeFeeGroup = $this->_getServicePaymentModeFeeGroupService();
        
        // check if exists pending group
        $paymentModeIds = array();
        array_push($paymentModeIds, $entityPaymentModeFeeGroup->getCorporateServicePaymentModeId());
        if( $listPaymentModeFeeGroupObject = $servicePaymentModeFeeGroup->getListByCorporrateServicePaymentModeIds(1,1,$paymentModeIds,NULL,NULL, PaymentModeFeeGroupStatus::CODE_PENDING) )
        {
            $this->setResponseCode(MessageCode::CODE_CORPORATE_PAYMENT_MODE_FEE_GROUP_ALREADY_EXISTS_PENDING);
            return false;
        }
        
        if( $servicePaymentModeFeeGroup->savePaymentModeFeeGroup($entityPaymentModeFeeGroup) )
        {
            $this->setResponseCode($servicePaymentModeFeeGroup->getResponseCode());
            return true;
        }
        $this->setResponseCode($servicePaymentModeFeeGroup->getResponseCode());
        return false;
    }
    
    private function saveCostGroup(PaymentModeCostGroup $entityPaymentModeCostGroup)
    {
        $servicePaymentModeCostGroup = $this->_getServicePaymentModeCostGroupService();
        
        // check if exists pending group
        $paymentModeIds = array();
        array_push($paymentModeIds, $entityPaymentModeCostGroup->getCorporateServicePaymentModeId());
        if( $listPaymentModeCostGroupObject = $servicePaymentModeCostGroup->getListByCorporrateServicePaymentModeIds(1,1,$paymentModeIds,NULL,NULL, PaymentModeCostGroupStatus::CODE_PENDING) )
        {
            $this->setResponseCode(MessageCode::CODE_CORPORATE_PAYMENT_MODE_COST_GROUP_ALREADY_EXISTS_PENDING);
            return false;
        }
        
        if( $servicePaymentModeCostGroup->savePaymentModeCostGroup($entityPaymentModeCostGroup) )
        {
            $this->setResponseCode($servicePaymentModeCostGroup->getResponseCode());
            return true;
        }
        $this->setResponseCode($servicePaymentModeCostGroup->getResponseCode());
        return false;
    }
    
    protected function _extractPaymentModeCostCollection(PaymentModeCostCollection $collection) {
        $userIds = array();
        $roleIds = array();

        foreach ($collection AS $paymentModeCost) {
            array_push($userIds, $paymentModeCost->getCreatedBy());
            array_push($userIds, $paymentModeCost->getUpdatedBy());
            array_push($userIds, $paymentModeCost->getServiceProviderId());
            
            array_push($roleIds, $paymentModeCost->getRoleId());
        }
        
        $accountService = $this->_getServiceAccount();
        if ($users_data = $accountService->getUsers($userIds))
        {
            $collection->joinServiceProvider($users_data);
        }
        if($roles_data = $accountService->getAllRoles())
        {
            $collection->joinRole($roles_data);
        }
        $collection->rewind();

        return $collection;
    }
    
    private function _getPaymentModeByPaymentModeId($paymentModeId, $listPaymentModeObject)
    {
        $paymentModeInfo = NULL;
        if($paymentModeId != NULL && $listPaymentModeObject != NULL)
        {
            foreach ($listPaymentModeObject->result as $entityPaymentMode) {
                if($entityPaymentMode->getId() == $paymentModeId)
                {
                    $paymentModeInfo = $entityPaymentMode;
                    break;
                }
            }
        }
        
        return $paymentModeInfo;
    }
    
    private function updatePaymentMode(PaymentMode $entity)
    {
        if( $entityOld = $this->getRepository()->findById($entity->getId()) )
        {
            $entity->setUpdatedBy($this->getUpdatedBy());
            
            if( $this->getRepository()->update($entity) )
            {
                $this->fireLogEvent('iafb_remittance.corporate_service_payment_mode', AuditLogAction::UPDATE, $entity->getId(), $entityOld);

                $this->setResponseCode(MessageCode::CODE_EDIT_CORPORATE_PAYMENT_MODE_SUCCESS);
                return true;
            }
        }
        $this->setResponseCode(MessageCode::CODE_ACTIVE_COROPORATE_PAYMENT_MODE_FAIL);
        return false;
    }
    
    private function _getRemittanceConfigByCroporateServiceId($corporateServiceId, $listRemittanceConfigObject)
    {
        $remittanceConfigInfo = NULL;
        if($listRemittanceConfigObject != NULL)
        {
            foreach ($listRemittanceConfigObject->result as $value) {
                if($value->getCashInCorporateServiceId() == $corporateServiceId || $value->getCashOutCorporateServiceId() == $corporateServiceId)
                {
                    $remittanceConfigInfo = $value;
                    break;
                }
            }
        }
        return $remittanceConfigInfo;
    }

    private function _getPaymentModeFeeGroup($payment_mode_id, $paymentModeFeeGroupList){
        
        if($paymentModeFeeGroupList == false)
            return false;
        
        foreach ($paymentModeFeeGroupList as $value) {
            if($value->getCorporateServicePaymentModeId() == $payment_mode_id)
            {
                return $value;
            }
        }
        return false;
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
   
    
    
    

    
    

    public function getPaymentModeFeeListingView($limit, $page, $corporateServicePaymentModeFeeGroupId)
    {
        //1. Get corporate_service_payment_mode_fee_group by corporate_service_payment_mode_fee_group_id
        $servicePaymentModeFeeGroup = $this->_getServicePaymentModeFeeGroupService();
        $entityPaymentModeFeeGroup = $servicePaymentModeFeeGroup->getPaymentModeFeeGroupById($corporateServicePaymentModeFeeGroupId);
        if($entityPaymentModeFeeGroup == false)
        {
            $this->setResponseCode(MessageCode::CODE_GET_CORPORATE_PAYMENT_MODE_FEE_GROUP_FAIL);
            return false;
        }
        
        //2. Get corporate_service_payment_mode_fee by corporate_service_payment_mode_fee_group_id
        $servicePaymentModeFee = $this->_getServicePaymentModeFeeService();
        if( $listPaymentModeFee = $servicePaymentModeFee->getListByCorporrateServicePaymentModeIds($limit, $page, NULL, array($corporateServicePaymentModeFeeGroupId), NULL, NULL) )
        {
            $this->setResponseCode(MessageCode::CODE_LIST_CORPORATE_PAYMENT_MODE_FEE_SUCCESS);
            return $listPaymentModeFee;
        }

        $this->setResponseCode(MessageCode::CODE_LIST_CORPORATE_PAYMENT_MODE_FEE_FAIL);
        return false;
    }
    
    public function getPaymentModeCostGroupInfoByGroupId($groupId, $isActive = NULL,  $status = NULL)
    {
        // get payment mode name
        $service_payment_mode = $this->_getServicePaymentModeMicroService();
        $paymentModeList = $service_payment_mode->getAllPaymentModes(null);
        
        $paymentModeName = "";
        
        //1. Get 1 payment_mode_cost_group by payment_mode_cost_group_id
        $servicePaymentModeCostGroup = $this->_getServicePaymentModeCostGroupService();
        $entityPaymentModeCostGroup = $servicePaymentModeCostGroup->getPaymentModeCostGroupById($groupId);
        if($entityPaymentModeCostGroup != false)
        {
            $tempArr = $entityPaymentModeCostGroup->jsonSerialize();
            $tempArr['payment_mode_name'] = $paymentModeName;
            $tempArr['payment_type'] = "";
            $tempArr['updated_by_name'] = "";
            $tempArr['approve_reject_by_name'] = "";

            $userIds = array();
            array_push($userIds, $entityPaymentModeCostGroup->getUpdatedBy());
            array_push($userIds, $entityPaymentModeCostGroup->getCreatedBy());
            array_push($userIds, $entityPaymentModeCostGroup->getApproveRejectBy());

             $serviceAccount = $this->_getServiceAccount();
            if( $collectionUsers = $serviceAccount->getUsers( array_unique($userIds) ) )
            {
                foreach ($collectionUsers as $entityUser) {
                    if( $entityUser->getId() == $entityPaymentModeCostGroup->getCreatedBy() )
                    {
                        $tempArr['created_by_name'] = $entityUser->getName();
                    }
                    if($entityUser->getId() == $entityPaymentModeCostGroup->getUpdatedBy())
                    {
                        $tempArr['updated_by_name'] = $entityUser->getName();
                    }
                    if($entityUser->getId() == $entityPaymentModeCostGroup->getApproveRejectBy())
                    {
                        $tempArr['approve_reject_by_name'] = $entityUser->getName();
                    }
                }
            }
            
            $corporateServicePaymentModeId = $entityPaymentModeCostGroup->getCorporateServicePaymentModeId();
            if( $entityPaymentModeInfo = $this->getRepository()->findById($corporateServicePaymentModeId) )
            {
                if( $paymentModeInfo = $this->_getPaymentModeInfo($entityPaymentModeInfo->getPaymentCode(), $paymentModeList) )
                {
                    $paymentModeName = $paymentModeInfo->name;
                }
                
                if($entityPaymentModeInfo->getDirection() == PaymentDirection::IN)
                    $tempArr['payment_type'] = "Payment";
                else if($entityPaymentModeInfo->getDirection() == PaymentDirection::OUT)
                    $tempArr['payment_type'] = "Collection";
            }

//            $entityPaymentModeCostGroup->payment_mode_name = $paymentModeName;
//            $this->setResponseCode(MessageCode::CODE_GET_CORPORATE_PAYMENT_MODE_COST_GROUP_SUCCESS);
//            return $entityPaymentModeCostGroup;
            $tempArr['payment_mode_name'] = $paymentModeName;
            $this->setResponseCode(MessageCode::CODE_GET_CORPORATE_PAYMENT_MODE_FEE_GROUP_SUCCESS);
            return $tempArr;
            
        }
        
        $this->setResponseCode(MessageCode::CODE_GET_CORPORATE_PAYMENT_MODE_COST_GROUP_FAIL);
        return false;
    }
    
    public function getPaymentModeCostListingView($limit, $page, $paymentModeCostGroupId)
    {
        //1. Get payment_mode_cost_group by payment_mode_cost_group_id
        $servicePaymentModeCostGroup = $this->_getServicePaymentModeCostGroupService();
        $entityPaymentModeCostGroup = $servicePaymentModeCostGroup->getPaymentModeCostGroupById($paymentModeCostGroupId);
        if($entityPaymentModeCostGroup == false)
        {
            $this->setResponseCode(MessageCode::CODE_GET_CORPORATE_PAYMENT_MODE_COST_GROUP_FAIL);
            return false;
        }
        
        //2. Get payment_mode_cost by payment_mode_cost_group_id
        $servicePaymentModeCost = $this->_getServicePaymentModeCostService();
        if( $listPaymentModeCost = $servicePaymentModeCost->getListByCorporrateServicePaymentModeIds($limit, $page, NULL, array($paymentModeCostGroupId), NULL, NULL) )
        {
            $this->_extractPaymentModeCostCollection($listPaymentModeCost->result);
            
            $this->setResponseCode(MessageCode::CODE_LIST_CORPORATE_PAYMENT_MODE_COST_SUCCESS);
            return $listPaymentModeCost;
        }

        $this->setResponseCode(MessageCode::CODE_LIST_CORPORATE_PAYMENT_MODE_COST_FAIL);
        return false;
    }
    
    public function getLastApprovedAtByRemittanceConfigurationCollection(RemittanceConfigCollection $remittanceConfigurationCollection)
    {
        $servicePaymentModeFeeGroup = $this->_getServicePaymentModeFeeGroupService();
        $servicePaymentModeCostGroup = $this->_getServicePaymentModeCostGroupService();
        
        $result = array();
        
        foreach ($remittanceConfigurationCollection as $entityRemittanceConfig) {
            $tempArr = array();
            
            $remittanceConfigurationId = $entityRemittanceConfig->getId();
            
            $result["$remittanceConfigurationId"] = NULL;
            
            $cashInCorporateServiceId = $entityRemittanceConfig->getCashInCorporateServiceId();
            $cashOutCorporateServiceId = $entityRemittanceConfig->getCashOutCorporateServiceId();

            $_paymentModeIds = array();
            // get payment mode
            if( $listPaymentModeObject = $this->getPaymentModeListByCorporateServiceId(MAX_VALUE, 1, array($cashInCorporateServiceId, $cashOutCorporateServiceId)) )
            {
                foreach ($listPaymentModeObject->result as $value) {
                    array_push($_paymentModeIds, $value->getId());
                }
            }
            
            $lastUpdateds = array();
            // get last approved fee group by paymentModeIds
            $entityApprovedPaymentModeFeeGroup = $servicePaymentModeFeeGroup->getLastFeeGroupInfo($_paymentModeIds, PaymentModeFeeGroupStatus::CODE_APPROVED);
            // get last approved cost group by paymentModeIds
            $entityApprovedPaymentModeCostGroup = $servicePaymentModeCostGroup->getLastCostGroupInfo($_paymentModeIds, PaymentModeCostGroupStatus::CODE_APPROVED);
                 
            if($entityApprovedPaymentModeFeeGroup != NULL && $entityApprovedPaymentModeFeeGroup->getApproveRejectAt() != NULL)
            {
                $lastUpdateds[] = $entityApprovedPaymentModeFeeGroup->getApproveRejectAt()->getUnix();
            }

            if($entityApprovedPaymentModeCostGroup != NULL && $entityApprovedPaymentModeCostGroup->getApproveRejectAt() != NULL)
            {
                $lastUpdateds[] = $entityApprovedPaymentModeCostGroup->getApproveRejectAt()->getUnix();
            }

            if(count($lastUpdateds) > 0)
            {
                rsort($lastUpdateds);
                $result["$remittanceConfigurationId"] = IappsDateTime::fromUnix($lastUpdateds[0])->getString();
            }
        }
        
        return $result;
    }
    
}
