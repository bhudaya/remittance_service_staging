<?php

namespace Iapps\RemittanceService\RemittanceRecord;

use Iapps\Common\SystemCode\SystemCodeInterface;
use Iapps\Common\Core\IappsConstant;

class RemittanceStatus extends IappsConstant
                       implements SystemCodeInterface{

    const INITIATE = 'initiate'; //initiate
    const PENDING_PAYMENT = 'pending_payment';
    const CANCELLED = 'cancelled';
    const PROCESSING = 'processing';
    const DELIVERING = 'delivering';
    const PENDING_COLLECTION = 'pending_collection';
    const COLLECTED = 'collected';
    const REJECTED = 'rejected';
    const EXPIRED = 'expired';
    const FAILED = 'failed';
    const APPROVED = 'approved';

    public static function getSystemGroupCode()
    {
        return 'remittance_status';
    }
}