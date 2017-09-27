<?php

use Iapps\RemittanceService\RemittanceConfig\IRemittanceConfigDataMapper;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfig;
use Iapps\Common\Core\IappsDateTime;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfigCollection;

class Remittance_config_model extends Base_Model implements IRemittanceConfigDataMapper{

    public function map(stdClass $data)
    {
        $entity = new RemittanceConfig();

        if(isset($data->remittance_config_id)){
            $entity->setId($data->remittance_config_id);
        }

        if(isset($data->remittance_service_id)){
            $entity->setRemittanceServiceId($data->remittance_service_id);
        }

        if(isset($data->min_limit)){
            $entity->setMinLimit($data->min_limit);
        }

        if(isset($data->max_limit)){
            $entity->setMaxLimit($data->max_limit);
        }

        if(isset($data->step_amount)){
            $entity->setStepAmount($data->step_amount);
        }

        if(isset($data->cashin_corporate_service_id)){
            $entity->setCashinCorporateServiceId($data->cashin_corporate_service_id);
        }

        if(isset($data->cashout_corporate_service_id)){
            $entity->setCashoutCorporateServiceId($data->cashout_corporate_service_id);
        }

        if(isset($data->channel_id)){
            $entity->setChannelID($data->channel_id);
        }

        if(isset($data->is_default)){
            $entity->setIsDefault($data->is_default);
        }

        if(isset($data->is_active)){
            $entity->setIsActive($data->is_active);
        }

        if(isset($data->home_collection_enabled)){
            $entity->setHomeCollectionEnabled($data->home_collection_enabled);
        }

        if(isset($data->cashin_expiry_period)){
            $entity->setCashinExpiryPeriod($data->cashin_expiry_period);
        }

        if(isset($data->status)){
            $entity->setStatus($data->status);
        }

        if(isset($data->approve_reject_remark)){
            $entity->setApproveRejectRemark($data->approve_reject_remark);
        }

        if(isset($data->approve_reject_at)){
            $entity->setApproveRejectAt(IappsDateTime::fromUnix($data->approve_reject_at));
        }

        if(isset($data->approve_reject_by)){
            $entity->setApproveRejectBy($data->approve_reject_by);
        }

        if( isset($data->approving_notification_emails) )
            $entity->getApprovingNotificationEmails()->setEncryptedValue($data->approving_notification_emails);

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

    public function findAll($limit, $page)
    {
        $total = 0;
        $offset = ($page - 1) * $limit;

        $this->db->start_cache(); //to cache active record query
        $this->db->select('id as remittance_config_id,
                           channelID as channel_id,
                           cashin_corporate_service_id,
                           cashout_corporate_service_id,
                           remittance_service_id,
                           status,
                           approve_reject_remark,
                           approve_reject_at,
                           approve_reject_by,
                           min_limit,
                           max_limit,
                           step_amount,
                           is_default,
                           is_active,                           
                           home_collection_enabled,
                           cashin_expiry_period,
                           approving_notification_emails,
                           created_at,
                           created_by,
                           updated_at,
                           updated_by,
                           deleted_at,
                           deleted_by');
        $this->db->from('iafb_remittance.remittance_configuration');
        $this->db->where('deleted_at', NULL);
        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit
        $this->db->limit($limit, $offset);
        $query = $this->db->get();
        $this->db->flush_cache();

        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new RemittanceConfigCollection(), $total);
        }

        return false;

    }

    public function findByIdArr(array $remittance_config_id_arr)
    {
        $this->db->select('id as remittance_config_id,
                           channelID as channel_id,
                           cashin_corporate_service_id,
                           cashout_corporate_service_id,
                           remittance_service_id,
                           status,
                           approve_reject_remark,
                           approve_reject_at,
                           approve_reject_by,
                           min_limit,
                           max_limit,
                           step_amount,
                           is_default,
                           is_active,                           
                           home_collection_enabled,
                           cashin_expiry_period,
                           approving_notification_emails,
                           created_at,
                           created_by,
                           updated_at,
                           updated_by,
                           deleted_at,
                           deleted_by');
        $this->db->from('iafb_remittance.remittance_configuration');
        $this->db->where_in('id', $remittance_config_id_arr);
        $this->db->where('deleted_at', NULL);

        $query = $this->db->get();

        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new RemittanceConfigCollection(), $query->num_rows());
        }

