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
use Iapps\Common\AuditLog\AuditLogAction;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeFeeServiceFactory;

/**
 * Description of PaymentModeFeeGroupService
 *
 * @author lichao
 */
class PaymentModeFeeGroupService extends IappsBaseService {
    //put your code here
    
    private function _getServicePaymentModeFeeService()
    {
        $service = PaymentModeFeeServiceFactory::build();
        $service->setUpdatedBy($this->getUpdatedBy());
        $service->setIpAddress($this->getIpAddress());
        return $service;
    }
    
    public function getLastFeeGroupInfo(array $paymentModeIds, $status = NULL)
    {
        if( $entity = $this->getRepository()->getLastFeeGroupInfo($paymentModeIds, $status) )
        {
            $this->setResponseCode(MessageCode::CODE_GET_CORPORATE_PAYMENT_MODE_FEE_GROUP_SUCCESS);
            return $entity;
        }
        $this->setResponseCode(MessageCode::CODE_GET_CORPORATE_PAYMENT_MODE_FEE_GROUP_FAIL);
        return false;
        
    }

    public function getActiveByCorporateServicePaymentModeIds(array $payment_mode_ids)
    {
        $collection = new PaymentModeFeeGroupCollection();
        foreach($payment_mode_ids AS $payment_mode_id)
        {
            if( $data = $this->getRepository()->findActiveByCorporateServicePaymentModeId($payment_mode_id) )
            {
                $collection->addData($data);
            }
        }

        if( count($collection) > 0 )
        {
            $this->setResponseCode(MessageCode::CODE_LIST_CORPORATE_PAYMENT_MODE_FEE_GROUP_SUCCESS);
            return $collection;
        }

        $this->setResponseCode(MessageCode::CODE_LIST_CORPORATE_PAYMENT_MODE_FEE_GROUP_FAIL);
        return false;
    }

    public function getListByCorporrateServicePaymentModeIds($limit, $page, array $payment_mode_ids = NULL, array $paymentModeFeeGroupIds = NULL, $isActive = NULL, $status = NULL)
    {
        if( $object = $this->getRepository()->getListByCorporrateServicePaymentModeIds($limit, $page, $payment_mode_ids, $paymentModeFeeGroupIds, $isActive, $status) )
        {
            $this->setResponseCode(MessageCode::CODE_LIST_CORPORATE_PAYMENT_MODE_FEE_GROUP_SUCCESS);
            return $object;
        }
        
        $this->setResponseCode(MessageCode::CODE_LIST_CORPORATE_PAYMENT_MODE_FEE_GROUP_FAIL);
        return false;
    }
    
    public function savePaymentModeFeeGroup(PaymentModeFeeGroup $entity)
    {
        if( !($entity instanceof PaymentModeFeeGroup) )
        {
            $this->setResponseCode(MessageCode::CODE_ADD_CORPORATE_PAYMENT_MODE_FEE_GROUP_FAIL);
            return false;
        }
        
        $servicePaymentModeFee = $this->_getServicePaymentModeFeeService();
        
        $entity->setCreatedBy($this->getUpdatedBy());

        $this->getRepository()->startDBTransaction();
        if( $this->getRepository()->insert($entity) )
        {
            foreach($entity->getPaymentModeFeeItems() AS $itemEntity)
            {
                if( !$servicePaymentModeFee->insertPaymentModeFee($itemEntity) )
                {
                    $this->getRepository()->rollbackDBTransaction();
                    return false;
                }
            }
            
            $this->fireLogEvent('iafb_remittance.corporate_service_payment_mode_fee_group', AuditLogAction::CREATE, $entity->getId());
            $this->getRepository()->completeDBTransaction();
            
            $this->setResponseCode(MessageCode::CODE_ADD_CORPORATE_PAYMENT_MODE_FEE_GROUP_SUCCESS);
            return $entity;
        }

        $this->getRepository()->rollbackDBTransaction();
        
        $this->setResponseCode(MessageCode::CODE_ADD_CORPORATE_PAYMENT_MODE_FEE_GROUP_FAIL);
        return false;
    }
    
//    public function removePaymentModeFeeGroup(PaymentModeFeeGroup $entity)
//    {
//        if( $this->getRepository()->remove($entity) )
//        {
//            $this->fireLogEvent('iafb_remittance.corporate_service_payment_mode_fee_group', AuditLogAction::DELETE, $entity->getId());
//            $this->setResponseCode(MessageCode::CODE_DELETE_CORPORATE_PAYMENT_MODE_FEE_GROUP_SUCCESS);
//            return true;
//        }
//        $this->setResponseCode(MessageCode::CODE_DELETE_CORPORATE_PAYMENT_MODE_FEE_GROUP_FAIL);
//        return false;
//    }
    
