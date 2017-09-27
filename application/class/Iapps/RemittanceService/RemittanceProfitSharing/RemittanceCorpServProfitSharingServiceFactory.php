<?php

namespace Iapps\RemittanceService\RemittanceProfitSharing;

require_once __DIR__ . '/../../../../modules/remittanceprofitsharing/models/Remittance_profit_sharing_model.php';


class RemittanceCorpServProfitSharingServiceFactory
{
    protected static $_instance = NULL;

    public static function build()
    {
        if( self::$_instance == NULL )
        {
            $dm = new \Remittance_profit_sharing_model();
            $repo = new RemittanceCorpServProfitSharingRepository($dm);
            self::$_instance = new RemittanceCorpServProfitSharingService($repo);
        }

        return self::$_instance;
    }
}