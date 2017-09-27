<?php

use Iapps\RemittanceService\RemittanceServiceConfig\RemittanceServiceConfig;
use Iapps\RemittanceService\RemittanceServiceConfig\IRemittanceServiceConfigDataMapper;
use Iapps\RemittanceService\RemittanceServiceConfig\RemittanceServiceConfigCollection;
use Iapps\Common\Core\IappsDateTime;

class remittance_service_model extends Base_Model
                               implements IRemittanceServiceConfigDataMapper{

    public function map(stdClass $data)
    {
        $entity = new RemittanceServiceConfig();

        if( isset($data->remittance_service_id) )
            $entity->setId($data->remittance_service_id);

        if( isset($data->from_country_currency_code) )
            $entity->setFromCountryCurrencyCode($data->from_country_currency_code);

        if( isset($data->to_country_currency_code) )
            $entity->setToCountryCurrencyCode($data->to_country_currency_code);

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
        $this->db->select('id as remittance_service_id,
                           from_country_currency_code,
                           to_country_currency_code,
                           created_at,
                           created_by,
                           updated_at,
                           updated_by,
                           deleted_at,
                           deleted_by');
        $this->db->from('iafb_remittance.remittance_service');
        if(!$deleted)
        {
            $this->db->where('deleted_at', NULL);
        }
        $this->db->where('id', $id);

        $query = $this->db->get();
        if( $query->num_rows() > 0 )
        {
            return $this->map($query->row());
        }

        return false;
    }

    public function findByFromAndToCountryCurrencyCode($from_country_currency_code, $to_country_currency_code)
    {
        $this->db->select('id as remittance_service_id,
                           from_country_currency_code,
                           to_country_currency_code,
                           created_at,
                           created_by,
                           updated_at,
                           updated_by,
                           deleted_at,
                           deleted_by');
        $this->db->from('iafb_remittance.remittance_service');
        $this->db->where('from_country_currency_code', $from_country_currency_code);
        $this->db->where('to_country_currency_code', $to_country_currency_code);
        $this->db->where('deleted_at', NULL);

        $query = $this->db->get();

        if( $query->num_rows() > 0 )
        {
            return $this->map($query->row());
        }

        return false;
    }


    public function findByFromCountryCurrencyList($from_country_currency_list)
    {
        $this->db->select('id as remittance_service_id,
                           from_country_currency_code,
                           to_country_currency_code,
                           created_at,
                           created_by,
                           updated_at,
                           updated_by,
                           deleted_at,
                           deleted_by');
        $this->db->from('iafb_remittance.remittance_service');
        $this->db->where_in('from_country_currency_code', $from_country_currency_list);
        $this->db->where('deleted_at', NULL);

        $query = $this->db->get();
        if( $query->num_rows() > 0 )
        {
            return $this->mapCollection($query->result(), new RemittanceServiceConfigCollection(), $query->num_rows());
        }

        return false;
    }


    public function findAll()
    {
        $this->db->select('id as remittance_service_id,
                           from_country_currency_code,
                           to_country_currency_code,
                           created_at,
                           created_by,
                           updated_at,
                           updated_by,
                           deleted_at,
                           deleted_by');

        $this->db->from('iafb_remittance.remittance_service');
        $this->db->where('deleted_at', NULL);

        $query = $this->db->get();

        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new RemittanceServiceConfigCollection(), $query->num_rows());
        }

        return false;
    }

    public function insert(RemittanceServiceConfig $config)
    {
        $this->db->set('id', $config->getId());
        $this->db->set('from_country_currency_code', $config->getFromCountryCurrencyCode());
        $this->db->set('to_country_currency_code', $config->getToCountryCurrencyCode());
        $this->db->set('created_by', $config->getCreatedBy());
        $this->db->set('created_at', IappsDateTime::now()->getUnix());

        if( $this->db->insert('iafb_remittance.remittance_service') )
        {
            return true;
        }

        return false;
    }

    public function update(RemittanceServiceConfig $config)
    {
//        $this->db->set('markup_on_rate', $config->getMarkupOnRate());
//        $this->db->set('sync_interval', $config->getSyncInterval());
//        $this->db->set('start_time', $config->getStartTime());
        $this->db->set('updated_at', IappsDateTime::now()->getUnix());
        $this->db->set('updated_by', $config->getUpdatedBy());
        $this->db->where('id', $config->getId());

        if( $this->db->update('iafb_remittance.remittance_service') )
        {
            return true;
        }

        return false;
    }

    public function updateRates(RemittanceServiceConfig $config)
    {
//        $this->db->set('exchange_rate_id', $config->getExchangeRateId());
//        $this->db->set('exchange_rate_last_value', $config->getExchangeRateLastValue());
//        $this->db->set('exchange_rate_expiry_date', $config->getExchangeRateExpiryDate()->getUnix());
//        $this->db->set('exchange_rate_last_updated_at', $config->getExchangeRateLastUpdatedAt()->getUnix());
        $this->db->set('updated_at', IappsDateTime::now()->getUnix());
        $this->db->set('updated_by', $config->getUpdatedBy());
        $this->db->where('id', $config->getId());

        if( $this->db->update('iafb_remittance.remittance_service') )
        {
            return true;
        }

        return false;
    }

    public function findByIds(array $corporate_service_ids)
    {
        $this->db->start_cache(); //to cache active record query
        $this->db->select('id as remittance_service_id,
                           from_country_currency_code,
                           to_country_currency_code,
                           created_at,
                           created_by,
                           updated_at,
                           updated_by,
                           deleted_at,
                           deleted_by');

        $this->db->from('iafb_remittance.remittance_service');
        $this->db->where('deleted_at', NULL);
        $this->db->stop_cache();
        $this->db->where_in('id' , $corporate_service_ids);

        $query = $this->db->get();
        
        $this->db->flush_cache();

        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new RemittanceServiceConfigCollection(), count($corporate_service_ids));
        }

        return false;
        
    }


}