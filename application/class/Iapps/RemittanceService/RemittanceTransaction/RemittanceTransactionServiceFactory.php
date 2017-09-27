<?php

namespace Iapps\RemittanceService\RemittanceTransaction;

use Iapps\RemittanceService\Common\SystemCodeServiceFactory;

require_once './application/modules/remittancetransaction/models/Remittance_transaction_model.php';

class RemittanceTransactionServiceFactory{

    protected static $_instance;

    public static function build()
    {
        if( self::$_instance == NULL )
        {
            $t_dm = new \Remittance_transaction_model();
            $t_repo = new RemittanceTransactionRepository($t_dm);

            $ti_serv = RemittanceTransactionItemServiceFactory::build();
            $sc_serv = SystemCodeServiceFactory::build();

            self::$_instance = new RemittanceTransactionService($t_repo, $ti_serv, $sc_serv);
        }

        return self::$_instance;
    }
}