        return false;

    }
    
    public function findById($id, $deleted = false)
    {
        $this->db->start_cache(); //to cache active record query
        $this->db->select('id as remittance_config_id,
                           channelID as channel_id,
                           cashin_corporate_service_id,
                           cashout_corporate_service_id,
                           remittance_service_id,
                           status,
                           approve_reject_remark,
                           approve_reject_at,
                           approve_reject_by,
                           min_limit,
                           max_limit,
                           step_amount,
                           is_default,
                           is_active,                           
                           home_collection_enabled,
                           cashin_expiry_period,
                           approving_notification_emails,
                           created_at,
                           created_by,
                           updated_at,
                           updated_by,
                           deleted_at,
                           deleted_by');
        $this->db->from('iafb_remittance.remittance_configuration');
        $this->db->where('id', $id);
        if(!$deleted)
        {
            $this->db->where('deleted_at', NULL);
        }
        $this->db->stop_cache();

        $query = $this->db->get();
        $this->db->flush_cache();
        if( $query->num_rows() > 0)
        {
            return $this->map($query->row());
        }

        return false;

    }

    public function findBySearchFilter(RemittanceConfig $remittanceConfig, $limit = NULL , $page = NULL)
    {

        $total = 0;
        if ($limit != NULL &&  $page != NULL) {
            $offset = ($page - 1) * $limit;
        }

        $this->db->start_cache(); //to cache active record query
        $this->db->select('id as remittance_config_id,
                           channelID as channel_id,
                           cashin_corporate_service_id,
                           cashout_corporate_service_id,
                           remittance_service_id,
                           status,
                           approve_reject_remark,
                           approve_reject_at,
                           approve_reject_by,
                           min_limit,
                           max_limit,
                           step_amount,
                           is_default,
                           is_active,                           
                           home_collection_enabled,
                           cashin_expiry_period,
                           approving_notification_emails,
                           created_at,
                           created_by,
                           updated_at,
                           updated_by,
                           deleted_at,
                           deleted_by');
        $this->db->from('iafb_remittance.remittance_configuration');

        if($remittanceConfig->getId())
        {
            $this->db->where('id', $remittanceConfig->getId());
        }


        if($remittanceConfig->getRemittanceServiceId())
        {
            $this->db->where('remittance_service_id', $remittanceConfig->getRemittanceServiceId());
        }

        if((bool)$remittanceConfig->getIsDefault())
        {
            $this->db->where('is_default', $remittanceConfig->getIsDefault());
        }

        if($remittanceConfig->getIsActive())
        {
            $this->db->where('is_active', $remittanceConfig->getIsActive());
        }

        if($remittanceConfig->getStatus())
        {
            $this->db->where('status', $remittanceConfig->getStatus());
        }

        if ($remittanceConfig->getCashInCorporateServiceId() && !is_null($remittanceConfig->getCashInCorporateServiceId())) {
            $this->db->where('cashin_corporate_service_id', $remittanceConfig->getCashInCorporateServiceId());
        }

        if ($remittanceConfig->getCashOutCorporateServiceId() && !is_null($remittanceConfig->getCashOutCorporateServiceId())) {
            $this->db->where('cashout_corporate_service_id', $remittanceConfig->getCashOutCorporateServiceId());
        }

        $this->db->where('deleted_at', NULL);
        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit
        if ($limit != NULL &&  $page != NULL) {
            $this->db->limit($limit, $offset);
        }
        $query = $this->db->get();
        $this->db->flush_cache();

        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new RemittanceConfigCollection(), $total);
        }

        return false;
    }

    public function findByRemittanceServiceIds(array $remittanceServiceIds, RemittanceConfig $configFilter, $limit = NULL, $page = NULL)
    {
        if ($limit != NULL &&  $page != NULL) {
            $offset = ($page - 1) * $limit;
        }

        $this->db->start_cache(); //to cache active record query
        $this->db->select('id as remittance_config_id,
                           channelID as channel_id,
                           cashin_corporate_service_id,
                           cashout_corporate_service_id,
                           remittance_service_id,
                           status,
                           approve_reject_remark,
                           approve_reject_at,
                           approve_reject_by,
                           min_limit,
                           max_limit,
                           step_amount,
                           is_default,
                           is_active,                           
                           home_collection_enabled,
                           cashin_expiry_period,
                           approving_notification_emails,
                           created_at,
                           created_by,
                           updated_at,
                           updated_by,
                           deleted_at,
                           deleted_by');
        $this->db->from('iafb_remittance.remittance_configuration');
        $this->db->where_in('remittance_service_id', $remittanceServiceIds);
        $this->db->where('deleted_at', NULL);

        if($configFilter->getId())
            $this->db->where('id', $configFilter->getId());

        if((bool)$configFilter->getIsDefault())
            $this->db->where('is_default', $configFilter->getIsDefault());

        if($configFilter->getIsActive())
            $this->db->where('is_active', $configFilter->getIsActive());

        if($configFilter->getStatus())
            $this->db->where('status', $configFilter->getStatus());

        if($configFilter->getInCorporateService()->getId())
            $this->db->where('cashin_corporate_service_id', $configFilter->getInCorporateService()->getId());

        if($configFilter->getOutCorporateService()->getId())
            $this->db->where('cashout_corporate_service_id', $configFilter->getCashOutCorporateServiceId());

        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit
        if ($limit != NULL &&  $page != NULL) {
            $this->db->limit($limit, $offset);
        }
        $query = $this->db->get();
        $this->db->flush_cache();

        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new RemittanceConfigCollection(), $total);
        }

        return false;
    }

    public function insert(RemittanceConfig $config)
    {
        $this->db->set('id', $config->getId());
        $this->db->set('cashin_corporate_service_id', $config->getCashinCorporateServiceId());
        $this->db->set('cashout_corporate_service_id', $config->getCashoutCorporateServiceId());
        $this->db->set('remittance_service_id', $config->getRemittanceServiceId());
        $this->db->set('channelID' , $config->getChannelID());
        $this->db->set('status' , $config->getStatus());
        $this->db->set('approve_reject_remark' , $config->getApproveRejectRemark());
        $this->db->set('approve_reject_at' , $config->getApproveRejectAt());
        $this->db->set('approve_reject_by' , $config->getApproveRejectBy());        
        $this->db->set('home_collection_enabled' , $config->getHomeCollectionEnabled());
        $this->db->set('cashin_expiry_period' , $config->getCashinExpiryPeriod());
        $this->db->set('min_limit', $config->getMinLimit());
        $this->db->set('max_limit', $config->getMaxLimit());
        $this->db->set('step_amount', $config->getStepAmount());
        $this->db->set('is_default', $config->getIsDefault());
        $this->db->set('is_active', $config->getIsActive());
        $this->db->set('approving_notification_emails', $config->getApprovingNotificationEmails()->getEncodedValue());
        $this->db->set('created_at', IappsDateTime::now()->getUnix());
        $this->db->set('created_by', $config->getCreatedBy());

        if( $this->db->insert('iafb_remittance.remittance_configuration') )
        {
            return true;
        }

        return false;
    }

    public function update(RemittanceConfig $config){
        //non null value
        //if( $config->getRemittanceServiceId() != NULL)
        //    $this->db->set('remittance_service_id', $config->getRemittanceServiceId());
        if( $config->getMinLimit() != NULL)
            $this->db->set('min_limit', $config->getMinLimit());
        if( $config->getMaxLimit() != NULL)
            $this->db->set('max_limit', $config->getMaxLimit());
        if ($config->getStepAmount() != NULL)
            $this->db->set('step_amount', $config->getStepAmount());
        if ($config->getIsDefault() != NULL)
            $this->db->set('is_default', $config->getIsDefault());
        if ($config->getIsActive() != NULL)
            $this->db->set('is_active', $config->getIsActive());        
        if ($config->getHomeCollectionEnabled() !== NULL)
            $this->db->set('home_collection_enabled' , $config->getHomeCollectionEnabled());

        $this->db->set('cashin_expiry_period' , $config->getCashinExpiryPeriod());

        if ( count($config->getApprovingNotificationEmailsArray()) > 0 )
            $this->db->set('approving_notification_emails', $config->getApprovingNotificationEmails()->getEncodedValue());

        $this->db->set('updated_at', IappsDateTime::now()->getUnix());
        $this->db->set('updated_by', $config->getUpdatedBy());
        $this->db->where('id', $config->getId());

        if ($this->db->update('iafb_remittance.remittance_configuration')) {
            return true;
        }

        return false;
    }

    public function updateStatus(RemittanceConfig $config)
    {
        $this->db->set('status', $config->getStatus());
        $this->db->set('approve_reject_remark', $config->getApproveRejectRemark());
        $this->db->set('approve_reject_at', IappsDateTime::now()->getUnix());
        $this->db->set('approve_reject_by', $config->getUpdatedBy());
        if ($config->getIsActive() != NULL)
            $this->db->set('is_active', $config->getIsActive());
        $this->db->set('updated_at', IappsDateTime::now()->getUnix());
        $this->db->set('updated_by', $config->getUpdatedBy());
        $this->db->where('id', $config->getId());

        if ($this->db->update('iafb_remittance.remittance_configuration')) {
            return true;
        }

        return false;
    }
    
    public function findByCorporateServiceIds(array $cashInCorporateServiceIds = NULL, array $cashOutCorporateServiceIds = NULL, RemittanceConfig $configFilter = NULL, $limit = NULL, $page = NULL )
    {
        if ($limit != NULL &&  $page != NULL) {
            $offset = ($page - 1) * $limit;
        }

        $this->db->start_cache(); //to cache active record query
        $this->db->select('id as remittance_config_id,
                           channelID as channel_id,
                           cashin_corporate_service_id,
                           cashout_corporate_service_id,
                           remittance_service_id,
                           status,
                           approve_reject_remark,
                           approve_reject_at,
                           approve_reject_by,
                           min_limit,
                           max_limit,
                           step_amount,
                           is_default,
                           is_active,                           
                           home_collection_enabled,
                           cashin_expiry_period,
                           approving_notification_emails,
                           created_at,
                           created_by,
                           updated_at,
                           updated_by,
                           deleted_at,
                           deleted_by');
        $this->db->from('iafb_remittance.remittance_configuration');
        $this->db->where('deleted_at', NULL);
        
        if( ($cashInCorporateServiceIds !== NULL AND count($cashInCorporateServiceIds) > 0) ||
            ($cashOutCorporateServiceIds !== NULL AND count($cashOutCorporateServiceIds) > 0) )
        {
            $this->db->group_start();
            if($cashInCorporateServiceIds !== NULL && count($cashInCorporateServiceIds) > 0)
            {
                $this->db->where_in('cashin_corporate_service_id', $cashInCorporateServiceIds);
            }
            
            if($cashOutCorporateServiceIds !== NULL && count($cashOutCorporateServiceIds) > 0 )
            {
                $this->db->or_where_in('cashout_corporate_service_id', $cashOutCorporateServiceIds);
            }
            $this->db->group_end();
        }
        
        if($configFilter != NULL && $configFilter->getId() !== NULL)
            $this->db->where('id', $configFilter->getId());
        
        if($configFilter != NULL && (bool)$configFilter->getIsDefault())
            $this->db->where('is_default', $configFilter->getIsDefault());

        if($configFilter != NULL && (bool)$configFilter->getIsActive())
            $this->db->where('is_active', $configFilter->getIsActive());

        if($configFilter != NULL && $configFilter->getStatus())
            $this->db->where('status', $configFilter->getStatus());

        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit
        if ($limit != NULL &&  $page != NULL) {
            $this->db->limit($limit, $offset);
        }
        $query = $this->db->get();
        $this->db->flush_cache();

        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new RemittanceConfigCollection(), $total);
        }

        return false;
    }

    public function findExists($limit, $page, $remittanceConfigId = NULL, $cashInCountryCurrencyCode, $cashOutCountryCurrencyCode, $cashInCountryPartnerId, $cashOutCountryPartnerId, array $status = NULL)
    {
        $total = 0;
        $offset = ($page - 1) * $limit;

        $this->db->start_cache(); //to cache active record query
        $this->db->select('rc.id as remittance_config_id,
                           rc.channelID as channel_id,
                           rc.cashin_corporate_service_id,
                           rc.cashout_corporate_service_id,
                           rc.remittance_service_id,
                           rc.status,
                           rc.approve_reject_remark,
                           rc.approve_reject_at,
                           rc.approve_reject_by,
                           rc.min_limit,
                           rc.max_limit,
                           rc.step_amount,
                           rc.is_default,
                           rc.is_active,                           
                           rc.home_collection_enabled,
                           rc.cashin_expiry_period,
                           rc.approving_notification_emails,
                           rc.created_at,
                           rc.created_by,
                           rc.updated_at,
                           rc.updated_by,
                           rc.deleted_at,
                           rc.deleted_by');
        $this->db->from('iafb_remittance.remittance_configuration as rc');
        $this->db->join('iafb_remittance.corporate_service as in_corp','in_corp.id = rc.cashin_corporate_service_id');
        $this->db->join('iafb_remittance.corporate_service as out_corp','out_corp.id = rc.cashout_corporate_service_id');
        $this->db->where('rc.deleted_at',NULL);
        if($remittanceConfigId !== NULL)
            $this->db->where('rc.id', $remittanceConfigId);
        if($cashInCountryCurrencyCode !== NULL)
            $this->db->where('in_corp.country_currency_code', $cashInCountryCurrencyCode);
        if($cashInCountryPartnerId !== NULL)
            $this->db->where('in_corp.service_provider_id', $cashInCountryPartnerId);
        if($cashOutCountryCurrencyCode !== NULL)
            $this->db->where('out_corp.country_currency_code', $cashOutCountryCurrencyCode);
        if($cashOutCountryPartnerId !== NULL)
            $this->db->where('out_corp.service_provider_id', $cashOutCountryPartnerId);
        if($status !== NULL && is_array($status) && count($status) > 0)
            $this->db->where_in('rc.status', $status);
        
        $this->db->stop_cache();
        
        $total = $this->db->count_all_results(); //to get total num of result w/o limit
        if ($limit != NULL &&  $page != NULL) {
            $this->db->limit($limit, $offset);
        }
        $query = $this->db->get();
        $this->db->flush_cache();
        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new RemittanceConfigCollection(), $total);
        }

        return false;
    }
}