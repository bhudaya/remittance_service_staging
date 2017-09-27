<?php

namespace Iapps\RemittanceService\RemittanceCompanyUser;

use Iapps\Common\SystemCode\SystemCodeInterface;

class RemittanceCompanyUserStatus implements SystemCodeInterface{

    const READY_FOR_KYC = 'ready_for_kyc';
    const COMPLETED = 'completed';
    const VERIFIED = 'verified';
    const FAILED_VERIFY = 'failed_verify';

    public static function getSystemGroupCode()
    {
        return 'remittance_user_status';
    }
}