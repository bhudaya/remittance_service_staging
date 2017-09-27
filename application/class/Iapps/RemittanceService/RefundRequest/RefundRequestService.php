<?php

namespace Iapps\RemittanceService\RefundRequest;

use Iapps\Common\AuditLog\AuditLogAction;
use Iapps\Common\Core\IappsBaseService;
use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\Helper\GuidGenerator;
use Iapps\Common\Microservice\AccountService\AccountService;
use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\Common\Microservice\CountryService\CountryServiceFactory;
use Iapps\Common\Microservice\PaymentService\SystemPaymentService;
use Iapps\Common\Microservice\PaymentService\PaymentServiceFactory;
use Iapps\Common\Microservice\PaymentService\SystemPaymentServiceFactory;
use Iapps\RemittanceService\Attribute\AttributeCode;
use Iapps\RemittanceService\Attribute\AttributeValueServiceFactory;
use Iapps\RemittanceService\Attribute\RefundRequestAttributeService;
use Iapps\RemittanceService\Common\MessageCode;
use Iapps\RemittanceService\Attribute\AttributeValue;
use Iapps\Common\Transaction\Transaction;
use Iapps\Common\Transaction\TransactionService;
use Iapps\Common\Transaction\TransactionStatus;
use Iapps\RemittanceService\Common\IncrementIDServiceFactory;
use Iapps\RemittanceService\Common\IncrementIDAttribute;
use Iapps\Common\Core\IappsBaseRepository;
use Iapps\Common\SystemCode\SystemCode;
use Iapps\Common\SystemCode\SystemCodeService;


class RefundRequestService extends IappsBaseService{

    const CODE_GET_REFUND_REQUEST_SUCCESS = 9500;
    const CODE_GET_REFUND_REQUEST_FAILED = 9501;
    const CODE_GET_REFUND_REQUEST_NOT_FOUND = 9503;
    const CODE_ADD_REFUND_REQUEST_SUCCESS = 9504;
    const CODE_ADD_REFUND_REQUEST_FAILED = 9505;
    const CODE_EDIT_REFUND_REQUEST_SUCCESS = 9506;
    const CODE_EDIT_REFUND_REQUEST_FAILED = 9507;
    const CODE_INVALID_REFUND_REQUEST_STATUS = 9508;
    const CODE_INVALID_REFUND_TRANSACTION_TYPE = 9509;
    const CODE_INVALID_EXCLUDE_ITEM_TYPE = 9510;

    const CODE_GET_REFUND_TRANSACTION_SUCCESS = 9520;
    const CODE_GET_REFUND_TRANSACTION_FAILED = 9521;
    const CODE_GET_REFUND_TRANSACTION_NOT_FOUND = 9523;
    const CODE_ADD_REFUND_TRANSACTION_SUCCESS = 9524;
    const CODE_ADD_REFUND_TRANSACTION_FAILED = 9525;
    const CODE_EDIT_REFUND_TRANSACTION_SUCCESS = 9526;
    const CODE_EDIT_REFUND_TRANSACTION_FAILED = 9527;


    protected $_trans_serv;
    protected $_syscode_serv;
    protected $_refund_attribute_serv;

    protected $_accountService;
    protected $_systemPaymentService;
    protected $paymentInterface;

    protected $refund_request_table_name;
    protected $refund_transaction_type_code;
    protected $refund_transaction_type_system_group_code;
    protected $exclude_item_type_code_arr;
    protected $exclude_item_type_system_group_code;
    protected $refund_transaction;
    protected $refund_reason_attribute_code;
    protected $refund_reject_reason_attribute_code;
    protected $general_description;
    protected $display_transactionID;

    function __construct(IappsBaseRepository $refund_repo,
                         TransactionService $trans_serv,
                         SystemCodeService $syscode_serv,
                         RefundRequestAttributeService $refundattribute_serv)
    {
        $this->_trans_serv = $trans_serv;
        $this->_syscode_serv = $syscode_serv;
        $this->_refund_attribute_serv = $refundattribute_serv;

        parent::__construct($refund_repo);
    }


    public function setAccountService(AccountService $accountService)
    {
        $this->_accountService = $accountService;
    }

