<?php

namespace Iapps\RemittanceService\RemittanceCorporateService;

require_once './application/modules/common/models/Corporate_service_model.php';

class RemittanceCorporateServServiceFactory{
    protected static $_instance;

    public static function build()
    {
        if( self::$_instance == NULL )
        {
            $dm = new \Corporate_service_model();
            $repo = new RemittanceCorporateServRepository($dm);
            self::$_instance = new RemittanceCorporateServService($repo);
        }

        return self::$_instance;
    }
}