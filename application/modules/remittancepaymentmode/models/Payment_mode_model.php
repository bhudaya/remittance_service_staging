<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

use Iapps\RemittanceService\RemittancePaymentMode\IPaymentModeDataMapper;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentMode;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeCollection;
use Iapps\Common\Core\IappsDateTime;

/**
 * Description of Payment_mode_model
 *
 * @author lichao
 */
class Payment_mode_model extends Base_Model implements IPaymentModeDataMapper {
    
    public function map(\stdClass $data) {
        
        $entity = new PaymentMode();
        
        if( isset($data->id) )
            $entity->setId ($data->id);
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
        
        if( isset($data->direction) )
            $entity->setDirection($data->direction);
        if( isset($data->corporate_service_id) )
            $entity->setCorporateServiceId($data->corporate_service_id);
        if( isset($data->is_default) )
            $entity->setIsDefault($data->is_default);
        if( isset($data->payment_code) )
            $entity->setPaymentCode($data->payment_code);
        if( isset($data->is_active) )
            $entity->setIsActive($data->is_active);
        
        return $entity;
    }

    public function findListByCorporeateServiceId($limit, $page, array $corporateServiceIds = NULL, array $paymentModeIds = NULL, $isActive = NULL, $direction = NULL) {
        
        $offset = ($page - 1) * $limit;
        $this->db->start_cache(); //to cache active record query
        $this->db->select('`corporate_service_payment_mode`.`id`,
                            `corporate_service_payment_mode`.`direction`,
                            `corporate_service_payment_mode`.`corporate_service_id`,
                            `corporate_service_payment_mode`.`is_default`,
                            `corporate_service_payment_mode`.`payment_code`,
                            `corporate_service_payment_mode`.`is_active`,
                            `corporate_service_payment_mode`.`created_at`,
                            `corporate_service_payment_mode`.`created_by`,
                            `corporate_service_payment_mode`.`updated_at`,
                            `corporate_service_payment_mode`.`updated_by`,
                            `corporate_service_payment_mode`.`deleted_at`,
                            `corporate_service_payment_mode`.`deleted_by`
                        ');

        $this->db->from('`iafb_remittance`.`corporate_service_payment_mode`');
        $this->db->where('`corporate_service_payment_mode`.`deleted_at`', NULL);
        
        if( $paymentModeIds !== NULL )
            $this->db->where_in('`corporate_service_payment_mode`.`id`', $paymentModeIds);
        if($corporateServiceIds != NULL && is_array($corporateServiceIds) && count($corporateServiceIds) > 0)
            $this->db->where_in('`corporate_service_payment_mode`.`corporate_service_id`', $corporateServiceIds);
        if($isActive !== NULL)
            $this->db->where('is_active', $isActive);
        if($direction !== NULL)
            $this->db->where('direction', $direction);
        
        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit

        $this->db->limit($limit, $offset);
        $query = $this->db->get();
        $this->db->flush_cache();
        if($query != false && $query->num_rows() > 0)
        {
           return $this->mapCollection($query->result(), new PaymentModeCollection(), $total);
        }

        return false;
    }

    public function findById($id, $deleted = false) {
        $this->db->select('`corporate_service_payment_mode`.`id`,
                            `corporate_service_payment_mode`.`direction`,
                            `corporate_service_payment_mode`.`corporate_service_id`,
                            `corporate_service_payment_mode`.`is_default`,
                            `corporate_service_payment_mode`.`payment_code`,
                            `corporate_service_payment_mode`.`is_active`,
                            `corporate_service_payment_mode`.`created_at`,
                            `corporate_service_payment_mode`.`created_by`,
                            `corporate_service_payment_mode`.`updated_at`,
                            `corporate_service_payment_mode`.`updated_by`,
                            `corporate_service_payment_mode`.`deleted_at`,
                            `corporate_service_payment_mode`.`deleted_by`
                        ');

        $this->db->from('`iafb_remittance`.`corporate_service_payment_mode`');
        
        if(!$deleted)
            $this->db->where('`corporate_service_payment_mode`.`deleted_at`', NULL);
        
        $this->db->where('`corporate_service_payment_mode`.`id`', $id);

        $query = $this->db->get();
        if($query != false && $query->num_rows() > 0)
        {
            return $this->map($query->row());
        }

        return false;
    }
    
    public function exists(PaymentMode $entity)
    {
        $this->db->select('`corporate_service_payment_mode`.`id`,
                            `corporate_service_payment_mode`.`direction`,
                            `corporate_service_payment_mode`.`corporate_service_id`,
                            `corporate_service_payment_mode`.`is_default`,
                            `corporate_service_payment_mode`.`payment_code`,
                            `corporate_service_payment_mode`.`is_active`,
                            `corporate_service_payment_mode`.`created_at`,
                            `corporate_service_payment_mode`.`created_by`,
                            `corporate_service_payment_mode`.`updated_at`,
                            `corporate_service_payment_mode`.`updated_by`,
                            `corporate_service_payment_mode`.`deleted_at`,
                            `corporate_service_payment_mode`.`deleted_by`
                        ');

        $this->db->from('`iafb_remittance`.`corporate_service_payment_mode`');
        $this->db->where('`corporate_service_payment_mode`.`deleted_at`', NULL);
        
        $this->db->where('`corporate_service_payment_mode`.`corporate_service_id`', $entity->getCorporateServiceId());
        $this->db->where('`corporate_service_payment_mode`.`direction`', $entity->getDirection());
        $this->db->where('`corporate_service_payment_mode`.`payment_code`', $entity->getPaymentCode());

        $query = $this->db->get();
        if($query != false && $query->num_rows() > 0)
        {
            return $this->map($query->row());
        }

        return false;
    }

    public function insert(PaymentMode $entity) {
        
        $this->db->set('id', $entity->getId());
        
        if( $entity->getDirection() !== null)
            $this->db->set('direction', $entity->getDirection());
        if( $entity->getCorporateServiceId() !== null)
            $this->db->set('corporate_service_id', $entity->getCorporateServiceId());
        if( $entity->getIsDefault() !== null)
            $this->db->set('is_default', $entity->getIsDefault());
        if( $entity->getPaymentCode() !== null)
            $this->db->set('payment_code', $entity->getPaymentCode());
        if( $entity->getIsActive() !== null)
            $this->db->set('is_active', $entity->getIsActive());
        
        $this->db->set('created_at', IappsDateTime::now()->getUnix());
        $this->db->set('created_by', $entity->getCreatedBy());

        if( $this->db->insert('`iafb_remittance`.`corporate_service_payment_mode`') )
        {
            return true;
        }

        return false;
    }

    public function update(PaymentMode $entity) {
        
        $this->db->set('id', $entity->getId());
        
        if( $entity->getDirection() !== null)
            $this->db->set('direction', $entity->getDirection());
        if( $entity->getCorporateServiceId() !== null)
            $this->db->set('corporate_service_id', $entity->getCorporateServiceId());
        if( $entity->getIsDefault() !== null)
            $this->db->set('is_default', $entity->getIsDefault());
        if( $entity->getPaymentCode() !== null)
            $this->db->set('payment_code', $entity->getPaymentCode());
        if( $entity->getIsActive() !== null)
            $this->db->set('is_active', $entity->getIsActive());
        
        $this->db->set('updated_at', IappsDateTime::now()->getUnix());
        $this->db->set('updated_by', $entity->getUpdatedBy());
        
        $this->db->where('id', $entity->getId());

        if( $this->db->update('`iafb_remittance`.`corporate_service_payment_mode`') )
        {
            return true;
        }
        return false;
    }
    
    public function updateFields(PaymentMode $entity, array $fields, array $where)
    {
        //nothing to update
        if( count($fields) <= 0 )
            return false;

        foreach ($fields as $field) {
            switch ($field)
            {
                case 'is_default':
                    if( $entity->getIsDefault() !== null)
                        $this->db->set('is_default', $entity->getIsDefault());
                    else
                        return false;
                    break;
                    
            }
        }
        
        foreach ($where as $key => $value) {
            
            if( strpos($key,'!') === 0)
            {
                $key = str_replace('!','',$key);
                $key = $key . " <> ";
            }
            
            $this->db->where($key, $value);
        }
        
        if ($this->db->update('`iafb_remittance`.`corporate_service_payment_mode`')) {
            return $this->db->affected_rows();
        } else {
            return FALSE;
        }
        
        return true;
    }
    
    public function remove(PaymentMode $entity, $isLogic = true) {
        
        if($isLogic)
        {
            $data_delete = array(
                'deleted_at' => IappsDateTime::now()->getUnix(),
                'deleted_by' => $entity->getUpdatedBy()
            );
            
            $this->db->where('id', $entity->getId());
            $this->db->where('deleted_at', NULL);
            if( $this->db->update('`iafb_remittance`.`corporate_service_payment_mode`', $data_delete) )
            {
                return true;
            }
        }
        else
        {
            $this->db->where('id', $entity->getId());
            $this->db->where('deleted_at', NULL);
            if($this->db->delete('`iafb_remittance`.`corporate_service_payment_mode`'))
            {
                return true;
            }
        }
        
        return false;
    }
    
}
