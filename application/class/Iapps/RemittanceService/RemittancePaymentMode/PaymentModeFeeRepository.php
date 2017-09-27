<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Iapps\RemittanceService\RemittancePaymentMode;

use Iapps\Common\Core\IappsBaseRepository;
use Iapps\RemittanceService\Common\CacheKey;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeFeeGroup;

/**
 * Description of PaymentModeFeeRepository
 *
 * @author lichao
 */
class PaymentModeFeeRepository extends IappsBaseRepository {
    //put your code here

    public function findListByGroupId($feeGroupId)
    {
        $cacheKey = CacheKey::REMITTANCE_PAYMENT_MODE_FEE_FEE_GROUP_ID.$feeGroupId;
        if( !$result = $this->getElasticCache($cacheKey) )
        {
            if( $result = $this->getDataMapper()->findListByGroupId($feeGroupId) )
            {
                $this->setElasticCache($cacheKey, $result);
            }
        }

        return $result;
    }

    public function findListByGroupIds($limit, $page, $feeGroupIds = NULL)
    {
        return $this->getDataMapper()->findListByGroupIds($limit, $page, $feeGroupIds);
    }
    
    public function findListByCorporrateServicePaymentModeIds($limit, $page, array $corporateServicePaymentModeIds = NULL,  array $paymentModeFeeGroupIds = NULL, $isActive = NULL, $status = NULL)
    {
        return $this->getDataMapper()->findListByCorporrateServicePaymentModeIds($limit, $page, $corporateServicePaymentModeIds, $paymentModeFeeGroupIds, $isActive, $status);
    }
    
    public function insert(PaymentModeFee $entity)
    {
        $this->_removeCache($entity);
        return $this->getDataMapper()->insert($entity);
    }
    
    public function update(PaymentModeFee $entity)
    {
        $this->_removeCache($entity);
        return $this->getDataMapper()->update($entity);
    }
    
    public function remove(PaymentModeFee $entity, $isLogic = true)
    {
        $this->_removeCache($entity);
        return $this->getDataMapper()->remove($entity, $isLogic);
    }
    
    public function removeNotExists(PaymentModeFeeGroup $entity, array $fee_ids)
    {
        $paymentModeFee = new PaymentModeFee();
        $paymentModeFee->setCorporateServicePaymentModeFeeGroupId($entity->getId());
        $this->_removeCache($paymentModeFee);

        return $this->getDataMapper()->removeNotExists($entity, $fee_ids);
    }

    protected function _removeCache(PaymentModeFee $paymentModeFee)
    {
        $cacheKeys = array(
            CacheKey::REMITTANCE_PAYMENT_MODE_FEE_FEE_GROUP_ID. $paymentModeFee->getCorporateServicePaymentModeFeeGroupId()
        );

        foreach($cacheKeys AS $cacheKey)
        {
            $this->deleteElastiCache($cacheKey);
        }
    }
}