    public function updatePaymentModeFeeGroup(PaymentModeFeeGroup $entity)
    {
        if( $entityOld = $this->getRepository()->findById($entity->getId()) )
        {
            $entity->setUpdatedBy($this->getUpdatedBy());
            
            if( $this->getRepository()->update($entity) )
            {
                $this->fireLogEvent('iafb_remittance.corporate_service_payment_mode_fee_group', AuditLogAction::UPDATE, $entity->getId(), $entityOld);

                $this->setResponseCode(MessageCode::CODE_EDIT_CORPORATE_PAYMENT_MODE_FEE_GROUP_SUCCESS);
                return true;
            }
        }
        $this->setResponseCode(MessageCode::CODE_EDIT_CORPORATE_PAYMENT_MODE_FEE_GROUP_FAIL);
        return false;
    }
    
    public function updatePaymentModeFeeGroupStatus(PaymentModeFeeGroup $entity)
    {
        if( $entityOld = $this->getRepository()->findById($entity->getId()) )
        {
            //no need update updated_by & updated_at
//            $entity->setUpdatedBy($this->getUpdatedBy());
            
            if( $this->getRepository()->updateStatus($entity) )
            {
                $this->fireLogEvent('iafb_remittance.corporate_service_payment_mode_fee_group', AuditLogAction::UPDATE, $entity->getId(), $entityOld);

                $this->setResponseCode(MessageCode::CODE_EDIT_CORPORATE_PAYMENT_MODE_FEE_GROUP_SUCCESS);
                return true;
            }
        }
        $this->setResponseCode(MessageCode::CODE_EDIT_CORPORATE_PAYMENT_MODE_FEE_GROUP_FAIL);
        return false;
    }
    
    public function updatePaymentModeFee(PaymentModeFeeGroup $entity)
    {
        if( !($entity instanceof PaymentModeFeeGroup) )
        {
            $this->setResponseCode(MessageCode::CODE_EDIT_CORPORATE_PAYMENT_MODE_FEE_GROUP_FAIL);
            return false;
        }
        
        $servicePaymentModeFee = $this->_getServicePaymentModeFeeService();
        
        $entity->setUpdatedBy($this->getUpdatedBy());

//        //get status id if needed
//        if( !$this->_extractStatus($entity) )
//            return false;
        
        $item_ids = array();
        foreach($entity->getPaymentModeFeeItems() AS $itemEntity)
        {
            // get all item id
            array_push($item_ids, $itemEntity->getId());
        }

        $isUpdated = false;
            
        $this->getRepository()->startDBTransaction();
        
        // delete by without ids
        if( $servicePaymentModeFee->removeByWithOutIds($entity, $item_ids) )
        {
            $isUpdated = true;
        }

        foreach($entity->getPaymentModeFeeItems() AS $itemEntity)
        {
            //update or insert
            if( !$servicePaymentModeFee->updateOrInsertPaymentModeFee($itemEntity) )
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

        if( $this->getRepository()->update($entity) )
        {
            $this->fireLogEvent('iafb_remittance.corporate_service_payment_mode_fee_group', AuditLogAction::UPDATE, $entity->getId());
            $this->getRepository()->completeDBTransaction();

            $this->setResponseCode(MessageCode::CODE_ADD_CORPORATE_PAYMENT_MODE_FEE_GROUP_SUCCESS);
            return $entity;
        }

        $this->getRepository()->rollbackDBTransaction();
        
        $this->setResponseCode(MessageCode::CODE_ADD_CORPORATE_PAYMENT_MODE_FEE_GROUP_FAIL);
        return false;
    }
    
    public function getPaymentModeFeeGroupById($paymentModeFeeGroupId)
    {
        if( $object = $this->getRepository()->findById($paymentModeFeeGroupId) )
        {
            $this->setResponseCode(MessageCode::CODE_GET_CORPORATE_PAYMENT_MODE_FEE_GROUP_SUCCESS);
            return $object;
        }
        $this->setResponseCode(MessageCode::CODE_GET_CORPORATE_PAYMENT_MODE_FEE_GROUP_FAIL);
        return false;
    }
    
}
