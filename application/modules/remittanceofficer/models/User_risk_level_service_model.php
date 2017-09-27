<?php

use Iapps\Common\Core\IappsDateTime;
use Iapps\RemittanceService\UserRiskLevel\UserRiskLevelDataMapper;
use Iapps\RemittanceService\UserRiskLevel\UserRiskLevelCollection;
use Iapps\RemittanceService\UserRiskLevel\UserRiskLevel;
use Iapps\RemittanceService\UserRiskLevel\UserRiskLevelStatus;
use Iapps\RemittanceService\UserRiskLevel\UserRiskLevelApprovalStatus;

class User_risk_level_service_model extends Base_Model implements UserRiskLevelDataMapper{

    public function map(stdClass $data)
    {
        $entity = new UserRiskLevel();

        if(isset($data->id)){
            $entity->setId($data->id);
        }

        if(isset($data->user_profile_id)){
            $entity->setUserProfileId($data->user_profile_id);
        }

        if(isset($data->unactive_risk_level)){
            $entity->setUnActiveRiskLevel($data->unactive_risk_level);
        }

        if(isset($data->active_risk_level)){
            $entity->setActiveRiskLevel($data->active_risk_level);
        }

        if(isset($data->level_changed_reason)){
            $entity->setLevelChangedReason($data->level_changed_reason);
        }

        if(isset($data->approval_status)){
            $entity->setApprovalStatus($data->approval_status);
        }

        if(isset($data->is_active)){
            $entity->setIsActive($data->is_active);
        }
        
        
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


    public function findById($id, $deleted = false){

        $this->db->start_cache(); //to cache active record query
        $this->db->select('id,
                           user_profile_id,
                           unactive_risk_level,
                           active_risk_level,
                           level_changed_reason,
                           approval_status,
                           is_active,
                           created_at,
                           created_by,
                           updated_at,
                           updated_by,
                           deleted_at,
                           deleted_by');

        $this->db->from('iafb_remittance.user_risk_level');
        $this->db->where('id', $id);
        $this->db->where('deleted_at',NULL);

        $this->db->stop_cache();

        $query = $this->db->get();
        $this->db->flush_cache();
        if( $query->num_rows() > 0)
        {
            return $this->map($query->row());
        }

        return false;
    }

    public function findAllUserRiskLevel($limit, $page)
    {
        $offset = ($page - 1) * $limit;

        $this->db->start_cache(); //to cache active record query
        $this->db->select('id,
                           user_profile_id,
                           unactive_risk_level,
                           active_risk_level,
                           level_changed_reason,
                           approval_status,
                           is_active,
                           created_at,
                           created_by,
                           updated_at,
                           updated_by,
                           deleted_at,
                           deleted_by');
        $this->db->from('iafb_remittance.user_risk_level');
        $this->db->where('deleted_at', NULL);
        $this->db->where('approval_status', UserRiskLevelApprovalStatus::PENDING);

        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit

        $this->db->limit($limit, $offset);
        $query = $this->db->get();
        $this->db->flush_cache();

        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new UserRiskLevelCollection(), $total);
        }

        return false;
    }

    public function findByUserProfileId($id, $deleted = false){

        $this->db->start_cache(); //to cache active record query
        $this->db->select('id,
                           user_profile_id,
                           unactive_risk_level,
                           active_risk_level,
                           level_changed_reason,
                           approval_status,
                           is_active,
                           created_at,
                           created_by,
                           updated_at,
                           updated_by,
                           deleted_at,
                           deleted_by');

        $this->db->from('iafb_remittance.user_risk_level');
        $this->db->where('user_profile_id', $id);
        $this->db->where('deleted_at',NULL);

        $this->db->stop_cache();

        $query = $this->db->get();
        $this->db->flush_cache();
        if( $query->num_rows() > 0)
        {
            return $this->map($query->row());
        }

        return false;
    }

    public function update(UserRiskLevel $userRiskLevel){

        if ($userRiskLevel->getUnActiveRiskLevel()) {
            $this->db->set('unactive_risk_level', $userRiskLevel->getUnActiveRiskLevel());
        }
        if ($userRiskLevel->getActiveRiskLevel()) {
            $this->db->set('active_risk_level', $userRiskLevel->getActiveRiskLevel());
        }
        if ($userRiskLevel->getLevelChangedReason()) {
            $this->db->set('level_changed_reason', $userRiskLevel->getLevelChangedReason());
        }
        if($userRiskLevel->getApprovalStatus()) {
            $this->db->set('approval_status', $userRiskLevel->getApprovalStatus());
        }

        $this->db->set('is_active', $userRiskLevel->getIsActive());

        $this->db->set('updated_at', IappsDateTime::now()->getUnix());
        $this->db->set('updated_by', $userRiskLevel->getUpdatedBy());

        if ($userRiskLevel->getUserProfileId()) {
            $this->db->where('user_profile_id', $userRiskLevel->getUserProfileId());
        }
        if ($userRiskLevel->getId()) {
            $this->db->where('id', $userRiskLevel->getId());
        }
        $this->db->where('deleted_at',NULL);


        if( $this->db->update('iafb_remittance.user_risk_level') )
        {
            return true;
        }

        return false;
    }

    public function insert(UserRiskLevel $userRiskLevel){

        $this->db->set('id', $userRiskLevel->getId());
        $this->db->set('user_profile_id', $userRiskLevel->getUserProfileId());
        if ($userRiskLevel->getUnActiveRiskLevel()) {
            $this->db->set('unactive_risk_level', $userRiskLevel->getUnActiveRiskLevel());
        }
        if ($userRiskLevel->getActiveRiskLevel()) {
            $this->db->set('active_risk_level', $userRiskLevel->getActiveRiskLevel());
        }
        $this->db->set('level_changed_reason', $userRiskLevel->getLevelChangedReason());
        $this->db->set('approval_status', $userRiskLevel->getApprovalStatus());
        $this->db->set('is_active', $userRiskLevel->getIsActive());

        $this->db->set('created_at', IappsDateTime::now()->getUnix());
        $this->db->set('created_by', $userRiskLevel->getCreatedBy());

        if( $this->db->insert('iafb_remittance.user_risk_level') )
        {
            return true;
        }

        return false;
    }

    public function updateApprovalStatus(UserRiskLevel $userRiskLevel)
    {
        $this->db->set('approval_status', $userRiskLevel->getApprovalStatus());
        $this->db->set('updated_at', IappsDateTime::now()->getUnix());
        $this->db->set('updated_by', $userRiskLevel->getUpdatedBy());
        $this->db->where('id', $userRiskLevel->getId());
        $this->db->where('deleted_at',NULL);

        if( $this->db->update('iafb_remittance.user_risk_level') )
        {
            return true;
        }

        return false;
    }

    public function checkHasPendingStatusRequest($user_profile_id)
    {
        $this->db->start_cache(); //to cache active record query
        $this->db->select('*');

        $this->db->from('iafb_remittance.user_risk_level');
        $this->db->where('user_profile_id', $user_profile_id);
        $this->db->where('approval_status', UserRiskLevelApprovalStatus::PENDING);
        $this->db->where('deleted_at',NULL);

        $this->db->stop_cache();

        $query = $this->db->get();
        $this->db->flush_cache();
        if( $query->num_rows() > 0)
        {
            return true;
        }

        return false;
    }

    public function checkHasApprovedAndIsActiveRequest($user_profile_id)
    {
        $this->db->start_cache(); //to cache active record query
        $this->db->select('*');

        $this->db->from('iafb_remittance.user_risk_level');
        $this->db->where('user_profile_id', $user_profile_id);
        $this->db->where('approval_status', UserRiskLevelApprovalStatus::APPROVED);
        $this->db->where('is_active', '1');
        $this->db->where('deleted_at',NULL);

        $this->db->stop_cache();

        $query = $this->db->get();
        $this->db->flush_cache();
        if( $query->num_rows() > 0)
        {
            return $this->map($query->row());
        }

        return false;
    }

    public function findByPar(UserRiskLevel $userRiskLevel)
    {
        $this->db->start_cache(); //to cache active record query
        $this->db->select('*');

        $this->db->from('iafb_remittance.user_risk_level');

        if ($userRiskLevel->getUserProfileId()) {
            $this->db->where('user_profile_id', $userRiskLevel->getUserProfileId());
        }

        if ($userRiskLevel->getApprovalStatus()) {
            $this->db->where('approval_status', $userRiskLevel->getApprovalStatus());
        }

        if ($userRiskLevel->getIsActive()) {
            $this->db->where('is_active', $userRiskLevel->getIsActive());
        }

        $this->db->where('deleted_at',NULL);

        $this->db->stop_cache();
        $total = $this->db->count_all_results(); //to get total num of result w/o limit

        $query = $this->db->get();
        $this->db->flush_cache();
        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new UserRiskLevelCollection(), $total);
        }

        return false;
    }

}