<?php

use Iapps\Common\DepositTracker\DepositTrackerHistory;
use Iapps\Common\DepositTracker\IDepositTrackerHistoryDataMapper;
use Iapps\Common\DepositTracker\DepositTrackerHistoryCollection;
use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\Microservice\PaymentService\PaymentService;
use Iapps\Common\Helper\GuidGenerator;
use Iapps\Common\DepositTracker\DepositTracker;


class Deposit_tracker_history_model extends Base_Model implements IDepositTrackerHistoryDataMapper{

    const FLAG_SCREENED = 1;

    public function map(stdClass $data)
    {
        $entity = new DepositTrackerHistory();

        if(isset($data->id))
            $entity->setId($data->id);

        if( isset($data->deposit_tracker_id) )
            $entity->setDepositTrackerId($data->deposit_tracker_id);

        if( isset($data->history_id))
            $entity->setHistoryId($data->history_id);

        if( isset($data->amount) )
            $entity->setAmount($data->amount);


        if( isset($data->last_balance) )
            $entity->setLastBalance($data->last_balance);

        if( isset($data->status) )
            $entity->setStatus($data->status);

        if( isset($data->last_status))
            $entity->setLastStatus($data->last_status);

        if( isset($data->approve_rejected_by) )
            $entity->setApproveRejectedBy($data->approve_rejected_by);

        if( isset($data->approve_rejected_at) )
            $entity->setApproveRejectedAt($data->approve_rejected_at);


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
        
        if(isset($data->screened))
            $entity->setScreened ($data->screeneed);

        return $entity;
    }



    protected function _prepareSelect()
    {
        $this->db->select('id as deposit_tracker_history_id,
                           deposit_tracker_id,
                           amount,
                           reference_id,
                           last_balance,
                           type_of_trans,
                           status,
                           reference_no,
                           approve_reject_by,
                           approve_reject_at,                           
                           remarks,
                           created_at,
                           created_by,
                           updated_at,
                           updated_by,
                           deleted_at,
                           deleted_by');
        $this->db->from('iafb_remittance.deposit_tracker_history');
    }

    public function findById($id, $deleted = false)
    {
        $this->_prepareSelect();
        if( !$deleted )
            $this->db->where('deleted_at', NULL);
        $this->db->where('id', $id);

        $query = $this->db->get();
        if( $query->num_rows() > 0)
        {
            return $this->map($query->row());
        }

        return false;
    }


    public function getDepositTrackerHistoryList($limit, $page)
    {
        $offset = ($page - 1) * $limit;

        $this->db->start_cache(); //to cache active record query
        $this->_prepareSelect();
        $this->db->where('deleted_at', NULL);
        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit

        $this->db->limit($limit, $offset);
        $query = $this->db->get();
        $this->db->flush_cache();
        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new DepositTrackerHistoryCollection(), $total);
        }

        return false;
    }


    public function findByDepositTrackerId($id)
    {
        $this->_prepareSelect();
        $this->db->where('deposit_tracker_id', $id);
        $this->db->where('deleted_at', NULL);

        $query = $this->db->get();
        if( $query->num_rows() > 0 )
        {
            return $this->map($query->row());
        }

        return false;
    }

    public function findAll($limit, $page)
    {
        $offset = ($page - 1) * $limit;
        $this->db->start_cache(); //to cache active record query
        $this->_prepareSelect();
        $this->db->where('deleted_at', NULL);

        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit

        $this->db->limit($limit, $offset);
        $query = $this->db->get();

        $this->db->flush_cache();

        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new DepositTrackerHistoryCollection(), $total);
        }

        return false;
    }

    public function insert(DepositTrackerHistory $config)
    {

        $this->db->set('id', $config->getId());
        $this->db->set('deposit_tracker_id', $config->getDepositTrackerId());
        $this->db->set('reference_id', $config->getRefTransactionId());
        $this->db->set('amount', $config->getAmount());
        $this->db->set('last_balance', $config->getLastBalance());
        $this->db->set('type_of_trans', $config->getTypeOfTrans());
        $this->db->set('status', $config->getStatus());
        $this->db->set('reference_no', $config->getReferenceNo());
        $this->db->set('approve_reject_by', $config->getApproveRejectBy());
        $this->db->set('approve_reject_at', $config->getApproveRejectAt());
        $this->db->set('remarks', $config->getRemarks());
        $this->db->set('created_by', $config->getCreatedBy());
        $this->db->set('created_at', IappsDateTime::now()->getUnix());

        if( $this->db->insert('iafb_remittance.deposit_tracker_history') )
        {
            return true;
        }

        return false;
    }
    /*@rahul :: Update function to update status
     *
     */
    public function update(DepositTrackerHistory $config)
    {

        $this->db->set('deposit_tracker_id', $config->getDepositTrackerId());
        $this->db->set('reference_id', $config->getRefTransactionId());
        $this->db->set('amount', $config->getAmount());
        $this->db->set('last_balance', $config->getLastBalance());
        $this->db->set('type_of_trans', $config->getTypeOfTrans());
        $this->db->set('status', $config->getStatus());
        $this->db->set('reference_no', $config->getReferenceNo());
        $this->db->set('approve_reject_by', $config->getApproveRejectBy());
        $this->db->set('approve_reject_at', $config->getApproveRejectAt());
        $this->db->set('remarks', $config->getRemarks());
        $this->db->where('id', $config->getId());

        if( $this->db->update('iafb_remittance.deposit_tracker_history') )
        {
            return true;
        }

        return false;
    }

