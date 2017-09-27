<?php

use Iapps\RemittanceService\RemittanceTransaction\RemittanceGenerateReceiptListener;
use Iapps\RemittanceService\RemittanceRecord\RemittanceStatusChangedNotificationService;
use Iapps\Common\Core\IpAddress;
use Iapps\Common\Helper\RequestHeader;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransactionRepository;
use Iapps\RemittanceService\Common\RemittanceAutoCancelService;
use Iapps\RemittanceService\Recipient\RecipientConversionListener;
use Iapps\RemittanceService\Common\MessageCode;
use Iapps\RemittanceService\RemittanceRecord\CompleteEwalletCashoutListener;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceNotifyAdminListener;
use Iapps\RemittanceService\RemittanceConfig\RemittanceUpdateAdminEmailListener;
use Iapps\RemittanceService\RemittanceRecord\RemittanceProcessDeliveryListener;
use Iapps\RemittanceService\RefundRequest\ProcessRefundListener;
use Iapps\RemittanceService\RefundRequest\RefundRequestStatusChangedNotificationService;
use Iapps\RemittanceService\RemittanceRecord\PaymentRequestChangedListener;
use Iapps\RemittanceService\RemittanceCompanyUser\AccountVerifiedNotificationService;

class Batch_job extends System_Base_Controller{

    function __construct()
    {
        parent::__construct();

        $this->load->model('remittancetransaction/Remittance_transaction_model');
    }

    public function listenGenerateReceipt()
    {
        if( !$system_user_id = $this->_getUserProfileId() )
            return false;

        RequestHeader::set(ResponseHeader::FIELD_X_AUTHORIZATION, $this->clientToken);

        $listener = new RemittanceGenerateReceiptListener();
        $listener->setHeader(RequestHeader::get());
        $listener->setIpAddress(IpAddress::fromString($this->_getIpAddress()));
        $listener->setUpdatedBy($system_user_id);
        $listener->listenEvent();

        $this->_respondWithSuccessCode(MessageCode::CODE_JOB_PROCESS_PASSED);
        return true;
    }

    public function listenRemittanceStatusChanged()
    {
        if( !$system_user_id = $this->_getUserProfileId() )
            return false;

        RequestHeader::set(ResponseHeader::FIELD_X_AUTHORIZATION, $this->clientToken);

        $listener = new RemittanceStatusChangedNotificationService();
        $listener->setHeader(RequestHeader::get());
        $listener->setIpAddress(IpAddress::fromString($this->_getIpAddress()));
        $listener->setUpdatedBy($system_user_id);
        $listener->listenEvent();

        $this->_respondWithSuccessCode(MessageCode::CODE_JOB_PROCESS_PASSED);
        return true;
    }

