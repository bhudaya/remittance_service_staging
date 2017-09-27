<?php

namespace Iapps\RemittanceService\RemittanceRecord;

use Iapps\Common\Core\IappsBasicBaseService;
use Iapps\Common\Microservice\UserCreditService\PrelimCheckServiceFactory;

class PrelimChecker extends IappsBasicBaseService{

    public static function check($remittanceId)
    {//to be implemented

        $prelimServ = PrelimCheckServiceFactory::build();
        if( $prelimCheck = $prelimServ->check($remittanceId) )
            return $prelimCheck->isPass();

        return false;
    }
}