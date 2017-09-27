<?php

use Iapps\RemittanceService\RefundRequest\IRefundRequestDataMapper;
use Iapps\RemittanceService\RefundRequest\RefundRequest;
use Iapps\RemittanceService\RefundRequest\RefundRequestStatus;
use Iapps\RemittanceService\RefundRequest\RefundRequestCollection;
use Iapps\Common\Core\IappsDateTime;

class Refund_request_model extends Base_Model
                      implements IRefundRequestDataMapper{


    public function TransBegin(){
        $this->db->trans_begin();
    }

    public function TransCommit(){
        $this->db->trans_commit();
    }

    public function TransStatus(){
        $this->db->trans_status();
    }

    public function map(stdClass $data)
    {
        $entity = new RefundRequest();

        if( isset($data->refund_request_id) )
            $entity->setId($data->refund_request_id);

        if( isset($data->transactionID) )
            $entity->setTransactionID($data->transactionID);

        if( isset($data->refundID) )
            $entity->setRefundID($data->refundID);

        if( isset($data->refund_remarks) )
            $entity->setRefundRemarks($data->refund_remarks);

        if( isset($data->reference_no) )
            $entity->setReferenceNo($data->reference_no);

        if( isset($data->payment_code) )
            $entity->setPaymentCode($data->payment_code);

        if( isset($data->status_id) )
            $entity->getStatus()->setId($data->status_id);

        if( isset($data->status_code) )
            $entity->getStatus()->setCode($data->status_code);

        if( isset($data->status_name) )
            $entity->getStatus()->setDisplayName($data->status_name);

        if( isset($data->status_group_id) )
            $entity->getStatus()->getGroup()->setId($data->status_group_id);

        if( isset($data->status_group_code) )
            $entity->getStatus()->getGroup()->setCode($data->status_group_code);

        if( isset($data->status_group_name) )
            $entity->getStatus()->getGroup()->setDisplayName($data->status_group_name);

        if( isset($data->paid_at) )
            $entity->setPaidAt(IappsDateTime::fromUnix($data->paid_at));

        if( isset($data->payment_request_id) )
            $entity->setPaymentRequestId($data->payment_request_id);

        if( isset($data->country_currency_code) )
            $entity->setCountryCurrencyCode($data->country_currency_code);

        if( isset($data->amount) )
            $entity->setAmount($data->amount);

        if( isset($data->approval_required) )
            $entity->setApprovalRequired($data->approval_required);

        if( isset($data->approval_status) )
            $entity->setApprovalStatus($data->approval_status);

        if( isset($data->approved_rejected_by) )
            $entity->setApprovedRejectedBy($data->approved_rejected_by);

        if( isset($data->approved_rejected_at) )
            $entity->setApprovedRejectedAt(IappsDateTime::fromUnix($data->approved_rejected_at));

        if( isset($data->approved_rejected_remarks) )
            $entity->setApprovedRejectedRemarks($data->approved_rejected_remarks);

        if( isset($data->user_profile_id) )
            $entity->setUserProfileId($data->user_profile_id);

        if( isset($data->created_at) )
            $entity->setCreatedAt(IappsDateTime::fromUnix($data->created_at));

        if( isset($data->created_by) )
            $entity->setCreatedBy($data->created_by);

        if( isset($data->updated_at) )
            $entity->setUpdatedAt(IappsDateTime::fromUnix($data->updated_at));

        if( isset($data->updated_by) )
            $entity->setUpdatedBy($data->updated_by);

        if( isset($data->deleted_at) )
            $entity->setDeletedAt(IappsDateTime::fromUnix($data->deleted_at));

        if( isset($data->deleted_by) )
            $entity->setDeletedBy($data->deleted_by);

        return $entity;
    }

    public function findById($id, $deleted = false)
    {
        $this->db->select('r.id as refund_request_id,
                           r.transactionID,
                           r.refundID,
                           r.refund_remarks,
                           r.reference_no,
                           r.payment_code,
                           r.status_id,
                           sc.code as status_code,
                           sc.display_name as status_name,
                           scg.id as status_group_id,
                           scg.code as status_group_code,
                           scg.display_name as status_group_name,
                           r.paid_at,
                           r.payment_request_id,
                           r.country_currency_code,
                           r.amount,
                           r.approval_required,
                           r.approval_status,
                           r.approved_rejected_by,
                           r.approved_rejected_at,
                           r.approved_rejected_remarks,
                           r.user_profile_id,
                           r.created_at,
                           r.created_by,
                           r.updated_at,
                           r.updated_by,
                           r.deleted_at,
                           r.deleted_by');
        $this->db->from('iafb_remittance.refund_request r');
        $this->db->join('iafb_remittance.system_code sc', 'r.status_id = sc.id');
        $this->db->join('iafb_remittance.system_code_group scg', 'sc.system_code_group_id = scg.id');
        $this->db->where('scg.code', RefundRequestStatus::getSystemGroupCode());
        $this->db->where('r.deleted_at', NULL);
        $this->db->where('scg.deleted_at', NULL);

        if( !$deleted )
            $this->db->where('r.deleted_at', NULL);
        $this->db->where('r.id', $id);

        $query = $this->db->get();
        if( $query->num_rows() > 0)
        {
            return $this->map($query->row());
        }

        return false;
    }


    public function findByParam(RefundRequest $refundRequest, array $created_by_arr, $limit, $page, IappsDateTime $date_from = NULL, IappsDateTime $date_to = NULL)
    {
        $offset = ($page - 1) * $limit;
        $this->db->start_cache(); //to cache active record query
        $this->db->select('r.id as refund_request_id,
                           r.transactionID,
                           r.refundID,
                           r.refund_remarks,
                           r.reference_no,
                           r.payment_code,
                           r.status_id,
                           sc.code as status_code,
                           sc.display_name as status_name,
                           scg.id as status_group_id,
                           scg.code as status_group_code,
                           scg.display_name as status_group_name,
                           r.paid_at,
                           r.payment_request_id,
                           r.country_currency_code,
                           r.amount,
                           r.approval_required,
                           r.approval_status,
                           r.approved_rejected_by,
                           r.approved_rejected_at,
                           r.approved_rejected_remarks,
                           r.user_profile_id,
                           r.created_at,
                           r.created_by,
                           r.updated_at,
                           r.updated_by,
                           r.deleted_at,
                           r.deleted_by');
        $this->db->from('iafb_remittance.refund_request r');
        $this->db->join('iafb_remittance.system_code sc', 'r.status_id = sc.id');
        $this->db->join('iafb_remittance.system_code_group scg', 'sc.system_code_group_id = scg.id');
        $this->db->where('scg.code', RefundRequestStatus::getSystemGroupCode());
        $this->db->where('r.deleted_at', NULL);
        $this->db->where('scg.deleted_at', NULL);

        if($created_by_arr != NULL)
        {
            if(count($created_by_arr) > 0) {
                $this->db->where_in('r.created_by', $created_by_arr);
            }
        }
        if($date_from != NULL){
            $this->db->where('r.created_at >=', $date_from->getUnix());
        }
        if($date_to != NULL){
            $this->db->where('r.created_at <=', $date_to->getUnix());
        }
        if($refundRequest->getTransID() != NULL)
        {
            $this->db->group_start();
            $this->db->where('r.transactionID', $refundRequest->getTransID());
            $this->db->or_where('r.refundID', $refundRequest->getTransID());
            $this->db->group_end();
        }
        if($refundRequest->getTransactionID() != NULL) {
            $this->db->where('r.transactionID', $refundRequest->getTransactionID());
        }
        if($refundRequest->getRefundID() != NULL) {
            $this->db->where('r.refundID', $refundRequest->getRefundID());
        }
        if($refundRequest->getPaymentCode() != NULL) {
            $this->db->where('r.payment_code', $refundRequest->getPaymentCode());
        }
        if($refundRequest->getCreatedBy() != NULL) {
            $this->db->where('r.created_by', $refundRequest->getCreatedBy());
        }

        if($refundRequest->getApprovalRequired() != NULL) {
            $this->db->where('r.approval_required', $refundRequest->getApprovalRequired());
        }

        if($refundRequest->getApprovalStatus() != NULL) {
            $this->db->where('r.approval_status', $refundRequest->getApprovalStatus());
        }

        if($refundRequest->getId() != NULL) {
            $this->db->where('r.id', $refundRequest->getId());
        }

        $this->db->order_by("r.created_at", "desc");

        $this->db->stop_cache();
        $total = $this->db->count_all_results(); //to get total num of result w/o limit
        $this->db->limit($limit, $offset);
        $query = $this->db->get();

        $this->db->flush_cache();

        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new RefundRequestCollection(), $total);
        }

        return false;
    }

    public function insert(RefundRequest $refundRequest)
    {
        $this->db->set('id', $refundRequest->getId());
        $this->db->set('transactionID', $refundRequest->getTransactionID());
        $this->db->set('refundID', $refundRequest->getRefundID());
        $this->db->set('refund_remarks', $refundRequest->getRefundRemarks());
        $this->db->set('reference_no', $refundRequest->getReferenceNo());
        $this->db->set('payment_code', $refundRequest->getPaymentCode());
        $this->db->set('status_id', $refundRequest->getStatus()->getId());
        $this->db->set('paid_at', $refundRequest->getPaidAt()->getUnix());
        $this->db->set('payment_request_id', $refundRequest->getPaymentRequestId());
        $this->db->set('country_currency_code', $refundRequest->getCountryCurrencyCode());
        $this->db->set('amount', $refundRequest->getAmount());
        $this->db->set('approval_required', $refundRequest->getApprovalRequired());
        $this->db->set('approval_status', $refundRequest->getApprovalStatus());
        $this->db->set('approved_rejected_by', $refundRequest->getApprovedRejectedBy());
        $this->db->set('approved_rejected_at', $refundRequest->getApprovedRejectedAt()->getUnix());
        $this->db->set('approved_rejected_remarks', $refundRequest->getApprovedRejectedRemarks());
        $this->db->set('user_profile_id', $refundRequest->getUserProfileId());
        $this->db->set('created_at', IappsDateTime::now()->getUnix());
        $this->db->set('created_by', $refundRequest->getCreatedBy());

        if( $this->db->insert('iafb_remittance.refund_request') )
        {
            return true;
        }

        return false;
    }

    public function update(RefundRequest $refundRequest)
    {
        /*
        if( $refundRequest->getRefundRemarks() != NULL ) {
            $this->db->set('refund_remarks', $refundRequest->getRefundRemarks());
        }*/
        if( $refundRequest->getReferenceNo() != NULL ) {
            $this->db->set('reference_no', $refundRequest->getReferenceNo());
        }
        if( $refundRequest->getPaymentCode() != NULL ) {
            $this->db->set('payment_code', $refundRequest->getPaymentCode());
        }
        if( $refundRequest->getStatus() != NULL ) {
            $this->db->set('status_id', $refundRequest->getStatus()->getId());
        }
        if( $refundRequest->getPaidAt() != NULL ) {
            $this->db->set('paid_at', $refundRequest->getPaidAt()->getUnix());
        }
        if( $refundRequest->getPaymentRequestId() != NULL ) {
            $this->db->set('payment_request_id', $refundRequest->getPaymentRequestId());
        }
        if( $refundRequest->getApprovalRequired() != NULL ) {
            $this->db->set('approval_required', $refundRequest->getApprovalRequired());
        }

        if( $refundRequest->getApprovalStatus() != NULL ) {
            $this->db->set('approval_status', $refundRequest->getApprovalStatus());
        }
        if( $refundRequest->getApprovedRejectedBy() != NULL ) {
            $this->db->set('approved_rejected_by', $refundRequest->getApprovedRejectedBy());
        }
        if( $refundRequest->getApprovedRejectedAt() != NULL ) {
            $this->db->set('approved_rejected_at', $refundRequest->getApprovedRejectedAt()->getUnix());
        }
        if( $refundRequest->getApprovedRejectedRemarks() != NULL ) {
            $this->db->set('approved_rejected_remarks', $refundRequest->getApprovedRejectedRemarks());
        }

        $this->db->set('updated_at', IappsDateTime::now()->getUnix());
        $this->db->set('updated_by', $refundRequest->getUpdatedBy());

        $this->db->where('id', $refundRequest->getId());
        if( $this->db->update('iafb_remittance.refund_request') )
        {
            return true;
        }

        return false;
    }

}