<?php

namespace Iapps\RemittanceService\RefundRequest;

use Iapps\Common\Core\EncryptedField;
use Iapps\Common\Core\IappsBaseEntity;
use Iapps\Common\SystemCode\SystemCode;
use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\Transaction\Transaction;
use Iapps\Common\Microservice\AccountService\User;
use Iapps\Common\Helper\GuidGenerator;
use Iapps\Common\Transaction\TransactionItem;
use Iapps\Common\Transaction\TransactionStatus;
use Iapps\RemittanceService\RemittanceTransaction\ItemType;

class RefundRequest extends IappsBaseEntity{
    protected $transactionID;
    protected $refundID;
    protected $refund_remarks;
    protected $reference_no;
    protected $payment_code;
    protected $status;
    protected $paid_at;
    protected $payment_request_id;
    protected $country_currency_code;
    protected $amount;
    protected $approval_status;
    protected $approved_rejected_by;
    protected $approved_rejected_by_name;
    protected $approved_rejected_at;
    protected $approved_rejected_remarks;
    protected $user_profile_id;
    protected $user_profile_name;
    protected $user_profile_accountID;
    protected $transID;
    protected $payment_code_name;

    protected $transaction;
    protected $refund_transaction;

    protected $attributes;

    protected $approval_required;

    function __construct()
    {
        parent::__construct();

        $this->status = new SystemCode();
        $this->paid_at = new IappsDateTime();
        $this->approved_rejected_at = new IappsDateTime();
        $this->transaction = new Transaction();
        $this->refund_transaction = new Transaction();
    }


    public function setTransactionID($transactionID)
    {
        $this->transactionID = $transactionID;
        return $this;
    }

    public function getTransactionID()
    {
        return $this->transactionID;
    }


    public function setRefundID($refundID)
    {
        $this->refundID = $refundID;
        return $this;
    }

    public function getRefundID()
    {
        return $this->refundID;
    }


    public function setRefundRemarks($refund_remarks)
    {
        $this->refund_remarks = $refund_remarks;
        return $this;
    }

    public function getRefundRemarks()
    {
        return $this->refund_remarks;
    }


    public function setReferenceNo($reference_no)
    {
        $this->reference_no = $reference_no;
        return $this;
    }

    public function getReferenceNo()
    {
        return $this->reference_no;
    }


    public function setPaymentCode($payment_code)
    {
        $this->payment_code = $payment_code;
        return $this;
    }

    public function getPaymentCode()
    {
        return $this->payment_code;
    }


    public function setStatus(SystemCode $status)
    {
        $this->status = $status;
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }


    public function setPaidAt(IappsDateTime $paid_at)
    {
        $this->paid_at = $paid_at;
        return $this;
    }

    public function getPaidAt()
    {
        return $this->paid_at;
    }


    public function setPaymentRequestId($payment_request_id)
    {
        $this->payment_request_id = $payment_request_id;
        return $this;
    }

    public function getPaymentRequestId()
    {
        return $this->payment_request_id;
    }


    public function setCountryCurrencyCode($country_currency_code)
    {
        $this->country_currency_code = $country_currency_code;
        return $this;
    }

    public function getCountryCurrencyCode()
    {
        return $this->country_currency_code;
    }


    public function setAmount($amount)
    {
        $this->amount = $amount;
        return $this;
    }

    public function getAmount()
    {
        return $this->amount;
    }


    public function setApprovalStatus($approval_status)
    {
        $this->approval_status = $approval_status;
        return $this;
    }

    public function getApprovalStatus()
    {
        return $this->approval_status;
    }


    public function setApprovedRejectedBy($approved_rejected_by)
    {
        $this->approved_rejected_by = $approved_rejected_by;
        return $this;
    }

    public function getApprovedRejectedBy()
    {
        return $this->approved_rejected_by;
    }

    public function setApprovedRejectedByName($approved_rejected_by_name)
    {
        $this->approved_rejected_by_name = $approved_rejected_by_name;
        return $this;
    }

    public function getApprovedRejectedByName()
    {
        return $this->approved_rejected_by_name;
    }

    public function setApprovedRejectedAt(IappsDateTime $approved_rejected_at)
    {
        $this->approved_rejected_at = $approved_rejected_at;
        return $this;
    }

    public function getApprovedRejectedAt()
    {
        return $this->approved_rejected_at;
    }

    public function setApprovedRejectedRemarks($approved_rejected_remarks)
    {
        $this->approved_rejected_remarks = $approved_rejected_remarks;
        return $this;
    }

    public function getApprovedRejectedRemarks()
    {
        return $this->approved_rejected_remarks;
    }

    public function setUserProfileId($user_profile_id)
    {
        $this->user_profile_id = $user_profile_id;
        return $this;
    }

    public function getUserProfileId()
    {
        return $this->user_profile_id;
    }

    public function setUserProfileName($user_profile_name)
    {
        $this->user_profile_name = $user_profile_name;
        return $this;
    }

