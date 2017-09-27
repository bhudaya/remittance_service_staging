<?php

namespace Iapps\RemittanceService\RefundRequest;

use Iapps\Common\Helper\MessageBroker\BroadcastEventProducer;

class RefundRequestEventProducer extends BroadcastEventProducer{

    protected $refund_request_id;

    public function setRefundRequestId($refund_request_id)
    {
        $this->refund_request_id = $refund_request_id;
        return $this;
    }

    public function getRefundRequestId()
    {
        return $this->refund_request_id;
    }

    public function getMessage()
    {
        $temp['refund_request_id'] = $this->getRefundRequestId();
        return json_encode($temp);
    }

    public static function publishRefundInitiated($refund_request_id)
    {
        $e = new RefundRequestEventProducer();

        $e->setRefundRequestId($refund_request_id);
        return $e->trigger(RefundRequestEventType::REFUND_REQUEST_INITATED, NULL, $e->getMessage());
    }

    public static function publishStatusChanged($refund_request_id, $status)
    {
        $e = new RefundRequestEventProducer();

        $e->setRefundRequestId($refund_request_id);
        return $e->trigger(RefundRequestEventType::REFUND_REQUEST_STATUS_CHANGED, $status, $e->getMessage());
    }
}