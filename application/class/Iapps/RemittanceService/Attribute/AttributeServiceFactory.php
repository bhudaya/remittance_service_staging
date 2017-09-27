<?php

namespace Iapps\RemittanceService\Attribute;

require_once './application/modules/attribute/models/Attribute_model.php';

class AttributeServiceFactory{

    protected static $_instance;

    public static function build()
    {
        if( self::$_instance == NULL )
        {
            $dm = new \Attribute_model();
            $repo = new AttributeRepository($dm);
            self::$_instance = new AttributeService($repo);
        }

        return self::$_instance;
    }
}