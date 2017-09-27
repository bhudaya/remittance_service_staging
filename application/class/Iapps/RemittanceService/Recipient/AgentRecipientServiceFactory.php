<?php

namespace Iapps\RemittanceService\Recipient;

class AgentRecipientServiceFactory {

    protected static $_instance = null;

    public static function build()
    {    
        if( self::$_instance == NULL )
        {
            $_ci = get_instance();
            $_ci->load->model('recipient/Recipient_model');
            $repo = new RecipientRepository($_ci->Recipient_model);
			self::$_instance = new AgentRecipientService($repo);            
        }

        return self::$_instance;
	}
}