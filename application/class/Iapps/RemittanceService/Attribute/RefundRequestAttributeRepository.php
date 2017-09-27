<?php

namespace Iapps\RemittanceService\Attribute;

use Iapps\Common\Core\IappsBaseRepository;

class RefundRequestAttributeRepository extends IappsBaseRepository{

    public function findByRefundRequestId($refund_request_id, $attribute_code = NULL)
    {
        return $this->getDataMapper()->findByRefundRequestId($refund_request_id, $attribute_code);
    }

    public function insert(RefundRequestAttribute $user_attribute)
    {
        return $this->getDataMapper()->insert($user_attribute);
    }

    public function update(RefundRequestAttribute $user_attribute)
    {
        return $this->getDataMapper()->update($user_attribute);
    }

    public function delete(RefundRequestAttribute $user_attribute)
    {
        return $this->getDataMapper()->delete($user_attribute);
    }
}