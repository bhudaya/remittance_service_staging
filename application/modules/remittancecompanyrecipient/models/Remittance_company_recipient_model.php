<?php

use Iapps\RemittanceService\RemittanceCompanyRecipient\IRemittanceCompanyRecipientDataMapper;
use Iapps\RemittanceService\RemittanceCompanyRecipient\RemittanceCompanyRecipient;
use Iapps\RemittanceService\RemittanceCompanyRecipient\RemittanceCompanyRecipientCollection;
use Iapps\Common\Core\IappsDateTime;

class Remittance_company_recipient_model extends Base_Model
                                    implements IRemittanceCompanyRecipientDataMapper{

    private $sql_query = 'r.id as remittance_company_recipient_id,
                           r.country_code,
                           r.remittance_company_id,
                           r.recipient_id,
                           r.recipient_status_id,
                           stat.code recipient_status_code,
                           stat.display_name recipient_status_name,
                           r.face_to_face_verified_by,
                           r.face_to_face_verified_at,
                           r.created_at,
                           r.created_by,
                           r.updated_at,
                           r.updated_by,
                           r.deleted_at,
                           r.deleted_by';
    private $table_name = 'iafb_remittance.remittance_company_recipient r';


    public function map(\stdClass $data)
    {
        $entity = new RemittanceCompanyRecipient();

        if( isset($data->remittance_company_recipient_id) )
            $entity->setId($data->remittance_company_recipient_id);

        if( isset($data->country_code) )
            $entity->setCountryCode($data->country_code);

        if( isset($data->remittance_company_id) )
            $entity->getRemittanceCompany()->setId($data->remittance_company_id);

        if( isset($data->recipient_id) )
            $entity->getRecipient()->setId($data->recipient_id);

        if( isset($data->recipient_status_id) )
            $entity->getRecipientStatus()->setId($data->recipient_status_id);

        if( isset($data->recipient_status_code) )
            $entity->getRecipientStatus()->setCode($data->recipient_status_code);

        if( isset($data->recipient_status_name) )
            $entity->getRecipientStatus()->setDisplayName($data->recipient_status_name);

        if( isset($data->face_to_face_verified_by) )
            $entity->setFaceToFaceVerifiedBy($data->face_to_face_verified_by);

        if( isset($data->face_to_face_verified_at) )
            $entity->setFaceToFaceVerifiedAt(IappsDateTime::fromUnix($data->face_to_face_verified_at));

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
        $this->db->select($this->sql_query);
        $this->db->from($this->table_name);
        $this->db->join('iafb_remittance.system_code stat', 'r.recipient_status_id = stat.id');
        $this->db->join('iafb_remittance.system_code_group stat_group', 'stat.system_code_group_id = stat_group.id');

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

    public function findByFilter(RemittanceCompanyRecipient $remittanceCompanyRecipient)
    {
        $this->db->select($this->sql_query);
        $this->db->from($this->table_name);
        $this->db->join('iafb_remittance.system_code stat', 'r.recipient_status_id = stat.id');
        $this->db->join('iafb_remittance.system_code_group stat_group', 'stat.system_code_group_id = stat_group.id');

        $this->db->where('r.deleted_at', NULL);

        if( $remittanceCompanyRecipient->getId() )
            $this->db->where('r.remittance_company_recipient_id',  $remittanceCompanyRecipient->getId());

        if( $remittanceCompanyRecipient->getRecipient()->getId() )
            $this->db->where('r.recipient_id',  $remittanceCompanyRecipient->getRecipient()->getId());

        if( $remittanceCompanyRecipient->getRemittanceCompany()->getId() )
            $this->db->where('r.remittance_company_id',  $remittanceCompanyRecipient->getRemittanceCompany()->getId());

        if( $remittanceCompanyRecipient->getRecipientStatus()->getId() )
            $this->db->where('r.recipient_status_id',  $remittanceCompanyRecipient->getRecipientStatus()->getId());

        $query = $this->db->get();
        if( $query->num_rows() > 0 )
        {
            return $this->mapCollection($query->result(), new RemittanceCompanyRecipientCollection(), $query->num_rows());
        }

        return false;
    }

    public function findByFilters(RemittanceCompanyRecipientCollection $filters)
    {
        $this->db->select($this->sql_query);
        $this->db->from($this->table_name);
        $this->db->join('iafb_remittance.system_code stat', 'r.recipient_status_id = stat.id');
        $this->db->join('iafb_remittance.system_code_group stat_group', 'stat.system_code_group_id = stat_group.id');
        $this->db->where('r.deleted_at', NULL);

        $this->db->group_start();
        foreach($filters AS $filter)
        {
            if($filter instanceof RemittanceCompanyRecipient)
            {
                $this->db->or_group_start();

                if( $filter->getId() )
                    $this->db->where('r.id',  $filter->getId());

                if( $filter->getCountryCode() )
                    $this->db->where('r.country_code',  $filter->getCountryCode());

                if( $filter->getRecipient()->getId() )
                    $this->db->where('r.recipient_id',  $filter->getRecipient()->getId());

                if( $filter->getRemittanceCompany()->getId() )
                    $this->db->where('r.remittance_company_id',  $filter->getRemittanceCompany()->getId());

                if( $filter->getRecipientStatus()->getId() )
                    $this->db->where('u.recipient_status_id',  $filter->getRecipientStatus()->getId());

                $this->db->group_end();
            }
        }
        $this->db->group_end();

        $query = $this->db->get();
        if( $query->num_rows() > 0 )
        {
            return $this->mapCollection($query->result(), new RemittanceCompanyRecipientCollection(), $query->num_rows());
        }

        return false;
    }

    public function update(RemittanceCompanyRecipient $remittanceCompanyRecipient, $checkNull = true)
    {
        $updatedAt = IappsDateTime::now();

        if( $remittanceCompanyRecipient->getRecipientStatus()->getId() OR !$checkNull)
            $this->db->set('recipient_status_id', $remittanceCompanyRecipient->getRecipientStatus()->getId());

        if( $remittanceCompanyRecipient->getFaceToFaceVerifiedBy() OR !$checkNull)
            $this->db->set('face_to_face_verified_by', $remittanceCompanyRecipient->getFaceToFaceVerifiedBy());

        if( !$remittanceCompanyRecipient->getFaceToFaceVerifiedAt()->isNull() OR !$checkNull)
            $this->db->set('face_to_face_verified_at', $remittanceCompanyRecipient->getFaceToFaceVerifiedAt()->getUnix());

        $this->db->set('updated_at', $updatedAt->getUnix());
        $this->db->set('updated_by', $remittanceCompanyRecipient->getUpdatedBy());

        $this->db->where('id', $remittanceCompanyRecipient->getId());
        if( $this->db->update('iafb_remittance.remittance_company_recipient') )
        {
            $remittanceCompanyRecipient->setUpdatedAt($updatedAt);
            return $remittanceCompanyRecipient;
        }

        return false;
    }

    public function insert(RemittanceCompanyRecipient $remittanceCompanyRecipient)
    {
        $createdAt = IappsDateTime::now();

        $this->db->set('id', $remittanceCompanyRecipient->getId());
        $this->db->set('country_code', $remittanceCompanyRecipient->getCountryCode());
        $this->db->set('remittance_company_id', $remittanceCompanyRecipient->getRemittanceCompany()->getId());
        $this->db->set('recipient_id', $remittanceCompanyRecipient->getRecipient()->getId());
        $this->db->set('recipient_status_id', $remittanceCompanyRecipient->getRecipientStatus()->getId());
        $this->db->set('face_to_face_verified_by', $remittanceCompanyRecipient->getFaceToFaceVerifiedBy());
        $this->db->set('face_to_face_verified_at', $remittanceCompanyRecipient->getFaceToFaceVerifiedAt()->getUnix());
        $this->db->set('created_at', $createdAt->getUnix());
        $this->db->set('created_by', $remittanceCompanyRecipient->getCreatedBy());

        if( $this->db->insert('iafb_remittance.remittance_company_recipient') )
        {
            $remittanceCompanyRecipient->setCreatedAt($createdAt);
            return $remittanceCompanyRecipient;
        }

        return false;
    }
}