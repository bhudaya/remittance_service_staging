<?php

namespace Iapps\RemittanceService\Common;

use Iapps\Common\SystemCode\SystemCodeRepository;
use Iapps\Common\SystemCode\SystemCodeService;

require_once __DIR__ . '/../../../../modules/common/models/Systemcode_model.php';

class SystemCodeServiceFactory{

    protected static $_instance = NULL;

    public static function build()
    {
        if( self::$_instance == NULL )
        {
            $dm = new \Systemcode_model();
            $repo = new SystemCodeRepository($dm);
            self::$_instance = new SystemCodeService($repo);
        }

        return self::$_instance;
    }
}