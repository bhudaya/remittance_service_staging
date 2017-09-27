<?php

namespace Iapps\RemittanceService\RemittanceRecord;

use Iapps\Common\Helper\MessageBroker\BroadcastEventConsumer;
use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\Common\Microservice\PaymentService\PaymentService;
use Iapps\Common\Microservice\AccountService\AccountService;
use Iapps\Common\Microservice\PaymentService\PaymentServiceFactory;
use Iapps\Common\Microservice\PaymentService\PaymentStatus;
use Iapps\Common\Helper\RequestHeader;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransactionServiceFactory;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransaction;
use Iapps\RemittanceService\Common\Logger;

class PaymentRequestChangedListener extends BroadcastEventConsumer{

    protected $_accountServ;
    protected $_paymentServ;
    protected $header = array();

    protected function _getAccountService()
    {
        if( !$this->_accountServ )
            $this->_accountServ = AccountServiceFactory::build();

        return $this->_accountServ;
    }

    protected function _getPaymentService()
    {
        if( !$this->_paymentServ )
            $this->_paymentServ = PaymentServiceFactory::build();

        return $this->_paymentServ;
    }

    public function setHeader(array $header)
    {
        $this->header = $header;
        return $this;
    }

    public function getHeader()
    {
        return $this->header;
    }

    protected function doTask($msg)
    {
        $data = json_decode($msg->body);

        try
        {
            sleep(5);

            $tranServ = RemittanceTransactionServiceFactory::build();
            $tranServ->setUpdatedBy($this->getUpdatedBy());
            $tranServ->setIpAddress($this->getIpAddress());

            $_ci = get_instance();
            $_ci->load->model('remittancerecord/Remittance_model');
            $repo = new RemittanceRecordRepository($_ci->Remittance_model);
            $systemPayment = new SystemRemittancePayment();
            $remittanceServ = new RemittanceRecordService($repo, $this->getIpAddress()->getString(), $this->getUpdatedBy(), $systemPayment);
            $remittanceServ->setAccountService($this->_getAccountService());

            if( $transaction = $tranServ->findByTransactionId($data->transactionID) ) {
                 if ($transaction instanceof RemittanceTransaction) {
                     if ($transaction->isCashOut()) {
                         if ($remittance = $remittanceServ->getByTransactionId($transaction->getId(), false)) {
                             if ($remittance instanceof RemittanceRecord) {
                                 //check payment
                                 if ($paymentInfo = $this->_getPaymentService()->getPaymentByTransactionID($data->module_code, $data->transactionID)) {
                                     if (property_exists($paymentInfo, "result")) {
                                         if (count($paymentInfo->result) > 0) {
                                             //check payment status
                                             if ($paymentInfo->result[0]->status == PaymentStatus::COMPLETE) {
                                                 return $remittanceServ->collect($remittance->getId());
                                                 //complete
                                             }
                                         }
                                     }
                                 }else{
                                     //check fail
                                     if ($requestInfo = $this->_getPaymentService()->getPaymentRequestByTransactionID($data->transactionID, 'fail')) {
                                         try{
                                             $response = $requestInfo->result[0]->response;
                                             $paymentModeInterface = RemittancePaymentModeOptionFactory::build($transaction, $remittance->getSenderUserProfileId(), $remittance->getRecipientUserProfileId(), $this->getIpAddress(), $this->getUpdatedBy());

                                             $response = $paymentModeInterface->getFormattedResponseMessage($response);

                                             $remarks = $response['remarks'];

                                             return $remittanceServ->fail($remittance->getId(), $remarks);
                                         }catch(\Exception $e){
                                             return false;
                                         }
                                     }
                                 }
                             }
                         }
                     }
                 }
             }
            return true;
        }
        catch(\Exception $e)
        {
            return false;
        }
    }

    /*
     * listen for payment request change
     */
    public function listenEvent()
    {
        $this->listen('payment.request.changed', 'BT5', 'remittance.queue.paymentRequestChanged');
        $this->listen('payment.request.changed', 'BT6', 'remittance.queue.paymentRequestChanged');
        $this->listen('payment.request.changed', 'BT8', 'remittance.queue.paymentRequestChanged');
        $this->listen('payment.request.changed', 'BT7', 'remittance.queue.paymentRequestChanged');
        $this->listen('payment.request.changed', 'BT9', 'remittance.queue.paymentRequestChanged');
        $this->listen('payment.request.changed', 'TT1', 'remittance.queue.paymentRequestChanged');
        $this->listen('payment.request.changed', 'TT3', 'remittance.queue.paymentRequestChanged');
        $this->listen('payment.request.changed', 'CP2', 'remittance.queue.paymentRequestChanged');





    }
}