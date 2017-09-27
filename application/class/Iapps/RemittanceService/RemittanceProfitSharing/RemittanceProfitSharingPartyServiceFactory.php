<?php

namespace Iapps\RemittanceService\RemittanceProfitSharing;

require_once __DIR__ . '/../../../../modules/remittanceprofitsharing/models/Remittance_profti_sharing_party_model.php';

class RemittanceProfitSharingPartyServiceFactory{

    protected static $_instance = NULL;

    public static function build()
    {
        if( self::$_instance == NULL )
        {
            $dm = new \Remittance_profti_sharing_party_model();
            $repo = new RemittanceProfitSharingPartyRepository($dm);
            self::$_instance = new RemittanceProfitSharingPartyService($repo);
        }

        return self::$_instance;
    }
}