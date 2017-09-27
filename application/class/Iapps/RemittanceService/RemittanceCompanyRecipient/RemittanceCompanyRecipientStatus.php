<?php

namespace Iapps\RemittanceService\RemittanceCompanyRecipient;

use Iapps\Common\SystemCode\SystemCodeInterface;
use Iapps\Common\Core\IappsConstant;

class RemittanceCompanyRecipientStatus  extends IappsConstant
                                        implements SystemCodeInterface{

    const PENDING_VERIFY = 'pending_verify';
    const VERIFIED = 'verified';
    const FAILED_VERIFY = 'failed_verify';

    public static function getSystemGroupCode()
    {
        return 'remittance_recipient_status';
    }
}