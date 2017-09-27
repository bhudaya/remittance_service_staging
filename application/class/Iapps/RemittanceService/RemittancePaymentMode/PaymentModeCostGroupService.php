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
use Iapps\Common\SystemCode\SystemCode;
use Iapps\RemittanceService\Common\MessageCode;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeCostServiceFactory;
use Iapps\Common\AuditLog\AuditLogAction;

/**
 * Description of PaymentModeCostGroupService
 *
 * @author lichao
 */
class PaymentModeCostGroupService extends IappsBaseService {
    //put your code here
    
    private function _getServicePaymentModeCostService()
    {
        $service = PaymentModeCostServiceFactory::build();
        $service->setUpdatedBy($this->getUpdatedBy());
        $service->setIpAddress($this->getIpAddress());
        return $service;
    }
    
    public function getLastCostGroupInfo(array $paymentModeIds, $status = NULL)
    {
        if( $entity = $this->getRepository()->getLastCostGroupInfo($paymentModeIds, $status) )
        {
            $this->setResponseCode(MessageCode::CODE_GET_CORPORATE_PAYMENT_MODE_FEE_GROUP_SUCCESS);
            return $entity;
        }
        $this->setResponseCode(MessageCode::CODE_GET_CORPORATE_PAYMENT_MODE_FEE_GROUP_FAIL);
        return false;
        
    }
    
    public function getListByCorporrateServicePaymentModeIds($limit, $page, array $payment_mode_ids = NULL, array $paymentModeCostGroupIds = NULL, $isActive = NULL, $status = NULL, $noCost = NULL)
    {
        if( $object = $this->getRepository()->getListByCorporrateServicePaymentModeIds($limit, $page, $payment_mode_ids, $paymentModeCostGroupIds, $isActive, $status, $noCost) )
        {
            $groupCollection = $object->result;
            $costServ = PaymentModeCostServiceFactory::build();
            if( $costInfo = $costServ->getListByGroupIds(MAX_VALUE, 1, $groupCollection->getIds()) )
            {
                $groupCollection->joinPaymentModeCostItems($costInfo->result);
            }
            $object->result = $groupCollection;

            $this->setResponseCode(MessageCode::CODE_LIST_CORPORATE_PAYMENT_MODE_COST_GROUP_SUCCESS);
            return $object;
        }

        $this->setResponseCode(MessageCode::CODE_LIST_CORPORATE_PAYMENT_MODE_COST_GROUP_FAIL);
        return false;
    }
    
    public function savePaymentModeCostGroup(PaymentModeCostGroup $entity)
    {
        if( !($entity instanceof PaymentModeCostGroup) )
        {
            $this->setResponseCode(MessageCode::CODE_ADD_CORPORATE_PAYMENT_MODE_COST_GROUP_FAIL);
            return false;
        }
        
        $servicePaymentModeCost = $this->_getServicePaymentModeCostService();
        
        $entity->setCreatedBy($this->getUpdatedBy());

//        //get status id if needed
//        if( !$this->_extractStatus($entity) )
//            return false;

        $this->getRepository()->startDBTransaction();
        if( $this->getRepository()->insert($entity) )
        {
            if($entity->getNoCost() == 0 && $entity->getPaymentModeCostItems() != NULL)
            {
                foreach($entity->getPaymentModeCostItems() AS $itemEntity)
                {
                    if( !$servicePaymentModeCost->insertPaymentModeCost($itemEntity) )
                    {
                        $this->getRepository()->rollbackDBTransaction();
                        return false;
                    }
                }
            }
            
            $this->fireLogEvent('iafb_remittance.payment_mode_cost_group', AuditLogAction::CREATE, $entity->getId());

            $this->getRepository()->completeDBTransaction();
            
            $this->setResponseCode(MessageCode::CODE_ADD_CORPORATE_PAYMENT_MODE_COST_GROUP_SUCCESS);
            return $entity;
        }

        $this->getRepository()->rollbackDBTransaction();
        
        $this->setResponseCode(MessageCode::CODE_ADD_CORPORATE_PAYMENT_MODE_COST_GROUP_FAIL);
        return false;
    }
    
    
    public function updatePaymentModeCostGroup(PaymentModeCostGroup $entity)
    {
        if( $entityOld = $this->getRepository()->findById($entity->getId()) )
        {
            $entity->setUpdatedBy($this->getUpdatedBy());
            
            if( $this->getRepository()->update($entity) )
            {
                $this->fireLogEvent('iafb_remittance.payment_mode_cost_group', AuditLogAction::UPDATE, $entity->getId(), $entityOld);

                $this->setResponseCode(MessageCode::CODE_EDIT_CORPORATE_PAYMENT_MODE_COST_GROUP_SUCCESS);
                return true;
            }
        }
        $this->setResponseCode(MessageCode::CODE_EDIT_CORPORATE_PAYMENT_MODE_COST_GROUP_FAIL);
        return false;
    }
    
