<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Iapps\RemittanceService\RemittancePaymentMode;

use Iapps\Common\Core\IappsBaseDataMapper;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentMode;

/**
 * Description of IPaymentModeDataMapper
 *
 * @author lichao
 */
interface IPaymentModeDataMapper extends IappsBaseDataMapper {
    //put your code here
    
    public function findListByCorporeateServiceId($limit, $page, array $corporateServiceIds = NULL, array $paymentModeIds = NULL, $isActive = NULL, $direction = NULL);
    public function exists(PaymentMode $entity);
    public function insert(PaymentMode $entity);
    public function update(PaymentMode $entity);
    public function updateFields(PaymentMode $entity, array $fields, array $where);
    public function remove(PaymentMode $entity, $isLogic = true);
    
}
