<?php

namespace Iapps\RemittanceService\Attribute;

require_once './application/modules/attribute/models/Recipient_attribute_model.php';

class RecipientAttributeServiceFactory{

    protected static $_instance;

    public static function build()
    {
        if( self::$_instance == NULL )
        {
            $dm = new \Recipient_attribute_model();
            $repo = new RecipientAttributeRepository($dm);
            self::$_instance = new RecipientAttributeService($repo);
        }

        return self::$_instance;
    }
}