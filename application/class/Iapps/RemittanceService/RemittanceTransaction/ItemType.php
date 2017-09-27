<?php

namespace Iapps\RemittanceService\RemittanceTransaction;

use Iapps\Common\SystemCode\SystemCodeInterface;

class ItemType implements SystemCodeInterface{

    const CORPORATE_SERVICE = 'corporate_service';
    const CORPORATE_SERVICE_FEE = 'corporate_service_fee';
    const PAYMENT_FEE = 'payment_fee';
    const DISCOUNT = 'discount';

    public static function getSystemGroupCode()
    {
        return 'item_type';
    }
}