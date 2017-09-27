<?php

use Iapps\RemittanceService\RemittanceCompanyUser\IRemittanceCompanyUserDataMapper;
use Iapps\RemittanceService\RemittanceCompanyUser\RemittanceCompanyUser;
use Iapps\RemittanceService\RemittanceCompanyUser\RemittanceCompanyUserCollection;
use Iapps\Common\Core\IappsDateTime;

class Remittance_company_user_model extends Base_Model
                                    implements IRemittanceCompanyUserDataMapper{

    public function map(\stdClass $data)
    {
        $entity = new RemittanceCompanyUser();

        if( isset($data->remittance_company_user_id) )
            $entity->setId($data->remittance_company_user_id);

        if( isset($data->country_code) )
            $entity->setCountryCode($data->country_code);

        if( isset($data->remittance_company_id) )
            $entity->getRemittanceCompany()->setId($data->remittance_company_id);

        if( isset($data->user_profile_id) )
            $entity->getUser()->setId($data->user_profile_id);

        if( isset($data->customerID) )
            $entity->setCustomerID($data->customerID);

        if( isset($data->third_party_customerID) )
            $entity->setThirdPartyCustomerID($data->third_party_customerID);

        if( isset($data->user_status_id) )
            $entity->getUserStatus()->setId($data->user_status_id);

        if( isset($data->user_status_code) )
            $entity->getUserStatus()->setCode($data->user_status_code);

        if( isset($data->user_status_name) )
            $entity->getUserStatus()->setDisplayName($data->user_status_name);

        if( isset($data->completed_by) )
            $entity->setCompletedBy($data->completed_by);

        if( isset($data->completed_at) )
            $entity->setCompletedAt(IappsDateTime::fromUnix($data->completed_at));

        if( isset($data->verified_by) )
            $entity->setVerifiedBy($data->verified_by);

        if( isset($data->verified_at) )
            $entity->setVerifiedAt(IappsDateTime::fromUnix($data->verified_at));

        if( isset($data->verified_rejected_remark) )
            $entity->setVerifiedRejectedRemark($data->verified_rejected_remark);

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
        $this->db->select('u.id as remittance_company_user_id,
                           u.country_code,
                           u.remittance_company_id,
                           u.user_profile_id,
                           u.customerID,
                           u.third_party_customerID,
                           u.user_status_id,
                           stat.code user_status_code,
                           stat.display_name user_status_name,
                           u.completed_by,
                           u.completed_at,
                           u.verified_by,
                           u.verified_at,
                           u.rejected_by,
                           u.rejected_at,
                           u.verified_rejected_remark,
                           u.created_at,
                           u.created_by,
                           u.updated_at,
                           u.updated_by,
                           u.deleted_at,
                           u.deleted_by');
        $this->db->from('iafb_remittance.remittance_company_user u');
        $this->db->join('iafb_remittance.system_code stat', 'u.user_status_id = stat.id');
        $this->db->join('iafb_remittance.system_code_group stat_group', 'stat.system_code_group_id = stat_group.id');

        if( !$deleted )
            $this->db->where('u.deleted_at', NULL);

        $this->db->where('u.id', $id);

        $query = $this->db->get();
        if( $query->num_rows() > 0 )
        {
            return $this->map($query->row());
        }

        return false;
    }

    public function findByFilter(RemittanceCompanyUser $remittanceCompanyUser)
    {
        $this->db->select('u.id as remittance_company_user_id,
                           u.country_code,
                           u.remittance_company_id,
                           u.user_profile_id,
                           u.customerID,
                           u.third_party_customerID,
                           u.user_status_id,
                           stat.code user_status_code,
                           stat.display_name user_status_name,
                           u.completed_by,
                           u.completed_at,
                           u.verified_by,
                           u.verified_at,
                           u.rejected_by,
                           u.rejected_at,
                           u.verified_rejected_remark,
                           u.created_at,
                           u.created_by,
                           u.updated_at,
                           u.updated_by,
                           u.deleted_at,
                           u.deleted_by');
        $this->db->from('iafb_remittance.remittance_company_user u');
        $this->db->join('iafb_remittance.system_code stat', 'u.user_status_id = stat.id');
        $this->db->join('iafb_remittance.system_code_group stat_group', 'stat.system_code_group_id = stat_group.id');

        $this->db->where('u.deleted_at', NULL);

        if( $remittanceCompanyUser->getId() )
            $this->db->where('u.remittance_company_user_id',  $remittanceCompanyUser->getId());

        if( $remittanceCompanyUser->getUser()->getId() )
            $this->db->where('u.user_profile_id',  $remittanceCompanyUser->getUser()->getId());

        if( $remittanceCompanyUser->getRemittanceCompany()->getId() )
            $this->db->where('u.remittance_company_id',  $remittanceCompanyUser->getRemittanceCompany()->getId());

        if( $remittanceCompanyUser->getUserStatus()->getId() )
            $this->db->where('u.user_status_id',  $remittanceCompanyUser->getUserStatus()->getId());

        $query = $this->db->get();
        if( $query->num_rows() > 0 )
        {
            return $this->mapCollection($query->result(), new RemittanceCompanyUserCollection(), $query->num_rows());
        }

        return false;
    }

    public function findByFilters(RemittanceCompanyUserCollection $filters)
    {
        $this->db->select('u.id as remittance_company_user_id,
                           u.country_code,
                           u.remittance_company_id,
                           u.user_profile_id,
                           u.customerID,
                           u.third_party_customerID,
                           u.user_status_id,
                           stat.code user_status_code,
                           stat.display_name user_status_name,
                           u.completed_by,
                           u.completed_at,
                           u.verified_by,
                           u.verified_at,
                           u.rejected_by,
                           u.rejected_at,
                           u.verified_rejected_remark,
                           u.created_at,
                           u.created_by,
                           u.updated_at,
                           u.updated_by,
                           u.deleted_at,
                           u.deleted_by');
        $this->db->from('iafb_remittance.remittance_company_user u');
        $this->db->join('iafb_remittance.system_code stat', 'u.user_status_id = stat.id');
        $this->db->join('iafb_remittance.system_code_group stat_group', 'stat.system_code_group_id = stat_group.id');
        $this->db->where('u.deleted_at', NULL);

        $this->db->group_start();
        foreach($filters AS $filter)
        {
            if($filter instanceof RemittanceCompanyUser)
            {
                $this->db->or_group_start();

                if( $filter->getId() )
                    $this->db->where('u.remittance_company_user_id',  $filter->getId());

                if( $filter->getCountryCode() )
                    $this->db->where('u.country_code',  $filter->getCountryCode());

                if( $filter->getUser()->getId() )
                    $this->db->where('u.user_profile_id',  $filter->getUser()->getId());

                if( $filter->getRemittanceCompany()->getId() )
                    $this->db->where('u.remittance_company_id',  $filter->getRemittanceCompany()->getId());

                if( $filter->getUserStatus()->getId() )
                    $this->db->where('u.user_status_id',  $filter->getUserStatus()->getId());

                $this->db->group_end();
            }
        }
        $this->db->group_end();

        $query = $this->db->get();
        if( $query->num_rows() > 0 )
        {
            return $this->mapCollection($query->result(), new RemittanceCompanyUserCollection(), $query->num_rows());
        }

        return false;
    }

    public function update(RemittanceCompanyUser $remittanceCompanyUser, $checkNull = true)
    {
        $updatedAt = IappsDateTime::now();

        if( $remittanceCompanyUser->getUserStatus()->getId() OR !$checkNull)
            $this->db->set('user_status_id', $remittanceCompanyUser->getUserStatus()->getId());

        if( $remittanceCompanyUser->getCompletedBy() OR !$checkNull)
            $this->db->set('completed_by', $remittanceCompanyUser->getCompletedBy());

        if( !$remittanceCompanyUser->getCompletedAt()->isNull() OR !$checkNull)
            $this->db->set('completed_at', $remittanceCompanyUser->getCompletedAt()->getUnix());

        if( $remittanceCompanyUser->getVerifiedBy() OR !$checkNull)
            $this->db->set('verified_by', $remittanceCompanyUser->getVerifiedBy());

        if( !$remittanceCompanyUser->getVerifiedAt()->isNull() OR !$checkNull)
            $this->db->set('verified_at', $remittanceCompanyUser->getVerifiedAt()->getUnix());

        if( $remittanceCompanyUser->getRejectedBy() OR !$checkNull)
            $this->db->set('rejected_by', $remittanceCompanyUser->getRejectedBy());

        if( !$remittanceCompanyUser->getRejectedAt()->isNull() OR !$checkNull)
            $this->db->set('rejected_at', $remittanceCompanyUser->getRejectedAt()->getUnix());

        if( $remittanceCompanyUser->getVerifiedRejectedRemark() OR !$checkNull)
            $this->db->set('verified_rejected_remark', $remittanceCompanyUser->getVerifiedRejectedRemark());

        if( $remittanceCompanyUser->getThirdPartyCustomerID() OR !$checkNull)
            $this->db->set('third_party_customerID', $remittanceCompanyUser->getThirdPartyCustomerID());

        $this->db->set('updated_at', $updatedAt->getUnix());
        $this->db->set('updated_by', $remittanceCompanyUser->getUpdatedBy());

        $this->db->where('id', $remittanceCompanyUser->getId());
        if( $this->db->update('iafb_remittance.remittance_company_user') )
        {
            $remittanceCompanyUser->setUpdatedAt($updatedAt);
            return $remittanceCompanyUser;
        }

        return false;
    }

    public function insert(RemittanceCompanyUser $remittanceCompanyUser)
    {
        $createdAt = IappsDateTime::now();

        $this->db->set('id', $remittanceCompanyUser->getId());
        $this->db->set('country_code', $remittanceCompanyUser->getCountryCode());
        $this->db->set('remittance_company_id', $remittanceCompanyUser->getRemittanceCompany()->getId());
        $this->db->set('user_profile_id', $remittanceCompanyUser->getUser()->getId());
        $this->db->set('customerID', $remittanceCompanyUser->getCustomerID());
        $this->db->set('user_status_id', $remittanceCompanyUser->getUserStatus()->getId());
        $this->db->set('completed_by', $remittanceCompanyUser->getCompletedBy());
        $this->db->set('completed_at', $remittanceCompanyUser->getCompletedAt()->getUnix());
        $this->db->set('verified_by', $remittanceCompanyUser->getVerifiedBy());
        $this->db->set('verified_at', $remittanceCompanyUser->getVerifiedAt()->getUnix());
        $this->db->set('rejected_by', $remittanceCompanyUser->getRejectedBy());
        $this->db->set('rejected_at', $remittanceCompanyUser->getRejectedAt()->getUnix());
        $this->db->set('verified_rejected_remark', $remittanceCompanyUser->getVerifiedRejectedRemark());
        $this->db->set('created_at', $createdAt->getUnix());
        $this->db->set('created_by', $remittanceCompanyUser->getCreatedBy());

        if( $this->db->insert('iafb_remittance.remittance_company_user') )
        {
            $remittanceCompanyUser->setCreatedAt($createdAt);
            return $remittanceCompanyUser;
        }

        return false;
    }
}