<?php

use Iapps\Common\CorporateService\ICorporateServiceMapper;
use Iapps\Common\Core\IappsDateTime;
use Iapps\RemittanceService\RemittanceCorporateService\RemittanceCorporateService;
use Iapps\Common\CorporateService\CorporateService;
use Iapps\RemittanceService\RemittanceCorporateService\RemittanceCorporateServMapper;
use Iapps\RemittanceService\RemittanceCorporateService\RemittanceCorporateServiceCollection;
use Iapps\RemittanceService\Common\TransactionType;

class Corporate_service_model extends Base_Model implements RemittanceCorporateServMapper{

    public function map(stdClass $data)
    {
        $entity = new RemittanceCorporateService();

        if( isset($data->id) )
            $entity->setId($data->id);

        if( isset($data->country_code) )
            $entity->setCountryCode($data->country_code);

        if( isset($data->service_provider_id) )
            $entity->setServiceProviderId($data->service_provider_id);

        if( isset($data->name) )
            $entity->setName($data->name);

        if( isset($data->description) )
            $entity->setDescription($data->description);

        if( isset($data->transaction_type_id) )
            $entity->setTransactionTypeId($data->transaction_type_id);

        if( isset($data->transaction_type_code) )
            $entity->getTransactionType()->setCode($data->transaction_type_code);

        if( isset($data->transaction_type_name) )
            $entity->getTransactionType()->setDisplayName($data->transaction_type_name);

        if( isset($data->transaction_type_group_id) )
            $entity->getTransactionType()->getGroup()->setId($data->transaction_type_group_id);

        if( isset($data->transaction_type_group_code))
            $entity->getTransactionType()->getGroup()->setCode($data->transaction_type_group_code);

        if( isset($data->transaction_type_group_name))
            $entity->getTransactionType()->getGroup()->setDisplayName($data->transaction_type_group_name);

        if( isset($data->country_currency_code) )
            $entity->setCountryCurrencyCode($data->country_currency_code);

        if( isset($data->daily_limit) )
            $entity->setDailyLimit($data->daily_limit);

        if( isset($data->conversion_remittance_service_id) )
            $entity->getConversionRemittanceService()->setId($data->conversion_remittance_service_id);

        if( isset($data->exchange_rate_id) )
            $entity->setExchangeRateId($data->exchange_rate_id);

        if( isset($data->exchange_rate) )
            $entity->setExchangeRate($data->exchange_rate);

        if( isset($data->margin) )
            $entity->setMargin($data->margin);

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
        $this->db->select('*');

        $this->db->from('iafb_remittance.corporate_service');
        $this->db->where('deleted_at', NULL);
        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit

        $this->db->limit($limit, $offset);
        $query = $this->db->get();
        $this->db->flush_cache();
        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new RemittanceCorporateServiceCollection(), $total);
        }

        return false;
    }
    
    public function findById($id, $deleted = false)
    {
        $this->db->start_cache(); //to cache active record query
        $this->db->select('*');

        $this->db->from('iafb_remittance.corporate_service');
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

    public function findByServiceProviderIds(array $service_provider_ids)
    {
        $this->db->select('cs.id,
                           cs.country_code,
                           cs.service_provider_id,
                           cs.name,
                           cs.description,
                           cs.transaction_type_id,
                           tsc.code as transaction_type_code,
                           tsc.display_name as transaction_type_name,
                           tscg.id as transaction_type_group_id,
                           tscg.code as transaction_type_group_code,
                           tscg.display_name as transaction_type_group_name,
                           cs.country_currency_code,
                           cs.daily_limit,
                           cs.conversion_remittance_service_id,
                           cs.exchange_rate_id,
                           cs.exchange_rate,
                           cs.margin,
                           cs.created_at,
                           cs.created_by,
                           cs.updated_at,
                           cs.updated_by,
                           cs.deleted_at,
                           cs.deleted_by');

        $this->db->from('iafb_remittance.corporate_service cs');
        $this->db->join('iafb_remittance.system_code tsc', 'cs.transaction_type_id = tsc.id');
        $this->db->join('iafb_remittance.system_code_group tscg', 'tsc.system_code_group_id = tscg.id');
        $this->db->where('cs.deleted_at', NULL);
        $this->db->where_in('cs.service_provider_id', $service_provider_ids);
        $this->db->where('tscg.code', TransactionType::getSystemGroupCode());

        $query = $this->db->get();

        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new RemittanceCorporateServiceCollection(), $query->num_rows());
        }

        return false;
    }

    public function insert(CorporateService $serv){

        if( $serv instanceof RemittanceCorporateService )
        {
            $this->db->set('id', $serv->getId());
            $this->db->set('country_code', $serv->getCountryCode());
            $this->db->set('service_provider_id', $serv->getServiceProviderId());
            $this->db->set('name', $serv->getName());
            $this->db->set('description', $serv->getDescription());
            $this->db->set('transaction_type_id', $serv->getTransactionTypeId());
            $this->db->set('country_currency_code', $serv->getCountryCurrencyCode());
            $this->db->set('daily_limit', $serv->getDailyLimit());
            $this->db->set('conversion_remittance_service_id' , $serv->getConversionRemittanceServiceId());
            $this->db->set('created_at', IappsDateTime::now()->getUnix());
            $this->db->set('created_by', $serv->getCreatedBy());

            if( $this->db->insert('iafb_remittance.corporate_service') )
            {
                return true;
            }
        }

        return false;
    }

    public function update(CorporateService $serv){

        if( $serv instanceof RemittanceCorporateService ) {
            //non null value
            if ($serv->getCountryCode() != NULL)
                $this->db->set('country_code', $serv->getCountryCode());
            if ($serv->getServiceProviderId() != NULL)
                $this->db->set('service_provider_id', $serv->getServiceProviderId());
            if ($serv->getName() != NULL)
                $this->db->set('name', $serv->getName());
            if ($serv->getDescription() != NULL)
                $this->db->set('description', $serv->getDescription());
            if ($serv->getCountryCurrencyCode() != NULL)
                $this->db->set('country_currency_code', $serv->getCountryCurrencyCode());
            if ($serv->getDailyLimit() != NULL)
                $this->db->set('daily_limit', $serv->getDailyLimit());
            if ($serv->getConversionRemittanceService()->getId() != NULL)
                $this->db->set('conversion_remittance_service_id', $serv->getConversionRemittanceService()->getId());
            if ($serv->getExchangeRateId() != NULL )
                $this->db->set('exchange_rate_id', $serv->getExchangeRateId());
            if ($serv->getExchangeRate() != NULL )
                $this->db->set('exchange_rate', $serv->getExchangeRate());
            if ($serv->getMargin() != NULL )
                $this->db->set('margin', $serv->getMargin());


            $this->db->set('updated_at', IappsDateTime::now()->getUnix());
            $this->db->set('updated_by', $serv->getUpdatedBy());
            $this->db->where('id', $serv->getId());

            if ($this->db->update('iafb_remittance.corporate_service'))
            {
                return true;
            }
        }

        return false;
    }

    public function delete(CorporateService $serv){
        
    }



    public function findByIds(array $corporate_service_ids)
    {
        $this->db->start_cache(); //to cache active record query
        $this->db->select('*');

        $this->db->from('iafb_remittance.corporate_service');
        $this->db->where('deleted_at', NULL);
        $this->db->stop_cache();
        $this->db->where_in('id' , $corporate_service_ids);

        
        $query = $this->db->get();

        $this->db->flush_cache();
        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new RemittanceCorporateServiceCollection(), count($corporate_service_ids));
        }

        return false;
    }

    public function updateAccumulateAmount($total_net_amount,$item_id)
    {

    }
    
    public function getCorporateServiceByServiceProId($service_provider_id)
    {
        $total = 0;
        $this->db->start_cache(); //to cache active record query
        $this->db->select('*');

        $this->db->from('iafb_remittance.corporate_service');
        $this->db->where('deleted_at', NULL);
        $this->db->where('service_provider_id' , $service_provider_id);

        $this->db->stop_cache();
        $total = $this->db->count_all_results(); //to get total num of result w/o limit

        $query = $this->db->get();

        $this->db->flush_cache();
        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new RemittanceCorporateServiceCollection(), $total);
        }

        return false;
    }

}
