<?php

namespace Iapps\RemittanceService\RemittancePaymentMode;

require_once './application/modules/remittancepaymentmode/models/Payment_mode_cost_group_model.php';

class PaymentModeCostGroupServiceFactory{
    protected static $_instance;

    public static function build()
    {
        if( self::$_instance == NULL )
        {
            $dm = new \Payment_mode_cost_group_model();
            $repo = new PaymentModeCostGroupRepository($dm);
            self::$_instance = new PaymentModeCostGroupService($repo);
        }

        return self::$_instance;
    }
}