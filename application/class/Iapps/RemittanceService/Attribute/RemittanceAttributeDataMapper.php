<?php

namespace Iapps\RemittanceService\Attribute;

use Iapps\Common\Core\IappsBaseDataMapper;

interface RemittanceAttributeDataMapper extends IappsBaseDataMapper{
    public function findByRemittanceId($remittance_id, $attribute_code = NULL);
    public function insert(RemittanceAttribute $user_attribute);
    public function update(RemittanceAttribute $user_attribute);
    public function delete(RemittanceAttribute $user_attribute);
}