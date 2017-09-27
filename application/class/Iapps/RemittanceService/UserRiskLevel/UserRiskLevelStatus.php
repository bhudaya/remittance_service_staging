<?php

namespace Iapps\RemittanceService\UserRiskLevel;

use Iapps\Common\SystemCode\SystemCodeInterface;

class UserRiskLevelStatus implements SystemCodeInterface
{
    const LOW   = 'low';
    const MED   = 'mid';
    const HIGH  = 'high';

    public static function getSystemGroupCode()
    {
        return 'risk_level_status';
    }
}