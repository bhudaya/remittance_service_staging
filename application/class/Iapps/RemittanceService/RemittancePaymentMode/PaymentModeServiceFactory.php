<?php

namespace Iapps\RemittanceService\RemittancePaymentMode;

require_once './application/modules/remittancepaymentmode/models/Payment_mode_model.php';

class PaymentModeServiceFactory{
    protected static $_instance;

    public static function build()
    {
        if( self::$_instance == NULL )
        {
            $dm = new \Payment_mode_model();
            $repo = new PaymentModeRepository($dm);
            self::$_instance = new PaymentModeService($repo);
        }

        return self::$_instance;
    }
}