    public function autoCancelTransaction()
    {
        if( !$system_user_id = $this->_getUserProfileId() )
            return false;

        RequestHeader::set(ResponseHeader::FIELD_X_AUTHORIZATION, $this->clientToken);

        $this->load->model('remittancetransaction/Remittance_transaction_model');
        $repo = new RemittanceTransactionRepository($this->Remittance_transaction_model);
        $autocancelServ = new RemittanceAutoCancelService($repo, $this->_getIpAddress(), $system_user_id);
        if( $autocancelServ->process() )
        {
            $this->_respondWithSuccessCode($autocancelServ->getResponseCode());
            return true;
        }

        $this->_respondWithCode($autocancelServ->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function listenUserConversion()
    {
        if( !$system_user_id = $this->_getUserProfileId() )
            return false;

        RequestHeader::set(ResponseHeader::FIELD_X_AUTHORIZATION, $this->clientToken);

        $recipientConversionServ = new RecipientConversionListener();
        $recipientConversionServ->setIpAddress(IpAddress::fromString($this->_getIpAddress()));
        $recipientConversionServ->setUpdatedBy($system_user_id);
        $recipientConversionServ->listenEvent();

        $this->_respondWithSuccessCode(MessageCode::CODE_JOB_PROCESS_PASSED);
        return true;
    }

    public function listenCompleteEwaletCashout()
    {
        if( !$system_user_id = $this->_getUserProfileId() )
            return false;

        RequestHeader::set(ResponseHeader::FIELD_X_AUTHORIZATION, $this->clientToken);

        $recipientConversionServ = new CompleteEwalletCashoutListener();
        $recipientConversionServ->setIpAddress(IpAddress::fromString($this->_getIpAddress()));
        $recipientConversionServ->setUpdatedBy($system_user_id);
        $recipientConversionServ->listenEvent();

        $this->_respondWithSuccessCode(MessageCode::CODE_JOB_PROCESS_PASSED);
        return true;
    }

    public function listenNotifyAdmin()
    {
        if( !$system_user_id = $this->_getUserProfileId() )
            return false;

        RequestHeader::set(ResponseHeader::FIELD_X_AUTHORIZATION, $this->clientToken);

        $recipientConversionServ = new RemittanceNotifyAdminListener();
        $recipientConversionServ->setIpAddress(IpAddress::fromString($this->_getIpAddress()));
        $recipientConversionServ->setUpdatedBy($system_user_id);
        $recipientConversionServ->listenEvent();

        $this->_respondWithSuccessCode(MessageCode::CODE_JOB_PROCESS_PASSED);
        return true;
    }

    public function listenUpdateAdminEmail()
    {
        if( !$system_user_id = $this->_getUserProfileId() )
            return false;

        RequestHeader::set(ResponseHeader::FIELD_X_AUTHORIZATION, $this->clientToken);

        $recipientConversionServ = new RemittanceUpdateAdminEmailListener();
        $recipientConversionServ->setIpAddress(IpAddress::fromString($this->_getIpAddress()));
        $recipientConversionServ->setUpdatedBy($system_user_id);
        $recipientConversionServ->listenEvent();

        $this->_respondWithSuccessCode(MessageCode::CODE_JOB_PROCESS_PASSED);
        return true;
    }

    public function listenProcessDelivery()
    {
        if( !$system_user_id = $this->_getUserProfileId() )
            return false;

        RequestHeader::set(ResponseHeader::FIELD_X_AUTHORIZATION, $this->clientToken);

        $listener = new RemittanceProcessDeliveryListener();
        $listener->setIpAddress(IpAddress::fromString($this->_getIpAddress()));
        $listener->setUpdatedBy($system_user_id);
        $listener->listenEvent();

        $this->_respondWithSuccessCode(MessageCode::CODE_JOB_PROCESS_PASSED);
        return true;
    }

    public function listenPaymentRequestChanged(){
        if( !$system_user_id = $this->_getUserProfileId() )
            return false;

        RequestHeader::set(ResponseHeader::FIELD_X_AUTHORIZATION, $this->clientToken);

        $listener = new PaymentRequestChangedListener();
        $listener->setHeader(RequestHeader::get());
        $listener->setIpAddress(IpAddress::fromString($this->_getIpAddress()));
        $listener->setUpdatedBy($system_user_id);
        $listener->listenEvent();

        $this->_respondWithSuccessCode(MessageCode::CODE_JOB_PROCESS_PASSED);
        return true;
    }

    public function listenRefundInitiated()
    {
        if (!$system_user_id = $this->_getUserProfileId())
            return false;

        RequestHeader::set(ResponseHeader::FIELD_X_AUTHORIZATION, $this->clientToken);

        $listener = new ProcessRefundListener();
        $listener->setHeader(RequestHeader::get());
        $listener->setIpAddress(IpAddress::fromString($this->_getIpAddress()));
        $listener->setUpdatedBy($system_user_id);
        $listener->listenEvent();

        $this->_respondWithSuccessCode(MessageCode::CODE_JOB_PROCESS_PASSED);
        return true;
    }

    public function listenRefundRequestChanged(){
        if( !$system_user_id = $this->_getUserProfileId() )
            return false;

        RequestHeader::set(ResponseHeader::FIELD_X_AUTHORIZATION, $this->clientToken);

        $listener = new RefundRequestStatusChangedNotificationService();
        $listener->setHeader(RequestHeader::get());
        $listener->setIpAddress(IpAddress::fromString($this->_getIpAddress()));
        $listener->setUpdatedBy($system_user_id);
        $listener->listenEvent();

        $this->_respondWithSuccessCode(MessageCode::CODE_JOB_PROCESS_PASSED);
        return true;
    }

    public function listenNotifyAccountVerified()
    {
        if( !$system_user_id = $this->_getUserProfileId() )
            return false;

        RequestHeader::set(ResponseHeader::FIELD_X_AUTHORIZATION, $this->clientToken);

        $listener = new AccountVerifiedNotificationService();
        $listener->setIpAddress(IpAddress::fromString($this->_getIpAddress()));
        $listener->setUpdatedBy($system_user_id);
        $listener->listenEvent();

        $this->_respondWithSuccessCode(MessageCode::CODE_JOB_PROCESS_PASSED);
        return true;
    }

}