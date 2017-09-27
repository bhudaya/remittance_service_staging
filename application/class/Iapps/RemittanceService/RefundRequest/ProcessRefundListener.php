<?php

namespace Iapps\RemittanceService\RefundRequest;

use Iapps\Common\Helper\MessageBroker\BroadcastEventConsumer;
use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\Common\Microservice\PaymentService\SystemPaymentService;
use Iapps\Common\Microservice\AccountService\AccountService;

class ProcessRefundListener extends BroadcastEventConsumer{

    protected $_accountServ;
    protected $_systemPaymentServ;
    protected $header = array();

    protected function _getAccountService()
    {
        if( !$this->_accountServ )
            $this->_accountServ = AccountServiceFactory::build();

        return $this->_accountServ;
    }

    protected function _getSystemPaymentService()
    {
        if( !$this->_systemPaymentServ )
            $this->_systemPaymentServ = new SystemPaymentService(array('header' => $this->getHeader()));

        return $this->_systemPaymentServ;
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

        try {
            sleep(5);

            $refund_request_serv = RefundRequestServiceFactory::build();
            $refund_request_serv->setAccountService($this->_getAccountService());
            $refund_request_serv->setSystemPaymentService($this->_getSystemPaymentService());
            $refund_request_serv->setUpdatedBy($this->getUpdatedBy());
            $refund_request_serv->setIpAddress($this->getIpAddress());

            $refund_request = new RefundRequest();
            $refund_request->setId($data->refund_request_id);
            $refund_request->setApprovalRequired(0);
            $refund_request->setApprovalStatus(RefundRequestApprovalStatus::PENDING);

            if ($result = $refund_request_serv->getRefundRequestListForRequester($refund_request, array(), 1, 1)) {
                $refund_reject_reason = NULL;
                $refund_reject_remarks = NULL;

                $payment_info = array();
                $payment_info['payment_code'] = 'EWA';
                $payment_info['reference_no'] = 'Auto' . date('YmdHis');
                if (!$refund_request_serv->updateRefundRequestApprovalStatus($data->refund_request_id, RefundRequestApprovalStatus::APPROVED, $payment_info, $refund_reject_reason, $refund_reject_remarks)) {
                    if ($refund_request_serv->convertToManualApproval($data->refund_request_id)) {
                        return true;
                    }
                }

                return true;
            }
        }catch(\Exception $e)
        {
            return false;
        }
    }

    /*
     * listen for payment request change
     */
    public function listenEvent()
    {
        $this->listen('remittance.refund.initiated', null , 'remittance.queue.processRefund');
    }
}