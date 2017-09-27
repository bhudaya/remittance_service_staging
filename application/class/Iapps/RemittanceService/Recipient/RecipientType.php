<?php

namespace Iapps\RemittanceService\Recipient;

use Iapps\Common\SystemCode\SystemCodeInterface;

class RecipientType implements SystemCodeInterface
{
    const KYC   = 'kyc';
    const NON_KYC   = 'non_kyc';
    const NON_MEMBER  = 'non_member';

    public static function getSystemGroupCode()
    {
        return 'recipient_type';
    }
}