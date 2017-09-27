<?php

namespace Iapps\RemittanceService\RemittanceConfig;

class RemittanceConfigServiceFactory{

    protected static $_instance = array();

    public static function build($version = 1)
    {
        if( !array_key_exists($version, self::$_instance ) )
        {
            $_ci = get_instance();
            $_ci->load->model('remittanceconfig/Remittance_config_model');
            $repo = new RemittanceConfigRepository($_ci->Remittance_config_model);
            switch($version)
            {
                case 1:
                    self::$_instance[$version] = new RemittanceConfigService($repo);
                    break;
                case 2:
                    self::$_instance[$version] = new RemittanceConfigServiceV2($repo);
                    break;
                default:
                    self::$_instance[$version] = new RemittanceConfigService($repo);
                    break;
            }
        }

        return self::$_instance[$version];
    }
}