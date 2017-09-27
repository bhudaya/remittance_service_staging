<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Iapps\RemittanceService\RemittancePaymentMode;

use Iapps\Common\SystemCode\SystemCodeInterface;

/**
 * Description of PaymentModeFeeGroupStatus
 *
 * @author lichao
 */
class PaymentModeFeeMultitierType implements SystemCodeInterface {

    //put your code here
    CONST CODE_FLAT = "flat";
    CONST CODE_LESSTHAN = "less_than";
    CONST CODE_RANGE = "range";
    CONST CODE_GREATERTHAN = "greater_than";

    public static function getSystemGroupCode() {
        return "payment_mode_fee_multitier_type";
    }

}
