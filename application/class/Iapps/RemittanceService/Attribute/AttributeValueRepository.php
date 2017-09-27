<?php

namespace Iapps\RemittanceService\Attribute;

use Iapps\Common\Core\IappsBaseRepository;

class AttributeValueRepository extends IappsBaseRepository{

    public function findByAttributeId($attribute_id)
    {
        return $this->getDataMapper()->findByAttributeId($attribute_id);
    }

    public function findAll()
    {
        return $this->getDataMapper()->findAll();
    }

    public function insert(AttributeValue $attribute)
    {
        return $this->getDataMapper()->insert($attribute);
    }

    public function update(AttributeValue $attribute)
    {
        return $this->getDataMapper()->update($attribute);
    }
}