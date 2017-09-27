<?php

namespace Iapps\RemittanceService\Attribute;

use Iapps\Common\Core\IappsBaseEntity;
use Iapps\Common\Helper\GuidGenerator;

class RefundRequestAttribute extends IappsBaseEntity{

    protected $attribute;
    protected $refund_request_id;
    protected $attribute_value_id;
    protected $value;

    function __construct()
    {
        parent::__construct();

        $this->attribute = new Attribute();
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

    public function setRefundRequestId($refund_request_id)
    {
        $this->refund_request_id = $refund_request_id;
        return $this;
    }

    public function getRefundRequestId()
    {
        return $this->refund_request_id;
    }

    public function setAttributeValueId($attribute_value_id)
    {
        $this->attribute_value_id = $attribute_value_id;
        return $this;
    }

    public function getAttributeValueId()
    {
        return $this->attribute_value_id;
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

        $json['attribute_id'] = $this->getAttribute()->getId();
        $json['refund_request_id'] = $this->getRefundRequestId();
        $json['attribute_value_id'] = $this->getAttributeValueId();
        $json['value'] = $this->getValue();
        $json['attribute_code'] = $this->getAttribute()->getCode();
        $json['attribute_name'] = $this->getAttribute()->getName();

        return $json;
    }

    public static function create($refund_request_id, AttributeValue $attributeValue)
    {
        $ref_attr = new RefundRequestAttribute();

        $ref_attr->setId(GuidGenerator::generate());
        $ref_attr->setAttribute($attributeValue->getAttribute());
        $ref_attr->setRefundRequestId($refund_request_id);
        $ref_attr->setAttributeValueId($attributeValue->getId());
        $ref_attr->setValue($attributeValue->getValue());

        return $ref_attr;
    }
}