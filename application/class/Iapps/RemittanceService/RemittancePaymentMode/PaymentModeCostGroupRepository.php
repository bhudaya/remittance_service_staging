<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Iapps\RemittanceService\RemittancePaymentMode;

use Iapps\Common\Core\IappsBaseRepository;

/**
 * Description of PaymentModeCostGroupRepository
 *
 * @author lichao
 */
class PaymentModeCostGroupRepository extends IappsBaseRepository {
    //put your code here
    
    public function getLastCostGroupInfo(array $paymentModeIds, $status = NULL)
    {
        return $this->getDataMapper()->getLastCostGroupInfo($paymentModeIds, $status);
    }
    
    public function getListByCorporrateServicePaymentModeIds($limit, $page, array $corporateServicePaymentModeIds = NULL, array $paymentModeCostGroupIds = NULL, $isActive = NULL, $status = NULL, $noCost = NULL)
    {
        return $this->getDataMapper()->findListByCorporrateServicePaymentModeIds($limit, $page, $corporateServicePaymentModeIds, $paymentModeCostGroupIds, $isActive, $status, $noCost);
    }
    
    public function insert(PaymentModeCostGroup $entity)
    {
        return $this->getDataMapper()->insert($entity);
    }
    
    public function update(PaymentModeCostGroup $entity)
    {
        return $this->getDataMapper()->update($entity);
    }

    public function updateStatus(PaymentModeCostGroup $entity)
    {
        return $this->getDataMapper()->updateStatus($entity);
    }
    
    public function remove(PaymentModeCostGroup $entity, $isLogic = true)
    {
        return $this->getDataMapper()->remove($entity, $isLogic);
    }
    
}
