<?php

namespace Iapps\RemittanceService\Attribute;

require_once './application/modules/attribute/models/Refund_request_attribute_model.php';

class RefundRequestAttributeServiceFactory{

    protected static $_instance;

    public static function build()
    {
        if( self::$_instance == NULL )
        {
            $dm = new \Refund_request_attribute_model();
            $repo = new RefundRequestAttributeRepository($dm);
            self::$_instance = new RefundRequestAttributeService($repo);
        }

        return self::$_instance;
    }
}