<?php

namespace Iapps\RemittanceService\Attribute;

use Iapps\Common\Core\IappsBaseRepository;

class RemittanceAttributeRepository extends IappsBaseRepository{

    public function findByRemittanceId($remittance_id, $attribute_code = NULL)
    {
        return $this->getDataMapper()->findByRemittanceId($remittance_id, $attribute_code);
    }

    public function insert(RemittanceAttribute $user_attribute)
    {
        return $this->getDataMapper()->insert($user_attribute);
    }

    public function update(RemittanceAttribute $user_attribute)
    {
        return $this->getDataMapper()->update($user_attribute);
    }

    public function delete(RemittanceAttribute $user_attribute)
    {
        return $this->getDataMapper()->delete($user_attribute);
    }
}