<?php

namespace Iapps\RemittanceService\RemittanceRecord;

use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransaction;

interface VoidRemittancePaymentInterface extends RemittancePaymentInterface{

    public function paymentVoid(RemittanceTransaction $transaction);
}