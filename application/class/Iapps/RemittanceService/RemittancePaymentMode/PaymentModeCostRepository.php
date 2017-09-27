<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Iapps\RemittanceService\RemittancePaymentMode;

use Iapps\Common\Core\IappsBaseRepository;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeCostGroup;
use Iapps\RemittanceService\Common\CacheKey;
/**
 * Description of PaymentModeCostRepository
 *
 * @author lichao
 */
class PaymentModeCostRepository extends IappsBaseRepository {
    //put your code here
    
    public function findListByGroupIds($limit, $page, $costGroupIds = NULL, $status = NULL)
    {
        if( $limit == MAX_VALUE AND $page == 1 AND $status == NULL )
        {//use cache
            list($result, $remaining_ids) = $this->_getListFromCache($costGroupIds, CacheKey::PAYMENT_MODE_COST_PAYMENT_MODE_GROUP_ID, new PaymentModeCostCollection());
            if( count($remaining_ids) > 0)
            {
                $additional_result = $this->getDataMapper()->findListByGroupIds($limit, $page, $remaining_ids, $status);
                if( $additional_result )
                {
                    $this->_setListToCacheAsPaginatedResult("payment_mode_group_id", $remaining_ids, $additional_result, CacheKey::PAYMENT_MODE_COST_PAYMENT_MODE_GROUP_ID);
                    $result->combineCollection($additional_result->getResult());                
                }
            }

            if( count($result->getResult()) > 0)
                return $result;

            return false;
        }
        
        return $this->getDataMapper()->findListByGroupIds($limit, $page, $costGroupIds, $status);
    }
    
    public function findListByCorporrateServicePaymentModeIds($limit, $page, array $corporateServicePaymentModeIds = NULL,  array $paymentModeFeeGroupIds = NULL, $isActive = NULL, $status = NULL, $noCost = NULL)
    {
        return $this->getDataMapper()->findListByCorporrateServicePaymentModeIds($limit, $page, $corporateServicePaymentModeIds, $paymentModeFeeGroupIds, $isActive, $status, $noCost);
    }
    
    public function insert(PaymentModeCost $entity)
    {
        $this->_removeCache($entity);
        return $this->getDataMapper()->insert($entity);
    }
    
    public function update(PaymentModeCost $entity)
    {
        $this->_removeCache($entity);
        return $this->getDataMapper()->update($entity);
    }
    
    public function remove(PaymentModeCost $entity, $isLogic = true)
    {
        $this->_removeCache($entity);
        return $this->getDataMapper()->remove($entity, $isLogic);
    }
    
    public function removeNotExists(PaymentModeCostGroup $entity, array $fee_ids = NULL)
    {
        $this->_removeCache((new PaymentModeCost())->setPaymentModeGroupId($entity->getId()));
        return $this->getDataMapper()->removeNotExists($entity, $fee_ids);
    }
    
    protected function _removeCache(PaymentModeCost $entity)
    {
        $cacheKeys = array(
            CacheKey::PAYMENT_MODE_COST_PAYMENT_MODE_GROUP_ID . $entity->getPaymentModeGroupId()
        );
        
        foreach($cacheKeys AS $key)
            $this->deleteElastiCache($key);
    }
}
