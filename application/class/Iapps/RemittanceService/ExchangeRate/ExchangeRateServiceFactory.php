<?php

namespace Iapps\RemittanceService\ExchangeRate;

require_once './application/modules/exchangerate/models/Exchange_rate_model.php';

class ExchangeRateServiceFactory{
    protected static $_instance;

    public static function build()
    {
        if( self::$_instance == NULL )
        {
            $dm = new \Exchange_rate_model();
            $repo = new ExchangeRateRepository($dm);
            self::$_instance = new ExchangeRateService($repo);
        }

        return self::$_instance;
    }
}