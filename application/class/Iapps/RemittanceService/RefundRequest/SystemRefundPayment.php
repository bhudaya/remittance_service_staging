<?php

namespace Iapps\RemittanceService\RefundRequest;

use Iapps\Common\Microservice\PaymentService\PaymentServiceFactory;
use Iapps\Common\Microservice\PaymentService\SystemPaymentService;
use Iapps\Common\Microservice\PaymentService\SystemPaymentServiceFactory;
use Iapps\Common\Microservice\PaymentService\PaymentUserType;
use Iapps\Common\Transaction\Transaction;

class SystemRefundPayment implements RefundPaymentInterface{

    protected $_lastResponse;
    protected $_systemPaymentService;

    public function getLastResponse()
    {
        return $this->_lastResponse;
    }

    public function setSystemPaymentService(SystemPaymentService $systemPaymentService)
    {
        $this->_systemPaymentService = $systemPaymentService;
    }

    public function getSystemPaymentService()
    {
        if( !$this->_systemPaymentService )
        {
            $this->_systemPaymentService = SystemPaymentServiceFactory::build();
        }

        return $this->_systemPaymentService;
    }

    public function paymentRequest(Transaction $transaction, array $paymentInfo)
    {
        $user_profile_id = $transaction->getUserProfileId();
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
        //$payment_serv = SystemPaymentServiceFactory::build();
        $payment_serv = $this->getSystemPaymentService();
        if( $request_id = $payment_serv->requestPayment($user_profile_id, $payment_code, $country_currency_code,
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
        $user_profile_id = $transaction->getUserProfileId();
        //$payment_serv = SystemPaymentServiceFactory::build();
        $payment_serv = $this->getSystemPaymentService();
        $result = $payment_serv->completePayment(
            $user_profile_id,
            $payment_request_id,
            $transaction->getConfirmPaymentCode(),
            $response);

        $this->_lastResponse = $payment_serv->getLastResponse();
        return $result;
    }

    public function paymentCancel(Transaction $transaction, $payment_request_id)
    {
        $user_profile_id = $transaction->getUserProfileId();
        $payment_serv = SystemPaymentServiceFactory::build();
        $result = $payment_serv->cancelPayment(
            $user_profile_id,
            $payment_request_id,
            $transaction->getConfirmPaymentCode());

        $this->_lastResponse = $payment_serv->getLastResponse();
        return $result;
    }
}