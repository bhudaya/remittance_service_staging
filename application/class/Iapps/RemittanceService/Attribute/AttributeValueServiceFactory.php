<?php

namespace Iapps\RemittanceService\Attribute;

require_once './application/modules/attribute/models/Attribute_value_model.php';

class AttributeValueServiceFactory{

    protected static $_instance;

    public static function build()
    {
        if( self::$_instance == NULL )
        {
            $dm = new \Attribute_value_model();
            $repo = new AttributeValueRepository($dm);
            self::$_instance = new AttributeValueService($repo);
        }

        return self::$_instance;
    }
}