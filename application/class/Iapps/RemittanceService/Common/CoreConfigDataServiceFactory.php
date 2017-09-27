<?php

namespace Iapps\RemittanceService\Common;

class CoreConfigDataServiceFactory{

    protected static $_instance = NULL;

    public static function build()
    {
        if( self::$_instance == NULL )
        {
            $_ci = get_instance();
            $_ci->load->model('common/Core_config_data_model');
            $repo = new RemittanceCoreConfigDataRepository($_ci->Core_config_data_model);
            self::$_instance = new RemittanceCoreConfigDataService($repo);
        }

        return self::$_instance;
    }
}