    public function updatePaymentModeCostGroupStatus(PaymentModeCostGroup $entity)
    {
        if( $entityOld = $this->getRepository()->findById($entity->getId()) )
        {
            // no need update updated_by and updated_at
//            $entity->setUpdatedBy($this->getUpdatedBy());
            
            if( $this->getRepository()->updateStatus($entity) )
            {
                $this->fireLogEvent('iafb_remittance.payment_mode_cost_group', AuditLogAction::UPDATE, $entity->getId(), $entityOld);

                $this->setResponseCode(MessageCode::CODE_EDIT_CORPORATE_PAYMENT_MODE_COST_GROUP_SUCCESS);
                return true;
            }
        }
        $this->setResponseCode(MessageCode::CODE_EDIT_CORPORATE_PAYMENT_MODE_COST_GROUP_FAIL);
        return false;
    }
    
    public function updatePaymentModeCost(PaymentModeCostGroup $entity)
    {
        if( !($entity instanceof PaymentModeCostGroup) )
        {
            $this->setResponseCode(MessageCode::CODE_EDIT_CORPORATE_PAYMENT_MODE_COST_GROUP_FAIL);
            return false;
        }
        
        $servicePaymentModeCost = $this->_getServicePaymentModeCostService();
        
        $entity->setUpdatedBy($this->getUpdatedBy());

        $item_ids = NULL;
        foreach($entity->getPaymentModeCostItems() AS $itemEntity)
        {
            // get all item id
            if($item_ids == NULL)
                $item_ids = array();
            
            if($itemEntity != NULL)
                array_push($item_ids, $itemEntity->getId());
        }

        $isUpdated = false;
            
        $this->getRepository()->startDBTransaction();
        
        // delete by without ids
        if( $servicePaymentModeCost->removeByWithOutIds($entity, $item_ids) )
        {
            $isUpdated = true;
        }

        foreach($entity->getPaymentModeCostItems() AS $itemEntity)
        {
            //update or insert
            if( !$servicePaymentModeCost->updateOrInsertPaymentModeCost($itemEntity) )
            {
                $this->getRepository()->rollbackDBTransaction();
                return false;
            }

            $isUpdated = true;
        }

//            if($isUpdated)
//            {
//                $entity->setIsActive(0);
//            }
        
        
        if( $listPaymentModeCost = $servicePaymentModeCost->getListByGroupIds(1, 1, array($entity->getId()), NULL) )
        {
            $entity->setNoCost(0);
        }
        else
        {
            $entity->setNoCost(1);
        }

        if( $this->getRepository()->update($entity) )
        {
            $this->fireLogEvent('iafb_remittance.payment_mode_cost_group', AuditLogAction::UPDATE, $entity->getId());
            $this->getRepository()->completeDBTransaction();

            $this->setResponseCode(MessageCode::CODE_ADD_CORPORATE_PAYMENT_MODE_COST_GROUP_SUCCESS);
            return $entity;
        }

        $this->getRepository()->rollbackDBTransaction();
        
        $this->setResponseCode(MessageCode::CODE_ADD_CORPORATE_PAYMENT_MODE_COST_GROUP_FAIL);
        return false;
    }
    
    
    public function getPaymentModeCostGroupById($paymentModeCostGroupId)
    {
        if( $object = $this->getRepository()->findById($paymentModeCostGroupId) )
        {
            
            
            $this->setResponseCode(MessageCode::CODE_GET_CORPORATE_PAYMENT_MODE_COST_GROUP_SUCCESS);
            return $object;
        }
        $this->setResponseCode(MessageCode::CODE_GET_CORPORATE_PAYMENT_MODE_COST_GROUP_FAIL);
        return false;
    }
    
    
}
