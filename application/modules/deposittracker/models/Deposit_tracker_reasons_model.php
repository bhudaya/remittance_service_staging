<?php

use Iapps\Common\DepositTracker\DepositTracker;

use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\Helper\GuidGenerator;
use Iapps\Common\DepositTracker\IDepositTrackerReasonsDataMapper;
use Iapps\Common\DepositTracker\DepositTrackerReasons;
use Iapps\Common\DepositTracker\DepositTrackerReasonsCollection;


class Deposit_tracker_reasons_model extends Base_Model implements IDepositTrackerReasonsDataMapper
{

    const FLAG_SCREENED = 1;

    public function map(stdClass $data)
    {
        $entity = new DepositTrackerReasons();
        if ( isset($data->id) ) {
            $entity->setId($data->id);
        }

        if ( isset($data->type) ){
            $entity->setType($data->type);
        }

        if ( isset($data->action) ){
            $entity->setAction($data->action);
        }

        if ( isset($data->action_owner) ){
            $entity->setActionOwner($data->action_owner);
        }

        if ( isset($data->reason) ){
            $entity->setReason($data->reason);
        }

        if ( isset($data->status) ){
            $entity->setStatus($data->status);
        }

        if ( isset($data->created_at) ){
            $entity->setCreatedAt(IappsDateTime::fromUnix($data->created_at));
        }

        if ( isset($data->created_by) ){
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


    public function findById($id, $deleted = false)
    {
        $this->db->select('*');
        $this->db->from('iafb_remittance.deposit_tracker_reason');
        if( !$deleted )
            $this->db->where('deleted_at', NULL);
        $this->db->where('id',$id);

        $query = $this->db->get();
        if($query->num_rows() > 0)
        {
            return $this->map($query->row());
        }

        return false;
    }


    public function getDepositReason($type,$action=null,$action_owner)
    {
        $this->db->select('*');
        $this->db->where('type',$type);
        $this->db->where('deleted_at', NULL);
        if($action != null){
            $this->db->where('action',$action);
        }
        $this->db->where('action_owner',$action_owner);
        $this->db->from('iafb_remittance.deposit_tracker_reason');
        $query = $this->db->get();
        $total = $this->db->count_all_results();
        if($query && $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new DepositTrackerReasonsCollection(), $total);
        }
    }




}
