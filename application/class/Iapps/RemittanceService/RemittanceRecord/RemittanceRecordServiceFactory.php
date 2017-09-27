<?php

namespace Iapps\RemittanceService\RemittanceRecord;
require_once './application/modules/remittancerecord/models/Remittance_model.php';

class RemittanceRecordServiceFactory{
    protected static $_instance;

    public static function build()
    {
        if( self::$_instance == NULL )
        {
            $dm = new \Remittance_model();
            $repo = new RemittanceRecordRepository($dm);
            self::$_instance = new RemittanceRecordService($repo);
        }

        return self::$_instance;
    }
}