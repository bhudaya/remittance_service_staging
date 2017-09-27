<?php

namespace Iapps\RemittanceService\Common;

use Iapps\Common\SystemCode\SystemCodeInterface;

class TransactionType implements SystemCodeInterface{

	const CODE_CASH_IN       = 'cashin';
    const CODE_CASH_OUT      = 'cashout';
    const CODE_LOCAL_CASH_IN       = 'local_cashin';
    const CODE_LOCAL_CASH_OUT      = 'local_cashout';
    const CODE_REFUND        = 'refund';

    public static function getSystemGroupCode()
    {
        return 'transaction_type';
    }
}