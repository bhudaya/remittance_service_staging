<?php

use Iapps\Common\DepositTracker\DepositTrackerRequest;
use Iapps\Common\DepositTracker\DepositTracker;
use Iapps\Common\DepositTracker\IDepositTrackerRequestDataMapper;
use Iapps\Common\DepositTracker\DepositTrackerRequestCollection;
use Iapps\Common\Core\IappsDateTime;


class Deposit_tracker_request_model extends Base_Model implements IDepositTrackerRequestDataMapper
{


    public function map(stdClass $data)
    {

        $entity = new DepositTrackerRequest();

        if(isset($data->id)){
            $entity->setId($data->id);
        }
        
        if (isset($data->deposit_tracker_id)) {
            $entity->setDepositTrackerId($data->deposit_tracker_id);
        }

        if (isset($data->type)) {
            $entity->setType($data->type);
        }

        if (isset($data->status)) {
            $entity->setStatus($data->status);
        }

        if (isset($data->purpose) ) {
            $entity->setPurpose($data->purpose);
        }

        if (isset($data->bank)) {
            $entity->setBank($data->bank);
        }

        if (isset($data->trans_reference_num)){
            $entity->setTransReferenceNum($data->trans_reference_num);
        }

        if (isset($data->trans_proof_url)){
            $entity->setTransProofUrl($data->trans_proof_url);
        }

        if (isset($data->photoname)){
            $entity->setPhotoName($data->photoname);
        }
        
        if (isset($data->s3photoname)){
            $entity->setS3PhotoName($data->s3photoname);
        }
        
        if (isset($data->transfer_date)){
            $entity->setTransDate(IappsDateTime::fromUnix($data->transfer_date));
        }
        
        if (isset($data->amount)){
            $entity->setAmount($data->amount);
        }

        if (isset($data->processed_at)){
            $entity->setProcessedAt(IappsDateTime::fromUnix($data->processed_at));
        }

        if (isset($data->processed_by)){
            $entity->setProcessedBy($data->processed_by);
        }

        if (isset($data->approve_rejected_at)){
            $entity->setApprovedRejectedAt(IappsDateTime::fromUnix($data->approve_rejected_at));
        }

        if (isset($data->approve_rejected_by)){
            $entity->setApprovedRejectedBy($data->approve_rejected_by);
        }


        if (isset($data->approve_rejected_remarks)){
            $entity->setApprovedRejectedRemarks($data->approve_rejected_remarks);
        }

        if ( isset($data->created_at) ) {
            $entity->setCreatedAt(IappsDateTime::fromUnix($data->created_at));
        }

        if ( isset($data->created_by) ) {
            $entity->setCreatedBy($data->created_by);
        }

        if ( isset($data->updated_at) ) {
            $entity->setUpdatedAt(IappsDateTime::fromUnix($data->updated_at));
        }

        if ( isset($data->updated_by) ) {
            $entity->setUpdatedBy($data->updated_by);
        }

        if ( isset($data->deleted_at) ) {
            $entity->setDeletedAt(IappsDateTime::fromUnix($data->deleted_at));
        }

        if ( isset($data->deleted_by) ) {
            $entity->setDeletedBy($data->deleted_by);
        }

        return $entity;
    }