    public function getUserProfileName()
    {
        return $this->user_profile_name;
    }

    public function setUserProfileAccountID($accountID)
    {
        $this->user_profile_accountID = $accountID;
        return $this;
    }

    public function getUserProfileAccountID()
    {
        return $this->user_profile_accountID;
    }

    public function setTransID($transID)
    {
        $this->transID = $transID;
        return $this;
    }

    public function getTransID()
    {
        return $this->transID;
    }

    public function setPaymentCodeName($payment_code_name)
    {
        $this->payment_code_name = $payment_code_name;
        return $this;
    }

    public function getPaymentCodeName()
    {
        return $this->payment_code_name;
    }

    public function setTransaction(Transaction $transaction = NULL)
    {
        $this->transaction = $transaction;
        return $this;
    }

    public function getTransaction()
    {
        return $this->transaction;
    }

    public function setRefundTransaction(Transaction $transaction = NULL)
    {
        $this->refund_transaction = $transaction;
        return $this;
    }

    public function getRefundTransaction()
    {
        return $this->refund_transaction;
    }

    public function setAttributes($collection)
    {
        $this->attributes = $collection;
        return $this;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function setApprovalRequired($approval_required)
    {
        $this->approval_required = $approval_required;
        return $this;
    }

    public function getApprovalRequired()
    {
        return $this->approval_required;
    }

    public function generateRefundTransaction(Transaction $oriTransaction, Transaction $refundTransaction, $transaction_type_id, $remarks, $updated_by, $generalDesc, $display_transactionID, array $exclude_item_type_ids = NULL)
    {
        $refund_transaction_id = GuidGenerator::generate();

        $refundTransaction->setId($refund_transaction_id);
        $refundTransaction->getTransactionType()->setId($transaction_type_id);
        $refundTransaction->setUserProfileId($oriTransaction->getUserProfileId());
        $refundTransaction->getStatus()->setCode(TransactionStatus::INITIATED);
        $refundTransaction->setCountryCurrencyCode($oriTransaction->getCountryCurrencyCode());
        $refundTransaction->setRemark($remarks);
        $refundTransaction->setDescription($oriTransaction->getTransactionType()->getDisplayName());
        $refundTransaction->setRefTransactionId($oriTransaction->getId());

        foreach ($oriTransaction->getItems() AS $item) {

            if (!in_array($item->getItemType()->getId(), $exclude_item_type_ids)) {

                if($item->getItemType()->getCode() == ItemType::CORPORATE_SERVICE) {

                    if($generalDesc != NULL) {
                        $generalDesc->add('Transaction Type', $oriTransaction->getTransactionType()->getDisplayName());
                        $generalDesc->add('Transaction ID', $display_transactionID);
                        $item->setDescription($generalDesc->toJson());
                    }

                }

                $item->setTransactionId($refund_transaction_id);
                $item->setUnitPrice($item->getUnitPrice() * -1);
                $item->setNetAmount($item->getNetAmount() * -1);
                $item->setRefTransactionItemId($item->getId());
                $item->setId(GuidGenerator::generate());
                $item->setCreatedBy($updated_by);

                $refundTransaction->addItem($item);
            }

        }

        $this->setRefundTransaction($refundTransaction);

        return $refundTransaction;
    }


    public function jsonSerialize()
    {
        $json = parent::jsonSerialize();

        $json['transactionID'] = $this->getTransactionID();
        $json['refundID'] = $this->getRefundID();
        $json['refund_remarks'] = $this->getRefundRemarks();
        $json['reference_no'] = $this->getReferenceNo();
        $json['payment_code'] = $this->getPaymentCode();
        $json['status'] = $this->getStatus()->getCode();
        $json['paid_at'] = $this->getPaidAt()->getString();
        $json['payment_request_id'] = $this->getPaymentRequestId();
        $json['country_currency_code'] = $this->getCountryCurrencyCode();
        $json['amount'] = $this->getAmount();
        $json['approval_required'] = $this->getApprovalRequired();
        $json['approval_status'] = $this->getApprovalStatus();
        $json['approved_rejected_by'] = $this->getApprovedRejectedBy();
        $json['approved_rejected_by_name'] = $this->getApprovedRejectedByName();
        $json['approved_rejected_at'] = $this->getApprovedRejectedAt()->getString();
        $json['approved_rejected_remarks'] = $this->getApprovedRejectedRemarks();
        $json['user_profile_id'] = $this->getUserProfileId();
        $json['user_profile_name'] = $this->getUserProfileName();
        $json['user_profile_accountID'] = $this->getUserProfileAccountID();
        $json['payment_code_name'] = $this->getPaymentCodeName();

        $json['transaction'] = $this->getTransaction();
        $json['refund_transaction'] = $this->getRefundTransaction();
        $json['attributes'] = $this->getAttributes();

        return $json;
    }
}