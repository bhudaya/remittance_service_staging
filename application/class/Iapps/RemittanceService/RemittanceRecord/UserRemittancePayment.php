<?php

namespace Iapps\RemittanceService\RemittanceRecord;

use Iapps\Common\Core\IappsBasicBaseService;
use Iapps\Common\Microservice\PaymentService\PaymentServiceFactory;
use Iapps\RemittanceService\Common\MessageCode;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransaction;
use Iapps\Common\Microservice\PaymentService\PaymentUserType;

class UserRemittancePayment implements RemittancePaymentInterface{

    protected $_lastResponse;

    public function getLastResponse()
    {
        return $this->_lastResponse;
    }

    public function paymentRequest(RemittanceTransaction $transaction, array $paymentInfo)
    {
        $payment_code = $paymentInfo['payment_code'];
        $country_currency_code = $transaction->getCountryCurrencyCode();
        $amount = $transaction->getItems()->getTotalAmount();
        $module_code = getenv('MODULE_CODE');
        $transactionID = $transaction->getTransactionID();
        $option = array();
        if( isset($paymentInfo['option']) )
            $option = $paymentInfo['option'];
        $option['user_type'] = PaymentUserType::USER;

        //make payment
        $payment_serv = PaymentServiceFactory::build();
        if( $request_id = $payment_serv->requestPaymentByUser($payment_code, $country_currency_code,
                                                              $amount, $module_code, $transactionID, $option) )
        {
            $this->_lastResponse = $payment_serv->getLastResponse();
            return $request_id;
        }

        $this->_lastResponse = $payment_serv->getLastResponse();
        return false;
    }

    public function paymentComplete(RemittanceTransaction $transaction, $payment_request_id, array $response = array())
    {
        $payment_service = PaymentServiceFactory::build();
        $result = $payment_service->completePaymentByUser($payment_request_id,
            $transaction->getConfirmPaymentCode(),
            $response);

        $this->_lastResponse = $payment_service->getLastResponse();
        return $result;
    }

    public function paymentCancel(RemittanceTransaction $transaction, $payment_request_id)
    {
        $payment_service = PaymentServiceFactory::build();
        $result = $payment_service->cancelPaymentByUser($payment_request_id,
            $transaction->getConfirmPaymentCode());

        $this->_lastResponse = $payment_service->getLastResponse();
        return $result;
    }
}