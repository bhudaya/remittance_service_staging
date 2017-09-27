<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Iapps\RemittanceService\RemittancePaymentMode;

use Iapps\Common\Core\IappsBaseDataMapper;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeFeeGroup;

/**
 * Description of IPaymentModeFeeDataMapper
 *
 * @author lichao
 */
interface IPaymentModeFeeDataMapper extends IappsBaseDataMapper {

    //put your code here

    public function findListByGroupId($feeGroupId);

    public function findListByGroupIds($limit, $page, $feeGroupIds = NULL);

    public function findListByCorporrateServicePaymentModeIds($limit, $page, array $corporateServicePaymentModeIds = NULL, array $paymentModeFeeGroupIds = NULL, $isActive = NULL, $status = NULL);
//
    public function insert(PaymentModeFee $entity);

    public function update(PaymentModeFee $entity);

    public function remove(PaymentModeFee $entity, $isLogic = true);
    
    public function removeNotExists(PaymentModeFeeGroup $entity, array $fee_ids, $isLogic = true);
}
