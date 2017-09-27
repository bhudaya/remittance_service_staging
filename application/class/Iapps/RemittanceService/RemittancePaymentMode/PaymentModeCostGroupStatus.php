<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Iapps\RemittanceService\RemittancePaymentMode;

use Iapps\Common\SystemCode\SystemCodeInterface;

/**
 * Description of PaymentModeCostGroupStatus
 *
 * @author lichao
 */
class PaymentModeCostGroupStatus implements SystemCodeInterface {
    //put your code here
    
    CONST CODE_PENDING = 'pending';
    CONST CODE_APPROVED = 'approved';
    CONST CODE_REJECTED = 'rejected';

    public static function getSystemGroupCode() {
        
        return "payment_mode_cost_group_status";
    }

}
