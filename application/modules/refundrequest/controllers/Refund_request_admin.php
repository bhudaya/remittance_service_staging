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

class Refund_request_admin extends Admin_Base_Controller{

    protected $_serv;
    function __construct()
    {
        parent::__construct();

        $this->_serv = RefundRequestServiceFactory::build();
        $this->_serv->setIpAddress(IpAddress::fromString($this->_getIpAddress()));

        $this->_service_audit_log->setTableName('iafb_remittance.refund_request');
    }


    public function getRefundRequestForRequester()
    {
        if (!$admin_id = $this->_getUserProfileId(FunctionCode::ADMIN_LIST_REFUND_REQUEST_FOR_REQUESTER, AccessType::READ))
            return false;

        $this->_serv->setUpdatedBy($admin_id);

        $limit = $this->_getLimit();
        $page = $this->_getPage();

        $transID = $this->input->get('transID');
        $date_from = $this->input->get('date_from') ? IappsDateTime::fromString($this->input->get('date_from')) : NULL;
        $date_to = $this->input->get('date_to') ? IappsDateTime::fromString($this->input->get('date_to')) : NULL;

        $keyword = array();
        $member_id = $this->input->get('member_id');
        $member_name = $this->input->get('member_name');
        if ($member_id != NULL) {
            $keyword = array_merge($keyword, array('member_id' => $member_id));
        }
        if ($member_name != NULL) {
            $keyword = array_merge($keyword, array('member_name' => $member_name));
        }

        $refundRequest = new RefundRequest();

        if ($transID != NULL) {
            $refundRequest->setTransID($transID);
        }

        $this->_serv->setIpAddress(IpAddress::fromString($this->_getIpAddress()));

        if ($object = $this->_serv->getRefundRequestListForRequester($refundRequest, $keyword, $limit, $page, $date_from, $date_to)) {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $object->result, 'total' => $object->total));

            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }


    public function getRefundRequestForChecker()
    {
        if (!$admin_id = $this->_getUserProfileId(FunctionCode::ADMIN_LIST_REFUND_REQUEST_FOR_CHECKER, AccessType::READ))
            return false;

        $this->_serv->setUpdatedBy($admin_id);

        $limit = $this->_getLimit();
        $page = $this->_getPage();

        $transID = $this->input->get('transID');
        $date_from = $this->input->get('date_from') ? IappsDateTime::fromString($this->input->get('date_from')) : NULL;
        $date_to = $this->input->get('date_to') ? IappsDateTime::fromString($this->input->get('date_to')) : NULL;

        $keyword = array();
        $member_id = $this->input->get('member_id');
        $member_name = $this->input->get('member_name');
        if ($member_id != NULL) {
            $keyword = array_merge($keyword, array('member_id' => $member_id));
        }
        if ($member_name != NULL) {
            $keyword = array_merge($keyword, array('member_name' => $member_name));
        }

        $refundRequest = new RefundRequest();

        if ($transID != NULL) {
            $refundRequest->setTransID($transID);
        }

        $this->_serv->setIpAddress(IpAddress::fromString($this->_getIpAddress()));

        if ($object = $this->_serv->getRefundRequestListForChecker($refundRequest, $keyword, $limit, $page, $date_from, $date_to)) {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $object->result, 'total' => $object->total));

            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }


    public function getRefundRequestDetailForRequester()
    {
        if (!$admin_id = $this->_getUserProfileId(FunctionCode::ADMIN_GET_REFUND_REQUEST_FOR_REQUESTER, AccessType::READ))
            return false;

        $this->_serv->setUpdatedBy($admin_id);

        if( !$this->is_required($this->input->get(), array('refund_request_id')) )
            return false;

        $refund_request_id = $this->input->get('refund_request_id');
        $this->_serv->setIpAddress(IpAddress::fromString($this->_getIpAddress()));

        if ($object = $this->_serv->getRefundRequestDetail($refund_request_id)) {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $object));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getRefundRequestDetailForChecker()
    {
        if (!$admin_id = $this->_getUserProfileId(FunctionCode::ADMIN_GET_REFUND_REQUEST_FOR_CHECKER, AccessType::READ))
            return false;

        $this->_serv->setUpdatedBy($admin_id);

        if( !$this->is_required($this->input->get(), array('refund_request_id')) )
            return false;

        $refund_request_id = $this->input->get('refund_request_id');
        $this->_serv->setIpAddress(IpAddress::fromString($this->_getIpAddress()));

        if ($object = $this->_serv->getRefundRequestDetail($refund_request_id)) {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $object));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }


    public function updateRefundRequestApprovalStatus()
    {
        if (!$admin_id = $this->_getUserProfileId(FunctionCode::ADMIN_UPDATE_REFUND_REQUEST_APPROVAL_STATUS, AccessType::WRITE))
            return false;

        if( !$this->is_required($this->input->post(), array('refund_request_id', 'approval_status')) )
            return false;

        if($this->input->post('payment_code') == NULL &&  $this->input->post('refund_reject_remarks') == NULL) {
            $errMsg = InputValidator::getInvalidParamMessage('');
            $this->_response(InputValidator::constructInvalidParamResponse($errMsg));
            return false;
        }

        $refund_request_id = $this->input->post('refund_request_id');
        $approval_status = $this->input->post('approval_status');
        $refund_reject_reason = NULL;
        $refund_reject_remarks = $this->input->post('refund_reject_remarks') != NULL ? $this->input->post('refund_reject_remarks') : NULL;

        if($this->input->post('refund_reject_reason') != NULL) {
            if (!$refund_reject_reason = json_decode($this->input->post('refund_reject_reason'), true)) {
                $errMsg = InputValidator::getInvalidParamMessage('refund_reject_reason');
                $this->_response(InputValidator::constructInvalidParamResponse($errMsg));
                return false;
            }
        }

        $payment_info = array();
        if($this->input->post('payment_code') != NULL) {
            $payment_info['payment_code'] = $this->input->post('payment_code');
            $payment_info['reference_no'] = $this->input->post('reference_no') != NULL ? $this->input->post('reference_no') : NULL;
        }

        $this->_serv->setUpdatedBy($admin_id);
        $this->_serv->setIpAddress(IpAddress::fromString($this->_getIpAddress()));
        if ($object = $this->_serv->updateRefundRequestApprovalStatus($refund_request_id, $approval_status, $payment_info, $refund_reject_reason, $refund_reject_remarks)) {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $object));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

}