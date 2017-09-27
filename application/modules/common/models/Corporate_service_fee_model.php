<?php

use Iapps\Common\CorporateService\ICorporateServiceFeeMapper;
use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\CorporateService\CorporateServiceFee;
use Iapps\Common\CorporateService\CorporateServiceFeeCollection;

class Corporate_service_fee_model extends Base_Model implements ICorporateServiceFeeMapper{

    public function map(stdClass $data)
    {
        $entity = new CorporateServiceFee();

        if( isset($data->id) )
            $entity->setId($data->id);

        if( isset($data->corporate_service_id) )
            $entity->setCorporateServiceId($data->corporate_service_id);

        if( isset($data->service_provider_id) )
            $entity->setServiceProviderId($data->service_provider_id);

        if( isset($data->fee_type_id) )
            $entity->setFeeTypeId($data->fee_type_id);

        if( isset($data->name) )
            $entity->setName($data->name);

        if( isset($data->transaction_fee) )
            $entity->setTransactionFee($data->transaction_fee);

        if( isset($data->is_percentage) )
            $entity->setIsPercentage( $data->is_percentage );

        if( isset($data->original_transaction_fee) )
            $entity->setOriginalTransactionFee($data->original_transaction_fee);

        if( isset($data->original_country_currency_code) )
            $entity->setOriginalCountryCurrencyCode($data->original_country_currency_code);
        
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

    public function insert(CorporateServiceFee $serv){
        $this->db->set('id', $serv->getId());
        $this->db->set('corporate_service_id', $serv->getCorporateServiceId());
        $this->db->set('service_provider_id', $serv->getServiceProviderId());
        $this->db->set('is_percentage', $serv->getIsPercentage());
        $this->db->set('transaction_fee', $serv->getTransactionFee());
        $this->db->set('fee_type_id', $serv->getFeeTypeId());
        $this->db->set('name', $serv->getName());
        $this->db->set('original_transaction_fee', $serv->getOriginalTransactionFee());
        $this->db->set('original_country_currency_code', $serv->getOriginalCountryCurrencyCode());
        $this->db->set('created_at', IappsDateTime::now()->getUnix());
        $this->db->set('created_by', $serv->getCreatedBy());

        if( $this->db->insert('iafb_remittance.corporate_service_fee') )
        {
            return true;
        }

        return false;
    }


    public function findById($id, $deleted = false){
        
        $this->db->start_cache(); //to cache active record query
        $this->db->select('id,
                           corporate_service_id,
                           service_provider_id,
                           fee_type_id,
                           name,
                           transaction_fee,
                           is_percentage,
                           original_transaction_fee,
                           original_country_currency_code');
        $this->db->from('iafb_remittance.corporate_service_fee');
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

    public function findAllByCorporateServiceId($corporate_service_id,$limit, $page)
    {
        $offset = ($page - 1) * $limit;
        $this->db->start_cache(); //to cache active record query
        $this->db->select('id,
                           corporate_service_id,
                           service_provider_id,
                           fee_type_id,
                           name,
                           transaction_fee,
                           is_percentage,
                           original_transaction_fee,
                           original_country_currency_code');
        $this->db->from('iafb_remittance.corporate_service_fee');
        $this->db->where('corporate_service_id', $corporate_service_id);
        $this->db->where('deleted_at', NULL);
        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit

        $this->db->limit($limit, $offset);
        $query = $this->db->get();
        $this->db->flush_cache();
        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new CorporateServiceFeeCollection(), $total);
        }

        return false;
    }

    public function update(CorporateServiceFee $CorporateServiceFee){
        //non null value
        if( $CorporateServiceFee->getCorporateServiceId() != NULL)
            $this->db->set('corporate_service_id', $CorporateServiceFee->getCorporateServiceId());
        if( $CorporateServiceFee->getServiceProviderId() != NULL)
            $this->db->set('service_provider_id', $CorporateServiceFee->getServiceProviderId());
        if( $CorporateServiceFee->getIsPercentage() != NULL)
            $this->db->set('is_percentage', $CorporateServiceFee->getIsPercentage());
        if( $CorporateServiceFee->getTransactionFee() != NULL)
            $this->db->set('transaction_fee', $CorporateServiceFee->getTransactionFee());
        if( $CorporateServiceFee->getFeeTypeId() != NULL )
            $this->db->set('fee_type_id', $CorporateServiceFee->getFeeTypeId());
        if( $CorporateServiceFee->getName() != NULL )
            $this->db->set('name', $CorporateServiceFee->getName());
        if( $CorporateServiceFee->getOriginalTransactionFee() != NULL )
            $this->db->set('original_transaction_fee', $CorporateServiceFee->getOriginalTransactionFee());
        if( $CorporateServiceFee->getOriginalCountryCurrencyCode() != NULL )
            $this->db->set('original_country_currency_code', $CorporateServiceFee->getOriginalCountryCurrencyCode());

        $this->db->set('updated_at', IappsDateTime::now()->getUnix());
        $this->db->set('updated_by', $CorporateServiceFee->getUpdatedBy());
        $this->db->where('id', $CorporateServiceFee->getId());

        if( $this->db->update('iafb_remittance.corporate_service_fee') )
        {
            return true;
        }

        return false;
    }

    public function delete(CorporateServiceFee $fee){
        
        $this->db->set('deleted_at', IappsDateTime::now()->getUnix());
        $this->db->set('deleted_by', $fee->getDeletedBy());
        $this->db->where('id', $fee->getId());

        if( $this->db->update('iafb_remittance.corporate_service_fee') )
        {
            return true;
        }

        return false;
    } 
}