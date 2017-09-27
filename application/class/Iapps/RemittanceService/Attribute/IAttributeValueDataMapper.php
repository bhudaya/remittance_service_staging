<?php

namespace Iapps\RemittanceService\Attribute;

use Iapps\Common\Core\IappsBaseDataMapper;

interface IAttributeValueDataMapper extends IappsBaseDataMapper{
    public function findByAttributeId($attribute_id);
    public function findAll();
    public function insert(AttributeValue $attribute);
    public function update(AttributeValue $attribute);
}