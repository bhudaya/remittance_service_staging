<?php

use Iapps\Common\CorporateService\CorporateServicePaymentMode;
use Iapps\Common\CorporateService\ICorporateServicePaymentModeMapper;
use Iapps\Common\CorporateService\CorporateServicePaymentModeCollection;
use Iapps\Common\Core\IappsDateTime;

class corporate_service_payment_mode_model extends Base_Model
                               implements ICorporateServicePaymentModeMapper{

    public function map(stdClass $data)
    {
        $entity = new CorporateServicePaymentMode();

        if( isset($data->corporate_service_payment_mode_id) )
            $entity->setId($data->corporate_service_payment_mode_id);

        if( isset($data->corporate_service_id) )
            $entity->setCorporateServiceId($data->corporate_service_id);

        if( isset($data->payment_code) )
            $entity->setPaymentCode($data->payment_code);

        if( isset($data->is_default) )
            $entity->setIsDefault($data->is_default);

        if( isset($data->is_active) )
            $entity->setIsActive($data->is_active);

        if( isset($data->direction) )
            $entity->setDirection($data->direction);

        if( isset($data->role_id) )
            $entity->setRoleId($data->role_id);

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
        $this->db->select('id as corporate_service_payment_mode_id,
                           corporate_service_id,
                           payment_code,
                           is_default,
                           is_active,
                           direction,
                           created_at,
                           created_by,
                           updated_at,
                           updated_by,
                           deleted_at,
                           deleted_by');
        $this->db->from('iafb_remittance.corporate_service_payment_mode');
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

    public function findBySearchFilter(CorporateServicePaymentMode $corpServicePaymentMode, $limit, $page)
    {
        $total = 0;
        $offset = ($page - 1) * $limit;

        $this->db->start_cache(); //to cache active record query
        $this->db->select('id as corporate_service_payment_mode_id,
                           corporate_service_id,
                           payment_code,
                           is_default,
                           is_active,
                           direction,
                           created_at,
                           created_by,
                           updated_at,
                           updated_by,
                           deleted_at,
                           deleted_by');
        $this->db->from('iafb_remittance.corporate_service_payment_mode');
        if($corpServicePaymentMode->getCorporateServiceId())
        {
            $this->db->where('corporate_service_id', $corpServicePaymentMode->getCorporateServiceId());
        }
        if((bool)$corpServicePaymentMode->getIsDefault())
        {
            $this->db->where('is_default', $corpServicePaymentMode->getIsDefault());
        }
        if($corpServicePaymentMode->getIsActive())
        {
            $this->db->where('is_active', $corpServicePaymentMode->getIsActive());
        }

        if( $corpServicePaymentMode->getPaymentCode() )
            $this->db->where('payment_code', $corpServicePaymentMode->getPaymentCode());

        $this->db->where('deleted_at', NULL);
        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit
        $this->db->limit($limit, $offset);
        $query = $this->db->get();
        $this->db->flush_cache();

        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new CorporateServicePaymentModeCollection(), $total);
        }

        return false;
    }


    public function findAllByCorporateServiceId($corporate_service_id)
    {
        $this->db->select('id as corporate_service_payment_mode_id,
                           corporate_service_id,
                           payment_code,
                           is_default,
                           is_active,
                           direction,
                           created_at,
                           created_by,
                           updated_at,
                           updated_by,
                           deleted_at,
                           deleted_by');
        $this->db->from('iafb_remittance.corporate_service_payment_mode');
        $this->db->where('corporate_service_id', $corporate_service_id);
        $this->db->where('deleted_at', NULL);

        $query = $this->db->get();
        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new CorporateServicePaymentModeCollection(), 0);
        }

        return false;
    }

    public function findAllByCorporateServiceIds(CorporateServicePaymentMode $corpServPaymentMode, $corpServIds, $paymentModes)
    {
        $total = 0;
        $this->db->start_cache(); //to cache active record query
        $this->db->select('id as corporate_service_payment_mode_id,
                           corporate_service_id,
                           payment_code,
                           is_default,
                           is_active,
                           direction,
                           role_id,
                           created_at,
                           created_by,
                           updated_at,
                           updated_by,
                           deleted_at,
                           deleted_by');
        $this->db->from('iafb_remittance.corporate_service_payment_mode');

        if($corpServPaymentMode->getDirection())
        {
            $this->db->where('direction', $corpServPaymentMode->getDirection());
        }

        $this->db->where_in('corporate_service_id', $corpServIds);
        $this->db->where_in('payment_code', $paymentModes);
        $this->db->where('deleted_at', NULL);

        $this->db->stop_cache();
        $total = $this->db->count_all_results(); //to get total num of result w/o limit

        $query = $this->db->get();
        $this->db->flush_cache();
        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new CorporateServicePaymentModeCollection(), $total);
        }

        return false;
    }

    public function findByCorporateServiceIdPaymentMode($corporate_service_id,$payment_mode)
    {
        $this->db->select('id as corporate_service_payment_mode_id,
                           corporate_service_id,
                           payment_code,
                           is_default,
                           is_active,
                           direction,
                           created_at,
                           created_by,
                           updated_at,
                           updated_by,
                           deleted_at,
                           deleted_by');
        $this->db->from('iafb_remittance.corporate_service_payment_mode');
        $this->db->where('corporate_service_id', $corporate_service_id);
        $this->db->where('payment_code', $payment_mode);
        $this->db->where('deleted_at', NULL);

        $query = $this->db->get();
        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new CorporateServicePaymentModeCollection(), 0);
        }

        return false;
    }

    public function insert(CorporateServicePaymentMode $payment_mode)
    {
        $this->db->set('id', $payment_mode->getId());
        $this->db->set('corporate_service_id', $payment_mode->getCorporateServiceId());
        $this->db->set('payment_code', $payment_mode->getPaymentCode());
        $this->db->set('is_default', $payment_mode->getIsDefault());
        $this->db->set('is_active', $payment_mode->getIsActive());
        $this->db->set('direction', $payment_mode->getDirection());
        $this->db->set('created_by', $payment_mode->getCreatedBy());
        $this->db->set('created_at', IappsDateTime::now()->getUnix());

        if( $this->db->insert('iafb_remittance.corporate_service_payment_mode') )
        {
            return true;
        }

        return false;
    }

    public function update(CorporateServicePaymentMode $payment_mode)
    {
        $this->db->set('payment_code', $payment_mode->getPaymentCode());
        $this->db->set('is_default', $payment_mode->getIsDefault());
        $this->db->set('direction', $payment_mode->getDirection());
        $this->db->set('is_active', $payment_mode->getIsActive());
        $this->db->set('updated_at', IappsDateTime::now()->getUnix());
        $this->db->set('updated_by', $payment_mode->getUpdatedBy());
        $this->db->where('id', $payment_mode->getId());

        if( $this->db->update('iafb_remittance.corporate_service_payment_mode') )
        {
            return true;
        }

        return false;
    }

    public function delete(CorporateServicePaymentMode $payment_mode)
    {
        $this->db->set('deleted_at', IappsDateTime::now()->getUnix());
        $this->db->set('deleted_by', $payment_mode->getDeletedBy());
        $this->db->where('id', $payment_mode->getId());

        if( $this->db->update('iafb_remittance.corporate_service_payment_mode') )
        {
            return true;
        }

        return false;
    }
}