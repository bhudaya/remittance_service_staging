<?php

namespace Iapps\RemittanceService\Common;

use Iapps\Common\CorporateService\CorporateServiceFee;
use Iapps\Common\CorporateService\CorporateServiceFeeRepository;
use Iapps\Common\CorporateService\CorporateServiceFeeService;

require_once __DIR__ . '/../../../../modules/common/models/Corporate_service_fee_model.php';

class CorporateServiceFeeServiceFactory{

    protected static $_instance = NULL;

    public static function build()
    {
        if( CorporateServiceFeeServiceFactory::$_instance == NULL )
        {
            $dm = new \Corporate_service_fee_model();
            $repo = new CorporateServiceFeeRepository($dm);
            CorporateServiceFeeServiceFactory::$_instance = new CorporateServiceFeeService($repo, "iafb_remittance.corporate_service_fee");
        }

        return CorporateServiceFeeServiceFactory::$_instance;
    }
}