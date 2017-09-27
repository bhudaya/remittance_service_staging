<?php

namespace Iapps\RemittanceService\Common;

use Iapps\Common\CorporateService\CorporateServicePaymentMode;
use Iapps\Common\CorporateService\CorporateServicePaymentModeRepository;
use Iapps\RemittanceService\Common\CorporateServicePaymentModeExtendedService;

require_once __DIR__ . '/../../../../modules/common/models/Corporate_service_payment_mode_model.php';

class CorporateServicePaymentModeExtendedServiceFactory{

    protected static $_instance = NULL;

    public static function build()
    {
        if( self::$_instance == NULL )
        {
            $dm = new \Corporate_service_payment_mode_model();
            $repo = new RemittanceCorporateServicePaymentModeRepository($dm);
            self::$_instance = new CorporateServicePaymentModeExtendedService($repo, "iafb_remittance.corporate_service_payment_mode");
        }

        return self::$_instance;
    }
}