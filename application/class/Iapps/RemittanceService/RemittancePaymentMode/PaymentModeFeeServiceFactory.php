<?php

namespace Iapps\RemittanceService\RemittancePaymentMode;

require_once './application/modules/remittancepaymentmode/models/Payment_mode_fee_model.php';

class PaymentModeFeeServiceFactory{
    protected static $_instance;

    public static function build()
    {
        if( self::$_instance == NULL )
        {
            $dm = new \Payment_mode_fee_model();
            $repo = new PaymentModeFeeRepository($dm);
            self::$_instance = new PaymentModeFeeService($repo);
        }

        return self::$_instance;
    }
}