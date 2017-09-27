<?php

namespace Iapps\RemittanceService\Common;

use Iapps\Common\DepositTracker\DepositTrackerEmail;
use Iapps\Common\DepositTracker\DepositTrackerEmailRepository;
use Iapps\Common\DepositTracker\DepositTrackerEmailService;

require_once './application/modules/deposittracker/models/Deposit_tracker_email_model.php';

class DepositTrackerEmailServiceFactory {

    protected static $_instance;

    public static function build()
    {
        if( self::$_instance == NULL )
        {
            $dm = new \Deposit_tracker_email_model();
            $repo = new DepositTrackerEmailRepository($dm);
            self::$_instance = new DepositTrackerEmailService($repo);
        }

        return self::$_instance;
    }
}
