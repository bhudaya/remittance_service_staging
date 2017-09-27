<?php

namespace Iapps\RemittanceService\WorldCheck;

use Iapps\Common\SystemCode\SystemCodeInterface;

class WorldCheckStatus implements SystemCodeInterface
{
    const PASS  = 'pass';
    const FAIL  = 'fail';

    public static function getSystemGroupCode()
    {
        return 'world_check_status';
    }
}