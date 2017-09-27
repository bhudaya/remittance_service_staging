<?php

namespace Iapps\RemittanceService\Attribute;

use Iapps\Common\Core\IappsBaseDataMapper;

interface RefundRequestAttributeDataMapper extends IappsBaseDataMapper{
    public function findByRefundRequestId($refund_request_id, $attribute_code = NULL);
    public function insert(RefundRequestAttribute $user_attribute);
    public function update(RefundRequestAttribute $user_attribute);
    public function delete(RefundRequestAttribute $user_attribute);
}