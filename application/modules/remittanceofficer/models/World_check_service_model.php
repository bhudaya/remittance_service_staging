<?php

use Iapps\Common\Core\IappsDateTime;
use Iapps\RemittanceService\WorldCheck\WorldCheckDataMapper;
use Iapps\RemittanceService\WorldCheck\WorldCheckCollection;
use Iapps\RemittanceService\WorldCheck\WorldCheck;

class World_check_service_model extends Base_Model implements WorldCheckDataMapper{

    public function map(stdClass $data)
    {
        $entity = new WorldCheck();

        if(isset($data->id)){
            $entity->setId($data->id);
        }

        if(isset($data->user_profile_id)){
            $entity->setUserProfileId($data->user_profile_id);
        }

        if(isset($data->reference_no)){
            $entity->setReferenceNo($data->reference_no);
        }

        if(isset($data->status)){
            $entity->setStatus($data->status);
        }

        if(isset($data->remarks)){
            $entity->setRemarks($data->remarks);
        }

        if( isset($data->created_at) )
            $entity->setCreatedAt(IappsDateTime::fromUnix($data->created_at));

        if( isset($data->created_by) )
            $entity->setCreatedBy($data->created_by);

        if( isset($data->updated_at) )
            $entity->setUpdatedAt(IappsDateTime::fromUnix($data->updated_at));

        if( isset($data->updated_by) )
            $entity->setUpdatedBy($data->updated_by);

        // if( isset($data->deleted_at) )
        //     $entity->setDeletedAt(IappsDateTime::fromUnix($data->deleted_at));

        // if( isset($data->deleted_by) )
        //     $entity->setDeletedBy($data->deleted_by);

        return $entity;
    }

    public function insertWorldCheckProfile(WorldCheck $worldCheck){

        $this->db->set('id', $worldCheck->getId());
        $this->db->set('user_profile_id', $worldCheck->getUserProfileId());
        $this->db->set('reference_no', $worldCheck->getReferenceNo());
        $this->db->set('status', $worldCheck->getStatus());

        if( $worldCheck->getRemarks() != NULL)
            $this->db->set('remarks', $worldCheck->getRemarks());

        $this->db->set('created_at', IappsDateTime::now()->getUnix());
        $this->db->set('created_by', $worldCheck->getCreatedBy());

        if( $this->db->insert('iafb_remittance.worldcheck') )
        {
            return true;
        }

        return false;
    }

    public function updateWorldCheckProfile(WorldCheck $worldCheck){

        if( $worldCheck->getReferenceNo() != NULL)
            $this->db->set('reference_no', $worldCheck->getReferenceNo());
        if( $worldCheck->getStatus() != NULL)
            $this->db->set('status', $worldCheck->getStatus());
        if( $worldCheck->getRemarks() != NULL)
            $this->db->set('remarks', $worldCheck->getRemarks());


        $this->db->set('updated_at', IappsDateTime::now()->getUnix());
        $this->db->set('updated_by', $worldCheck->getUpdatedBy());
        $this->db->where('user_profile_id', $worldCheck->getUserProfileId());

        if( $this->db->update('iafb_remittance.worldcheck') )
        {
            return true;
        }

        return false;
    }

    public function findById($id, $deleted = false){

        $this->db->start_cache(); //to cache active record query
        $this->db->select('id,
                           user_profile_id,
                           reference_no,
                           status,
                           remarks,
                           created_at,
                           created_by,
                           updated_at,
                           updated_by');
        $this->db->from('iafb_remittance.worldcheck');
        $this->db->where('user_profile_id', $id);

        $this->db->stop_cache();

        $query = $this->db->get();
        $this->db->flush_cache();
        if( $query->num_rows() > 0)
        {
            return $this->map($query->row());
        }

        return false;
    }

    public function findByUserProfileIDArr(Array $id_arr){

        $this->db->start_cache(); //to cache active record query
        $this->db->select('id,
                           user_profile_id,
                           reference_no,
                           status,
                           remarks,
                           created_at,
                           created_by,
                           updated_at,
                           updated_by');
        $this->db->from('iafb_remittance.worldcheck');
        $this->db->where_in('user_profile_id', $id_arr);

        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit
        $query = $this->db->get();

        $this->db->flush_cache();
        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new WorldCheckCollection(), $total);
        }

        return false;
    }

}