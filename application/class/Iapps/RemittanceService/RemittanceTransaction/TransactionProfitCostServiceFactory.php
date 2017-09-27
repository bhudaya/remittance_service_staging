<?php

namespace Iapps\RemittanceService\RemittanceTransaction;

require_once './application/modules/remittancetransaction/models/Transaction_profit_cost_model.php';

class TransactionProfitCostServiceFactory{

    protected static $_instance;

    public static function build()
    {
        if( self::$_instance == NULL )
        {
            $dm = new \Transaction_profit_cost_model();
            $repo = new TransactionProfitCostRepository($dm);

            self::$_instance = new TransactionProfitCostService($repo);
        }

        return self::$_instance;
    }
}