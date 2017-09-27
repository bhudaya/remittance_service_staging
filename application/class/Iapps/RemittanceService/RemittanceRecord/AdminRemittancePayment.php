<?php

namespace Iapps\RemittanceService\RemittanceRecord;

use Iapps\Common\Microservice\PaymentService\AdminPaymentServiceFactory;
use Iapps\Common\Microservice\PaymentService\PaymentServiceFactory;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransaction;
use Iapps\Common\Microservice\PaymentService\PaymentUserType;

class AdminRemittancePayment implements VoidRemittancePaymentInterface{

    protected $_lastResponse;

    public function getLastResponse()
    {
        return $this->_lastResponse;
    }

    public function paymentRequest(RemittanceTransaction $transaction, array $paymentInfo)
    {
        return false;
    }

    public function paymentComplete(RemittanceTransaction $transaction, $payment_request_id, array $response = array())
    {
        return false;
    }

    public function paymentCancel(RemittanceTransaction $transaction, $payment_request_id)
    {
        return false;
    }

    public function paymentVoid(RemittanceTransaction $transaction)
    {
        //make payment
        $payment_serv = AdminPaymentServiceFactory::build();
        if( $status = $payment_serv->voidPayment($transaction->getUserProfileId(), $transaction->getConfirmPaymentCode(),
            getenv('MODULE_CODE'), $transaction->getTransactionID()) )
        {
            $this->_lastResponse = $payment_serv->getLastResponse();
            return $status;
        }

        $this->_lastResponse = $payment_serv->getLastResponse();
        return false;
    }
}