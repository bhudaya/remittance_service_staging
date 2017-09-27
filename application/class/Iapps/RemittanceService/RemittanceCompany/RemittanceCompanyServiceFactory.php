<?php

namespace Iapps\RemittanceService\RemittanceCompany;

class RemittanceCompanyServiceFactory{
    protected static $_instance;

    public static function build($client = NULL)
    {
        if( self::$_instance == NULL )
        {
            $_ci = get_instance();
            $_ci->load->model('remittancecompany/Remittance_company_model');
            $repo = new RemittanceCompanyRepository($_ci->Remittance_company_model);
            switch($client)
            {
                case 'agent':
                    self::$_instance = new AgentRemittanceCompanyService($repo);
                    break;
                default:
                    self::$_instance = new RemittanceCompanyService($repo);
                    break;
            }            
        }

        return self::$_instance;
    }
}