<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Iapps\RemittanceService\RemittancePaymentMode;

use Iapps\Common\Core\IappsBaseRepository;
use Iapps\RemittanceService\Common\CacheKey;

/**
 * Description of PaymentModeFeeGroupRepository
 *
 * @author lichao
 */
class PaymentModeFeeGroupRepository extends IappsBaseRepository {
    //put your code here
    
    public function getLastFeeGroupInfo(array $paymentModeIds, $status = NULL)
    {
        return $this->getDataMapper()->getLastFeeGroupInfo($paymentModeIds, $status);
    }
    
    public function getListByCorporrateServicePaymentModeIds($limit, $page, array $corporateServicePaymentModeIds = NULL, array $paymentModeFeeGroupIds = NULL, $isActive = NULL, $status = NULL)
    {
        return $this->getDataMapper()->findListByCorporrateServicePaymentModeIds($limit, $page, $corporateServicePaymentModeIds, $paymentModeFeeGroupIds, $isActive, $status);
    }

    public function findActiveByCorporateServicePaymentModeId($corporateServicePaymentModeId)
    {
        $cacheKey = CacheKey::REMITTANCE_PAYMENT_MODE_FEE_GROUP_ACTIVE_PAYMENT_MODE_ID.$corporateServicePaymentModeId;

        if( !$result = $this->getElasticCache($cacheKey) )
        {
            if( $result = $this->getDataMapper()->findActiveByCorporateServicePaymentModeId($corporateServicePaymentModeId) )
            {
                $this->setElasticCache($cacheKey, $result);
            }
        }

        return $result;
    }
    
    public function insert(PaymentModeFeeGroup $entity)
    {
        $this->_removeCache($entity);
        return $this->getDataMapper()->insert($entity);
    }
    
    public function update(PaymentModeFeeGroup $entity)
    {
        $this->_removeCache($entity);
        return $this->getDataMapper()->update($entity);
    }

    public function updateStatus(PaymentModeFeeGroup $entity)
    {
        $this->_removeCache($entity);
        return $this->getDataMapper()->updateStatus($entity);
    }
    
    public function remove(PaymentModeFeeGroup $entity, $isLogic = true)
    {
        $this->_removeCache($entity);
        return $this->getDataMapper()->remove($entity, $isLogic);
    }


    protected function _removeCache(PaymentModeFeeGroup $paymentModeFeeGroup)
    {
        $cacheKeys = array(
            CacheKey::REMITTANCE_PAYMENT_MODE_FEE_GROUP_ACTIVE_PAYMENT_MODE_ID.$paymentModeFeeGroup->getCorporateServicePaymentModeId()
        );

        foreach($cacheKeys AS $cacheKey)
        {
            $this->deleteElastiCache($cacheKey);
        }
    }
}
