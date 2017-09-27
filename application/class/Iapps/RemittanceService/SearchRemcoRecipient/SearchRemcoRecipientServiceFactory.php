<?php

namespace Iapps\RemittanceService\SearchRemcoRecipient;

class SearchRemcoRecipientServiceFactory{
    
    protected static $_instance;

    /**
     * 
     * @return SearchRemcoRecipientService
     */
    public static function build()
    {
        if( self::$_instance == NULL )
        {
            $_ci = get_instance();
            $_ci->load->model('remittancecompanyrecipient/Search_remco_recipient_model');
            $repo = new SearchRemcoRecipientRepository($_ci->Search_remco_recipient_model);
            self::$_instance = new SearchRemcoRecipientService($repo);
        }

        return self::$_instance;
    }
}

