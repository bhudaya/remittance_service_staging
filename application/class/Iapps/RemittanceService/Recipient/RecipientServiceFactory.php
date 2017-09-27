<?php

namespace Iapps\RemittanceService\Recipient;

class RecipientServiceFactory {

    protected static $_instance = array();

    public static function build($international = true, $version = '1')
    {
        if( $international )
            $key = 'international';
        else
            $key = 'local';

        if( !in_array($key, self::$_instance ) )
        {
            $_ci = get_instance();
            $_ci->load->model('recipient/Recipient_model');
            $repo = new RecipientRepository($_ci->Recipient_model);
            switch($key)
            {
                case 'international';
                    if( $version == '2' )
                        self::$_instance[$key] = new RecipientServiceV2($repo);
                    else
                        self::$_instance[$key] = new RecipientService($repo);
                    break;
                case 'local';
                    self::$_instance[$key] = new LocalRecipientService($repo);
                    break;
            }

        }

        return self::$_instance[$key];
    }
}