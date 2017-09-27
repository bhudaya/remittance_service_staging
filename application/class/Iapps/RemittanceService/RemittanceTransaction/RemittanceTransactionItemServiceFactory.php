<?php

namespace Iapps\RemittanceService\RemittanceTransaction;

use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransactionItemRepository;

require_once __DIR__ . '/../../../../modules/remittancetransaction/models/Remittance_transaction_item_model.php';

class RemittanceTransactionItemServiceFactory{

    protected static $_instance = NULL;

    public static function build()
    {
        if( RemittanceTransactionItemServiceFactory::$_instance == NULL )
        {
            $dm = new \Remittance_transaction_item_model();
            $repo = new RemittanceTransactionItemRepository($dm);
            RemittanceTransactionItemServiceFactory::$_instance = new RemittanceTransactionItemService($repo);
        }

        return RemittanceTransactionItemServiceFactory::$_instance;
    }
}