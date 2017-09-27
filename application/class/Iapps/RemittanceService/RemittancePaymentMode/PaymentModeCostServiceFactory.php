<?php

namespace Iapps\RemittanceService\RemittancePaymentMode;

require_once './application/modules/remittancepaymentmode/models/Payment_mode_cost_model.php';

class PaymentModeCostServiceFactory{
    protected static $_instance;

    public static function build()
    {
        if( self::$_instance == NULL )
        {
            $dm = new \Payment_mode_cost_model();
            $repo = new PaymentModeCostRepository($dm);
            self::$_instance = new PaymentModeCostService($repo);
        }

        return self::$_instance;
    }
}