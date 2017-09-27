<?php

use Iapps\RemittanceService\ExchangeRate\ExchangeRate;
use Iapps\RemittanceService\ExchangeRate\IExchangeRateDataMapper;
use Iapps\RemittanceService\ExchangeRate\ExchangeRateCollection;
use Iapps\Common\Core\IappsDateTime;
use Iapps\RemittanceService\Common\ChannelType;

class exchange_rate_model extends Base_Model
                               implements IExchangeRateDataMapper{

    public function map(stdClass $data)
    {
        $entity = new ExchangeRate();
        
        if( isset($data->id) )
            $entity->setId($data->id);

        if( isset($data->corporate_service_id) )
            $entity->setCorporateServiceId($data->corporate_service_id);

        if( isset($data->exchange_rate) )
            $entity->setExchangeRate($data->exchange_rate);

        if( isset($data->margin) )
            $entity->setMargin($data->margin);

        if( isset($data->ref_exchange_rate_id) )
            $entity->getRefExchangeRate()->setId($data->ref_exchange_rate_id);

        if( isset($data->status) )
            $entity->setStatus($data->status);

        if( isset($data->approve_reject_remark) )
            $entity->setApproveRejectRemark($data->approve_reject_remark);

        if( isset($data->approve_reject_at) )
            $entity->getApproveRejectAt()->setDateTimeUnix($data->approve_reject_at);

        if( isset($data->approve_reject_by) )
            $entity->setApproveRejectBy($data->approve_reject_by);

        if( isset($data->approved_rate) )
            $entity->setApproveRate($data->approved_rate);

        if( isset($data->is_active) )
            $entity->setIsActive($data->is_active);

        if( isset($data->channel_id) )
            $entity->getChannel()->setId($data->channel_id);

        if( isset($data->channel_code) )
            $entity->getChannel()->setCode($data->channel_code);

        if( isset($data->channel_name) )
            $entity->getChannel()->setDisplayName($data->channel_name);

        if( isset($data->channel_desc) )
            $entity->getChannel()->setDescription($data->channel_desc);

        if( isset($data->channel_group_id) )
            $entity->getChannel()->getGroup()->setId($data->channel_group_id);

        if( isset($data->channel_group_code) )
            $entity->getChannel()->getGroup()->setCode($data->channel_group_code);

        if( isset($data->channel_group_name) )
            $entity->getChannel()->getGroup()->setDisplayName($data->channel_group_name);

        if( isset($data->channel_group_desc) )
            $entity->getChannel()->getGroup()->setDescription($data->channel_group_desc);

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

    public function findById($id, $deleted_at = false)
    {
        $this->db->select('e.id,
                           e.corporate_service_id,
                           e.exchange_rate,
                           e.margin,
                           e.ref_exchange_rate_id,
                           e.status,
                           e.approve_reject_remark,
                           e.approve_reject_at,
                           e.approve_reject_by,
                           e.approved_rate,
                           e.is_active,
                           e.channel_id,
                           csc.code as channel_code,
                           csc.display_name as channel_name,
                           csc.description as channel_desc,
                           cscg.id as channel_group_id,
                           cscg.code as channel_group_code,
                           cscg.display_name as channel_group_name,
                           cscg.description as channel_group_desc,
                           e.created_at,
                           e.created_by,
                           e.updated_at,
                           e.updated_by,
                           e.deleted_at,
                           e.deleted_by');
        $this->db->from('iafb_remittance.exchange_rate e');
        $this->db->join('iafb_remittance.system_code csc', 'e.channel_id = csc.id');
        $this->db->join('iafb_remittance.system_code_group cscg', 'csc.system_code_group_id = cscg.id');
        
        if($deleted_at === false){
            $this->db->where('e.deleted_at', NULL);
        }
        $this->db->where('e.id', $id);
        $this->db->where('cscg.code', ChannelType::getSystemGroupCode());

        $query = $this->db->get();
        if( $query->num_rows() > 0 )
        {
            return $this->map($query->row());
        }

        return false;
    }

    public function findByIds(array $ids)
    {
        $this->db->select('e.id,
                           e.corporate_service_id,
                           e.exchange_rate,
                           e.margin,
                           e.ref_exchange_rate_id,
                           e.status,
                           e.approve_reject_remark,
                           e.approve_reject_at,
                           e.approve_reject_by,
                           e.approved_rate,
                           e.is_active,
                           e.channel_id,
                           csc.code as channel_code,
                           csc.display_name as channel_name,
                           csc.description as channel_desc,
                           cscg.id as channel_group_id,
                           cscg.code as channel_group_code,
                           cscg.display_name as channel_group_name,
                           cscg.description as channel_group_desc,
                           e.created_at,
                           e.created_by,
                           e.updated_at,
                           e.updated_by,
                           e.deleted_at,
                           e.deleted_by');

        $this->db->from('iafb_remittance.exchange_rate e');
        $this->db->join('iafb_remittance.system_code csc', 'e.channel_id = csc.id');
        $this->db->join('iafb_remittance.system_code_group cscg', 'csc.system_code_group_id = cscg.id');
        $this->db->where('e.deleted_at', NULL);
        $this->db->where_in('e.id', $ids);

        $query = $this->db->get();

        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new ExchangeRateCollection(), $query->num_rows());
        }

        return false;
    }

    public function findAll($limit, $page)
   {
       $offset = ($page - 1) * $limit;
       $this->db->start_cache(); //to cache active record query
       $this->db->select('e.id,
                           e.corporate_service_id,
                           e.exchange_rate,
                           e.margin,
                           e.ref_exchange_rate_id,
                           e.status,
                           e.approve_reject_remark,
                           e.approve_reject_at,
                           e.approve_reject_by,
                           e.approved_rate,
                           e.is_active,
                           e.channel_id,
                           csc.code as channel_code,
                           csc.display_name as channel_name,
                           csc.description as channel_desc,
                           cscg.id as channel_group_id,
                           cscg.code as channel_group_code,
                           cscg.display_name as channel_group_name,
                           cscg.description as channel_group_desc,
                           e.created_at,
                           e.created_by,
                           e.updated_at,
                           e.updated_by,
                           e.deleted_at,
                           e.deleted_by');

       $this->db->from('iafb_remittance.exchange_rate e');
       $this->db->join('iafb_remittance.system_code csc', 'e.channel_id = csc.id');
       $this->db->join('iafb_remittance.system_code_group cscg', 'csc.system_code_group_id = cscg.id');
       $this->db->where('e.deleted_at', NULL);
       $this->db->stop_cache();

       $total = $this->db->count_all_results(); //to get total num of result w/o limit

       $this->db->limit($limit, $offset);
       $query = $this->db->get();
       $this->db->flush_cache();
       if( $query->num_rows() > 0)
       {
           return $this->mapCollection($query->result(), new ExchangeRateCollection(), $total);
       }

       return false;
   }

    public function findByParam(ExchangeRate $exchangeRate, $limit = NULL, $page = NULL,
                                IappsDateTime $fromCreatedAt = NULL, IappsDateTime $toCreatedAt = NULL)
    {
        $offset = 0;
        if( $limit && $page )
            $offset = ($page - 1) * $limit;
        $this->db->start_cache(); //to cache active record query
        $this->db->select('e.id,
                           e.corporate_service_id,
                           e.exchange_rate,
                           e.margin,
                           e.ref_exchange_rate_id,
                           e.status,
                           e.approve_reject_remark,
                           e.approve_reject_at,
                           e.approve_reject_by,
                           e.approved_rate,
                           e.is_active,
                           e.channel_id,
                           csc.code as channel_code,
                           csc.display_name as channel_name,
                           csc.description as channel_desc,
                           cscg.id as channel_group_id,
                           cscg.code as channel_group_code,
                           cscg.display_name as channel_group_name,
                           cscg.description as channel_group_desc,
                           e.created_at,
                           e.created_by,
                           e.updated_at,
                           e.updated_by,
                           e.deleted_at,
                           e.deleted_by');

        $this->db->from('iafb_remittance.exchange_rate e');
        $this->db->join('iafb_remittance.system_code csc', 'e.channel_id = csc.id');
        $this->db->join('iafb_remittance.system_code_group cscg', 'csc.system_code_group_id = cscg.id');
        $this->db->where('e.deleted_at', NULL);
        if( $exchangeRate->getId() != NULL )
            $this->db->where('e.id', $exchangeRate->getId());
        if( $exchangeRate->getCorporateServiceId() != NULL )
            $this->db->where('e.corporate_service_id', $exchangeRate->getCorporateServiceId());
        if( $exchangeRate->getStatus() )
            $this->db->where('e.status', $exchangeRate->getStatus());
        if( $exchangeRate->getIsActive() )
            $this->db->where('e.is_active', $exchangeRate->getIsActive());
        if( $exchangeRate->getChannel()->getCode() )
            $this->db->where('csc.code', $exchangeRate->getChannel()->getCode());
        if( $exchangeRate->getRefExchangeRate()->getId() )
            $this->db->where('e.ref_exchange_rate_id', $exchangeRate->getRefExchangeRate()->getId());

        if( $fromCreatedAt )
            $this->db->where('e.created_at >=', $fromCreatedAt->getUnix());

        if( $toCreatedAt )
            $this->db->where('e.created_at <=', $toCreatedAt->getUnix());

        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit

        $this->db->limit($limit, $offset);
        $query = $this->db->get();
        $this->db->flush_cache();
        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new ExchangeRateCollection(), $total);
        }

        return false;
    }

    public function findByCorpServIdsAndStatuses(array $corpServIds, array $statuses, array $channels = array(), $limit = NULL, $page = NULL,
                                                 IappsDateTime $fromApprovalAt = NULL, IappsDateTime $toApprovalAt = NULL)
    {
        $offset = 0;
        if( $limit && $page )
            $offset = ($page - 1) * $limit;
        $this->db->start_cache(); //to cache active record query
        $this->db->select('e.id,
                           e.corporate_service_id,
                           e.exchange_rate,
                           e.margin,
                           e.ref_exchange_rate_id,
                           e.status,
                           e.approve_reject_remark,
                           e.approve_reject_at,
                           e.approve_reject_by,
                           e.approved_rate,
                           e.is_active,
                           e.channel_id,
                           csc.code as channel_code,
                           csc.display_name as channel_name,
                           csc.description as channel_desc,
                           cscg.id as channel_group_id,
                           cscg.code as channel_group_code,
                           cscg.display_name as channel_group_name,
                           cscg.description as channel_group_desc,
                           e.created_at,
                           e.created_by,
                           e.updated_at,
                           e.updated_by,
                           e.deleted_at,
                           e.deleted_by');

        $this->db->from('iafb_remittance.exchange_rate e');
        $this->db->join('iafb_remittance.system_code csc', 'e.channel_id = csc.id');
        $this->db->join('iafb_remittance.system_code_group cscg', 'csc.system_code_group_id = cscg.id');
        $this->db->where('e.deleted_at', NULL);
        if( count($corpServIds) > 0 )
            $this->db->where_in('e.corporate_service_id', $corpServIds);
        if( count($statuses) > 0 )
            $this->db->where_in('e.status', $statuses);
        if( count($channels) > 0 )
            $this->db->where_in('csc.code', $channels);

        if( $fromApprovalAt )
            $this->db->where('e.approve_reject_at >=', $fromApprovalAt->getUnix());

        if( $toApprovalAt )
            $this->db->where('e.approve_reject_at <=', $toApprovalAt->getUnix());

        $this->db->order_by('e.created_at', 'desc');
        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit

        $this->db->limit($limit, $offset);
        $query = $this->db->get();
        $this->db->flush_cache();

        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new ExchangeRateCollection(), $total);
        }

        return false;
    }

    public function insert(ExchangeRate $exchangeRate)
    {
        $created_at = IappsDateTime::now();

        $this->db->set('id', $exchangeRate->getId());
        $this->db->set('corporate_service_id', $exchangeRate->getCorporateServiceId());
        $this->db->set('exchange_rate', $exchangeRate->getExchangeRate());
        $this->db->set('margin', $exchangeRate->getMargin());
        $this->db->set('ref_exchange_rate_id', $exchangeRate->getRefExchangeRate()->getId());
        $this->db->set('status', $exchangeRate->getStatus());
        $this->db->set('approve_reject_remark', $exchangeRate->getApproveRejectRemark());
        $this->db->set('approve_reject_at', $exchangeRate->getApproveRejectAt()->getUnix());
        $this->db->set('approve_reject_by', $exchangeRate->getApproveRejectBy());
        $this->db->set('approved_rate', $exchangeRate->getApproveRate());
        $this->db->set('is_active', $exchangeRate->getIsActive());
        $this->db->set('channel_id', $exchangeRate->getChannel()->getId());
        $this->db->set('created_by', $exchangeRate->getCreatedBy());
        $this->db->set('created_at', $created_at->getUnix());

        if( $this->db->insert('iafb_remittance.exchange_rate') )
        {
            $exchangeRate->setCreatedAt($created_at);
            return $exchangeRate;
        }

        return false;
    }

    public function update(ExchangeRate $exchangeRate)
    {
        $updated_at = IappsDateTime::now();

        if( $exchangeRate->getExchangeRate() )
            $this->db->set('exchange_rate', $exchangeRate->getExchangeRate());

        if( $exchangeRate->getMargin() )
            $this->db->set('margin', $exchangeRate->getMargin());

        if( $exchangeRate->getRefExchangeRate()->getId() )
            $this->db->set('ref_exchange_rate_id', $exchangeRate->getRefExchangeRate()->getId());

        if( $exchangeRate->getStatus() )
            $this->db->set('status', $exchangeRate->getStatus());

        if( $exchangeRate->getApproveRejectRemark())
            $this->db->set('approve_reject_remark', $exchangeRate->getApproveRejectRemark());

        if( !$exchangeRate->getApproveRejectAt()->isNull() )
            $this->db->set('approve_reject_at', $exchangeRate->getApproveRejectAt()->getUnix());

        if( $exchangeRate->getApproveRejectBy())
            $this->db->set('approve_reject_by', $exchangeRate->getApproveRejectBy());

        if( $exchangeRate->getApproveRate())
            $this->db->set('approved_rate', $exchangeRate->getApproveRate());

        if( $exchangeRate->getIsActive() !== NULL )
            $this->db->set('is_active', $exchangeRate->getIsActive());

        $this->db->set('updated_by', $exchangeRate->getUpdatedBy());
        $this->db->set('updated_at', $updated_at->getUnix());

        $this->db->where('id', $exchangeRate->getId());
        $this->db->update('iafb_remittance.exchange_rate');

        if( $this->db->affected_rows() > 0 )
        {
            $exchangeRate->setUpdatedAt($updated_at);
            return $exchangeRate;
        }

        return false;
    }
}