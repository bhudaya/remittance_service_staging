<?php

use Iapps\RemittanceService\RemittanceRecord\IRemittanceRecordDataMapper;
use Iapps\RemittanceService\RemittanceRecord\RemittanceRecord;
use Iapps\RemittanceService\RemittanceRecord\RemittanceRecordCollection;
use Iapps\Common\Core\IappsDateTime;
use Iapps\RemittanceService\RemittanceRecord\RemittanceStatus;
use Iapps\RemittanceService\ValueObject\EncryptedFieldFactory;

class Remittance_model extends Base_Model
                       implements IRemittanceRecordDataMapper{

    public function map(\stdClass $data)
    {
        $entity = new RemittanceRecord();

        if( isset($data->remittance_id) )
            $entity->setId($data->remittance_id);

        if( isset($data->in_transaction_id) )
            $entity->setInTransactionId($data->in_transaction_id);

        if( isset($data->out_transaction_id) )
            $entity->setOutTransactionId($data->out_transaction_id);

        if( isset($data->remittanceID) )
            $entity->setRemittanceID($data->remittanceID);

        if( isset($data->sender_user_profile_id) )
            $entity->setSenderUserProfileId($data->sender_user_profile_id);

        if( isset($data->recipient_id) )
            $entity->getRecipient()->setId($data->recipient_id);

        if( isset($data->remittance_configuration_id) )
            $entity->setRemittanceConfigurationId($data->remittance_configuration_id);

        if( isset($data->in_exchange_rate_id) )
            $entity->setInExchangeRateId($data->in_exchange_rate_id);

        if( isset($data->out_exchange_rate_id) )
            $entity->setOutExchangeRateId($data->out_exchange_rate_id);

        if( isset($data->display_rate) )
            $entity->setDisplayRate($data->display_rate);

        if( isset($data->from_amount) )
            $entity->setFromAmount($data->from_amount);

        if( isset($data->to_amount) )
            $entity->setToAmount($data->to_amount);

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
            $entity->setPayMentRequestId($data->payment_request_id);

        if( isset($data->collected_at) )
            $entity->setCollectedAt(IappsDateTime::fromUnix($data->collected_at));

        if( isset($data->collection_request_id) )
            $entity->setCollectionRequestId($data->collection_request_id);

        if( isset($data->collection_info) )
            $entity->getCollectionInfo(false)->setEncryptedValue($data->collection_info);

        if( isset($data->approval_required) )
            $entity->setApprovalRequired($data->approval_required);

        if( isset($data->approval_status) )
            $entity->setApprovalStatus($data->approval_status);

        if( isset($data->approved_rejected_at) )
            $entity->setApprovedRejectedAt(IappsDateTime::fromUnix($data->approved_rejected_at));

        if( isset($data->approved_rejected_by) )
            $entity->setApprovedRejectedBy($data->approved_rejected_by);

        if( isset($data->approve_reject_remark) )
            $entity->setApproveRejectRemark($data->approve_reject_remark);

        if( isset($data->is_face_to_face_trans) )
            $entity->setIsFaceToFaceTrans($data->is_face_to_face_trans);

        if( isset($data->is_face_to_face_recipient) )
            $entity->setIsFaceToFaceRecipient($data->is_face_to_face_recipient);

        if( isset($data->is_home_collection) )
            $entity->setIsHomeCollection($data->is_home_collection);

        if( isset($data->lat) )
            $entity->setLat($data->lat);

        if( isset($data->lon) )
            $entity->setLon($data->lon);

		if( isset($data->is_nff) )
			$entity->setIsNFF($data->is_nff);
		
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

    public function findRemittanceTransactionList($limit, $page, $start_time = null, $end_time = null, $prelim_check = null, $status = null)
    {
        if ($limit && $page) {
            $offset = ($page - 1) * $limit;
        }

        $this->db->start_cache();
        $this->db->select('r.id as remittance_id,
                        r.in_transaction_id,
                        r.out_transaction_id,
                        r.remittanceID,
                        r.sender_user_profile_id,
                        r.recipient_id,
                        r.remittance_configuration_id,
                        r.in_exchange_rate_id,
                        r.out_exchange_rate_id,
                        r.display_rate,
                        r.from_amount,
                        r.to_amount,
                        r.status_id,
                        r.paid_at,
                        r.payment_request_id,
                        r.collected_at,
                        r.collection_request_id,
                        r.collection_info,
                        r.approval_required,
                        r.approval_status,
                        r.approved_rejected_at,
                        r.approved_rejected_by,
                        r.approve_reject_remark,
                        r.is_face_to_face_trans,
                        r.is_face_to_face_recipient,
                        r.is_home_collection,
                        r.lat,
                        r.lon,
                        r.is_nff,
                        sc.code as status_code,
                        sc.display_name as status_name,
                        scg.id as status_group_id,
                        scg.code as status_group_code,
                        scg.display_name as status_group_name,
                        r.created_at,
                        r.created_by,
                        r.updated_at,
                        r.updated_by,
                        r.deleted_at,
                        r.deleted_by');
        $this->db->from('iafb_remittance.remittance r');
        $this->db->join('iafb_remittance.system_code sc', 'r.status_id = sc.id');
        $this->db->join('iafb_remittance.system_code_group scg', 'sc.system_code_group_id = scg.id');
        $this->db->where('scg.code', RemittanceStatus::getSystemGroupCode());
        $this->db->where('r.deleted_at', NULL);
        $this->db->where('scg.deleted_at', NULL);

        if ( $start_time != null ) {
            $this->db->where('r.created_at>='.$start_time);
        }

        if ($end_time != null ) {
            $this->db->where('r.created_at<='.$end_time);
        }

        if ( $prelim_check != null || $prelim_check === 0 ) {
            $this->db->where('r.approval_required',$prelim_check);
        }

        if ( $status != null && $status != 'na' ) {
            $this->db->where('r.approval_status', $status);
        }

        $this->db->order_by("r.created_at", "desc");

        $this ->db->stop_cache();

        $total = $this->db->count_all_results();
        if ($limit && $page) {
            $this->db->limit($limit,$offset);
        }

        $query = $this ->db->get();
        $this->db->flush_cache();

        if( $query -> num_rows() > 0 )
        {
            return $this->mapCollection($query->result(), new RemittanceRecordCollection(), $total);
        }
        return false;
    }

    public function findById($id, $deleted = false)
    {
        $this->db->select('r.id as remittance_id,
                        r.in_transaction_id,
                        r.out_transaction_id,
                        r.remittanceID,
                        r.sender_user_profile_id,
                        r.recipient_id,
                        r.remittance_configuration_id,
                        r.in_exchange_rate_id,
                        r.out_exchange_rate_id,
                        r.display_rate,
                        r.from_amount,
                        r.to_amount,
                        r.status_id,
                        r.paid_at,
                        r.payment_request_id,
                        r.collected_at,
                        r.collection_request_id,
                        r.collection_info,
                        r.approval_required,
                        r.approval_status,
                        r.approved_rejected_at,
                        r.approved_rejected_by,
                        r.approve_reject_remark,
                        r.is_face_to_face_trans,
                        r.is_face_to_face_recipient,
                        r.is_home_collection,
                        r.lat,
                        r.lon,
                        r.is_nff,
                        sc.code as status_code,
                        sc.display_name as status_name,
                        scg.id as status_group_id,
                        scg.code as status_group_code,
                        scg.display_name as status_group_name,
                        r.created_at,
                        r.created_by,
                        r.updated_at,
                        r.updated_by,
                        r.deleted_at,
                        r.deleted_by');
        $this->db->from('iafb_remittance.remittance r');
        $this->db->join('iafb_remittance.system_code sc', 'r.status_id = sc.id');
        $this->db->join('iafb_remittance.system_code_group scg', 'sc.system_code_group_id = scg.id');
        $this->db->where('scg.code', RemittanceStatus::getSystemGroupCode());
        if( !$deleted )
            $this->db->where('r.deleted_at', NULL);
        $this->db->where('r.id', $id);

        $query = $this->db->get();
        if( $query->num_rows() > 0 )
        {
            return $this->map($query->row());
        }

        return false;
    }

    public function findByRemittanceID($remittanceID)
    {
        $this->db->select('r.id as remittance_id,
                        r.in_transaction_id,
                        r.out_transaction_id,
                        r.remittanceID,
                        r.sender_user_profile_id,
                        r.recipient_id,
                        r.remittance_configuration_id,
                        r.in_exchange_rate_id,
                        r.out_exchange_rate_id,
                        r.display_rate,
                        r.from_amount,
                        r.to_amount,
                        r.status_id,
                        r.paid_at,
                        r.payment_request_id,
                        r.collected_at,
                        r.collection_request_id,
                        r.collection_info,
                        r.approval_required,
                        r.approval_status,
                        r.approved_rejected_at,
                        r.approved_rejected_by,
                        r.approve_reject_remark,
                        r.is_face_to_face_trans,
                        r.is_face_to_face_recipient,
                        r.is_home_collection,
                        r.lat,
                        r.lon,
                        r.is_nff,
                        sc.code as status_code,
                        sc.display_name as status_name,
                        scg.id as status_group_id,
                        scg.code as status_group_code,
                        scg.display_name as status_group_name,
                        r.created_at,
                        r.created_by,
                        r.updated_at,
                        r.updated_by,
                        r.deleted_at,
                        r.deleted_by');
        $this->db->from('iafb_remittance.remittance r');
        $this->db->join('iafb_remittance.system_code sc', 'r.status_id = sc.id');
        $this->db->join('iafb_remittance.system_code_group scg', 'sc.system_code_group_id = scg.id');
        $this->db->where('scg.code', RemittanceStatus::getSystemGroupCode());
        $this->db->where('r.remittanceID', $remittanceID);

        $query = $this->db->get();
        if( $query->num_rows() > 0 )
        {
            return $this->map($query->row());
        }

        return false;
    }

    public function findByInTransactionId($inTransaction_id)
    {
        $this->db->select('r.id as remittance_id,
                        r.in_transaction_id,
                        r.out_transaction_id,
                        r.remittanceID,
                        r.sender_user_profile_id,
                        r.recipient_id,
                        r.remittance_configuration_id,
                        r.in_exchange_rate_id,
                        r.out_exchange_rate_id,
                        r.display_rate,
                        r.from_amount,
                        r.to_amount,
                        r.status_id,
                        r.paid_at,
                        r.payment_request_id,
                        r.collected_at,
                        r.collection_request_id,
                        r.collection_info,
                        r.approval_required,
                        r.approval_status,
                        r.approved_rejected_at,
                        r.approved_rejected_by,
                        r.approve_reject_remark,
                        r.is_face_to_face_trans,
                        r.is_face_to_face_recipient,
                        r.is_home_collection,
                        r.lat,
                        r.lon,
                        r.is_nff,
                        sc.code as status_code,
                        sc.display_name as status_name,
                        scg.id as status_group_id,
                        scg.code as status_group_code,
                        scg.display_name as status_group_name,
                        r.created_at,
                        r.created_by,
                        r.updated_at,
                        r.updated_by,
                        r.deleted_at,
                        r.deleted_by');
        $this->db->from('iafb_remittance.remittance r');
        $this->db->join('iafb_remittance.system_code sc', 'r.status_id = sc.id');
        $this->db->join('iafb_remittance.system_code_group scg', 'sc.system_code_group_id = scg.id');
        $this->db->where('scg.code', RemittanceStatus::getSystemGroupCode());
        $this->db->where('r.in_transaction_id', $inTransaction_id);

        $query = $this->db->get();
        if( $query->num_rows() > 0 )
        {
            return $this->map($query->row());
        }

        return false;
    }

    public function findByOutTransactionId($outTransaction_id)
    {
        $this->db->select('r.id as remittance_id,
                        r.in_transaction_id,
                        r.out_transaction_id,
                        r.remittanceID,
                        r.sender_user_profile_id,
                        r.recipient_id,
                        r.remittance_configuration_id,
                        r.in_exchange_rate_id,
                        r.out_exchange_rate_id,
                        r.display_rate,
                        r.from_amount,
                        r.to_amount,
                        r.status_id,
                        r.paid_at,
                        r.payment_request_id,
                        r.collected_at,
                        r.collection_request_id,
                        r.collection_info,
                        r.approval_required,
                        r.approval_status,
                        r.approved_rejected_at,
                        r.approved_rejected_by,
                        r.approve_reject_remark,
                        r.is_face_to_face_trans,
                        r.is_face_to_face_recipient,
                        r.is_home_collection,
                        r.lat,
                        r.lon,
                        r.is_nff,
                        sc.code as status_code,
                        sc.display_name as status_name,
                        scg.id as status_group_id,
                        scg.code as status_group_code,
                        scg.display_name as status_group_name,
                        r.created_at,
                        r.created_by,
                        r.updated_at,
                        r.updated_by,
                        r.deleted_at,
                        r.deleted_by');
        $this->db->from('iafb_remittance.remittance r');
        $this->db->join('iafb_remittance.system_code sc', 'r.status_id = sc.id');
        $this->db->join('iafb_remittance.system_code_group scg', 'sc.system_code_group_id = scg.id');
        $this->db->where('scg.code', RemittanceStatus::getSystemGroupCode());
        $this->db->where('r.out_transaction_id', $outTransaction_id);

        $query = $this->db->get();
        if( $query->num_rows() > 0 )
        {
            return $this->map($query->row());
        }

        return false;
    }

    public function reportFindByParam(RemittanceRecord $record, $start_time = NULL, $end_time = NULL)
    {
        $this->db->start_cache(); //to cache active record query
        $this->db->select('r.id as remittance_id,
                        r.in_transaction_id,
                        r.out_transaction_id,
                        r.remittanceID,
                        r.sender_user_profile_id,
                        r.recipient_id,
                        r.remittance_configuration_id,
                        r.in_exchange_rate_id,
                        r.out_exchange_rate_id,
                        r.display_rate,
                        r.from_amount,
                        r.to_amount,
                        r.status_id,
                        r.paid_at,
                        r.payment_request_id,
                        r.collected_at,
                        r.collection_request_id,
                        r.collection_info,
                        r.approval_required,
                        r.approval_status,
                        r.approved_rejected_at,
                        r.approved_rejected_by,
                        r.approve_reject_remark,
                        r.is_face_to_face_trans,
                        r.is_face_to_face_recipient,
                        r.is_home_collection,
                        r.lat,
                        r.lon,
                        r.is_nff,
                        sc.code as status_code,
                        sc.display_name as status_name,
                        scg.id as status_group_id,
                        scg.code as status_group_code,
                        scg.display_name as status_group_name,
                        r.created_at,
                        r.created_by,
                        r.updated_at,
                        r.updated_by,
                        r.deleted_at,
                        r.deleted_by');
        $this->db->from('iafb_remittance.remittance r');
        $this->db->join('iafb_remittance.system_code sc', 'r.status_id = sc.id');
        $this->db->join('iafb_remittance.system_code_group scg', 'sc.system_code_group_id = scg.id');
        $this->db->where('scg.code', RemittanceStatus::getSystemGroupCode());
        $this->db->where('r.deleted_at', NULL);
        $this->db->where('sc.deleted_at', NULL);
        $this->db->where('scg.deleted_at', NULL);
        if($record->getSenderUserProfileId()) {
            $this->db->where('r.sender_user_profile_id', $record->getSenderUserProfileId());
        }
        if($record->getRecipient()) {
            if($record->getRecipient()->getId()) {
                $this->db->where('r.recipient_id', $record->getRecipient()->getId());
            }
        }
        if ($record->getRemittanceConfigurationId()) {
            $this->db->where('r.remittance_configuration_id', $record->getRemittanceConfigurationId());
        }

        if ($start_time) {
            $this->db->where('r.created_at>='.$start_time);
        }

        if ($end_time) {
            $this->db->where('r.created_at<='.$end_time);
        }

        $this->db->order_by("r.created_at", "desc");

        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit

        $query = $this->db->get();
        $this->db->flush_cache();

        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new RemittanceRecordCollection(), $total);
        }

        return false;
    }

    public function findByParam(RemittanceRecord $record, $limit, $page, $recipient_id_arr = NULL, $remittance_configuration_ids = NULL, $start_time = null, $end_time = null, $prelim_check = null, $status = null)
    {
        if ($limit && $page) {
            $offset = ($page - 1) * $limit;
        }

        $this->db->start_cache(); //to cache active record query
        $this->db->select('r.id as remittance_id,
                        r.in_transaction_id,
                        r.out_transaction_id,
                        r.remittanceID,
                        r.sender_user_profile_id,
                        r.recipient_id,
                        r.remittance_configuration_id,
                        r.in_exchange_rate_id,
                        r.out_exchange_rate_id,
                        r.display_rate,
                        r.from_amount,
                        r.to_amount,
                        r.status_id,
                        r.paid_at,
                        r.payment_request_id,
                        r.collected_at,
                        r.collection_request_id,
                        r.collection_info,
                        r.approval_required,
                        r.approval_status,
                        r.approved_rejected_at,
                        r.approved_rejected_by,
                        r.approve_reject_remark,
                        r.is_face_to_face_trans,
                        r.is_face_to_face_recipient,
                        r.is_home_collection,
                        r.lat,
                        r.lon,
                        r.is_nff,
                        sc.code as status_code,
                        sc.display_name as status_name,
                        scg.id as status_group_id,
                        scg.code as status_group_code,
                        scg.display_name as status_group_name,
                        r.created_at,
                        r.created_by,
                        r.updated_at,
                        r.updated_by,
                        r.deleted_at,
                        r.deleted_by');
        $this->db->from('iafb_remittance.remittance r');
        $this->db->join('iafb_remittance.system_code sc', 'r.status_id = sc.id');
        $this->db->join('iafb_remittance.system_code_group scg', 'sc.system_code_group_id = scg.id');
        $this->db->where('scg.code', RemittanceStatus::getSystemGroupCode());
        $this->db->where('r.deleted_at', NULL);
        $this->db->where('sc.deleted_at', NULL);
        $this->db->where('scg.deleted_at', NULL);

        $sender_id_included = false;
        if($recipient_id_arr != NULL)
        {
            if(is_array($recipient_id_arr))
            {
                if(count($recipient_id_arr) > 0)
                {
                    $sender_id_included = true;
                    $this->db->group_start();

                    $this->db->where_in('r.recipient_id', $recipient_id_arr);

                    if($record->getSenderUserProfileId()) {
                        $this->db->or_where('r.sender_user_profile_id', $record->getSenderUserProfileId());
                    }

                    $this->db->group_end();

                }
            }
        }

        if(!$sender_id_included)
        {
            if($record->getSenderUserProfileId()) {
                $this->db->where('r.sender_user_profile_id', $record->getSenderUserProfileId());
            }
        }

        if( $record->getInTransaction()->getId() )
            $this->db->where('r.in_transaction_id', $record->getInTransaction()->getId());

        if( $record->getOutTransaction()->getId() )
            $this->db->where('r.out_transaction_id', $record->getOutTransaction()->getId());

        if( $remittance_configuration_ids != NULL )
            $this->db->where_in('r.remittance_configuration_id', $remittance_configuration_ids);

        if ( $start_time != null ) {
            $this->db->where('r.created_at>='.$start_time);
        }

        if ($end_time != null ) {
            $this->db->where('r.created_at<='.$end_time);
        }

        if ( $prelim_check != null || $prelim_check === 0 ) {
            $this->db->where('r.approval_required',$prelim_check);
        }

        if ( $status != null && $status != 'na' ) {
            $this->db->where('r.approval_status', $status);
        }

        if ($record->getStatus()->getId()) {
            $this->db->where('r.status_id',$record->getStatus()->getId());
        }

        if ($record->getRemittanceID()) {
            $this->db->where('r.remittanceID',$record->getRemittanceID());
        }

        $this->db->order_by("r.created_at", "desc");

        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit

        if ($limit && $page) {
            $this->db->limit($limit, $offset);
        }
        $query = $this->db->get();
        $this->db->flush_cache();

        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new RemittanceRecordCollection(), $total);
        }

        return false;
    }
    

    public function findByTrxIdsAndParam(RemittanceRecord $record, $limit, $page, $trxIds = NULL, $recipient_id_arr = NULL, $remittance_configuration_ids = NULL, $start_time = null, $end_time = null, $prelim_check = null, $status = null)
    {
        if ($limit && $page) {
            $offset = ($page - 1) * $limit;
        }

        $this->db->start_cache(); //to cache active record query
        $this->db->select('r.id as remittance_id,
                        r.in_transaction_id,
                        r.out_transaction_id,
                        r.remittanceID,
                        r.sender_user_profile_id,
                        r.recipient_id,
                        r.remittance_configuration_id,
                        r.in_exchange_rate_id,
                        r.out_exchange_rate_id,
                        r.display_rate,
                        r.from_amount,
                        r.to_amount,
                        r.status_id,
                        r.paid_at,
                        r.payment_request_id,
                        r.collected_at,
                        r.collection_request_id,
                        r.collection_info,
                        r.approval_required,
                        r.approval_status,
                        r.approved_rejected_at,
                        r.approved_rejected_by,
                        r.approve_reject_remark,
                        r.is_face_to_face_trans,
                        r.is_face_to_face_recipient,
                        r.is_home_collection,
                        r.lat,
                        r.lon,
                        r.is_nff,
                        sc.code as status_code,
                        sc.display_name as status_name,
                        scg.id as status_group_id,
                        scg.code as status_group_code,
                        scg.display_name as status_group_name,
                        r.created_at,
                        r.created_by,
                        r.updated_at,
                        r.updated_by,
                        r.deleted_at,
                        r.deleted_by');
        $this->db->from('iafb_remittance.remittance r');
        $this->db->join('iafb_remittance.system_code sc', 'r.status_id = sc.id');
        $this->db->join('iafb_remittance.system_code_group scg', 'sc.system_code_group_id = scg.id');
        $this->db->where('scg.code', RemittanceStatus::getSystemGroupCode());
        $this->db->where('r.deleted_at', NULL);
        $this->db->where('sc.deleted_at', NULL);
        $this->db->where('scg.deleted_at', NULL);

        $sender_id_included = false;
        if($recipient_id_arr != NULL)
        {
            if(is_array($recipient_id_arr))
            {
                if(count($recipient_id_arr) > 0)
                {
                    $sender_id_included = true;
                    $this->db->group_start();

                    $this->db->where_in('r.recipient_id', $recipient_id_arr);

                    if($record->getSenderUserProfileId()) {
                        $this->db->or_where('r.sender_user_profile_id', $record->getSenderUserProfileId());
                    }

                    $this->db->group_end();

                }
            }
        }

        if(!$sender_id_included)
        {
            if($record->getSenderUserProfileId()) {
                $this->db->where('r.sender_user_profile_id', $record->getSenderUserProfileId());
            }
        }

        if( $record->getInTransaction()->getId() )
            $this->db->where('r.in_transaction_id', $record->getInTransaction()->getId());

        if( $record->getOutTransaction()->getId() )
            $this->db->where('r.out_transaction_id', $record->getOutTransaction()->getId());

        if( $remittance_configuration_ids != NULL )
            $this->db->where_in('r.remittance_configuration_id', $remittance_configuration_ids);

        if( $trxIds != NULL )
            $this->db->where_in('r.out_transaction_id', $trxIds);
        
        if ( $start_time != null ) {
            $this->db->where('r.created_at>='.$start_time);
        }

        if ($end_time != null ) {
            $this->db->where('r.created_at<='.$end_time);
        }

        if ( $prelim_check != null || $prelim_check === 0 ) {
            $this->db->where('r.approval_required',$prelim_check);
        }

        if ( $status != null && $status != 'na' ) {
            $this->db->where('r.approval_status', $status);
        }

        $this->db->order_by("r.created_at", "desc");

        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit

        if ($limit && $page) {
            $this->db->limit($limit, $offset);
        }
        $query = $this->db->get();
        $this->db->flush_cache();

        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new RemittanceRecordCollection(), $total);
        }

        return false;
    }
    public function findByTransactionIDArray(array $transaction_arr)
    {
        $this->db->start_cache(); //to cache active record query
        $this->db->select('r.id as remittance_id,
                        r.in_transaction_id,
                        r.out_transaction_id,
                        r.remittanceID,
                        r.sender_user_profile_id,
                        r.recipient_id,
                        r.remittance_configuration_id,
                        r.in_exchange_rate_id,
                        r.out_exchange_rate_id,
                        r.display_rate,
                        r.from_amount,
                        r.to_amount,
                        r.status_id,
                        r.paid_at,
                        r.payment_request_id,
                        r.collected_at,
                        r.collection_request_id,
                        r.collection_info,
                        r.approval_required,
                        r.approval_status,
                        r.approved_rejected_at,
                        r.approved_rejected_by,
                        r.approve_reject_remark,
                        r.is_face_to_face_trans,
                        r.is_face_to_face_recipient,
                        r.is_home_collection,
                        r.lat,
                        r.lon,
                        r.is_nff,
                        sc.code as status_code,
                        sc.display_name as status_name,
                        scg.id as status_group_id,
                        scg.code as status_group_code,
                        scg.display_name as status_group_name,
                        r.created_at,
                        r.created_by,
                        r.updated_at,
                        r.updated_by,
                        r.deleted_at,
                        r.deleted_by');
        $this->db->from('iafb_remittance.remittance r');
        $this->db->join('iafb_remittance.system_code sc', 'r.status_id = sc.id');
        $this->db->join('iafb_remittance.system_code_group scg', 'sc.system_code_group_id = scg.id');
        $this->db->where('scg.code', RemittanceStatus::getSystemGroupCode());

        if($transaction_arr){
            $this->db->where_in('r.in_transaction_id', $transaction_arr);
            $this->db->or_where_in('r.out_transaction_id', $transaction_arr);
        }

        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit

        $query = $this->db->get();
        $this->db->flush_cache();

        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new RemittanceRecordCollection(), $total);
        }


        return false;
    }
    public function insert(RemittanceRecord $record)
    {
        $created_at = IappsDateTime::now();

        $this->db->set('id', $record->getId());
        $this->db->set('in_transaction_id', $record->getInTransaction()->getId());
        $this->db->set('out_transaction_id', $record->getOutTransaction()->getId());
        $this->db->set('remittanceID', $record->getRemittanceID());
        $this->db->set('sender_user_profile_id', $record->getSenderUserProfileId());
        $this->db->set('recipient_id', $record->getRecipient()->getId());
        $this->db->set('remittance_configuration_id', $record->getRemittanceConfigurationId());
        $this->db->set('in_exchange_rate_id', $record->getInExchangeRateId());
        $this->db->set('out_exchange_rate_id', $record->getOutExchangeRateId());
        $this->db->set('display_rate', $record->getDisplayRate());
        $this->db->set('from_amount', $record->getFromAmount());
        $this->db->set('to_amount', $record->getToAmount());
        $this->db->set('status_id', $record->getStatus()->getId());
        $this->db->set('paid_at', $record->getPaidAt()->getUnix());
        $this->db->set('payment_request_id', $record->getPayMentRequestId());
        $this->db->set('collected_at', $record->getCollectedAt()->getUnix());
        $this->db->set('collection_request_id', $record->getCollectionRequestId());
        $this->db->set('collection_info', $record->getCollectionInfo(false) ? $record->getCollectionInfo(false)->getEncodedValue() : NULL);
        $this->db->set('approval_required', $record->getApprovalRequired());
        $this->db->set('approval_status', $record->getApprovalStatus());
        $this->db->set('approved_rejected_at', $record->getApprovedRejectedAt()->getUnix());
        $this->db->set('approved_rejected_by', $record->getApprovedRejectedBy());
        $this->db->set('approve_reject_remark', $record->getApproveRejectRemark());
        $this->db->set('is_face_to_face_trans', $record->getIsFaceToFaceTrans());
        $this->db->set('is_face_to_face_recipient', $record->getIsFaceToFaceRecipient());
        $this->db->set('is_home_collection', $record->getIsHomeCollection());
        $this->db->set('lat', $record->getLat());
        $this->db->set('lon', $record->getLon());
		$this->db->set('is_nff', $record->getIsNFF());
        $this->db->set('created_at', $created_at->getUnix());
        $this->db->set('created_by', $record->getCreatedBy());

        if( $this->db->insert('iafb_remittance.remittance') )
        {
            $record->setCreatedAt($created_at);
            return $record;
        }

        return false;
    }

    public function updateStatus(RemittanceRecord $record)
    {
        $this->db->set('status_id', $record->getStatus()->getId());
        $this->db->set('paid_at', $record->getPaidAt()->getUnix());
        $this->db->set('redeemed_at', $record->getRedeemedAt()->getUnix());
        $this->db->set('updated_at', IappsDateTime::now()->getUnix());
        $this->db->set('updated_by', $record->getUpdatedBy());
        $this->db->where('id', $record->getId());

        if( $this->db->update('iafb_remittance.remittance') )
        {
            return true;
        }

        return false;
    }

    public function update(RemittanceRecord $record)
    {
        $updated_at = IappsDateTime::now();

        if( $record->getStatus()->getId() )
            $this->db->set('status_id', $record->getStatus()->getId());

        if( !$record->getPaidAt()->isNull() )
            $this->db->set('paid_at', $record->getPaidAt()->getUnix());

        if( $record->getPayMentRequestId() )
            $this->db->set('payment_request_id', $record->getPayMentRequestId());

        if( !$record->getCollectedAt()->isNull() )
            $this->db->set('collected_at', $record->getCollectedAt()->getUnix());

        if( $record->getCollectionRequestId() )
            $this->db->set('collection_request_id', $record->getCollectionRequestId());

        if( $record->getCollectionInfo(false) )
            $this->db->set('collection_info', $record->getCollectionInfo(false)->getEncodedValue());

        if( !is_null($record->getApprovalRequired()) )
            $this->db->set('approval_required', $record->getApprovalRequired());

        if( $record->getApprovalStatus() )
            $this->db->set('approval_status', $record->getApprovalStatus());

        if( !$record->getApprovedRejectedAt()->isNull() )
            $this->db->set('approved_rejected_at', $record->getApprovedRejectedAt()->getUnix());

        if( $record->getApprovedRejectedBy() )
            $this->db->set('approved_rejected_by', $record->getApprovedRejectedBy());

        if( $record->getApproveRejectRemark() )
            $this->db->set('approve_reject_remark', $record->getApproveRejectRemark());

        if( $record->getIsFaceToFaceTrans() )
            $this->db->set('is_face_to_face_trans', $record->getIsFaceToFaceTrans());

        if( $record->getIsFaceToFaceRecipient() )
            $this->db->set('is_face_to_face_recipient', $record->getIsFaceToFaceRecipient());

        if( $record->getIsHomeCollection() )
            $this->db->set('is_home_collection', $record->getIsHomeCollection());

        if( $record->getLat() )
            $this->db->set('lat', $record->getLat());

        if( $record->getLon() )
            $this->db->set('lon', $record->getLon());
		
        if( !is_null($record->getIsNFF()) )
            $this->db->set('is_nff', $record->getIsNFF());

        $this->db->set('updated_at', $updated_at->getUnix());
        $this->db->set('updated_by', $record->getUpdatedBy());

        $this->db->where('id', $record->getId());

        if( $this->db->update('iafb_remittance.remittance') )
        {
            $record->setUpdatedAt($updated_at);
            return $record;
        }

        return false;
    }

    public function updateRequestCollectionId(RemittanceRecord $remittanceRecord)
    {
        $updated_at = IappsDateTime::now();

        $this->db->set('collection_request_id', $remittanceRecord->getCollectionRequestId());
        $this->db->set('updated_by', $remittanceRecord->getUpdatedBy());
        $this->db->set('updated_at', $updated_at->getUnix());

        $this->db->where('id', $remittanceRecord->getId());

        if( $this->db->update('iafb_remittance.remittance') )
        {
            $remittanceRecord->setUpdatedAt($updated_at);
            return $remittanceRecord;
        }

        return false;
    }

    public function findSenderRemittanceInfo(RemittanceRecord $record, $status, $start_time, $end_time)
    {
        
        $this->db->start_cache(); //to cache active record query
        $this->db->select('r.id as remittance_id,
                        r.in_transaction_id,
                        r.out_transaction_id,
                        r.remittanceID,
                        r.sender_user_profile_id,
                        r.recipient_id,
                        r.remittance_configuration_id,
                        r.in_exchange_rate_id,
                        r.out_exchange_rate_id,
                        r.display_rate,
                        r.from_amount,
                        r.to_amount,
                        r.status_id,
                        r.paid_at,
                        r.payment_request_id,
                        r.collected_at,
                        r.collection_request_id,
                        r.collection_info,
                        r.approval_required,
                        r.approval_status,
                        r.approved_rejected_at,
                        r.approved_rejected_by,
                        r.approve_reject_remark,
                        r.is_face_to_face_trans,
                        r.is_face_to_face_recipient,
                        r.is_home_collection,
                        r.lat,
                        r.lon,
                        r.is_nff,
                        sc.code as status_code,
                        sc.display_name as status_name,
                        scg.id as status_group_id,
                        scg.code as status_group_code,
                        scg.display_name as status_group_name,
                        r.created_at,
                        r.created_by,
                        r.updated_at,
                        r.updated_by,
                        r.deleted_at,
                        r.deleted_by');
        $this->db->from('iafb_remittance.remittance r');
        $this->db->join('iafb_remittance.system_code sc', 'r.status_id = sc.id');
        $this->db->join('iafb_remittance.system_code_group scg', 'sc.system_code_group_id = scg.id');
        $this->db->where('scg.code', RemittanceStatus::getSystemGroupCode());
        $this->db->where('r.deleted_at', NULL);
        $this->db->where('sc.deleted_at', NULL);
        $this->db->where('scg.deleted_at', NULL);

  
        if($record->getSenderUserProfileId()) {
            $this->db->where('r.sender_user_profile_id', $record->getSenderUserProfileId());
        }

        if ( $start_time != null ) {
            $this->db->where('r.created_at>='.$start_time);
        }

        if ($end_time != null ) {
            $this->db->where('r.created_at<='.$end_time);
        }

        if ($record->getStatus()->getId()) {
            $this->db->where('r.status_id',$record->getStatus()->getId());
        }
        
        if ($status != null) {
            $this->db->where_in('sc.code',$status);
        }

        $this->db->order_by("r.created_at", "desc");

        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit

        $query = $this->db->get();
        $this->db->flush_cache();

        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new RemittanceRecordCollection(), $total);
        }

        return false;
    }
     
    public function findRecipintRemittanceInfo($status, $recipient_id_arr, $start_time, $end_time)
    {
        
        $this->db->start_cache(); //to cache active record query
        $this->db->select('r.id as remittance_id,
                        r.in_transaction_id,
                        r.out_transaction_id,
                        r.remittanceID,
                        r.sender_user_profile_id,
                        r.recipient_id,
                        r.remittance_configuration_id,
                        r.in_exchange_rate_id,
                        r.out_exchange_rate_id,
                        r.display_rate,
                        r.from_amount,
                        r.to_amount,
                        r.status_id,
                        r.paid_at,
                        r.payment_request_id,
                        r.collected_at,
                        r.collection_request_id,
                        r.collection_info,
                        r.approval_required,
                        r.approval_status,
                        r.approved_rejected_at,
                        r.approved_rejected_by,
                        r.approve_reject_remark,
                        sc.code as status_code,
                        sc.display_name as status_name,
                        scg.id as status_group_id,
                        scg.code as status_group_code,
                        scg.display_name as status_group_name,
                        r.created_at,
                        r.created_by,
                        r.updated_at,
                        r.updated_by,
                        r.deleted_at,
                        r.deleted_by');
        $this->db->from('iafb_remittance.remittance r');
        $this->db->join('iafb_remittance.system_code sc', 'r.status_id = sc.id');
        $this->db->join('iafb_remittance.system_code_group scg', 'sc.system_code_group_id = scg.id');
        $this->db->where('scg.code', RemittanceStatus::getSystemGroupCode());
        $this->db->where('r.deleted_at', NULL);
        $this->db->where('sc.deleted_at', NULL);
        $this->db->where('scg.deleted_at', NULL);

        if($recipient_id_arr != NULL)
        {
            if(is_array($recipient_id_arr))
            {
                if(count($recipient_id_arr) > 0)
                {
                    $this->db->where_in('r.recipient_id', $recipient_id_arr);
                }
            }
        }

        if ( $start_time != null ) {
            $this->db->where('r.created_at>='.$start_time);
        }

        if ($end_time != null ) {
            $this->db->where('r.created_at<='.$end_time);
        }

        if ($status != null) {
            $this->db->where_in('sc.code',$status);
        }

        $this->db->order_by("r.created_at", "desc");

        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit

        $query = $this->db->get();
        $this->db->flush_cache();

        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new RemittanceRecordCollection(), $total);
        }

        return false;
    }
}