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
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeCostGroup;
use Iapps\Common\AuditLog\AuditLogAction;

/**
 * Description of PaymentModeCostService
 *
 * @author lichao
 */
class PaymentModeCostService extends IappsBaseService {
    //put your code here
    
    public function insertPaymentModeCost(PaymentModeCost $entity)
    {
        $entity->setCreatedBy($this->getUpdatedBy());
        
        if( $this->getRepository()->insert($entity) )
        {
            $this->fireLogEvent('iafb_remittance.payment_mode_cost', AuditLogAction::CREATE, $entity->getId());
            
            $this->setResponseCode(MessageCode::CODE_ADD_CORPORATE_PAYMENT_MODE_COST_SUCCESS);
            return true;
        }
        
        $this->setResponseCode(MessageCode::CODE_ADD_CORPORATE_PAYMENT_MODE_COST_FAIL);
        return false;
    }
    
    public function getListByGroupIds($limit, $page, $costGroupIds = NULL, $status = NULL)
    {
        if( $object = $this->getRepository()->findListByGroupIds($limit, $page, $costGroupIds, $status) )
        {
            $this->setResponseCode(MessageCode::CODE_LIST_CORPORATE_PAYMENT_MODE_COST_SUCCESS);
            return $object;
        }
        $this->setResponseCode(MessageCode::CODE_LIST_CORPORATE_PAYMENT_MODE_COST_FAIL);
        return false;
    }

    public function getListByCorporrateServicePaymentModeIds($limit, $page, array $paymentModeIds = NULL, array $costGroupIds = NULL, $isActive = NULL, $status = NULL, $noCost = NULL)
    {
        if( $object = $this->getRepository()->findListByCorporrateServicePaymentModeIds($limit, $page, $paymentModeIds, $costGroupIds, $isActive, $status, $noCost) )
        {
            $this->setResponseCode(MessageCode::CODE_LIST_CORPORATE_PAYMENT_MODE_COST_SUCCESS);
            return $object;
        }
        
        $this->setResponseCode(MessageCode::CODE_LIST_CORPORATE_PAYMENT_MODE_COST_FAIL);
        return false;
    }
    
    public function updatePaymentModeFee(PaymentModeCost $entity)
    {
        if( $entityOld = $this->getRepository()->findById($entity->getId()) )
        {
            $entity->setUpdatedBy($this->getUpdatedBy());
            
            if( $this->getRepository()->update($entity) )
            {
                $this->fireLogEvent('iafb_remittance.payment_mode_cost', AuditLogAction::UPDATE, $entity->getId(), $entityOld);

                $this->setResponseCode(MessageCode::CODE_EDIT_CORPORATE_PAYMENT_MODE_COST_SUCCESS);
                return true;
            }
        }
        $this->setResponseCode(MessageCode::CODE_EDIT_CORPORATE_PAYMENT_MODE_COST_FAIL);
        return false;
    }
    
    public function updateOrInsertPaymentModeCost(PaymentModeCost $entity)
    {
        if( $entityOld = $this->getRepository()->findById($entity->getId()) )
        {
            $entity->setUpdatedBy($this->getUpdatedBy());
            
            if( $this->getRepository()->update($entity) )
            {
                $this->fireLogEvent('iafb_remittance.payment_mode_cost', AuditLogAction::UPDATE, $entity->getId(), $entityOld);

                $this->setResponseCode(MessageCode::CODE_EDIT_CORPORATE_PAYMENT_MODE_COST_SUCCESS);
                return true;
            }
            else {
                $this->setResponseCode(MessageCode::CODE_EDIT_CORPORATE_PAYMENT_MODE_COST_FAIL);
                return false;
            }
        }
        else
        {
            $entity->setCreatedBy($this->getUpdatedBy());
            
            if( $this->getRepository()->insert($entity) )
            {
                $this->fireLogEvent('iafb_remittance.payment_mode_cost', AuditLogAction::CREATE, $entity->getId());

                $this->setResponseCode(MessageCode::CODE_ADD_CORPORATE_PAYMENT_MODE_COST_SUCCESS);
                return true;
            }
            else
            {
                $this->setResponseCode(MessageCode::CODE_ADD_CORPORATE_PAYMENT_MODE_COST_FAIL);
                return false;
            }
        }
    }

    public function removeByWithOutIds(PaymentModeCostGroup $entity, array $cost_ids = NULL)
    {
        if( $object = $this->getRepository()->removeNotExists($entity, $cost_ids) )
        {
            $this->setResponseCode(MessageCode::CODE_REMOVE_CORPORATE_PAYMENT_MODE_COST_SUCCESS);
            return $object;
        }
        
        $this->setResponseCode(MessageCode::CODE_REMOVE_CORPORATE_PAYMENT_MODE_COST_FAIL);
        return false;
    }
    
}
