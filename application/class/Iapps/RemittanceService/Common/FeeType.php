<?php

namespace Iapps\RemittanceService\Common;

use Iapps\Common\SystemCode\SystemCodeInterface;

class FeeType implements SystemCodeInterface{

    const CODE_SPREAD       = 'spread';
    const CODE_FEE       = 'fee';
    const CODE_PAYMENT_MODE_FEE = "payment_mode_fee";
    const CODE_CORPORATE_SERVICE_FEE = "corporate_service_fee";

    public static function getSystemGroupCode()
    {
        return 'fee_type';
    }
}