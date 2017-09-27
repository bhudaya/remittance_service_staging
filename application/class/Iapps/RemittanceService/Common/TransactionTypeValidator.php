<?php

namespace Iapps\RemittanceService\Common;

use Iapps\Common\SystemCode\SystemCodeObject;
use Iapps\RemittanceService\Common\SystemCodeServiceFactory;

class TransactionTypeValidator {

    public static function validate($code)
    {
        $systemcode = SystemCodeServiceFactory::build();
        return $systemcode->validateSystemCode($code, new TransactionType());
    }
}
