<?php

namespace Iapps\RemittanceService\RemittanceCompanyUser;

class RemittanceCompanyUserServiceFactory{
    protected static $_instance;

    public static function build()
    {
        if( self::$_instance == NULL )
        {
            $_ci = get_instance();
            $_ci->load->model('remittancecompanyuser/Remittance_company_user_model');
            $repo = new RemittanceCompanyUserRepository($_ci->Remittance_company_user_model);
            self::$_instance = new RemittanceCompanyUserService($repo);
        }

        return self::$_instance;
    }
}