    protected function _prepareSelect()
    {
        $this->db->select(' id,
                            deposit_tracker_id,
                            type,
                            status,
                            purpose,
                            bank,
                            trans_reference_num,
                            trans_proof_url,
                            amount,
                            processed_at,
                            processed_by,
                            approve_rejected_at,
                            approve_rejected_by,
                            approve_rejected_remarks,
                            created_at,
                            created_by,
                            updated_at,
                            updated_by,
                            deleted_at,
                            deleted_by');
        $this->db->from('iafb_remittance.deposit_request');
    }


    public function findById($id, $deleted = false)
    {
        $this->db->select('*');
        $this->db->from('iafb_remittance.deposit_request');
        if( !$deleted )
            $this->db->where('deleted_at', NULL);
        $this->db->where('id', $id);

        $query = $this->db->get();
        if ( $query->num_rows() > 0)
        {
            return $this->map($query->row());
        }

        return false;
    }

    public function findTopupToUpdate($id)
    {

        $this->db->select('*');
        $this->db->where('id', $id);
        $this->db->from('iafb_remittance.deposit_request');
        $query = $this->db->get();
        if ( $query && $query->num_rows() > 0)
        {
            return $this->map($query->row());
        }

        return false;
    }

    public function findDeductionToUpdate($id)
    {

        $this->db->select('*');
        $this->db->where('id', $id);
        $this->db->from('iafb_remittance.deposit_request');
        $query = $this->db->get();
        if ( $query && $query->num_rows() > 0)
        {
            return $this->map($query->row());
        }

        return false;
    }


    public function findByRequestId($id){
        $this->db->select('*');
        $this->db->where('id', $id);
        $query = $this->db->get();
        if ( $query && $query->num_rows() > 0)
        {
            return $this->map($query->row());
        }

        return false;
    }

    public function addTopup($config)
    {
        $this->db->set('id', $config->getId());
        $this->db->set('deposit_tracker_id', $config->getDepositTrackerId());
        $this->db->set('type', $config->getType());
        $this->db->set('status', $config->getStatus());
        $this->db->set('bank', $config->getBank());
        $this->db->set('trans_reference_num', $config->getTransReferenceNum());
        $this->db->set('trans_proof_url',$config->getTransProofUrl());
        $this->db->set('photoname', $config->getPhotoName());
        $this->db->set('s3photoname', $config->getS3PhotoName());
        $this->db->set('transfer_date', IappsDateTime::fromString($config->getTransDate())->getUnix());
        $this->db->set('amount',$config->getAmount());
        $this->db->set('created_by', $config->getCreatedBy());
        $this->db->set('created_at', IappsDateTime::now()->getUnix());

        if($this->db->insert('iafb_remittance.deposit_request')){
            return true;
        } else {
            return false;
        }

    }

    public function getTopupList($limit,$page,$depositid)
    {
        $offset = ($page - 1) * $limit;
        $this->db->select('*');
        $this->db->where('deposit_tracker_id',$depositid);
        $this->db->where('type','topup');
//        $this->db->where('status != ','cancelled');
//        $this->db->where('status','approved');
        $this->db->order_by('created_at','DESC');
        $this->db->limit($limit, $offset);
        $this->db->from('iafb_remittance.deposit_request');
        $query = $this->db->get();
        $total = $this->db->count_all_results();
        if( $query && $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(),new DepositTrackerRequestCollection(), $query->num_rows());
        }

        return false;
    }


    public function getTransactionList($limit,$page,$depositid)
    {
        $offset = ($page - 1) * $limit;
        $this->db->select('*');
        $this->db->where('deposit_tracker_id',$depositid);
        $this->db->where('status !=','pending');
        $this->db->where('status !=', 'cancelled');
        $this->db->where('status !=','rejected');
        $this->db->order_by('approve_rejected_at','DESC');
        $this->db->limit($limit, $offset);
        $this->db->from('iafb_remittance.deposit_request');
        $query = $this->db->get();
        $total = $this->db->count_all_results();
        if( $query && $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new DepositTrackerRequestCollection(), $total);
        }

