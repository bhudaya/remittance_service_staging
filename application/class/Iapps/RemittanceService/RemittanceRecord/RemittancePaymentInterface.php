<?php

namespace Iapps\RemittanceService\RemittanceRecord;

use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransaction;

interface RemittancePaymentInterface {
    function getLastResponse();
    function paymentRequest(RemittanceTransaction $transaction, array $paymentInfo);
    function paymentComplete(RemittanceTransaction $transaction, $payment_request_id, array $response = array());
    function paymentCancel(RemittanceTransaction $transaction, $payment_request_id);
}