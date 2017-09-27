<?php

namespace Iapps\RemittanceService\Common;

use Iapps\Common\DepositTracker\DepositHistoryUserRepository;
use Iapps\Common\DepositTracker\DepositHistoryUserService;


require_once __DIR__ . '/../../../../modules/deposittracker/models/Deposit_tracker_history_user_model.php';

class DepositHistoryUserServiceFactory {

    protected static $_instance;

    public static function build()
    {
        if( self::$_instance == NULL )
        {
            $dm = new \Deposit_tracker_history_user_model();
            $repo = new DepositHistoryUserRepository($dm);
            self::$_instance = new DepositHistoryUserService($repo);
        }

        return self::$_instance;
    }
}
