<?php

namespace Iapps\RemittanceService\Attribute;

use Iapps\Common\Core\IappsBaseEntity;
use Iapps\Common\Helper\GuidGenerator;

class AttributeValue extends IappsBaseEntity{

    protected $country_code;
    protected $attribute;
    protected $value;

    function __construct()
    {
        parent::__construct();

        $this->attribute = new Attribute();
    }

    public function setCountryCode($country_code)
    {
        $this->country_code = $country_code;
        return $this;
    }

    public function getCountryCode()
    {
        return $this->country_code;
    }

    public function setAttribute(Attribute $attribute)
    {
        $this->attribute = $attribute;
        return $this;
    }

    public function getAttribute()
    {
        return $this->attribute;
    }

    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function jsonSerialize()
    {
        $json = parent::jsonSerialize();

        $json['country_code'] = $this->getCountryCode();
        $json['attribute_id'] = $this->getAttribute()->getId();
        $json['value'] = $this->getValue();

        return $json;
    }

    public static function createNew(Attribute $attribute, $value, $country_code)
    {
        $val = new AttributeValue();
        $val->setId(GuidGenerator::generate());
        $val->setAttribute($attribute);
        $val->setValue($value);
        $val->setCountryCode($country_code);

        return $val;
    }
}