<?php

namespace Iapps\RemittanceService\RemittanceConfig;

class AgentRemittanceConfigServiceFactory{

    protected static $_instance;

    public static function build()
    {
        if( self::$_instance == NULL )
        {
            $_ci = get_instance();
            $_ci->load->model('remittanceconfig/Remittance_config_model');
            $repo = new RemittanceConfigRepository($_ci->Remittance_config_model);
            self::$_instance = new AgentRemittanceConfigService($repo);
        }

        return self::$_instance;
    }
}