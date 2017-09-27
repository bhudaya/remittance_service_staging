<?php

namespace Iapps\RemittanceService\Attribute;

use Iapps\RemittanceService\Common\MessageCode;
use Iapps\Common\Core\IappsBaseService;

class AttributeService extends IappsBaseService{

    public function getAllAttribute()
    {
        if( $attribute = $this->getRepository()->findAll() )
        {
            $this->setResponseCode(MessageCode::CODE_ATTRIBUTE_FOUND);
            return $attribute->result;
        }

        $this->setResponseCode(MessageCode::CODE_ATTRIBUTE_NOT_FOUND);
        return false;
    }

    public function getByCode($code)
    {
        if( $attribute = $this->getRepository()->findByCode($code) )
        {
            $this->setResponseCode(MessageCode::CODE_ATTRIBUTE_FOUND);
            return $attribute;
        }

        $this->setResponseCode(MessageCode::CODE_ATTRIBUTE_NOT_FOUND);
        return false;
    }
}