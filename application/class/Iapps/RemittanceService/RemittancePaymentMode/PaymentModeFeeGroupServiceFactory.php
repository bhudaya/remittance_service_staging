<?php

namespace Iapps\RemittanceService\RemittancePaymentMode;

require_once './application/modules/remittancepaymentmode/models/Payment_mode_fee_group_model.php';

class PaymentModeFeeGroupServiceFactory{
    protected static $_instance;

    public static function build()
    {
        if( self::$_instance == NULL )
        {
            $dm = new \Payment_mode_fee_group_model();
            $repo = new PaymentModeFeeGroupRepository($dm);
            self::$_instance = new PaymentModeFeeGroupService($repo);
        }

        return self::$_instance;
    }
}