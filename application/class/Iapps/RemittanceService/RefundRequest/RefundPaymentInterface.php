<?php

namespace Iapps\RemittanceService\RefundRequest;

use Iapps\Common\Transaction\Transaction;

interface RefundPaymentInterface {
    function getLastResponse();
    function paymentRequest(Transaction $transaction, array $paymentInfo);
    function paymentComplete(Transaction $transaction, $payment_request_id, array $response = array());
    function paymentCancel(Transaction $transaction, $payment_request_id);
}