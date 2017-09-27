<?php

namespace Iapps\RemittanceService\Common;

use Iapps\Common\DepositTracker\DepositTrackerRequest;
use Iapps\Common\DepositTracker\DepositTrackerRequestRepository;
use Iapps\Common\DepositTracker\DepositTrackerRequestService;


require_once __DIR__ . '/../../../../modules/deposittracker/models/Deposit_tracker_request_model.php';

class DepositTrackerRequestServiceFactory {

    protected static $_instance;

    public static function build()
    {
        if( self::$_instance == NULL )
        {
            $dm = new \Deposit_tracker_request_model();
            $repo = new DepositTrackerRequestRepository($dm);
            self::$_instance = new DepositTrackerRequestService($repo);
        }

        return self::$_instance;
    }
}
