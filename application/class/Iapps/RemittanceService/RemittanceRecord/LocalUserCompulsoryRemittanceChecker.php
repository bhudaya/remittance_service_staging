<?php

namespace Iapps\RemittanceService\RemittanceRecord;

use Iapps\RemittanceService\RemittanceConfig\RemittanceConfig;

class LocalUserCompulsoryRemittanceChecker extends UserCompulsoryRemittanceChecker{

    public static function checkRequestEligible($user_profile_id, RemittanceConfig $remittanceConfig)
    {//nothing to check for domestic
        $c = new LocalUserCompulsoryRemittanceChecker();
        $c->setPass();
        return $c;
    }

    public static function check($user_profile_id, RemittanceConfig $remittanceConfig)
    {
        $c = new LocalUserCompulsoryRemittanceChecker();
        $c->setPass();
        return $c;
    }
}