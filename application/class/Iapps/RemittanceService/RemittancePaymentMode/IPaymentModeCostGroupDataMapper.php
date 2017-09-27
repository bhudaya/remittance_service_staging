<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Iapps\RemittanceService\RemittancePaymentMode;

use Iapps\Common\Core\IappsBaseDataMapper;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeCostGroup;

/**
 * Description of IPaymentModeFeeGroupDataMapper
 *
 * @author lichao
 */
interface IPaymentModeCostGroupDataMapper extends IappsBaseDataMapper {
    //put your code here
    
    public function getLastCostGroupInfo(array $paymentModeIds, $status = NULL);
    
    public function findListByCorporrateServicePaymentModeIds($limit, $page, array $corporateServicePaymentModeIds = NULL, array $paymentModeCostGroupIds = NULL, $isActive = NULL, $status = NULL, $noCost = NULL);
  
    public function insert(PaymentModeCostGroup $entity);
    
    public function update(PaymentModeCostGroup $entity);

    public function updateStatus(PaymentModeCostGroup $entity);
    
    public function remove(PaymentModeCostGroup $entity, $isLogic = true);
    
}
