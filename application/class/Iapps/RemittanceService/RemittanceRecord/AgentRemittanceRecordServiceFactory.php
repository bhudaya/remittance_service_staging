<?php

namespace Iapps\RemittanceService\RemittanceRecord;

class AgentRemittanceRecordServiceFactory{

    protected static $_instance;

    public static function build()
    {
        if( self::$_instance == NULL )
        {
            $_ci = get_instance();
            $_ci->load->model('remittancerecord/Remittance_model');
            $repo = new RemittanceRecordRepository($_ci->Remittance_model);
            self::$_instance = new AgentRemittanceRecordService($repo);
        }

        return self::$_instance;
    }
}