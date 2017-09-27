<?php

namespace Iapps\RemittanceService\Attribute;

use Iapps\Common\Core\IappsBaseEntityCollection;

class RecipientAttributeCollection extends IappsBaseEntityCollection{

    public function hasAttribute($code)
    {
        if( $ref_value = $this->getByCode($code) )
            return $ref_value->getValue();            

        return false;
    }
    
    public function getByCode($code)
    {
        foreach($this as $ref_value)
        {
            if( $ref_value->getAttribute()->getCode() == $code )
                return $ref_value;                
        }

        return false;
    }

    public function toList()
    {
        $list = array();
        foreach($this as $attribute)
        {
            if( $attribute instanceof RecipientAttribute )
            {
                $list[$attribute->getAttribute()->getCode()] = $attribute->getValue();
            }
        }

        return $list;
    }
}