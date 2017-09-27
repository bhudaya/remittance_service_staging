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
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeFeeGroup;
use Iapps\Common\AuditLog\AuditLogAction;

/**
 * Description of PaymentModeFeeGroupService
 *
 * @author lichao
 */
class PaymentModeFeeService extends IappsBaseService {
    //put your code here
    
    public function insertPaymentModeFee(PaymentModeFee $entity)
    {
        $entity->setCreatedBy($this->getUpdatedBy());
        
        if( $this->getRepository()->insert($entity) )
        {
            $this->fireLogEvent('iafb_remittance.corporate_service_payment_mode_fee', AuditLogAction::CREATE, $entity->getId());
            
            $this->setResponseCode(MessageCode::CODE_ADD_CORPORATE_PAYMENT_MODE_FEE_SUCCESS);
            return true;
        }
        
        $this->setResponseCode(MessageCode::CODE_ADD_CORPORATE_PAYMENT_MODE_FEE_FAIL);
        return false;
    }
    
    public function getListByGroupIds($limit, $page, array $feeGroupIds = NULL)
    {
        $collection = new PaymentModeFeeCollection();
        foreach($feeGroupIds AS $feeGroupId )
        {
            if( $datas = $this->getRepository()->findListByGroupId($feeGroupId) )
            {
                foreach($datas->result AS $data)
                    $collection->addData($data);
            }
        }

        if( count($collection) )
        {
            $object = $collection->pagination($limit, $page);
            $this->setResponseCode(MessageCode::CODE_LIST_CORPORATE_PAYMENT_MODE_FEE_SUCCESS);
            return $object;
        }

        $this->setResponseCode(MessageCode::CODE_LIST_CORPORATE_PAYMENT_MODE_FEE_FAIL);
        return false;
    }
    
    public function getListByCorporrateServicePaymentModeIds($limit, $page, array $paymentModeIds = NULL, array $feeGroupIds = NULL, $isActive = NULL, $status = NULL)
    {
        if( $object = $this->getRepository()->findListByCorporrateServicePaymentModeIds($limit, $page, $paymentModeIds, $feeGroupIds, $isActive, $status) )
        {
            $this->setResponseCode(MessageCode::CODE_LIST_CORPORATE_PAYMENT_MODE_FEE_SUCCESS);
            return $object;
        }
        
        $this->setResponseCode(MessageCode::CODE_LIST_CORPORATE_PAYMENT_MODE_FEE_FAIL);
        return false;
    }
    
    public function updatePaymentModeFee(PaymentModeFee $entity)
    {
        if( $entityOld = $this->getRepository()->findById($entity->getId()) )
        {
            $entity->setUpdatedBy($this->getUpdatedBy());
            
            if( $this->getRepository()->update($entity) )
            {
                $this->fireLogEvent('iafb_remittance.corporate_service_payment_mode_fee', AuditLogAction::UPDATE, $entity->getId(), $entityOld);

                $this->setResponseCode(MessageCode::CODE_EDIT_CORPORATE_PAYMENT_MODE_FEE_SUCCESS);
                return true;
            }
        }
        $this->setResponseCode(MessageCode::CODE_EDIT_CORPORATE_PAYMENT_MODE_FEE_FAIL);
        return false;
    }
    
    public function updateOrInsertPaymentModeFee(PaymentModeFee $entity)
    {
        if( $entityOld = $this->getRepository()->findById($entity->getId()) )
        {
            $entity->setUpdatedBy($this->getUpdatedBy());
            
            if( $this->getRepository()->update($entity) )
            {
                $this->fireLogEvent('iafb_remittance.corporate_service_payment_mode_fee', AuditLogAction::UPDATE, $entity->getId(), $entityOld);

                $this->setResponseCode(MessageCode::CODE_EDIT_CORPORATE_PAYMENT_MODE_FEE_SUCCESS);
                return true;
            }
            else {
                $this->setResponseCode(MessageCode::CODE_EDIT_CORPORATE_PAYMENT_MODE_FEE_FAIL);
                return false;
            }
        }
        else
        {
            $entity->setCreatedBy($this->getUpdatedBy());
            
            if( $this->getRepository()->insert($entity) )
            {
                $this->fireLogEvent('iafb_remittance.corporate_service_payment_mode_fee', AuditLogAction::CREATE, $entity->getId());

                $this->setResponseCode(MessageCode::CODE_ADD_CORPORATE_PAYMENT_MODE_FEE_SUCCESS);
                return true;
            }
            else
            {
                $this->setResponseCode(MessageCode::CODE_ADD_CORPORATE_PAYMENT_MODE_FEE_FAIL);
                return false;
            }
        }
    }

    public function removeByWithOutIds(PaymentModeFeeGroup $entity, array $fee_ids)
    {
        if( $object = $this->getRepository()->removeNotExists($entity, $fee_ids) )
        {
            $this->setResponseCode(MessageCode::CODE_REMOVE_CORPORATE_PAYMENT_MODE_FEE_SUCCESS);
            return $object;
        }
        
        $this->setResponseCode(MessageCode::CODE_REMOVE_CORPORATE_PAYMENT_MODE_FEE_FAIL);
        return false;
    }
    
}
