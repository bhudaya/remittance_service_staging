<?php

namespace Iapps\RemittanceService\UserRiskLevel;

use Iapps\Common\SystemCode\SystemCodeObject;
use Iapps\RemittanceService\Common\SystemCodeServiceFactory;

class UserRiskLevelStatusValidator {

    public static function validate($code)
    {
        $systemcode = SystemCodeServiceFactory::build();
        return $systemcode->validateSystemCode($code, new UserRiskLevelStatus());
    }
}
