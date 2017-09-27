<?php

namespace Iapps\RemittanceService\RemittanceRecord;

class LocalPrelimChecker extends PrelimChecker{

    public static function check($remittanceId)
    {//no prelim check required
        return true;
    }
}