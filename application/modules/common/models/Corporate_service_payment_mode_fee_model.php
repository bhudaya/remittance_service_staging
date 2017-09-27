<?php

use Iapps\Common\CorporateService\CorporateServicePaymentModeFee;
use Iapps\Common\CorporateService\ICorporateServicePaymentModeFeeMapper;
use Iapps\Common\CorporateService\CorporateServicePaymentModeFeeCollection;
use Iapps\Common\Core\IappsDateTime;

class corporate_service_payment_mode_fee_model extends Base_Model
                               implements ICorporateServicePaymentModeFeeMapper{

    public function map(stdClass $data)
    {
        $entity = new CorporateServicePaymentModeFee();

        if( isset($data->corporate_service_payment_mode_fee_id) )
            $entity->setId($data->corporate_service_payment_mode_fee_id);

        if( isset($data->corporate_service_payment_mode_id) )
            $entity->setCorporateServicePaymentModeId($data->corporate_service_payment_mode_id);

        if( isset($data->is_percentage ))
            $entity->setIsPercentage($data->is_percentage);
        
        if( isset($data->name) )
            $entity->setName($data->name);

        if( isset($data->fee) )
            $entity->setFee($data->fee);

        if( isset($data->converted_fee) )
            $entity->setConvertedFee($data->converted_fee);

        if( isset($data->converted_fee_country_currency_code) )
            $entity->setConvertedFeeCountryCurrencyCode($data->converted_fee_country_currency_code);

        if( isset($data->service_provider_id) )
            $entity->setServiceProviderId($data->service_provider_id);

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
        $this->db->select('id as corporate_service_payment_mode_fee_id,
                           corporate_service_payment_mode_id,
                           is_percentage,
                           name,
                           fee,
                           converted_fee,
                           converted_fee_country_currency_code,
                           service_provider_id,
                           created_at,
                           created_by,
                           updated_at,
                           updated_by,
                           deleted_at,
                           deleted_by');
        $this->db->from('iafb_remittance.corporate_service_payment_mode_fee');
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

    public function findAllByCorporateServicePaymentModeId($corporate_service_payment_mode_id)
    {
        $this->db->select('id as corporate_service_payment_mode_fee_id,
                           corporate_service_payment_mode_id,
                           is_percentage,
                           name,
                           fee,
                           converted_fee,
                           converted_fee_country_currency_code,
                           service_provider_id,
                           created_at,
                           created_by,
                           updated_at,
                           updated_by,
                           deleted_at,
                           deleted_by');
        $this->db->from('iafb_remittance.corporate_service_payment_mode_fee');
        $this->db->where('corporate_service_payment_mode_id', $corporate_service_payment_mode_id);
        $this->db->where('deleted_at', NULL);

        $query = $this->db->get();
        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new CorporateServicePaymentModeFeeCollection(), 0);
        }

        return false;
    }

    public function insert(CorporateServicePaymentModeFee $payment_mode_fee)
    {
        $this->db->set('id', $payment_mode_fee->getId());
        $this->db->set('corporate_service_payment_mode_id', $payment_mode_fee->getCorporateServicePaymentModeId());
        $this->db->set('is_percentage', $payment_mode_fee->getIsPercentage());
        $this->db->set('name', $payment_mode_fee->getName());
        $this->db->set('fee', $payment_mode_fee->getFee());
        $this->db->set('converted_fee', $payment_mode_fee->getConvertedFee());
        $this->db->set('converted_fee_country_currency_code', $payment_mode_fee->getConvertedFeeCountryCurrencyCode());
        $this->db->set('service_provider_id', $payment_mode_fee->getServiceProviderId());
        $this->db->set('created_by', $payment_mode_fee->getCreatedBy());
        $this->db->set('created_at', IappsDateTime::now()->getUnix());

        if( $this->db->insert('iafb_remittance.corporate_service_payment_mode_fee') )
        {
            return true;
        }

        return false;
    }

    public function update(CorporateServicePaymentModeFee $payment_mode_fee)
    {
        $this->db->set('is_percentage', $payment_mode_fee->getIsPercentage());
        $this->db->set('name', $payment_mode_fee->getName());
        $this->db->set('fee', $payment_mode_fee->getFee());
        $this->db->set('converted_fee', $payment_mode_fee->getConvertedFee());
        $this->db->set('converted_fee_country_currency_code', $payment_mode_fee->getConvertedFeeCountryCurrencyCode());
        $this->db->set('service_provider_id', $payment_mode_fee->getServiceProviderId());
        $this->db->set('updated_at', IappsDateTime::now()->getUnix());
        $this->db->set('updated_by', $payment_mode_fee->getUpdatedBy());
        $this->db->where('id', $payment_mode_fee->getId());

        if( $this->db->update('iafb_remittance.corporate_service_payment_mode_fee') )
        {
            return true;
        }

        return false;
    }

    public function delete(CorporateServicePaymentModeFee $payment_mode_fee)
    {
        $this->db->set('deleted_at', IappsDateTime::now()->getUnix());
        $this->db->set('deleted_by', $payment_mode_fee->getDeletedBy());
        $this->db->where('id', $payment_mode_fee->getId());

        if( $this->db->update('iafb_remittance.corporate_service_payment_mode_fee') )
        {
            return true;
        }

        return false;
    }
}