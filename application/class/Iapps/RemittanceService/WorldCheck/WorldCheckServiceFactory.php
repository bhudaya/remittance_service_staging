<?php

namespace Iapps\RemittanceService\WorldCheck;

require_once __DIR__ . '/../../../../modules/remittanceofficer/models/World_check_service_model.php';

class WorldCheckServiceFactory{

    protected static $_instance = NULL;

    public static function build()
    {
        if( self::$_instance == NULL )
        {
            $dm = new \World_check_service_model();
            $repo = new WorldCheckRepository($dm);
            self::$_instance = new WorldCheckService($repo);
        }

        return self::$_instance;
    }
}