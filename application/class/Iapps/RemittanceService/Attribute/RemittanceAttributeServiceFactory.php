<?php

namespace Iapps\RemittanceService\Attribute;

require_once './application/modules/attribute/models/Remittance_attribute_model.php';

class RemittanceAttributeServiceFactory{

    protected static $_instance;

    /**
     * 
     * @return RemittanceAttributeService
     */
    public static function build()
    {
        if( self::$_instance == NULL )
        {
            $dm = new \Remittance_attribute_model();
            $repo = new RemittanceAttributeRepository($dm);
            self::$_instance = new RemittanceAttributeService($repo);
        }

        return self::$_instance;
    }
}