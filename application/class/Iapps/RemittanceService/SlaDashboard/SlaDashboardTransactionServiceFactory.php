<?php

namespace Iapps\RemittanceService\SlaDashboard;


require_once './application/modules/sladashboard/models/Sla_transaction_model.php';

class SlaDashboardTransactionServiceFactory{

    protected static $_instance = array();

    public static function build()
    {
        if (self::$_instance == NULL) {
            $dm = new \Sla_transaction_model();
            $repo = new SlaDashboardTransactionRepository($dm);
            self::$_instance = new SlaDashboardTransactionService($repo);
        }

        return self::$_instance;
    }
}