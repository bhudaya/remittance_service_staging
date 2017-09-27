<?php

namespace Iapps\RemittanceService\Common;

use Iapps\Common\DepositTracker\DepositTrackerReasonsRepository;
use Iapps\Common\DepositTracker\DepositTrackerReasonsService;

require_once './application/modules/deposittracker/models/Deposit_tracker_reasons_model.php';

class DepositTrackerReasonsServiceFactory {

    protected static $_instance;

    public static function build()
    {
        if( self::$_instance == NULL )
        {
            $dm = new \Deposit_tracker_reasons_model();
            $repo = new DepositTrackerReasonsRepository($dm);
            self::$_instance = new DepositTrackerReasonsService($repo);
        }

        return self::$_instance;
    }
}
