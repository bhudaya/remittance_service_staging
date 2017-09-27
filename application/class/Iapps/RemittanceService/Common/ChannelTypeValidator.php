<?php

namespace Iapps\RemittanceService\Common;

class ChannelTypeValidator {

    public static function validate($code)
    {
        $systemcode = SystemCodeServiceFactory::build();
        return $systemcode->validateSystemCode($code, new ChannelType());
    }
}
