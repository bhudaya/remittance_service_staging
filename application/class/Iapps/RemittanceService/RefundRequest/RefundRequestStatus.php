<?php

namespace Iapps\RemittanceService\RefundRequest;

use Iapps\Common\SystemCode\SystemCodeInterface;

class RefundRequestStatus implements SystemCodeInterface{

    const INITIATED = 'initiated';
    const CANCELLED = 'cancelled';
    const REFUNDED = 'refunded';
    const AUTO_REFUNDED = 'auto_refunded';
    const REJECTED = 'rejected';

    public static function getSystemGroupCode()
    {
        return 'refund_request_status';
    }
}