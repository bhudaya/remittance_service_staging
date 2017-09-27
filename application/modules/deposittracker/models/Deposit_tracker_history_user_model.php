<?php

use Iapps\Common\DepositTracker\DepositHistoryUser;
use Iapps\Common\DepositTracker\IDepositHistoryUserDataMapper;
use Iapps\Common\DepositTracker\DepositHistoryUserCollection;
use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\Microservice\PaymentService\PaymentService;
use Iapps\Common\Helper\GuidGenerator;


class Deposit_tracker_history_user_model extends Base_Model implements IDepositHistoryUserDataMapper{

    const FLAG_SCREENED = 1;

    public function map(stdClass $data)
    {
        $entity = new DepositHistoryUser();

        if(isset($data->id))
            $entity->setId($data->id);

        if( isset($data->deposit_tracker_id) )
            $entity->setDepositTrackerId($data->deposit_tracker_id);

        if( isset($data->history_id))
            $entity->setHistoryId($data->history_id);

        if( isset($data->tracker_id) )
            $entity->setTrackerId($data->tracker_id);

        if( isset($data->status) )
            $entity->setStatus($data->status);

        if( isset($data->created_at) )
            $entity->setCreatedAt(IappsDateTime::fromUnix($data->created_at));

        if( isset($data->created_by) )
            $entity->setCreatedBy($data->created_by);

        if( isset($data->updated_at) )
            $entity->setUpdatedAt(IappsDateTime::fromUnix($data->updated_at));

        if( isset($data->updated_by) )
            $entity->setUpdatedBy($data->updated_by);

        if ( isset($data->deleted_at) )
            $entity->setDeletedAt(IappsDateTime::fromUnix($data->deleted_at));

        if ( isset($data->deleted_by) )
            $entity->setDeletedBy($data->deleted_by);

        if(isset($data->screened))
            $entity->setScreened ($data->screeneed);

        return $entity;
    }


    public function getOldTrackers($historyid)
    {
        $this->db->select('*');
        $this->db->where('history_id',$historyid);
        $this->db->where('deleted_at', NULL);
        $this->db->from('iafb_remittance.deposit_history_user');
        $query = $this->db->get();
        $total = $this->db->count_all_results();
        if($query->num_rows() > 0){
            return $this->mapCollection($query->result(), new DepositHistoryUserCollection(), $total);
        }
        return false;
    }

    public function getDepositReason($type, $action, $action_owner){}

    public function findById($id, $deleted = false)
    {
        $this->db->select('*');
        $this->db->from('iafb_remittance.deposit_history_user');
        if( !$deleted )
            $this->db->where('deleted_at', NULL);
        $this->db->where('id',$id);

        $query = $this->db->get();
        if($query->num_rows() > 0){
            return $this->map($query->row());
        }

        return false;
    }

}



