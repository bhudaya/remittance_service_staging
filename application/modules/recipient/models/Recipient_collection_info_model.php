<?php

use Iapps\RemittanceService\RecipientCollectionInfo\RecipientCollectionInfoDataMapper;
use Iapps\RemittanceService\RecipientCollectionInfo\RecipientCollectionInfo;
use Iapps\RemittanceService\RecipientCollectionInfo\RecipientCollectionInfoCollection;
use Iapps\Common\Core\IappsDateTime;
use Iapps\RemittanceService\ValueObject\EncryptedFieldFactory;

class Recipient_collection_info_model extends Base_Model
                      implements RecipientCollectionInfoDataMapper{

    public function map(stdClass $data)
    {
        $entity = new RecipientCollectionInfo();

        if( isset($data->id) )
            $entity->setId($data->id);

        if( isset($data->recipient_id) )
            $entity->setRecipientId($data->recipient_id);

        //this code remains to support old versoin ONLY
        if( isset($data->payment_code) )
            $entity->setPaymentCode($data->payment_code);

        if( isset($data->country_code) )
            $entity->setCountryCode($data->country_code);

        if( isset($data->option) )
            $entity->getOption()->setEncryptedValue($data->option);

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
        $this->db->select('id,
                           recipient_id,
                           payment_code,
                           country_code,
                           option,
                           created_at,
                           created_by,
                           updated_at,
                           updated_by,
                           deleted_at,
                           deleted_by');
        $this->db->from('iafb_remittance.recipient_collection_info');
        if( !$deleted )
            $this->db->where('deleted_at', NULL);

        $this->db->where('id', $id);

        $query = $this->db->get();
        if( $query->num_rows() > 0)
        {
            return $this->map($query->row());
        }

        return false;
    }

    public function findByRecipientId($recipient_id)
    {
        $this->db->start_cache(); //to cache active record query
        $this->db->select('id,
                           recipient_id,
                           payment_code,
                           country_code,
                           option,
                           created_at,
                           created_by,
                           updated_at,
                           updated_by,
                           deleted_at,
                           deleted_by');

        $this->db->from('iafb_remittance.recipient_collection_info');
        $this->db->where('deleted_at', NULL);
        $this->db->where('recipient_id', $recipient_id);
        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit

        $query = $this->db->get();
        $this->db->flush_cache();

        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new RecipientCollectionInfoCollection(), $total);
        }

        return false;
    }

    public function findByRecipientIds(array $recipient_ids)
    {
        $this->db->select('id,
                           recipient_id,
                           payment_code,
                           country_code,
                           option,
                           created_at,
                           created_by,
                           updated_at,
                           updated_by,
                           deleted_at,
                           deleted_by');

        $this->db->from('iafb_remittance.recipient_collection_info');
        $this->db->where('deleted_at', NULL);
        $this->db->where_in('recipient_id', $recipient_ids);

        $query = $this->db->get();

        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new RecipientCollectionInfoCollection(), $query->num_rows());
        }

        return false;
    }

    public function findbyParm(RecipientCollectionInfo $info)
    {
        $this->db->start_cache(); //to cache active record query
        $this->db->select('id,
                           recipient_id,
                           payment_code,
                           country_code,
                           option,
                           created_at,
                           created_by,
                           updated_at,
                           updated_by,
                           deleted_at,
                           deleted_by');

        $this->db->from('iafb_remittance.recipient_collection_info');
        $this->db->where('deleted_at', NULL);
        if ($info->getRecipientId()) {
            $this->db->where('recipient_id', $info->getRecipientId());
        }
        if ($info->getPaymentCode()) {
            $this->db->where('payment_code', $info->getPaymentCode());
        }
        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit

        $query = $this->db->get();
        $this->db->flush_cache();

        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new RecipientCollectionInfoCollection(), $total);
        }

        return false;
    }

    public function insert(RecipientCollectionInfo $info)
    {
        $this->db->set('id', $info->getId());
        $this->db->set('recipient_id', $info->getRecipientId());
        $this->db->set('country_code', $info->getCountryCode());
        $this->db->set('option', $info->getOption()->getEncodedValue());
        $this->db->set('created_at', IappsDateTime::now()->getUnix());
        $this->db->set('created_by', $info->getCreatedBy());

        if( $this->db->insert('iafb_remittance.recipient_collection_info') )
        {
            return true;
        }

        return false;
    }

    public function update(RecipientCollectionInfo $info)
    {

        if( $info->getCountryCode() != NULL ) {

            $this->db->set('country_code', $info->getCountryCode());
        }

        if( $info->getOption() != NULL ) {
          
            $this->db->set('option', $info->getOption()->getEncodedValue());
        }
        
        $this->db->set('updated_at', IappsDateTime::now()->getUnix());
        $this->db->set('updated_by', $info->getUpdatedBy());

        $this->db->where('id', $info->getId());

        if( $this->db->update('iafb_remittance.recipient_collection_info') )
        {
            return true;
        }

        return false;
    }
}