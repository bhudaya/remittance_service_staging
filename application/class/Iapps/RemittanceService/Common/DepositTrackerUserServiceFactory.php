<?php

namespace Iapps\RemittanceService\Common;

use Iapps\Common\DepositTracker\DepositTrackerUser;
use Iapps\Common\DepositTracker\DepositTrackerUserRepository;
use Iapps\Common\DepositTracker\DepositTrackerUserService;

require_once './application/modules/deposittracker/models/Deposit_tracker_user_model.php';

class DepositTrackerUserServiceFactory {

    protected static $_instance;

    public static function build()
    {
        if( self::$_instance == NULL )
        {
            $dm = new \Deposit_tracker_user_model();
            $repo = new DepositTrackerUserRepository($dm);
            self::$_instance = new DepositTrackerUserService($repo);
        }

        return self::$_instance;
    }

}
