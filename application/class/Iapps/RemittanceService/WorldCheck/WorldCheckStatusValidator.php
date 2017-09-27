<?php

namespace Iapps\RemittanceService\WorldCheck;

use Iapps\Common\SystemCode\SystemCodeObject;
use Iapps\RemittanceService\Common\SystemCodeServiceFactory;

class WorldCheckStatusValidator {

    public static function validate($code)
    {
        $systemcode = SystemCodeServiceFactory::build();
        return $systemcode->validateSystemCode($code, new WorldCheckStatus());
    }
}