    public function getAccountService()
    {
        if( !$this->_accountService )
        {
            $this->_accountService = AccountServiceFactory::build();
        }

        return $this->_accountService;
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


    public function setPaymentInterface($paymentInterface)
    {
        $this->paymentInterface = $paymentInterface;
    }

    public function setRefundRequestTableName($refund_request_table_name)
    {
        $this->refund_request_table_name = $refund_request_table_name;
        return $this;
    }

    public function getRefundRequestTableName()
    {
        return $this->refund_request_table_name;
    }

    public function setRefundTransactionTypeCode($refund_transaction_type_code)
    {
        $this->refund_transaction_type_code = $refund_transaction_type_code;
        return $this;
    }

    public function getRefundTransactionTypeCode()
    {
        return $this->refund_transaction_type_code;
    }

    public function setRefundTransactionTypSystemGroupCode($refund_transaction_type_system_group_code)
    {
        $this->refund_transaction_type_system_group_code = $refund_transaction_type_system_group_code;
        return $this;
    }

    public function getRefundTransactionTypSystemGroupCode()
    {
        return $this->refund_transaction_type_system_group_code;
    }

    public function setExcludeItemTypeCodeArr(array $exclude_item_type_code_arr)
    {
        $this->exclude_item_type_code_arr = $exclude_item_type_code_arr;
        return $this;
    }

    public function getExcludeItemTypeCodeArr()
    {
        return $this->exclude_item_type_code_arr;
    }

    public function setExcludeItemTypeSystemGroupCode($exclude_item_type_system_group_code)
    {
        $this->exclude_item_type_system_group_code = $exclude_item_type_system_group_code;
        return $this;
    }

    public function getExcludeItemTypeSystemGroupCode()
    {
        return $this->exclude_item_type_system_group_code;
    }

    public function setRefundTransaction($refundTransaction)
    {
        $this->refund_transaction = $refundTransaction;
        return $this;
    }

    public function getRefundTransaction()
    {
        return $this->refund_transaction;
    }

    public function setRefundReasonAttributeCode($refund_reason_attribute_code)
    {
        $this->refund_reason_attribute_code = $refund_reason_attribute_code;
        return $this;
    }

    public function getRefundReasonAttributeCode()
    {
        return $this->refund_reason_attribute_code;
    }

    public function setRefundRejectReasonAttributeCode($refund_reject_reason_attribute_code)
    {
        $this->refund_reject_reason_attribute_code = $refund_reject_reason_attribute_code;
        return $this;
    }

    public function getRefundRejectReasonAttributeCode()
    {
        return $this->refund_reject_reason_attribute_code;
    }

    public function setGeneralDescription($generalDesc)
    {
        $this->general_description = $generalDesc;
        return $this;
    }

    public function getGeneralDescription()
    {
        return $this->general_description;
    }

    public function setDisplayTransactionID($transactionDetail, $transactionID)
    {
        $this->display_transactionID = $transactionID;

        if(isset($transactionDetail->remittance)) {
            if(array_key_exists('remittanceID', $transactionDetail->remittance)) {
                $this->display_transactionID = $transactionDetail->remittance['remittanceID'];
            }
        }
    }

    protected function _hasRefundRecord($transactionID)
    {
        $has_refund_record = FALSE;
        $refundRequest = new RefundRequest();
        $refundRequest->setTransactionID($transactionID);
        if( $existingRefundRequestColl = $this->getRepository()->findByParam($refundRequest, array(), 100, 1) )
        {
            foreach ($existingRefundRequestColl->result as $existingRefundRequestEach) {
                if($existingRefundRequestEach->getStatus()->getCode() != RefundRequestStatus::REJECTED)
                {
                    $has_refund_record = TRUE;
                    break;
                }
            }
        }

        return $has_refund_record;
    }

    protected function _hasRefundedItem(Transaction $transaction)
    {
        $has_refunded_item = FALSE;

        foreach ($transaction->getItems() as $transItemEach) {
            if ($transItemEach->getRefundedQuantity() != 0) {
                $has_refunded_item = TRUE;
                break;
            }
        }

        return $has_refunded_item;
    }

    protected function _generateRefundID()
    {
        $inc_serv = IncrementIDServiceFactory::build();
        return $inc_serv->getIncrementID(IncrementIDAttribute::REFUND_ID);
    }

    protected function _getRefundTransactionType()
    {
        if(!$transactionType = $this->_syscode_serv->getByCode($this->refund_transaction_type_code, $this->refund_transaction_type_system_group_code))
        {
            $this->setResponseCode(self::CODE_INVALID_REFUND_TRANSACTION_TYPE);
            return false;
        }

        return $transactionType;
    }

    protected function _extractStatusId(RefundRequest $refundRequest)
    {
        //extract status id
        if( !$status = $this->_syscode_serv->getByCode($refundRequest->getStatus()->getCode(), RefundRequestStatus::getSystemGroupCode()))
        {
            $this->setResponseCode(self::CODE_INVALID_REFUND_REQUEST_STATUS);
            return false;
        }
        $refundRequest->setStatus($status);

        return $refundRequest;
    }

    protected function _extractTransactionStatus($code)
    {
        return $this->_syscode_serv->getByCode($code, TransactionStatus::getSystemGroupCode());
    }

    protected function _getExcludeTransactionTypes()
    {
        $exclude_item_type_id_arr = array();
        if($this->exclude_item_type_code_arr != NULL) {
            foreach ($this->exclude_item_type_code_arr as $exclude_code) {
                if($excludeItemType = $this->_syscode_serv->getByCode($exclude_code, $this->exclude_item_type_system_group_code))
                {
                    $exclude_item_type_id_arr[] = $excludeItemType->getId();
                }
                else
                {
                    $this->setResponseCode(self::CODE_INVALID_EXCLUDE_ITEM_TYPE);
                    return false;
                }
            }
        }

        return $exclude_item_type_id_arr;
    }

    protected function _saveTransaction(Transaction $transaction)
    {
        $this->_trans_serv->setUpdatedBy($this->getUpdatedBy());
        $this->_trans_serv->setIpAddress($this->getIpAddress());

        $transaction->setCreatedBy($this->getUpdatedBy());
        if( !$result = $this->_trans_serv->saveTransaction($transaction) )
        {
            $this->setResponseCode(self::CODE_ADD_REFUND_TRANSACTION_FAILED);
            return false;
        }

        return $result;
    }

    protected function _saveRefund(RefundRequest $refundRequest)
    {
        if( !$this->_extractStatusId($refundRequest) )
            return false;

        if( $this->getRepository()->insert($refundRequest) )
        {
            $this->fireLogEvent($this->refund_request_table_name, AuditLogAction::CREATE, $refundRequest->getId());
            return $refundRequest;
        }

        $this->setResponseCode(self::CODE_ADD_REFUND_REQUEST_SUCCESS);
        return false;
    }

    protected function _updateRefund(RefundRequest $refundRequest, RefundRequest $ori_record)
    {
        if( !$this->_extractStatusId($refundRequest) )
            return false;

        $refundRequest->setUpdatedBy($this->getUpdatedBy());
        if( $this->getRepository()->update($refundRequest) )
        {
            $this->fireLogEvent($this->refund_request_table_name, AuditLogAction::UPDATE, $refundRequest->getId(), $ori_record);
            return $refundRequest;
        }

        $this->setResponseCode(self::CODE_EDIT_REFUND_REQUEST_SUCCESS);
        return false;
    }

    protected function _completeTransaction(Transaction $transaction)
    {
        $this->_trans_serv->setUpdatedBy($this->getUpdatedBy());
        $this->_trans_serv->setIpAddress($this->getIpAddress());

        if( !$result = $this->_trans_serv->completeTransaction($transaction) )
        {
            $this->setResponseCode(self::CODE_EDIT_REFUND_TRANSACTION_FAILED);
            return false;
        }

        return $result;
    }

    protected function _requestPayment(Transaction $transaction, array $payment_info, $bySystem = false)
    {
        if( $bySystem ) {
            $paymentInterface = new SystemRefundPayment();
            $paymentInterface->setSystemPaymentService($this->getSystemPaymentService());
        }else{
            $paymentInterface = $this->paymentInterface;
        }
        if( $transaction->getItems()->validatePaymentAmount($payment_info['amount']) )
        {
            if( $request_id = $paymentInterface->paymentRequest($transaction, $payment_info) )
            {
                return $request_id;
            }

            $lastResponse = $paymentInterface->getLastResponse();
            if( isset($lastResponse['status_code']) )
                $this->setResponseCode($lastResponse['status_code']);
            else
                $this->setResponseCode(MessageCode::CODE_PAYMENT_REQUEST_FAILED);

            if( isset($lastResponse['message']) )
                $this->setResponseMessage($lastResponse['message']);
            return false;
        }
        else
        {//invalid payment amount
            $this->setResponseCode(MessageCode::CODE_INVALID_PAYMENT_AMOUNT);
            return false;
        }
    }

    protected function _completePayment(Transaction $transaction, $request_id, $bySystem = false)
    {
        if ($bySystem) {
            $paymentInterface = new SystemRefundPayment();
            $paymentInterface->setSystemPaymentService($this->getSystemPaymentService());
        }else {
            $paymentInterface = $this->paymentInterface;
        }
        if ($info = $paymentInterface->paymentComplete($transaction, $request_id))
            return $info;

        $lastResponse = $paymentInterface->getLastResponse();
        if (isset($lastResponse['status_code']))
            $this->setResponseCode($lastResponse['status_code']);
        else
            $this->setResponseCode(MessageCode::CODE_MAKE_PAYMENT_FAIL);

        if (isset($lastResponse['message']))
            $this->setResponseMessage($lastResponse['message']);

        return false;
    }

    protected function _cancelPayment(Transaction $transaction, $request_id, $bySystem = false)
    {
        if ($bySystem) {
            $paymentInterface = new SystemRefundPayment();
            $paymentInterface->setSystemPaymentService($this->getSystemPaymentService());
        }else {
            $paymentInterface = $this->paymentInterface;
        }

        if ($info = $paymentInterface->paymentCancel($transaction, $request_id))
            return $info;

        $lastResponse = $paymentInterface->getLastResponse();
        if (isset($lastResponse['status_code']))
            $this->setResponseCode($lastResponse['status_code']);
        else
            $this->setResponseCode(MessageCode::CODE_MAKE_PAYMENT_FAIL);

        if (isset($lastResponse['message']))
            $this->setResponseMessage($lastResponse['message']);

        return false;
    }

    protected function _completeRefund(RefundRequest $refundRequest, $bySystem = false)
    {
        if( $this->_completeTransaction($refundRequest->getRefundTransaction()) )
        {
            //complete payment
            if( $info = $this->_completePayment($refundRequest->getRefundTransaction(), $refundRequest->getPaymentRequestId(), $bySystem) )
            {
                $refundRequest->setPaidAt(IappsDateTime::now());
                if($refundRequest->getApprovalRequired()){
                    $refundRequest->getStatus()->setCode(RefundRequestStatus::REFUNDED);
                }else{
                    $refundRequest->getStatus()->setCode(RefundRequestStatus::AUTO_REFUNDED);
                }
                return $info;
            }
        }

        return false;
    }

    protected function _updateTransactionAndItems(RefundRequest $refundRequest) {

        //update confirm payment code on refund transaction
        $oriRefundTransaction = clone($refundRequest->getRefundTransaction());
        $refundRequest->getRefundTransaction()->setConfirmPaymentCode($refundRequest->getPaymentCode());
        $this->_trans_serv->setUpdatedBy($this->getUpdatedBy());
        if (!$this->_trans_serv->update($refundRequest->getRefundTransaction(), $oriRefundTransaction)) {

            $this->setResponseCode(self::CODE_EDIT_REFUND_TRANSACTION_FAILED);
            return false;

        }

        //update refunded qty on original transaction items
        $this->_trans_serv->getItemService()->setUpdatedBy($this->getUpdatedBy());
        foreach ($refundRequest->getRefundTransaction()->getItems() as $refundItemEach) {

            foreach ($refundRequest->getTransaction()->getItems() as $transItemEach) {

                if ($transItemEach->getId() == $refundItemEach->getRefTransactionItemId()) {

                    $oriItem = clone($transItemEach);
                    $refunded_qty = $transItemEach->getRefundedQuantity() != NULL ? $transItemEach->getRefundedQuantity() : 0;
                    $total_refunded_qty = $refundItemEach->getQuantity() + $refunded_qty;
                    if ($total_refunded_qty <= $transItemEach->getQuantity()) {

                        $transItemEach->setRefundedQuantity($total_refunded_qty);
                        if (!$this->_trans_serv->getItemService()->update($transItemEach, $oriItem)) {

                            $this->setResponseCode(self::CODE_EDIT_REFUND_TRANSACTION_FAILED);
                            return false;

                        }

                        break;

                    } else {

                        $this->setResponseCode(self::CODE_EDIT_REFUND_TRANSACTION_FAILED);
                        return false;

                    }
                }
            }
        }

        return true;
    }

    public function initiateFullRefundRequest($transactionID, array $refund_reason, $refund_remarks, $approval_required = true, $handle_transaction = true)
    {
        if( $transaction = $this->_trans_serv->findByTransactionID($transactionID)) {

            if($transaction instanceof Transaction) {

                if($completedTransStatus = $this->_extractTransactionStatus(TransactionStatus::COMPLETED)) {

                    if ($transaction->getStatus()->getId() == $completedTransStatus->getid()) {

                        if (!$this->_hasRefundRecord($transactionID)) {

                            if ($transaction->getItems()) {

                                if (!$this->_hasRefundedItem($transaction)) {

                                    if (!$refundID = $this->_generateRefundID())
                                        return false;

                                    if ($refundTransactionType = $this->_getRefundTransactionType()) {

                                        $refundRequest = new RefundRequest();
                                        $exclude_transaction_type_ids = array();
                                        if ($exclude_item_type_ids = $this->_getExcludeTransactionTypes()) {

                                            $transactionDetail = $this->_trans_serv->getTransactionDetail($transaction, 1, 1);
                                            $this->setDisplayTransactionID($transactionDetail, $transaction->getTransactionID());
                                            $this->refund_transaction->setTransactionID($refundID);
                                            $refundRequest->generateRefundTransaction($transaction, $this->refund_transaction, $refundTransactionType->getId(), $refund_remarks,
                                                $this->getUpdatedBy(), $this->general_description, $this->display_transactionID, $exclude_item_type_ids);

                                            $this->getRepository()->startDBTransaction($handle_transaction);

                                            //create refund transaction
                                            if ($this->_saveTransaction($refundRequest->getRefundTransaction())) {

                                                $refundRequest->setId(GuidGenerator::generate());
                                                $refundRequest->setTransactionID($transaction->getTransactionID());
                                                $refundRequest->setRefundID($refundID);
                                                $refundRequest->setRefundRemarks($refund_remarks);
                                                $refundRequest->setCountryCurrencyCode($transaction->getCountryCurrencyCode());
                                                $refundRequest->setAmount($refundRequest->getRefundTransaction()->getItems()->getTotalAmount());
                                                $refundRequest->getStatus()->setCode(RefundRequestStatus::INITIATED);
                                                $refundRequest->setApprovalStatus(RefundRequestApprovalStatus::PENDING);
                                                $refundRequest->setUserProfileId($transaction->getUserProfileId());
                                                $refundRequest->setCreatedBy($this->getUpdatedBy());
                                                $refundRequest->setApprovalRequired($approval_required);

                                                //create refund request
                                                if ($this->_saveRefund($refundRequest)) {

                                                    //set refund attribute
                                                    $this->_refund_attribute_serv->setUpdatedBy($this->getUpdatedBy());
                                                    $this->_refund_attribute_serv->setIpAddress($this->getIpAddress());

                                                    $attributeV = new AttributeValue();
                                                    if (is_array($refund_reason)) {
                                                        $attributeV->getAttribute()->setCode($this->refund_reason_attribute_code);
                                                        if (array_key_exists('id', $refund_reason)) {
                                                            $attributeV->setId($refund_reason['id']);
                                                        }
                                                        if (array_key_exists('value', $refund_reason)) {
                                                            $attributeV->setValue($refund_reason['value']);
                                                        }
                                                    }
                                                    //update attributes
                                                    if (!$this->_refund_attribute_serv->setRefundRequestAttribute($refundRequest->getId(), $attributeV)) {
                                                        $this->getRepository()->rollbackDBTransaction($handle_transaction);
                                                        $this->setResponseCode(self::CODE_ADD_REFUND_REQUEST_FAILED);
                                                        return false;
                                                    }

                                                    $this->getRepository()->completeDBTransaction($handle_transaction);
                                                    $this->setResponseCode(self::CODE_ADD_REFUND_REQUEST_SUCCESS);

                                                    if(!$approval_required){
                                                        RefundRequestEventProducer::publishRefundInitiated($refundRequest->getId());
                                                    }
                                                    return array(
                                                        'refund' => $refundRequest->getSelectedField(array('id', 'refundID', 'country_currency_code', 'amount', 'status', 'user_profile_id'))
                                                    );
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if( !$this->getResponseCode() )
            $this->setResponseCode(self::CODE_ADD_REFUND_REQUEST_FAILED);
        return false;
    }

    public function updateRefundRequestApprovalStatus($refund_request_id, $approval_status, array $payment_info, array $refund_reject_reason = NULL, $refund_reject_remarks = NULL)
    {
        if( $refundRequest = $this->getRepository()->findById($refund_request_id, true) ) {

            if ($refundRequest instanceof RefundRequest) {

                if ($refundRequest->getStatus()->getCode() == RefundRequestStatus::INITIATED) {

                    if ($approval_status == RefundRequestApprovalStatus::APPROVED || $approval_status == RefundRequestApprovalStatus::REJECTED) {


                        if ($transaction = $this->_trans_serv->findByTransactionID($refundRequest->getTransactionID())) {
                            if ($transaction instanceof Transaction) {
                                $refundRequest->setTransaction($transaction);
                            }
                        }

                        if ($refundTransaction = $this->_trans_serv->findByTransactionID($refundRequest->getRefundID())) {
                            if ($refundTransaction instanceof Transaction) {
                                $refundRequest->setRefundTransaction($refundTransaction);
                            }
                        }

                        $this->getRepository()->beginDBTransaction();

                        $oriRefundRequest = clone($refundRequest);
                        if ($approval_status == RefundRequestApprovalStatus::REJECTED) {
                            $refundRequest->getStatus()->setCode(RefundRequestStatus::REJECTED);
                        }
                        $refundRequest->setApprovalStatus($approval_status);
                        $refundRequest->setApprovedRejectedAt(IappsDateTime::now());
                        $refundRequest->setApprovedRejectedBy($this->getUpdatedBy());
                        if ($refund_reject_remarks != NULL) {
                            $refundRequest->setApprovedRejectedRemarks($refund_reject_remarks);
                        }
                        if (array_key_exists('reference_no', $payment_info)) {
                            if ($payment_info['reference_no'] != NULL) {
                                $refundRequest->setReferenceNo($payment_info['reference_no']);
                            }
                        }
                        $refundRequest->setUpdatedBy($this->getUpdatedBy());

                        //save refund reject reason attribute
                        if ($refund_reject_reason != NULL) {
                            $this->_refund_attribute_serv->setUpdatedBy($this->getUpdatedBy());
                            $this->_refund_attribute_serv->setIpAddress($this->getIpAddress());

                            $attributeV = new AttributeValue();
                            if (is_array($refund_reject_reason)) {
                                $attributeV->getAttribute()->setCode($this->refund_reject_reason_attribute_code);
                                if (array_key_exists('id', $refund_reject_reason)) {
                                    $attributeV->setId($refund_reject_reason['id']);
                                }
                                if (array_key_exists('value', $refund_reject_reason)) {
                                    $attributeV->setValue($refund_reject_reason['value']);
                                }
                            }
                            //update attributes
                            if (!$this->_refund_attribute_serv->setRefundRequestAttribute($refundRequest->getId(), $attributeV)) {
                                $this->getRepository()->rollbackDBTransaction();
                                $this->setResponseCode(self::CODE_EDIT_REFUND_REQUEST_FAILED);
                                return false;
                            }
                        }

                        if ($approval_status == RefundRequestApprovalStatus::APPROVED) {

                            if (!array_key_exists('payment_code', $payment_info)) {
                                $this->getRepository()->rollbackDBTransaction();
                                $this->setResponseCode(self::CODE_EDIT_REFUND_REQUEST_FAILED);
                                return false;
                            }

                            //check if payment info -> payment code is valid
                            $payment_service = PaymentServiceFactory::build();
                            if ($paymentModeInfo = $payment_service->getPaymentModeInfo($payment_info['payment_code'])) {

                                if ($paymentModeInfo->getForRefund()) {

                                    $refundRequest->setPaymentCode($payment_info['payment_code']);

                                    //update transaction and items
                                    if (!$this->_updateTransactionAndItems($refundRequest)) {
                                        $this->getRepository()->rollbackDBTransaction();
                                        return false;
                                    }

                                    //request payment
                                    $payment_info['amount'] = $refundRequest->getRefundTransaction()->getItems()->getTotalAmount();
                                    $payment_info['option'] = array( 'reference_no' => $payment_info['reference_no'] );
                                    if (!$request_id = $this->_requestPayment($refundRequest->getRefundTransaction(), $payment_info, true)) {
                                        $this->getRepository()->rollbackDBTransaction();
                                        return false;
                                    }

                                    $refundRequest->setPaymentRequestId($request_id);
                                    if (!$refund_payment = $this->_completeRefund($refundRequest, !$refundRequest->getApprovalRequired())) {
                                        $this->getRepository()->rollbackDBTransaction();
                                        return false;
                                    }
                                }
                            }

                        }

                        if ($this->_updateRefund($refundRequest, $oriRefundRequest)) {

                            if ($this->getRepository()->statusDBTransaction() === FALSE){
                                $this->getRepository()->rollbackDBTransaction();
                            }
                            else{
                                $this->getRepository()->commitDBTransaction();
                            }
                            RefundRequestEventProducer::publishStatusChanged($refundRequest->getId(), $refundRequest->getStatus()->getCode());


                            $this->setResponseCode(self::CODE_EDIT_REFUND_REQUEST_SUCCESS);
                            return array(
                                'refund' => $refundRequest->getSelectedField(array('id', 'refundID', 'country_currency_code', 'amount', 'status', 'user_profile_id'))
                                //return refund payment
                            );

                        }

                    }
                }
            }
        }

        if( !$this->getResponseCode() )
            $this->setResponseCode(self::CODE_EDIT_REFUND_REQUEST_FAILED);
        return false;
    }

    public function convertToManualApproval($refund_request_id){
        if( $refundRequest = $this->getRepository()->findById($refund_request_id, true) ) {

            if ($refundRequest instanceof RefundRequest) {

                if ($refundRequest->getStatus()->getCode() == RefundRequestStatus::INITIATED) {

                    if (!$refundRequest->getApprovalRequired()) {
                        $this->getRepository()->beginDBTransaction();

                        $oriRefundRequest = clone($refundRequest);
                        $refundRequest->setApprovalRequired(true);

                        if ($this->_updateRefund($refundRequest, $oriRefundRequest)) {
                            if ($this->getRepository()->statusDBTransaction() === FALSE){
                                $this->getRepository()->rollbackDBTransaction();
                            }else {
                                $this->getRepository()->commitDBTransaction();
                            }

                            return array(
                                'refund' => $refundRequest->getSelectedField(array('id', 'refundID', 'country_currency_code', 'amount', 'status', 'user_profile_id'))
                                //return refund payment
                            );
                        }
                        $this->getRepository()->rollbackDBTransaction();
                        return false;
                    }
                }
            }
        }
        return false;
    }


    public function getRefundRequestListForRequester(RefundRequest $refundRequest, array $keywords, $limit, $page, IappsDateTime $date_from = NULL, IappsDateTime $date_to = NULL)
    {
        $created_by_arr = array();
        if($keywords != NULL)
        {
            //search to account service
            //extract user_profile_id array to $created_by_arr
        }

        if( $collection = $this->getRepository()->findByParam($refundRequest, $created_by_arr, $limit, $page, $date_from, $date_to) )
        {
            $user_profile_id_arr = array();

            foreach ($collection->result as $refundEach) {
                $user_profile_id_arr[] = $refundEach->getUserProfileId();
                $user_profile_id_arr[] = $refundEach->getCreatedBy();
            }

            if($userColl = $this->getAccountService()->getUsers(array_unique($user_profile_id_arr))) {

                $profile_name_found = FALSE;
                $creator_name_found = FALSE;

                foreach ($collection->result as $refundEach) {

                    $refundEach->setTransaction(NULL);
                    $refundEach->setRefundTransaction(NULL);

                    $profile_name_found = FALSE;
                    $creator_name_found = FALSE;

                    foreach ($userColl as $userEach) {
                        if ($userEach->getId() == $refundEach->getUserProfileId()) {
                            $refundEach->setUserProfileAccountID($userEach->getAccountID());
                            $refundEach->setUserProfileName($userEach->getFullName());
                            $profile_name_found = TRUE;
                        }
                        if ($userEach->getId() == $refundEach->getCreatedBy()) {
                            $refundEach->setCreatedByName($userEach->getFullName());
                            $creator_name_found = TRUE;
                        }

                        if($profile_name_found && $creator_name_found) {
                            break;
                        }
                    }

                }
            }

            $this->setResponseCode(self::CODE_GET_REFUND_REQUEST_SUCCESS);
            return $collection;
        }

        $this->setResponseCode(self::CODE_GET_REFUND_REQUEST_FAILED);
        return false;
    }


    public function getRefundRequestListForChecker(RefundRequest $refundRequest, array $keywords, $limit, $page, IappsDateTime $date_from = NULL, IappsDateTime $date_to = NULL)
    {
        $created_by_arr = array();
        if($keywords != NULL)
        {
            //search to account service
            //extract user_profile_id array to $created_by_arr
        }

        if( $collection = $this->getRepository()->findByParam($refundRequest, $created_by_arr, $limit, $page, $date_from, $date_to) )
        {
            $user_profile_id_arr = array();

            foreach ($collection->result as $refundEach) {
                $user_profile_id_arr[] = $refundEach->getUserProfileId();
                $user_profile_id_arr[] = $refundEach->getCreatedBy();
            }

            if($userColl = $this->getAccountService()->getUsers(array_unique($user_profile_id_arr))) {

                $profile_name_found = FALSE;
                $creator_name_found = FALSE;

                foreach ($collection->result as $refundEach) {

                    $refundEach->setTransaction(NULL);
                    $refundEach->setRefundTransaction(NULL);

                    $profile_name_found = FALSE;
                    $creator_name_found = FALSE;

                    foreach ($userColl as $userEach) {
                        if ($userEach->getId() == $refundEach->getUserProfileId()) {
                            $refundEach->setUserProfileAccountID($userEach->getAccountID());
                            $refundEach->setUserProfileName($userEach->getFullName());
                            $profile_name_found = TRUE;
                        }
                        if ($userEach->getId() == $refundEach->getCreatedBy()) {
                            $refundEach->setCreatedByName($userEach->getFullName());
                            $creator_name_found = TRUE;
                        }

                        if($profile_name_found && $creator_name_found) {
                            break;
                        }
                    }

                }
            }

            $this->setResponseCode(self::CODE_GET_REFUND_REQUEST_SUCCESS);
            return $collection;
        }

        $this->setResponseCode(self::CODE_GET_REFUND_REQUEST_FAILED);
        return false;
    }

    public function getRefundRequestDetail($refund_request_id, $for_self = FALSE)
    {
        if( $refundRequest = $this->getRepository()->findById($refund_request_id, true) )
        {
            if($refundRequest instanceof RefundRequest) {

                if($for_self) {
                    if ($refundRequest->getCreatedBy() != $this->getUpdatedBy()) {
                        $this->setResponseCode(self::CODE_GET_REFUND_REQUEST_FAILED);
                        return FALSE;
                    }
                }

                $user_profile_id_arr = array ( $refundRequest->getUserProfileId(), $refundRequest->getCreatedBy() );
                if($refundRequest->getApprovedRejectedBy() != NULL) {
                    $user_profile_id_arr[] = $refundRequest->getApprovedRejectedBy();
                }

                if($userColl = $this->getAccountService()->getUsers($user_profile_id_arr)) {
                    foreach ($userColl as $userEach) {

                        if ($userEach->getId() == $refundRequest->getUserProfileId()) {
                            $refundRequest->setUserProfileAccountID($userEach->getAccountID());
                            $refundRequest->setUserProfileName($userEach->getFullName());
                        }
                        if ($userEach->getId() == $refundRequest->getCreatedBy()) {
                            $refundRequest->setCreatedByName($userEach->getFullName());
                        }
                        if($refundRequest->getApprovedRejectedBy() != NULL) {
                            if ($userEach->getId() == $refundRequest->getApprovedRejectedBy()) {
                                $refundRequest->setApprovedRejectedByName($userEach->getFullName());
                            }
                        }

                    }
                }

                $payment_service = PaymentServiceFactory::build();
                if ($paymentModeInfo = $payment_service->getPaymentModeInfo($refundRequest->getPaymentCode())) {
                    $refundRequest->setPaymentCodeName($paymentModeInfo->getName());
                }

                if ($refundAttributeColl = $this->_refund_attribute_serv->getAllRefundRequestAttribute($refundRequest->getId())) {
                    $refundRequest->setAttributes($refundAttributeColl);
                }

                if ($transaction = $this->_trans_serv->findByTransactionID($refundRequest->getTransactionID())) {
                    if ($transaction instanceof Transaction) {
                        $refundRequest->setTransaction($transaction);
                    }
                }

                if ($refundTransaction = $this->_trans_serv->findByTransactionID($refundRequest->getRefundID())) {
                    if ($refundTransaction instanceof Transaction) {
                        $refundRequest->setRefundTransaction($refundTransaction);
                    }
                }

                $this->setResponseCode(self::CODE_GET_REFUND_REQUEST_SUCCESS);
                return $refundRequest;
            }
        }

        $this->setResponseCode(self::CODE_GET_REFUND_REQUEST_FAILED);
        return false;
    }

    public function getRefundRequestDetailByTransactionID($transactionID)
    {
        if( $refundRequest = $this->getByTransactionID($transactionID) )
        {
            return $this->getRefundRequestDetail($refundRequest->getId());
        }

        $this->setResponseCode(self::CODE_GET_REFUND_REQUEST_FAILED);
        return false;
    }

    public function getByTransactionID($transactionID)
    {
        $refundRequest = new RefundRequest();
        $refundRequest->setTransactionID($transactionID);
        if ($existingRefundRequestColl = $this->getRepository()->findByParam($refundRequest, array(), 100, 1)) {
            return $existingRefundRequestColl->result->current();
        }

        return false;
    }
    
    /*
     * This function will search for refund - attribute value
     * workaround, search by value
     * In the future, we just need to update this code for a proper way
     */
    public function getRefundReason($code)
    {
        $attrib_value_serv = AttributeValueServiceFactory::build();
        if($refundReasonAttributeValueColl =  $attrib_value_serv->getByAttributeCode(AttributeCode::REFUND_REASON))
        {
            if(array_key_exists('list', $refundReasonAttributeValueColl))
            {
                if(array_key_exists('none', $refundReasonAttributeValueColl['list']))
                {
                    foreach($refundReasonAttributeValueColl['list']['none'] as $refundReason)
                    {
                        if($refundReason['value'] == $code)
                        {
                            return $refundReason;
                        }
                    }
                }
            }
        }

        return false;
    }
}