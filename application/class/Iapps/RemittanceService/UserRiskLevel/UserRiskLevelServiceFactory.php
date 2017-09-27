<?php

namespace Iapps\RemittanceService\UserRiskLevel;

require_once __DIR__ . '/../../../../modules/remittanceofficer/models/User_risk_level_service_model.php';

class UserRiskLevelServiceFactory{

    protected static $_instance = NULL;

    public static function build()
    {
        if( self::$_instance == NULL )
        {
            $dm = new \User_risk_level_service_model();
            $repo = new UserRiskLevelRepository($dm);
            self::$_instance = new UserRiskLevelService($repo);
        }

        return self::$_instance;
    }
}