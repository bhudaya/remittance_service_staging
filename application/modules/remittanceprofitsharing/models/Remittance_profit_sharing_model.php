<?php

use Iapps\RemittanceService\RemittanceProfitSharing\RemittanceCorpServProfitSharingDataMapper;
use Iapps\RemittanceService\RemittanceProfitSharing\RemittanceCorpServProfitSharing;
use Iapps\RemittanceService\RemittanceProfitSharing\RemittanceCorpServProfitSharingStatus;
use Iapps\RemittanceService\RemittanceProfitSharing\RemittanceCorpServProfitSharingCollection;

use Iapps\Common\Core\IappsDateTime;

class Remittance_profit_sharing_model extends Base_Model
                       implements RemittanceCorpServProfitSharingDataMapper{

    public function map(\stdClass $data)
    {
        $entity = new RemittanceCorpServProfitSharing();

        if( isset($data->id) )
            $entity->setId($data->id);

        if( isset($data->corporate_service_id) )
            $entity->setCorporateServiceId($data->corporate_service_id);

        if( isset($data->status) )
            $entity->setStatus($data->status);

        if( isset($data->approve_reject_remark) )
            $entity->setApproveRejectRemark($data->approve_reject_remark);

        if( isset($data->approve_reject_at) )
            $entity->setApproveRejectAt(IappsDateTime::fromUnix($data->approve_reject_at));

        if( isset($data->approve_reject_by) )
            $entity->setApproveRejectBy($data->approve_reject_by);

        if( isset($data->is_active) )
            $entity->setIsActive($data->is_active);

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
            $entity->setDeletedBy($data->deleted_b);

        return $entity;
    }

    public function findById($id, $deleted = false)
    {   
        $this->db->start_cache();
        $this->db->select('*');
        $this->db->from('iafb_remittance.corporate_service_profit_sharing csps');
        $this->db->where('id', $id);
        $this->db->where('deleted_at', NULL);
        $this->db->stop_cache();

        $query = $this->db->get();
        $this->db->flush_cache();

        if( $query->num_rows() > 0 )
        {   
            return $this->map($query->row());
            // return $this->mapCollection($query->result(), new RemittanceCorpServProfitSharingCollection(), $query->num_rows());
        }

        return false;
    }

    public function checkHasOtherPendingProfitSharing($corporate_service_id)
    {
        $this->db->select('*');
        $this->db->from('iafb_remittance.corporate_service_profit_sharing csps');
        $this->db->where('csps.corporate_service_id', $corporate_service_id);
        $this->db->where('status', RemittanceCorpServProfitSharingStatus::PENDING);
        $this->db->where('deleted_at', NULL);

        $query = $this->db->get();
        if( $query->num_rows() > 0 )
        {
            return $this->map($query->row());
        }

        return false;
    }

    public function findAllByStatus($collection, $limit, $page, $is_active = NULL, $status = NULL, $corporate_service_id = NULL)
    {
        $total = 0;
        $offset = ($page - 1) * $limit;

        $this->db->start_cache();
        $this->db->select('*');
        $this->db->from('iafb_remittance.corporate_service_profit_sharing csps');
        if ($status && !is_null($status)) {
            $this->db->where('status', $status);
        }
        if ($is_active && !is_null($is_active)) {
            $this->db->where('is_active', $is_active);
        }
        if ($corporate_service_id && !is_null($corporate_service_id)) {
            $this->db->where('corporate_service_id', $corporate_service_id);
        }

        $this->db->where('deleted_at', NULL);
        $this->db->order_by('created_at', 'desc');
        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit
        $this->db->limit($limit, $offset);
        $query = $this->db->get();
        $this->db->flush_cache();

        if( $query->num_rows() > 0 )
        {
            return $this->mapCollection($query->result(), $collection, $total);
        }

        return false;
    }

    public function findByParam(RemittanceCorpServProfitSharing $corp_serv_profit_sharing, $limit = NULL, $page = NULL)
    {
        $total = 0;
        if( $limit AND $page )
        {
            $offset = ($page - 1) * $limit;
            $this->db->start_cache();
        }

        $this->db->select('*');
        $this->db->from('iafb_remittance.corporate_service_profit_sharing csps');
        if( $corp_serv_profit_sharing->getId() )
            $this->db->where('id', $corp_serv_profit_sharing->getId());
        if( $corp_serv_profit_sharing->getStatus() )
            $this->db->where('status', $corp_serv_profit_sharing->getStatus());
        if( $corp_serv_profit_sharing->getIsActive() )
            $this->db->where('is_active', $corp_serv_profit_sharing->getIsActive());
        if( $corp_serv_profit_sharing->getCorporateServiceId() )
            $this->db->where('corporate_service_id', $corp_serv_profit_sharing->getCorporateServiceId());

        $this->db->where('deleted_at', NULL);

        if( $limit AND $page )
        {
            $this->db->stop_cache();
            $total = $this->db->count_all_results(); //to get total num of result w/o limit
            $this->db->limit($limit, $offset);
        }

        $query = $this->db->get();

        if( $limit AND $page )
            $this->db->flush_cache();
        else
            $total = $query->num_rows();

        if( $query->num_rows() > 0 )
        {
            return $this->mapCollection($query->result(), new RemittanceCorpServProfitSharingCollection(), $total);
        }

        return false;
    }

    public function insert(RemittanceCorpServProfitSharing $corp_serv_profit_sharing)
    {
        $this->db->set('id', $corp_serv_profit_sharing->getId());
        $this->db->set('corporate_service_id', $corp_serv_profit_sharing->getCorporateServiceId());
        $this->db->set('status', $corp_serv_profit_sharing->getStatus());
        $this->db->set('is_active', $corp_serv_profit_sharing->getIsActive());
        $this->db->set('created_at', IappsDateTime::now()->getUnix());
        $this->db->set('created_by', $corp_serv_profit_sharing->getCreatedBy());

        if( $this->db->insert('iafb_remittance.corporate_service_profit_sharing') )
        {
            return true;
        }

        return false;
    }

    public function update(RemittanceCorpServProfitSharing $corp_serv_profit_sharing)
    {
        $this->db->set('status', $corp_serv_profit_sharing->getStatus());
        $this->db->set('approve_reject_remark', $corp_serv_profit_sharing->getApproveRejectRemark());

        if ($corp_serv_profit_sharing->getApproveRejectAt()) {
            $this->db->set('approve_reject_at', $corp_serv_profit_sharing->getApproveRejectAt()->getUnix());
        }
        if ($corp_serv_profit_sharing->getApproveRejectBy()) {
            $this->db->set('approve_reject_by', $corp_serv_profit_sharing->getApproveRejectBy());
        }

        $this->db->set('is_active', $corp_serv_profit_sharing->getIsActive());
        $this->db->set('updated_at', IappsDateTime::now()->getUnix());
        $this->db->set('updated_by', $corp_serv_profit_sharing->getUpdatedBy());
        $this->db->where('id', $corp_serv_profit_sharing->getId());

        if( $this->db->update('iafb_remittance.corporate_service_profit_sharing') )
        {
            return true;
        }

        return false;
    }
    
    public function findAllList($limit, $page, array $corporateServiceIds = NULL, $isActive = NULL, array $status = NULL)
    {
        $total = 0;
        $offset = ($page - 1) * $limit;

        $this->db->start_cache();
        $this->db->select('*');
        $this->db->from('iafb_remittance.corporate_service_profit_sharing csps');
        
        if($corporateServiceIds !== NULL)
            $this->db->where_in('corporate_service_id', $corporateServiceIds);
        if($isActive !== NULL)
            $this->db->where('is_active', $isActive);
        if($status !== NULL && is_array($status) && count($status) > 0)
            $this->db->where_in('status', $status);
        
        $this->db->where('deleted_at', NULL);
        $this->db->order_by('created_at', 'desc');
        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit
        $this->db->limit($limit, $offset);
        $query = $this->db->get();
        $this->db->flush_cache();

        if( $query->num_rows() > 0 )
        {
            return $this->mapCollection($query->result(), new RemittanceCorpServProfitSharingCollection(), $total);
        }

        return false;
    }
}