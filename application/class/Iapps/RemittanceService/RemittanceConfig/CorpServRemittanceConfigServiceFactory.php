<?php

namespace Iapps\RemittanceService\RemittanceConfig;

use Iapps\RemittanceService\RemittanceConfig\RemittanceConfigRepository;

require_once __DIR__ . '/../../../../modules/remittanceconfig/models/Remittance_config_model.php';

class CorpServRemittanceConfigServiceFactory{

    protected static $_instance = NULL;

    public static function build()
    {
        if( CorpServRemittanceConfigServiceFactory::$_instance == NULL )
        {
            $dm = new \Remittance_config_model();
            $repo = new RemittanceConfigRepository($dm);
            CorpServRemittanceConfigServiceFactory::$_instance = new CorpServRemittanceConfigService($repo);
        }

        return CorpServRemittanceConfigServiceFactory::$_instance;
    }
}