        return false;
    }

    public function getTopup($topupid)
    {
        $this->db->select('*');
        $this->db->where("id",$topupid);
        $this->db->from('iafb_remittance.deposit_request');
        $query = $this->db->get();
        if($query && $query->num_rows() > 0)
        {
            return $this->map($query->row());
        }
        return false;
    }

    public function approveTopup($config)
    {
        $this->TransStart();
        $this->db->set('status',$config->getStatus());
        $this->db->set('updated_by',$config->getUpdatedBy());
        $this->db->set('approve_rejected_at',IappsDateTime::now()->getUnix());
        $this->db->set('approve_rejected_by',$config->getUpdatedBy());
        $this->db->set('approve_rejected_remarks', $config->getApprovedRejectedRemark());
        if($config->getApproveTopupReason() == 777) {
            $this->db->set('approve_rejected_remarks', $config->getApprovedRejectedRemark());
        }
        $this->db->set('updated_at',IappsDateTime::now()->getUnix());
        $this->db->where('deposit_tracker_id',$config->getDepositTrackerId());
        $this->db->where('id', $config->getId());
        if($this->db->update('iafb_remittance.deposit_request')){
            $this->updateDepositUpdatedby($config);
            $this->TransComplete();
            return true;
        }
        $this->TransRollback();
        return false;
    }


    public function updateDepositUpdatedby($config)
    {
        $this->db->set('updated_at',IappsDateTime::now()->getUnix());
        $this->db->set('updated_by',$config->getUpdatedBy());
        $this->db->where('id',$config->getDepositTrackerId());
        $this->db->update('iafb_remittance.deposit_tracker');
    }

    public function updateDepositAmount($config)
    {
        $this->db->set("amount", $config->getAmount());
        $this->db->where("id", $config->getDepositTrackerId());
        if($this->db->update('iafb_remittance.deposit_tracker')){
            return true;
        }
        return false;
    }

    public function processDeduction($config)
    {
        $this->db->set("trans_proof_url", $config->getTransProofUrl());
        if($config->getStatus() != 'rejected') {
            $this->db->set("photoname", $config->getPhotoName());
            $this->db->set("s3photoname", $config->getS3PhotoName());
            $this->db->set("transfer_date", $config->getProcessedAt()->getUnix());
            $this->db->set('processed_at',IappsDateTime::now()->getUnix());
            $this->db->set("processed_by", $config->getProcessedBy());
            $this->db->set("bank", $config->getBank());
            $this->db->set('trans_reference_num',$config->getTransReferenceNum());
        }
        if($config->getStatus() == 'rejected'){
            $this->db->set('status', $config->getStatus());
            $this->db->set('approve_rejected_at',IappsDateTime::now()->getUnix());
            $this->db->set('approve_rejected_by', $config->getApprovedRejectedBy());
        }
        $this->db->set('approve_rejected_remarks', $config->getApprovedRejectedRemark());
        $this->db->set('updated_at', IappsDateTime::now()->getUnix());
        $this->db->set('updated_by', $config->getUpdatedBy());
        $this->db->where("id", $config->getId());
        if($this->db->update('iafb_remittance.deposit_request')){
            return true;
        }
        return false;
    }

    public function findDepositToUpdate($id)
    {
        $this->db->select('*');
        $this->db->where("id", $id);
        $this->db->from('iafb_remittance.deposit_tracker');
        $query = $this->db->get();
        $total = $this->db->count_all_results();
        if( $query && $query->num_rows() > 0)
        {
           return $this->map($query->row());
        }
        return false;
    }

    public function rejectTopup($config)
    {
        $this->db->set('status',$config->getStatus());
        $this->db->set('updated_by', $config->getUpdatedBy());
        $this->db->set('updated_at', IappsDateTime::now()->getUnix());
        $this->db->set('approve_rejected_at',IappsDateTime::now()->getUnix());
        $this->db->set('approve_rejected_by',$config->getUpdatedBy());
        $this->db->set('approve_rejected_remarks',$config->getApprovedRejectedRemark());
        if($config->getRejectTopupReason() == 777) {
            $this->db->set('approve_rejected_remarks', $config->getApprovedRejectedRemark());
        }
        $this->db->where('deposit_tracker_id',$config->getDepositTrackerId());
        $this->db->where('id', $config->getId());
        if($this->db->update('iafb_remittance.deposit_request')){
            $this->updateDepositDate($config);
            return true;
        }
        return false;
    }

    public function rejectDeduction($config)
    {
        $this->db->set('status',$config->getStatus());
        $this->db->set('updated_by', $config->getUpdatedBy());
        $this->db->set('updated_at', IappsDateTime::now()->getUnix());
        $this->db->set('approve_rejected_at', IappsDateTime::now()->getUnix());
        $this->db->set('approve_rejected_by',$config->getApprovedRejectedBy());
        $this->db->set('approve_rejected_remarks', $config->getApprovedRejectedRemark());
        $this->db->where('deposit_tracker_id', $config->getDepositTrackerId());
        $this->db->where('id', $config->getId());
        if($this->db->update('iafb_remittance.deposit_request')){
            $this->updateDepositDate($config);
            return true;
        }
        return false;
    }

    public function cancelTopup($config)
    {
        $this->TransStart();
        $this->db->set('status',$config->getStatus());
        $this->db->set('updated_by', $config->getUpdatedBy());
        $this->db->set('updated_at',IappsDateTime::now()->getUnix());
        $this->db->where('id', $config->getId());
        if($this->db->update('iafb_remittance.deposit_request')){
            $this->updateDepositDate($config);
            $this->TransComplete();
            return true;
        }
        $this->TransRollback();
        return false;
    }

    public function cancelDeduction($config)
    {
        $this->db->set('status',$config->getStatus());
        $this->db->set('approve_rejected_remarks',$config->getApprovedRejectedRemark());
        $this->db->set('updated_by', $config->getUpdatedBy());
        $this->db->set('updated_at',IappsDateTime::now()->getUnix());
        $this->db->where('id', $config->getId());
        if($this->db->update('iafb_remittance.deposit_request')){
            $this->updateDepositDate($config);
            return true;
        }
        return false;
    }

    public function approveDeduction($config)
    {
        $this->db->set('status',$config->getStatus());
        $this->db->set('approve_rejected_by',$config->getUpdatedBy());
        $this->db->set('approve_rejected_at',IappsDateTime::now()->getUnix());
        $this->db->set('approve_rejected_remarks',$config->getApprovedRejectedRemark());
        $this->db->set('updated_by', $config->getUpdatedBy());
        $this->db->set('updated_at',IappsDateTime::now()->getUnix());
        $this->db->where('id', $config->getId());
        if($this->db->update('iafb_remittance.deposit_request')){
            return true;
        }
        return false;

    }


    private function updateDepositDate($config)
    {
        $this->db->set('updated_at',IappsDateTime::now()->getUnix());
        $this->db->set('updated_by',$config->getUpdatedBy());
        $this->db->where('id',$config->getDepositTrackerId());
        if($this->db->update('iafb_remittance.deposit_tracker')){
            return true;
        }
        return false;
    }


    public function getDeductionList($limit,$page,$depositid)
    {

        $offset = ($page - 1) * $limit;

        $this->db->start_cache(); //to cache active record query
        $this->db->select('*');
        $this->db->where('deposit_tracker_id',$depositid);
        $this->db->where('type','deduction');
        $this->db->order_by('created_at desc');
        $this->db->from('iafb_remittance.deposit_request');
        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit
        $this->db->limit($limit, $offset);
        $query = $this->db->get();
        $this->db->flush_cache();
        if( $query && $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(),new DepositTrackerRequestCollection(),$total);// $query->result();//$this->mapCollection($query->result(), new DepositTrackerCollection(), $total);
        }

        return false;

    }


    public function addDeduction($config)
    {
        $this->TransStart();
        $this->db->set('id', $config->getId());
        $this->db->set('deposit_tracker_id', $config->getDepositTrackerId());
        $this->db->set('amount', $config->getAmount());
        $this->db->set('type', $config->getType());
        $this->db->set('created_by', $config->getCreatedBy());
        if($config->getPurpose() == 'transaction'){
            $this->db->set('status', 'approved');
            $this->db->set('approve_rejected_at',IappsDateTime::now()->getUnix());
        } else {
            $this->db->set('status',$config->getStatus());
        }

        $this->db->set('purpose', $config->getPurpose());
        $this->db->set('created_by', $config->getCreatedBy());
        $this->db->set('created_at', IappsDateTime::now()->getUnix());
        if( $this->db->insert('iafb_remittance.deposit_request') )
        {
            if($config->getPurpose() == 'transaction'){
                $deposit = $this->getDepositById($config->getDepositTrackerId());
                $newbalance = $deposit[0]->amount - $config->getAmount();
                $this->updateExistingBalance($newbalance,$deposit[0]->threshold_amount,$deposit[0]->id);
                $this->TransComplete();
                return true;
            }
            $this->TransComplete();
            return true;
        }
        $this->TransRollback();
        return false;
    }


    public function updateExistingBalance($newbalance,$thresholdamount,$depositid)
    {

        $this->db->set('amount', $newbalance);
        if($newbalance < $thresholdamount){
            $this->db->set('threshold_status','low');
        }
        $this->db->where('id',$depositid);
        $this->db->update('iafb_remittance.deposit_tracker');
    }


    public function getDepositById($depositid)
    {
        $this->db->select('*');
        $this->db->where('id',$depositid);
        $this->db->from('iafb_remittance.deposit_tracker');
        $query = $this->db->get();
        if($query && $query->num_rows() > 0){
            return $query->result();
        }
        return false;

    }


    public function getDeduction($deductionid)
    {
        $this->db->select('*');
        $this->db->where('id',$deductionid);
        $this->db->from('iafb_remittance.deposit_request');

        $query = $this->db->get();
        if($query && $query->num_rows() > 0)
        {
            return $this->map($query->row());
        }
        return false;
    }
    
    public function insertDeductionRequest(DepositTrackerRequest $request, DepositTracker $deposit)
    {
        $this->TransStart();
        $this->db->set('id', $request->getId());
        $this->db->set('deposit_tracker_id', $request->getDepositTrackerId());
        $this->db->set('amount', $request->getAmount());
        $this->db->set('type', $request->getType());
        $this->db->set('trans_reference_num', $request->getTransReferenceNum());
        $this->db->set('created_by', $request->getCreatedBy());
        if($request->getPurpose() == 'transaction'){
            $this->db->set('approve_rejected_at',IappsDateTime::now()->getUnix());
            $this->db->set('status', 'approved');
        } else {
            $this->db->set('status',$request->getStatus());
        }

        $this->db->set('purpose', $request->getPurpose());
        $this->db->set('created_by', $request->getCreatedBy());
        $this->db->set('created_at', IappsDateTime::now()->getUnix());
        if( $this->db->insert('iafb_remittance.deposit_request') )
        {
            if($this->updateDepositBalance($deposit)){
                $this->TransComplete();
                return true;
            } else {
                $this->TransRollback();
                return false;
            }
        }

               
    }
    
    
    private function updateDepositBalance(DepositTracker $deposit)
    {
        $this->db->set('threshold_status',$deposit->getThresholdStatus());
        $this->db->set('amount',$deposit->getAmount());
        $this->db->set('updated_at', IappsDateTime::now()->getUnix());
//        $this->db->set('updated_by', $deposit->getUpdatedBy());
        $this->db->where('id', $deposit->getId());
        if( $this->db->update('iafb_remittance.deposit_tracker') )
        {
            return true;
        }
        return false;           
    }


    public function getPendingDeduction($limit,$page)
    {
        $offset = ($page - 1) * $limit;

        $this->db->start_cache(); //to cache active record query
        $this->db->select('*');
        $this->db->where('status','pending');
        $this->db->where('purpose','settlement');
        $this->db->where('processed_at !=', NULL);
        $this->db->where('type','deduction');
        $this->db->order_by('created_at','desc');
        $this->db->from('iafb_remittance.deposit_request');
        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit

        $this->db->limit($limit, $offset);
        $query = $this->db->get();
        $this->db->flush_cache();
        if( $query && $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new DepositTrackerRequestCollection(), $total);
        }

        return false;
    }


    public function partnerListPendingDeduction($limit,$page)
    {
        $offset = ($page - 1) * $limit;

        $this->db->start_cache(); //to cache active record query
        $this->db->select('*');
        $this->db->where('status','pending');
        $this->db->where('purpose','settlement');
        $this->db->where('type','deduction');
        $this->db->where('processed_at', NULL);
        $this->db->order_by('created_at','desc');
        $this->db->from('iafb_remittance.deposit_request');
        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit

        $this->db->limit($limit, $offset);
        $query = $this->db->get();
        $this->db->flush_cache();
        if( $query && $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new DepositTrackerRequestCollection(), $total);
        }

        return false;
    }


    public function getPendingTopupList($limit,$page,$depositid)
    {
        $offset = ($page - 1) * $limit;
        $this->db->select('*');
        $this->db->where('deposit_tracker_id',$depositid);
        $this->db->where('type','topup');
//        $this->db->where('status','pending');
        $this->db->order_by('created_at','DESC');
        $this->db->limit($limit, $offset);
        $this->db->from('iafb_remittance.deposit_request');
        $query = $this->db->get();
        $total = $this->db->count_all_results();
        if( $query && $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(),new DepositTrackerRequestCollection(), $query->num_rows());
        }

        return false;


    }


    public function getPartnerPendingTopup($limit,$page)
    {
        $offset = ($page - 1) * $limit;
        $this->db->select('*');
        $this->db->where('type','topup');
        $this->db->where('status','pending');
        $this->db->order_by('created_at','DESC');
        $this->db->limit($limit, $offset);
        $this->db->from('iafb_remittance.deposit_request');
        $query = $this->db->get();
        $total = $this->db->count_all_results();
        if( $query && $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new DepositTrackerRequestCollection(), $total);
        }

        return false;
    }


    public function updateThresholdStatus($depositid,$thresholdstatus)
    {
        $this->db->set('threshold_status', $thresholdstatus);
        $this->db->where('id',$depositid);
        $this->db->update('iafb_remittance.deposit_tracker');
    }


    /*
     * mapping was not used in this function
     * because, if i use the remittance record
     * collection mapping, it won't work
     * as the map() function of this model
     * will be called. I created this
     * function because there's no existing
     * function that will actually search
     * for the record by remittanceID
     * remittanceID
     */
    public function getRemittanceByRemittanceID($remittanceid)
    {

        $this->db->select('*');
        $this->db->from('iafb_remittance.remittance r');
        $this->db->join('iafb_remittance.transaction t','r.in_transaction_id = t.id');
        $this->db->where('r.remittanceID',$remittanceid);
        $query = $this->db->get();
        $total = $this->db->count_all_results();
        if($query && $query->num_rows() > 0){
            $query = $query->result();
            return $query;
        }
        return false;
    }


    public function getAllPendingDeductionsByDepositId($depositid)
    {
        $this->db->select('*');
        $this->db->where('deposit_tracker_id',$depositid);
        $this->db->where('status','pending');
        $this->db->where('purpose','settlement');
        $this->db->where('type','deduction');
        $this->db->where('processed_at',NULL);
        $this->db->from('iafb_remittance.deposit_request');
        $query = $this->db->get();
        $total = $this->db->count_all_results();
        if($query && $query->num_rows() > 0){
            return $this->mapCollection($query->result(), new DepositTrackerRequestCollection(), $total);
        }
        return false;
    }

    public function findByDepositId($depositid){}
    public function findDepositById($depositid){}
    public function getOrganization($userid){}
    public function getBanks(){}

}
