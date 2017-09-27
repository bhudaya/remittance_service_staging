<?php

namespace Iapps\RemittanceService\Attribute;

use Iapps\Common\Validator\IappsValidator;

class RecipientAttributeValidation extends IappsValidator{

    protected $user_attribute;
    public static function make(RecipientAttribute $user_attr)
    {
        $v = new RecipientAttributeValidation();
        $v->user_attribute = $user_attr;
        $v->validate();

        return $v;
    }

    public function validate()
    {
        $this->isFailed = true;

        if( $this->_validateValue() )
        {
            $this->isFailed = false;
        }
    }

    protected function _validateValue()
    {
        if( $value = $this->user_attribute->getValue() )
        {
            $input_type = $this->user_attribute->getAttribute()->getInputType();

            if( $this->user_attribute->getAttribute()->isSelectionOnly() )
            {
                $attr_value = AttributeValueServiceFactory::build();
                return $attr_value->checkValue($this->user_attribute->getAttribute(), $this->user_attribute->getValue());
            }

            switch($input_type)
            {
                case AttributeInputType::NUMBER:
                    return is_numeric($value);
                    break;
                case AttributeInputType::TEXT:
                    return ($value != NULL);
                    break;
                default:
                    return false;
                    break;
            }
        }

        return false;
    }
}