<?php

namespace Iapps\RemittanceService\Attribute;

use Iapps\Common\Core\IappsBaseEntity;
use Iapps\Common\Helper\GuidGenerator;
use Iapps\RemittanceService\Recipient\Recipient;
use Iapps\RemittanceService\ValueObject\EncryptedFieldFactory;

class RecipientAttribute extends IappsBaseEntity{

    protected $attribute;
    protected $recipient_id;
    protected $attribute_value_id;
    protected $value;

    function __construct()
    {
        parent::__construct();

        $this->attribute = new Attribute();
        $this->value = EncryptedFieldFactory::build();
    }

    public function setAttribute(Attribute $attribute)
    {
        $this->attribute = $attribute;
        return $this;
    }

    /**
     * 
     * @return Attribute
     */
    public function getAttribute()
    {
        return $this->attribute;
    }

    public function setRecipientId($recipient_id)
    {
        $this->recipient_id = $recipient_id;
        return $this;
    }

    public function getRecipientId()
    {
        return $this->recipient_id;
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
        $this->value->setValue($value);
        return $this;
    }

    public function getValue($isValue = true)
    {
        if( $isValue )
            return $this->value->getValue();
        else
            return $this->value;
    }

    public function jsonSerialize()
    {
        $json = parent::jsonSerialize();

        $json['attribute_id'] = $this->getAttribute()->getId();
        $json['recipient_id'] = $this->getRecipientId();
        $json['attribute_value_id'] = $this->getAttributeValueId();
        $json['value'] = $this->getValue();
        $json['attribute_code'] = $this->getAttribute()->getCode();
        $json['attribute_name'] = $this->getAttribute()->getName();

        return $json;
    }

    public static function create($recipient_id, AttributeValue $attributeValue)
    {
        $ref_attr = new RecipientAttribute();

        $ref_attr->setId(GuidGenerator::generate());
        $ref_attr->setAttribute($attributeValue->getAttribute());
        $ref_attr->setRecipientId($recipient_id);
        $ref_attr->setAttributeValueId($attributeValue->getId());
        $ref_attr->setValue($attributeValue->getValue());

        return $ref_attr;
    }

    public function belongsTo(Recipient $recipient)
    {
        return $this->getRecipientId() == $recipient->getId();
    }
}