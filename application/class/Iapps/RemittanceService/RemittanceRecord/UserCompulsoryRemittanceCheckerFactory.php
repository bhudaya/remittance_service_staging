<?php

namespace Iapps\RemittanceService\RemittanceRecord;

use Iapps\RemittanceService\RemittanceConfig\RemittanceConfig;

class UserCompulsoryRemittanceCheckerFactory{

    public static function build(RemittanceConfig $config)
    {
        if( $config->getRemittanceService()->isDomestic() )
            return new LocalUserCompulsoryRemittanceChecker();
        else
            return new UserCompulsoryRemittanceChecker();
    }
}