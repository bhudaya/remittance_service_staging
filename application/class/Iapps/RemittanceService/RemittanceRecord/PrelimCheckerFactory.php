<?php

namespace Iapps\RemittanceService\RemittanceRecord;

use Iapps\RemittanceService\RemittanceConfig\RemittanceConfig;

class PrelimCheckerFactory{

    public static function build(RemittanceConfig $config)
    {
        if( $config->getRemittanceService()->isDomestic() )
            return new LocalPrelimChecker();
        else
            return new PrelimChecker();
    }
}