<?php

namespace Iapps\RemittanceService\RemittanceCompanyRecipient;

class AgentRemittanceCompanyRecipientServiceFactory{
    protected static $_instance;

    public static function build()
    {
        if( self::$_instance == NULL )
        {
            $_ci = get_instance();
            $_ci->load->model('remittancecompanyrecipient/Remittance_company_recipient_model');
            $repo = new RemittanceCompanyRecipientRepository($_ci->Remittance_company_recipient_model);
            self::$_instance = new AgentRemittanceCompanyRecipientService($repo);
        }

        return self::$_instance;
    }
}