// Return all topup
    public function getByParam(DepositTrackerHistory $filter, $limit, $page)
    {
        $init_predicate = true;
        $offset = ($page - 1) * $limit;
        $this->db->start_cache(); //to cache active record query

        $this->_prepareSelect();
        $this->db->where('deleted_at', NULL);

        if($filter->getId() )
            $this->db->where('id', $filter->getId());

        if($filter->getTypeOfTrans() )
            $this->db->where('type_of_trans', $filter->getTypeOfTrans());

        if($filter->getStatus() )
            $this->db->where('status', $filter->getStatus());



        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit

        $this->db->limit($limit, $offset);
        $query = $this->db->get();

        $this->db->flush_cache();

        if( $query->num_rows() > 0 )
        {
            return $this->mapCollection($query->result(), new DepositTrackerHistoryCollection(), $total);
        }

        return false;
    }

    public function insertHistory(DepositTracker $config,$trackers)
    {

        $this->TransStart();
        $id = GuidGenerator::generate();
        $this->db->set('id',$id);
        $this->db->set('deposit_tracker_id',$config->getId());
        $this->db->set('amount', $config->getAmount());
        $this->db->set('last_balance',$config->getAmount());
        $threshold_amount = number_format(str_replace(",","",$config->getThresholdAmount()),2,".","");
        $this->db->set('last_threshold_amount', $threshold_amount);
        $this->db->set('last_status', $config->getDepositStatus());
        $this->db->set('approve_rejected_by', $config->getApproveRejectedBy());
        $this->db->set('approve_rejected_at', $config->getApproveRejectedAt());
        $this->db->set('created_by', $config->getCreatedBy());
        $this->db->set('created_at', IappsDateTime::now()->getUnix());
        $this->db->set('updated_by', $config->getUpdatedBy());
        $this->db->set('updated_at', IappsDateTime::now()->getUnix());//$config->getHistoryUpdatedAt());

        if( $this->db->insert('iafb_remittance.deposit_tracker_history'))
        {
            if(!empty($trackers)){
                $this->resetTrackers($config);
                foreach($trackers as $tracker) {
                    $this->db->set('id', GuidGenerator::generate());
                    $this->db->set('history_id', $id);
                    $this->db->set('tracker_id', $tracker);
                    $this->db->set('deposit_tracker_id', $config->getId());
                    $this->db->set('status', 'active');
                    $this->db->set('created_at', IappsDateTime::now()->getUnix());
                    $this->db->set('created_by', $config->getCreatedBy());
                    $this->db->insert('iafb_remittance.deposit_history_user');
                }
                    if(!empty($config->getTrackerEmail())){
                            $trackeremail = $config->getTrackerEmail();
                            $this->resetTrackerEmail($config);
                            $cleanemail = str_replace(" ", "", $trackeremail);
                            $emailarray = explode(",",$cleanemail);
                            $counter = 0;
                            foreach($emailarray as $email){
                                if($this->insertEmail($config,$email,$id)){
                                    $counter++;
                                }
                            }
                            if($counter == sizeof($emailarray)){
                                $this->updateDepositUpdatedDate($config);
                                $this->TransComplete();
                                return true;
                            }

                        $this->updateDepositUpdatedDate($config);
                        $this->TransComplete();
                        return true;
                    }

            } else if(empty($trackers) && !empty($config->getTrackerEmail())){
                $trackeremail = $config->getTrackerEmail();
                $this->resetTrackerEmail($config);
                $cleanemail = str_replace(" ", "", $trackeremail);
                $emailarray = explode(",",$cleanemail);
                $counter = 0;

                $this->db->set('id', GuidGenerator::generate());
                $this->db->set('history_id', $id);
                $this->db->set('tracker_id', '');
                $this->db->set('deposit_tracker_id', $config->getId());
                $this->db->set('status', 'active');
                $this->db->set('created_at', IappsDateTime::now()->getUnix());
                $this->db->set('created_by', $config->getCreatedBy());
                $this->db->insert('iafb_remittance.deposit_history_user');

                foreach($emailarray as $email){
                    if($this->insertEmail($config,$email,$id)){
                        $counter++;
                    }
                }
                if($counter == sizeof($emailarray)){
                    $this->updateDepositUpdatedDate($config);
                    $this->TransComplete();
                    return true;
                }

            }
            $this->updateDepositUpdatedDate($config);
            $this->TransComplete();
            return true;
        }
        $this->TransRollback();
        return false;

    }

    private function updateDepositUpdatedDate(DepositTracker $config)
    {
        $this->db->set('updated_at',IappsDateTime::now()->getUnix());
        $this->db->set('updated_by', $config->getUpdatedBy());
        $this->db->where('id',$config->getId());
        $this->db->update('iafb_remittance.deposit_tracker');
    }

    public function resetTrackerEmail(DepositTracker $config)
    {
        $this->db->set('status','inactive');
        $this->db->set('updated_by',$config->getUpdatedBy());
        $this->db->set('updated_at',IappsDateTime::now()->getUnix());
        $this->db->where('deposit_tracker_id',$config->getId());
        $this->db->update('iafb_remittance.deposit_tracker_email');

    }

    //this one is used during update deposit
    public  function insertEmail(DepositTracker $config, $email,$id)
    {
        $this->db->set("id", GuidGenerator::generate());
        $this->db->set('history_id',$id);
        $this->db->set("deposit_tracker_id", $config->getId());
        $this->db->set("email", $email);
        $this->db->set("status", "pending");
        $this->db->set("screened", self::FLAG_SCREENED);
        $this->db->set("created_at", IappsDateTime::now()->getUnix());
        $this->db->set("created_by", $config->getCreatedBy());
        if($this->db->insert('iafb_remittance.deposit_tracker_email'))
        {
            return true;
        }
        return false;
    }


    public function resetTrackers(DepositTracker $config)
    {
        $this->db->set('status',"inactive");
        $this->db->set('updated_at', IappsDateTime::now()->getUnix());
        $this->db->set('updated_by', $config->getUpdatedBy());
        $this->db->where("deposit_tracker_id",$config->getId());
        $this->db->update('iafb_remittance.deposit_history_user');
    }

    public function getLastHistory($depositid){
        $this->db->select('*');
        $this->db->where('deposit_tracker_id',$depositid);
        $this->db->order_by('created_at desc');
        $this->db->from('iafb_remittance.deposit_tracker_history');
        $query = $this->db->get();
        $total = $this->db->count_all_results();
        if($query->num_rows() > 0){
           return $this->map($query->row());
        }
        return false;
    }


    protected function _historySelect()
    {
        $this->db->select('id,
                           deposit_tracker_id,
                           amount,
                           last_balance,
                           last_threshold_amount,
                           last_status,
                           approve_rejected_by,
                           approve_rejected_at,
                           created_at,
                           created_by,
                           updated_at,
                           updated_by,
                           deleted_at,
                           deleted_by');
        $this->db->from('iafb_remittance.deposit_tracker_history');
    }



    public function getHistoryList($limit, $page, $depositid)
    {
        $offset = ($page - 1) * $limit;
        $this->db->start_cache(); //to cache active record query
        $this->_historySelect();
        $this->db->where('deposit_tracker_id', $depositid);

        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit

        $this->db->limit($limit, $offset);
        $this->db->order_by('approve_rejected_at desc');
        $this->db->from('iafb_remittance.deposit_tracker_history');
        $query = $this->db->get();

        $this->db->flush_cache();

        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new DepositTrackerHistoryCollection(), $total);
        }

        return false;
    }


    public function getOldTrackers($historyid){}


}



