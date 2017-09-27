<?php

namespace Iapps\RemittanceService\Attribute;

use Iapps\Common\Core\IappsBaseDataMapper;

interface RecipientAttributeDataMapper extends IappsBaseDataMapper{
    public function findByRecipientId($recipient_id, $attribute_code = NULL);
    public function insert(RecipientAttribute $user_attribute);
    public function update(RecipientAttribute $user_attribute);
    public function delete(RecipientAttribute $user_attribute);
}