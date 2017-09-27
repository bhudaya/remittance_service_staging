<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

use Iapps\RemittanceService\RemittancePaymentMode\IPaymentModeCostDataMapper;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeCost;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeCostCollection;
use Iapps\Common\Core\IappsDateTime;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeCostGroup;

/**
 * Description of Payment_mode_fee_model
 *
 * @author lichao
 */
class Payment_mode_cost_model extends Base_Model implements IPaymentModeCostDataMapper {
    //put your code here
    
    public function map(\stdClass $data) {
        
        $entity = new PaymentModeCost();
        
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
        
        if( isset($data->payment_mode_group_id) )
            $entity->setPaymentModeGroupId($data->payment_mode_group_id);
        
        if( isset($data->service_provider_id) )
            $entity->setServiceProviderId($data->service_provider_id);
        if( isset($data->role_id) )
            $entity->setRoleId($data->role_id);
        if( isset($data->is_percentage) )
            $entity->setIsPercentage($data->is_percentage);
        if( isset($data->country_currency_code) )
            $entity->setCountryCurrencyCode($data->country_currency_code);
        if( isset($data->cost) )
            $entity->setCost($data->cost);
        
        return $entity;
    }
    
    public function findListByGroupIds($limit, $page, $costGroupIds = NULL, $status = NULL) {
        
        $offset = ($page - 1) * $limit;
        $this->db->start_cache(); //to cache active record query
        $this->db->select('`payment_mode_cost`.`id`,
                            `payment_mode_cost`.`payment_mode_group_id`,
                            `payment_mode_cost`.`service_provider_id`,
                            `payment_mode_cost`.`role_id`,
                            `payment_mode_cost`.`is_percentage`,
                            `payment_mode_cost`.`country_currency_code`,
                            `payment_mode_cost`.`cost`,
                            `payment_mode_cost`.`created_at`,
                            `payment_mode_cost`.`created_by`,
                            `payment_mode_cost`.`updated_at`,
                            `payment_mode_cost`.`updated_by`,
                            `payment_mode_cost`.`deleted_at`,
                            `payment_mode_cost`.`deleted_by`
                        ');

        $this->db->from('`iafb_remittance`.`payment_mode_cost`');
        $this->db->where('`payment_mode_cost`.`deleted_at`', NULL);
        if($costGroupIds != null)
            $this->db->where_in('`payment_mode_cost`.`payment_mode_group_id`', $costGroupIds);
        if($status != null)
            $this->db->where('`payment_mode_cost`.`status`', $status);
        
        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit

        $this->db->limit($limit, $offset);
        $query = $this->db->get();
        $this->db->flush_cache();
        if($query != false && $query->num_rows() > 0)
        {
           return $this->mapCollection($query->result(), new PaymentModeCostCollection(), $total);
        }

        return false;
    }
    
    public function findListByCorporrateServicePaymentModeIds($limit, $page, array $corporateServicePaymentModeIds = NULL, array $paymentModeCostGroupIds = NULL, $isActive = NULL, $status = NULL, $noCost = NULL)
    {
        $offset = ($page - 1) * $limit;
        $this->db->start_cache(); //to cache active record query
        $this->db->select('`payment_mode_cost`.`id`,
                            `payment_mode_cost`.`payment_mode_group_id`,
                            `payment_mode_cost`.`service_provider_id`,
                            `payment_mode_cost`.`role_id`,
                            `payment_mode_cost`.`is_percentage`,
                            `payment_mode_cost`.`country_currency_code`,
                            `payment_mode_cost`.`cost`,
                            `payment_mode_cost`.`created_at`,
                            `payment_mode_cost`.`created_by`,
                            `payment_mode_cost`.`updated_at`,
                            `payment_mode_cost`.`updated_by`,
                            `payment_mode_cost`.`deleted_at`,
                            `payment_mode_cost`.`deleted_by`
                        ');

        $this->db->from('`iafb_remittance`.`payment_mode_cost`');
        $this->db->join('`iafb_remittance`.`payment_mode_cost_group` as fg', 'payment_mode_cost.payment_mode_group_id = fg.id');
        $this->db->where('`payment_mode_cost`.`deleted_at`', NULL);
        
        if( $paymentModeCostGroupIds != NULL)
            $this->db->where_in('`payment_mode_cost`.`payment_mode_group_id`', $paymentModeCostGroupIds);
        
        if($corporateServicePaymentModeIds != null)
            $this->db->where_in('`fg`.`corporate_service_payment_mode_id`', $corporateServicePaymentModeIds);
        
        if($isActive !== NULL)
            $this->db->where('`fg`.`is_active`', $isActive);
        
        if($status !== NULL)
            $this->db->where('`fg`.`status`', $status);
        
        if($noCost !== NULL)
            $this->db->where('`fg`.`no_cost`', $noCost);
        
        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit

        $this->db->limit($limit, $offset);
        $query = $this->db->get();
        $this->db->flush_cache();
        if($query != false && $query->num_rows() > 0)
        {
           return $this->mapCollection($query->result(), new PaymentModeCostCollection(), $total);
        }

        return false;
    }

    public function findById($id, $deleted = false) {
        $this->db->select('`payment_mode_cost`.`id`,
                            `payment_mode_cost`.`payment_mode_group_id`,
                            `payment_mode_cost`.`service_provider_id`,
                            `payment_mode_cost`.`role_id`,
                            `payment_mode_cost`.`is_percentage`,
                            `payment_mode_cost`.`country_currency_code`,
                            `payment_mode_cost`.`cost`,
                            `payment_mode_cost`.`created_at`,
                            `payment_mode_cost`.`created_by`,
                            `payment_mode_cost`.`updated_at`,
                            `payment_mode_cost`.`updated_by`,
                            `payment_mode_cost`.`deleted_at`,
                            `payment_mode_cost`.`deleted_by`
                        ');

        $this->db->from('`iafb_remittance`.`payment_mode_cost`');
        if(!$deleted)
            $this->db->where('`payment_mode_cost`.`deleted_at`', NULL);
        
        $this->db->where('`payment_mode_cost`.`id`', $id);

        $query = $this->db->get();
        if($query != false && $query->num_rows() > 0)
        {
            return $this->map($query->row());
        }

        return false;
    }

    public function insert(PaymentModeCost $entity) {
        
        $this->db->set('id', $entity->getId());
        $this->db->set('payment_mode_group_id', $entity->getPaymentModeGroupId());
        $this->db->set('service_provider_id', $entity->getServiceProviderId());
        $this->db->set('role_id', $entity->getRoleId());
        $this->db->set('is_percentage', $entity->getIsPercentage());
        $this->db->set('country_currency_code', $entity->getCountryCurrencyCode());
        $this->db->set('cost', $entity->getCost());
        
        $this->db->set('created_at', IappsDateTime::now()->getUnix());
        $this->db->set('created_by', $entity->getCreatedBy());

        if( $this->db->insert('`iafb_remittance`.`payment_mode_cost`') )
        {
            return true;
        }

        return false;
    }

    public function update(PaymentModeCost $entity) {
        
        $this->db->set('id', $entity->getId());
        
        if( $entity->getPaymentModeGroupId() !== null)
            $this->db->set('payment_mode_group_id', $entity->getPaymentModeGroupId());
        
        if( $entity->getServiceProviderId() !== null)
        {
            $this->db->set('service_provider_id', $entity->getServiceProviderId());
            $this->db->set('role_id', NULL);
        }
        if( $entity->getRoleId() !== null)
        {
            $this->db->set('role_id', $entity->getRoleId());
            $this->db->set('service_provider_id', NULL);
        }
        if( $entity->getIsPercentage() !== null)
            $this->db->set('is_percentage', $entity->getIsPercentage());
        if( $entity->getCountryCurrencyCode() !== null)
            $this->db->set('country_currency_code', $entity->getCountryCurrencyCode());
        if( $entity->getCost() !== null)
            $this->db->set('cost', $entity->getCost());
        
        $this->db->set('updated_at', IappsDateTime::now()->getUnix());
        $this->db->set('updated_by', $entity->getUpdatedBy());

        if( $this->db->insert('`iafb_remittance`.`payment_mode_cost`') )
        {
            return true;
        }
        return false;
    }

    public function remove(PaymentModeCost $entity, $isLogic = true) {
        
        if($isLogic)
        {
            $data_delete = array(
                'deleted_at' => IappsDateTime::now()->getUnix(),
                'deleted_by' => $entity->getUpdatedBy()
            );
            
            $this->db->where('id', $entity->getId());
            $this->db->where('deleted_at', NULL);
            if( $this->db->update('`iafb_remittance`.`payment_mode_cost`', $data_delete) )
            {
                return true;
            }
        }
        else
        {
            $this->db->where('id', $entity->getId());
            $this->db->where('deleted_at', NULL);
            if($this->db->delete('`iafb_remittance`.`payment_mode_cost`'))
            {
                return true;
            }
        }
        
        return false;
    }

    public function removeNotExists(PaymentModeCostGroup $entity, array $cost_ids = NULL, $isLogic = true)
    {
        if($isLogic)
        {
            $data_delete = array(
                'deleted_at' => IappsDateTime::now()->getUnix(),
                'deleted_by' => $entity->getUpdatedBy()
            );
            
            $this->db->where('payment_mode_group_id', $entity->getId());
            
            if($cost_ids !== NULL)
                $this->db->where_not_in('id', $cost_ids);
            
            $this->db->where('deleted_at', NULL);
            if( $this->db->update('`iafb_remittance`.`payment_mode_cost`', $data_delete) )
            {
                return true;
            }
        }
        else
        {
            $this->db->where('payment_mode_group_id', $entity->getId());
            $this->db->where_not_in('id', $cost_ids);
            $this->db->where('deleted_at', NULL);
            if($this->db->delete('`iafb_remittance`.`payment_mode_cost`'))
            {
                return true;
            }
        }
        
        return false;
    }

}
