<?php

namespace Iapps\RemittanceService\RemittanceServiceConfig;

class RemittanceServiceConfigServiceFactory{

    protected static $_instance = NULL;

    public static function build()
    {
        if( self::$_instance == NULL )
        {
            $_ci = get_instance();
            $_ci->load->model('remittanceservice/Remittance_service_model');
            $repo = new RemittanceServiceConfigRepository($_ci->Remittance_service_model);
            self::$_instance = new RemittanceServiceConfigService($repo);
        }

        return self::$_instance;
    }
}