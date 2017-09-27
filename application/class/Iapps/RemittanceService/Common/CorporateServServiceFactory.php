<?php

namespace Iapps\RemittanceService\Common;

use Iapps\Common\CorporateService\CorporateService;
use Iapps\Common\CorporateService\CorporateServiceRepository;
use Iapps\Common\CorporateService\CorporateServService;
use Iapps\RemittanceService\RemittanceCorporateService\RemittanceCorporateServRepository;
use Iapps\RemittanceService\RemittanceCorporateService\RemittanceCorporateServService;

require_once __DIR__ . '/../../../../modules/common/models/Corporate_service_model.php';

class CorporateServServiceFactory{

    protected static $_instance = NULL;

    public static function build()
    {
        if( self::$_instance == NULL )
        {
            $dm = new \Corporate_service_model();
            $repo = new RemittanceCorporateServRepository($dm);
            self::$_instance = new RemittanceCorporateServService($repo, "iafb_remittance.corporate_service");
        }

        return self::$_instance;
    }
}