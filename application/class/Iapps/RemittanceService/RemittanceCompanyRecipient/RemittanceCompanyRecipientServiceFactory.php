<?php

namespace Iapps\RemittanceService\RemittanceCompanyRecipient;

class RemittanceCompanyRecipientServiceFactory{
    protected static $_instance;

    public static function build()
    {
        if( self::$_instance == NULL )
        {
            $_ci = get_instance();
            $_ci->load->model('remittancecompanyrecipient/Remittance_company_recipient_model');
            $repo = new RemittanceCompanyRecipientRepository($_ci->Remittance_company_recipient_model);
            self::$_instance = new RemittanceCompanyRecipientService($repo);
        }

        return self::$_instance;
    }
}