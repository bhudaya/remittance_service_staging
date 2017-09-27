<?php

namespace Iapps\RemittanceService\Common;

use Iapps\Common\CorporateService\CorporateServicePaymentModeFee;
use Iapps\Common\CorporateService\CorporateServicePaymentModeFeeRepository;
use Iapps\Common\CorporateService\CorporateServicePaymentModeFeeService;

require_once __DIR__ . '/../../../../modules/common/models/Corporate_service_payment_mode_fee_model.php';

class CorporateServicePaymentModeFeeServiceFactory{

    protected static $_instance = NULL;

    public static function build()
    {
        if( CorporateServicePaymentModeFeeServiceFactory::$_instance == NULL )
        {
            $dm = new \Corporate_service_payment_mode_fee_model();
            $repo = new CorporateServicePaymentModeFeeRepository($dm);
            CorporateServicePaymentModeFeeServiceFactory::$_instance = new CorporateServicePaymentModeFeeService($repo, "iafb_remittance.corporate_service_payment_mode_fee");
        }

        return CorporateServicePaymentModeFeeServiceFactory::$_instance;
    }
}