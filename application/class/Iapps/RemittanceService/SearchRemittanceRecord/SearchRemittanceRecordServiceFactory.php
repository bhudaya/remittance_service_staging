<?php

namespace Iapps\RemittanceService\SearchRemittanceRecord;

class SearchRemittanceRecordServiceFactory{
    protected static $_instance;

    public static function build()
    {
        if( self::$_instance == NULL )
        {
            $_ci = get_instance();
            $_ci->load->model('remittancerecord/Search_remittance_model');
            $repo = new SearchRemittanceRepository($_ci->Search_remittance_model);
            self::$_instance = new SearchRemittanceRecordService($repo);
        }

        return self::$_instance;
    }
}