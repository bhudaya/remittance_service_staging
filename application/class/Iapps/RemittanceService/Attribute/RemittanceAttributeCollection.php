<?php

namespace Iapps\RemittanceService\Attribute;

use Iapps\Common\Core\IappsBaseEntityCollection;

class RemittanceAttributeCollection extends IappsBaseEntityCollection{

    public function hasAttribute($code)
    {
        foreach($this as $ref_value)
        {
            if( $ref_value->getAttribute()->getCode() == $code )
                return $ref_value->getValue();
        }

        return false;
    }
}