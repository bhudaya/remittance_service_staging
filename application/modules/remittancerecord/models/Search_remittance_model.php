<?php

use Iapps\RemittanceService\SearchRemittanceRecord\ISearchRemittanceRecordDataMapper;
use Iapps\RemittanceService\RemittanceRecord\RemittanceRecord;
use Iapps\RemittanceService\RemittanceRecord\RemittanceRecordCollection;
use Iapps\Common\Core\IappsDateTime;
use Iapps\RemittanceService\RemittanceRecord\RemittanceStatus;

class Search_remittance_model extends Base_Model
                              implements ISearchRemittanceRecordDataMapper{
    
    public function map(\stdClass $data) {
        
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
            $entity->getRemittanceConfiguration()->setId($data->remittance_configuration_id);            

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
    
    protected function _select()
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
    }

    public function findById($id, $deleted = false) 
    {
        $this->_select();
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
    
    public function findByFilters(RemittanceRecordCollection $filters, $limit = NULL, $page = NULL) {
        
        if(!is_null($limit) AND ! is_null($page) )
            $offset = ($page - 1) * $limit;
        
        $this->db->start_cache(); //to cache active record query
        $this->_select();
        $this->db->from('iafb_remittance.remittance r');        
        $this->db->join('iafb_remittance.system_code sc', 'r.status_id = sc.id');
        $this->db->join('iafb_remittance.system_code_group scg', 'sc.system_code_group_id = scg.id');
        $this->db->where('scg.code', RemittanceStatus::getSystemGroupCode());
        $this->db->where('r.deleted_at', NULL);
        
        $this->db->group_start();
        foreach($filters AS $filter)
        {
            if($filter instanceof RemittanceRecord)
            {
                $this->db->or_group_start();

                if( $filter->getId() )
                    $this->db->where('r.id',  $filter->getId());

                if( $filter->getRemittanceID() )
                    $this->db->where('r.remittanceID', $filter->getRemittanceID ());
                
                if( $filter->getStatus()->getCode() )
                    $this->db->where('sc.code', $filter->getStatus()->getCode());
                
                if( $filter->getSender()->getId() )
                    $this->db->where('r.sender_user_profile_id', $filter->getSender()->getId());
                
                if( $filter->getRemittanceConfiguration()->getId() )
                    $this->db->where('r.remittance_configuration_id', $filter->getRemittanceConfiguration()->getId());
                
                if( !is_null($filter->getApprovalRequired()) )
                    $this->db->where('r.approval_required', $filter->getApprovalRequired());
                
                if( $filter->getApprovalStatus() )
                    $this->db->where('r.approval_status', $filter->getApprovalStatus());
                
                if( $filter->getRecipient()->getId() )
                    $this->db->where('r.recipient_id', $filter->getRecipient()->getId());
                
                if( !is_null($filter->getIsNFF()) )
                    $this->db->where('r.is_nff', $filter->getIsNFF());
                                
                $this->db->group_end();
            }
        }
        $this->db->group_end();
        
        if( !$this->getFromCreatedAt()->isNull() )
            $this->db->where('r.created_at >=', $this->getFromCreatedAt()->getUnix());
        
        if( !$this->getToCreatedAt()->isNull() )
            $this->db->where('r.created_at <=', $this->getToCreatedAt()->getUnix());

        $this->db->stop_cache();        
        $total = $this->db->count_all_results(); //to get total num of result w/o limit

        $this->db->limit($limit, $offset);                
        $this->db->order_by('r.created_at', 'desc');
        $query = $this->db->get();
        $this->db->flush_cache();
        if( $query->num_rows() > 0 )
        {
            return $this->mapCollection($query->result(), new RemittanceRecordCollection(), $total);
        }

        return false;
    }
}

