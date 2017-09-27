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
 * Description of PaymentModeCostGroupRepository
 *
 * @author lichao
 */
class PaymentModeRepository extends IappsBaseRepository {
    //put your code here
        
    public function findListByCorporeateServiceId($limit, $page, array $corporateServiceIds = NULL, array $paymentModeIds = NULL, $isActive = NULL, $direction = NULL)
    {
        if( $paymentModeIds == NULL AND $isActive == NULL AND $direction == NULL AND $limit == MAX_VALUE)
        {//try to use cache if only corporate service ids is given
            list($result, $remaining_ids) = $this->_getListFromCache($corporateServiceIds, CacheKey::REMITTANCE_PAYMENT_MODE_CORPORATE_SERVICE_ID2, new PaymentModeCollection());
            if( count($remaining_ids)>0 )
            {//get remaining
                $additional_result = $this->getDataMapper()->findListByCorporeateServiceId($limit, $page, $corporateServiceIds, $paymentModeIds, $isActive, $direction);
                if( $additional_result )
                {//save remaining to cache
                    $this->_setListToCacheAsPaginatedResult("corporate_service_id", $remaining_ids, $additional_result, CacheKey::REMITTANCE_PAYMENT_MODE_CORPORATE_SERVICE_ID2);
                    $result->combineCollection($additional_result->getResult());                    
                }
            }
            
            if( count($result->getResult()) > 0 )
                return $result;
            
            return false;
        }
        
        return $this->getDataMapper()->findListByCorporeateServiceId($limit, $page, $corporateServiceIds, $paymentModeIds, $isActive, $direction);
    }
    
    public function exists(PaymentMode $entity)
    {
        return $this->getDataMapper()->exists($entity);
    }
    
    public function insert(PaymentMode $entity)
    {
        $this->_removeCache($entity);
        return $this->getDataMapper()->insert($entity);
    }
    
    public function update(PaymentMode $entity)
    {
        $this->_removeCache($entity);
        return $this->getDataMapper()->update($entity);
    }

    public function updateFields(PaymentMode $entity, array $fields, array $where)
    {
        $this->_removeCache($entity);
        return $this->getDataMapper()->updateFields($entity, $fields, $where);
    }
    
    public function remove(PaymentMode $entity, $isLogic = true)
    {
        $this->_removeCache($entity);
        return $this->getDataMapper()->remove($entity, $isLogic);
    }

    protected function _removeCache(PaymentMode $paymentMode)
    {//need to remove this cache as two model changing the same table!!!
        $cacheKeys = array(
          CacheKey::REMITTANCE_PAYMENT_MODE_CORPORATE_SERVICE_ID . $paymentMode->getCorporateServiceId(),
            CacheKey::REMITTANCE_PAYMENT_MODE_CORPORATE_SERVICE_ID2 . $paymentMode->getCorporateServiceId()
        );

        foreach($cacheKeys AS $cacheKey)
        {
            $this->deleteElastiCache($cacheKey);
        }
    }
}
