<?php

namespace Iapps\RemittanceService\Common;

use Iapps\Common\DepositTracker\DepositTracker;
use Iapps\Common\DepositTracker\DepositTrackerRepository;
use Iapps\Common\DepositTracker\DepositTrackerService;

require_once './application/modules/deposittracker/models/Deposit_tracker_model.php';

class DepositTrackerServiceFactory {

    protected static $_instance;

    public static function build()
    {
        if( self::$_instance == NULL )
        {
            $dm = new \Deposit_tracker_model();
            $repo = new DepositTrackerRepository($dm);
            self::$_instance = new DepositTrackerService($repo);
        }

        return self::$_instance;
    }
}
