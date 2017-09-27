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
 * Description of IPaymentModeCostDataMapper
 *
 * @author lichao
 */
interface IPaymentModeCostDataMapper extends IappsBaseDataMapper {
    //put your code here
    
    public function findListByGroupIds($limit, $page, $costGroupIds = NULL, $status = NULL);
    public function findListByCorporrateServicePaymentModeIds($limit, $page, array $corporateServicePaymentModeIds = NULL, array $paymentModeCostGroupIds = NULL, $isActive = NULL, $status = NULL, $noCost = NULL);
    public function insert(PaymentModeCost $entity);
    public function update(PaymentModeCost $entity);
    public function remove(PaymentModeCost $entity, $isLogic = true);
    public function removeNotExists(PaymentModeCostGroup $entity, array $cost_ids = NULL, $isLogic = true);
    
}
