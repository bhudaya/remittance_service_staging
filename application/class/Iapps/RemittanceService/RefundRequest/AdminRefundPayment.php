<?php

namespace Iapps\RemittanceService\RefundRequest;

use Iapps\Common\Core\IappsBasicBaseService;
use Iapps\Common\Microservice\PaymentService\AdminPaymentServiceFactory;
use Iapps\Common\Transaction\Transaction;
use Iapps\Common\Microservice\PaymentService\PaymentUserType;

class AdminRefundPayment implements RefundPaymentInterface{

    protected $_lastResponse;

    public function getLastResponse()
    {
        return $this->_lastResponse;
    }

    public function paymentRequest(Transaction $transaction, array $paymentInfo)
    {
        $payment_code = $paymentInfo['payment_code'];
        $country_currency_code = $transaction->getCountryCurrencyCode();
        $amount = $transaction->getItems()->getTotalAmount();
        $module_code = getenv('MODULE_CODE');
        $transactionID = $transaction->getTransactionID();
        $option = NULL;
        if( isset($paymentInfo['option']) )
            $option = $paymentInfo['option'];
        $option['user_type'] = PaymentUserType::USER;

        //make payment
        $payment_serv = AdminPaymentServiceFactory::build();
        if( $request_id = $payment_serv->requestPayment($transaction->getUserProfileId(), $payment_code, $country_currency_code,
                                                              $amount, $module_code, $transactionID, $option) )
        {
            $this->_lastResponse = $payment_serv->getLastResponse();
            return $request_id;
        }

        $this->_lastResponse = $payment_serv->getLastResponse();
        return false;
    }

    public function paymentComplete(Transaction $transaction, $payment_request_id, array $response = array())
    {
        $payment_service = AdminPaymentServiceFactory::build();
        $result = $payment_service->completePayment($transaction->getUserProfileId(), $payment_request_id,
            $transaction->getConfirmPaymentCode(),
            $response);

        $this->_lastResponse = $payment_service->getLastResponse();
        return $result;
    }

    public function paymentCancel(Transaction $transaction, $payment_request_id)
    {
        $payment_service = AdminPaymentServiceFactory::build();
        $result = $payment_service->cancelPayment($transaction->getUserProfileId(), $payment_request_id,
            $transaction->getConfirmPaymentCode());

        $this->_lastResponse = $payment_service->getLastResponse();
        return $result;
    }
}