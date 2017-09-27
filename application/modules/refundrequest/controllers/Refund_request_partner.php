<?php

use Iapps\Common\Helper\ResponseHeader;
use Iapps\Common\Helper\InputValidator;
use Iapps\Common\Microservice\AccountService\SessionType;
use Iapps\RemittanceService\Common\FunctionCode;
use Iapps\Common\Microservice\AccountService\AccessType;
use Iapps\RemittanceService\RefundRequest\RefundRequestRepository;
use Iapps\RemittanceService\RefundRequest\RefundRequestService;
use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\Core\IpAddress;
use Iapps\RemittanceService\RefundRequest\RefundRequest;
use Iapps\RemittanceService\RefundRequest\RefundRequestServiceFactory;

class Refund_request_partner extends Partner_Base_Controller{

    protected $_serv;
    function __construct()
    {
        parent::__construct();

        $this->_serv = RefundRequestServiceFactory::build();
        $this->_serv->setIpAddress(IpAddress::fromString($this->_getIpAddress()));
    }


    public function getByTransactionID()
    {
        if (!$admin_id = $this->_getUserProfileId(FunctionCode::PARTNER_GET_REMITTANCE_TRANSACTION_DETAIL, AccessType::READ))
            return false;

        if (!$this->is_required($this->input->get(), array('transactionID')))
            return false;

        $transactionID = $this->input->get("transactionID");

        if( $refund = $this->_serv->getRefundRequestDetailByTransactionID($transactionID) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $refund));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
}