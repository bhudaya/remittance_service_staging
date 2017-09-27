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
 * Description of IPaymentModeFeeGroupDataMapper
 *
 * @author lichao
 */
interface IPaymentModeFeeGroupDataMapper extends IappsBaseDataMapper {
    //put your code here
    
    public function getLastFeeGroupInfo(array $paymentModeIds, $status = NULL);
    
    public function findListByCorporrateServicePaymentModeIds($limit, $page, array $corporateServicePaymentModeIds = NULL, array $paymentModeFeeGroupIds = NULL, $isActive = NULL, $status = NULL);

    public function findActiveByCorporateServicePaymentModeId($corporate_service_payment_mode_id);
  
    public function insert(PaymentModeFeeGroup $entity);
    
    public function update(PaymentModeFeeGroup $entity);

    public function updateStatus(PaymentModeFeeGroup $entity);
    
    public function remove(PaymentModeFeeGroup $entity, $isLogic = true);
    
}
