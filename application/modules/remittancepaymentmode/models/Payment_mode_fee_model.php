<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

use Iapps\RemittanceService\RemittancePaymentMode\IPaymentModeFeeDataMapper;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeFee;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeFeeCollection;
use Iapps\Common\Core\IappsDateTime;
use Iapps\RemittanceService\Common\FeeType;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeFeeGroup;

/**
 * Description of Payment_mode_fee_model
 *
 * @author lichao
 */
class Payment_mode_fee_model extends Base_Model implements IPaymentModeFeeDataMapper {
    //put your code here
    
    public function map(\stdClass $data) {
        
        $entity = new PaymentModeFee();
        
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

        if( isset($data->corporate_service_payment_mode_fee_group_id) )
            $entity->setCorporateServicePaymentModeFeeGroupId($data->corporate_service_payment_mode_fee_group_id);
        if( isset($data->multitier_type) )
            $entity->setMultitierType($data->multitier_type);
        if( isset($data->reference_value1) )
            $entity->setReferenceValue1($data->reference_value1);
        if( isset($data->reference_value2) )
            $entity->setReferenceValue2($data->reference_value2);
        if( isset($data->is_percentage) )
            $entity->setIsPercentage($data->is_percentage);
        if( isset($data->fee) )
            $entity->setFee($data->fee);

        
        return $entity;
    }

    public function findListByGroupId($feeGroupId) {

        $this->db->select('`corporate_service_payment_mode_fee`.`id`,
                            `corporate_service_payment_mode_fee`.`corporate_service_payment_mode_fee_group_id`,
                            `corporate_service_payment_mode_fee`.`multitier_type`,
                            `corporate_service_payment_mode_fee`.`reference_value1`,
                            `corporate_service_payment_mode_fee`.`reference_value2`,
                            `corporate_service_payment_mode_fee`.`is_percentage`,
                            `corporate_service_payment_mode_fee`.`fee`,
                            `corporate_service_payment_mode_fee`.`created_at`,
                            `corporate_service_payment_mode_fee`.`created_by`,
                            `corporate_service_payment_mode_fee`.`updated_at`,
                            `corporate_service_payment_mode_fee`.`updated_by`,
                            `corporate_service_payment_mode_fee`.`deleted_at`,
                            `corporate_service_payment_mode_fee`.`deleted_by`
                        ');

        $this->db->from('`iafb_remittance`.`corporate_service_payment_mode_fee`');
        $this->db->where('`corporate_service_payment_mode_fee`.`deleted_at`', NULL);
        $this->db->where('`corporate_service_payment_mode_fee`.`corporate_service_payment_mode_fee_group_id`', $feeGroupId);

        $this->db->order_by("case multitier_type when 'flat' then 0 when 'less_than' then 1 when 'range' then 2 when 'greater_than' then 3 else 4 end,reference_value1 asc", null, false);

        $query = $this->db->get();

        if($query != false && $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new PaymentModeFeeCollection(), $query->num_rows());
        }

        return false;
    }

    public function findListByGroupIds($limit, $page, $feeGroupIds = NULL) {
        
        $offset = ($page - 1) * $limit;
        $this->db->start_cache(); //to cache active record query
        $this->db->select('`corporate_service_payment_mode_fee`.`id`,
                            `corporate_service_payment_mode_fee`.`corporate_service_payment_mode_fee_group_id`,
                            `corporate_service_payment_mode_fee`.`multitier_type`,
                            `corporate_service_payment_mode_fee`.`reference_value1`,
                            `corporate_service_payment_mode_fee`.`reference_value2`,
                            `corporate_service_payment_mode_fee`.`is_percentage`,
                            `corporate_service_payment_mode_fee`.`fee`,
                            `corporate_service_payment_mode_fee`.`created_at`,
                            `corporate_service_payment_mode_fee`.`created_by`,
                            `corporate_service_payment_mode_fee`.`updated_at`,
                            `corporate_service_payment_mode_fee`.`updated_by`,
                            `corporate_service_payment_mode_fee`.`deleted_at`,
                            `corporate_service_payment_mode_fee`.`deleted_by`
                        ');

        $this->db->from('`iafb_remittance`.`corporate_service_payment_mode_fee`');
        $this->db->where('`corporate_service_payment_mode_fee`.`deleted_at`', NULL);
        if($feeGroupIds !== null && count($feeGroupIds) > 0)
            $this->db->where_in('`corporate_service_payment_mode_fee`.`corporate_service_payment_mode_fee_group_id`', $feeGroupIds);
        
        $this->db->order_by("case multitier_type when 'flat' then 0 when 'less_than' then 1 when 'range' then 2 when 'greater_than' then 3 else 4 end,reference_value1 asc", null, false);
        
        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit

        $this->db->limit($limit, $offset);
        $query = $this->db->get();
        $this->db->flush_cache();
        if($query != false && $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new PaymentModeFeeCollection(), $total);
        }

        return false;
    }

    public function findListByCorporrateServicePaymentModeIds($limit, $page, array $corporateServicePaymentModeIds = NULL, array $paymentModeFeeGroupIds = NULL, $isActive = NULL, $status = NULL)
    {
        $offset = ($page - 1) * $limit;
        $this->db->start_cache(); //to cache active record query
        $this->db->select('`corporate_service_payment_mode_fee`.`id`,
                            `corporate_service_payment_mode_fee`.`corporate_service_payment_mode_fee_group_id`,
                            fg.corporate_service_payment_mode_id,
                            fg.fee_type_id,
                            scf.code as fee_type,
                            fg.name,
                            fg.status,
                            fg.approve_reject_remark,
                            fg.approve_reject_at,
                            fg.approve_reject_by,
                            `corporate_service_payment_mode_fee`.`multitier_type`,
                            `corporate_service_payment_mode_fee`.`reference_value1`,
                            `corporate_service_payment_mode_fee`.`reference_value2`,
                            `corporate_service_payment_mode_fee`.`is_percentage`,
                            `corporate_service_payment_mode_fee`.`fee`,
                            `corporate_service_payment_mode_fee`.`created_at`,
                            `corporate_service_payment_mode_fee`.`created_by`,
                            `corporate_service_payment_mode_fee`.`updated_at`,
                            `corporate_service_payment_mode_fee`.`updated_by`,
                            `corporate_service_payment_mode_fee`.`deleted_at`,
                            `corporate_service_payment_mode_fee`.`deleted_by`
                        ');

        $this->db->from('`iafb_remittance`.`corporate_service_payment_mode_fee`');
        $this->db->join('`iafb_remittance`.`corporate_service_payment_mode_fee_group` as fg', 'corporate_service_payment_mode_fee.corporate_service_payment_mode_fee_group_id = fg.id');
        $this->db->join('iafb_remittance.system_code scf', 'fg.fee_type_id = scf.id');
        $this->db->join('iafb_remittance.system_code_group scgf', 'scf.system_code_group_id = scgf.id');
        
        
        $this->db->where('`corporate_service_payment_mode_fee`.`deleted_at`', NULL);
        $this->db->where('fg.deleted_at', NULL);
        $this->db->where('scgf.code', FeeType::getSystemGroupCode());
        
        if( $paymentModeFeeGroupIds != NULL)
            $this->db->where_in('`corporate_service_payment_mode_fee`.`corporate_service_payment_mode_fee_group_id`', $paymentModeFeeGroupIds);
        
        if($corporateServicePaymentModeIds != null)
            $this->db->where_in('`fg`.`corporate_service_payment_mode_id`', $corporateServicePaymentModeIds);
        
        if($isActive !== NULL)
            $this->db->where('`fg`.`is_active`', $isActive);
        
        if($status !== NULL)
            $this->db->where('`fg`.`status`', $status);
        
        if( $paymentModeFeeGroupIds != NULL && count($paymentModeFeeGroupIds) == 1)
            $this->db->order_by("case corporate_service_payment_mode_fee.multitier_type when 'flat' then 0 when 'less_than' then 1 when 'range' then 2 when 'greater_than' then 3 else 4 end,reference_value1 asc", NULL, FALSE);
        
        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit

        $this->db->limit($limit, $offset);
        $query = $this->db->get();
        $this->db->flush_cache();
        if($query != false && $query->num_rows() > 0)
        {
           return $this->mapCollection($query->result(), new PaymentModeFeeCollection(), $total);
        }

        return false;
    }
    
    public function findById($id, $deleted = false) {
        $this->db->select('`corporate_service_payment_mode_fee`.`id`,
                            `corporate_service_payment_mode_fee`.`corporate_service_payment_mode_fee_group_id`,
                            `corporate_service_payment_mode_fee`.`multitier_type`,
                            `corporate_service_payment_mode_fee`.`reference_value1`,
                            `corporate_service_payment_mode_fee`.`reference_value2`,
                            `corporate_service_payment_mode_fee`.`is_percentage`,
                            `corporate_service_payment_mode_fee`.`fee`,
                            `corporate_service_payment_mode_fee`.`created_at`,
                            `corporate_service_payment_mode_fee`.`created_by`,
                            `corporate_service_payment_mode_fee`.`updated_at`,
                            `corporate_service_payment_mode_fee`.`updated_by`,
                            `corporate_service_payment_mode_fee`.`deleted_at`,
                            `corporate_service_payment_mode_fee`.`deleted_by`
                        ');

        $this->db->from('`iafb_remittance`.`corporate_service_payment_mode_fee`');
        if(!$deleted)
            $this->db->where('`corporate_service_payment_mode_fee`.`deleted_at`', NULL);
        
        $this->db->where('`corporate_service_payment_mode_fee`.`id`', $id);

        $query = $this->db->get();
        if($query != false && $query->num_rows() > 0)
        {
            return $this->map($query->row());
        }

        return false;
    }

    public function insert(PaymentModeFee $entity) {
        
        $this->db->set('id', $entity->getId());
        $this->db->set('corporate_service_payment_mode_fee_group_id', $entity->getCorporateServicePaymentModeFeeGroupId());
        $this->db->set('multitier_type', $entity->getMultitierType());
        $this->db->set('reference_value1', $entity->getReferenceValue1());
        $this->db->set('reference_value2', $entity->getReferenceValue2());
        $this->db->set('is_percentage', $entity->getIsPercentage());
        $this->db->set('fee', $entity->getFee());
        
        $this->db->set('created_at', IappsDateTime::now()->getUnix());
        $this->db->set('created_by', $entity->getCreatedBy());

        if( $this->db->insert('`iafb_remittance`.`corporate_service_payment_mode_fee`') )
        {
            return true;
        }

        return false;
    }

    public function update(PaymentModeFee $entity) {
        
        $this->db->set('id', $entity->getId());
        
        if( $entity->getCorporateServicePaymentModeFeeGroupId() !== null)
            $this->db->set('corporate_service_payment_mode_fee_group_id', $entity->getCorporateServicePaymentModeFeeGroupId());
        if( $entity->getMultitierType() !== null)
            $this->db->set('multitier_type', $entity->getMultitierType());
        if( $entity->getReferenceValue1() !== null)
            $this->db->set('reference_value1', $entity->getReferenceValue1());
        if( $entity->getReferenceValue2() !== null)
            $this->db->set('reference_value2', $entity->getReferenceValue2());
        if( $entity->getIsPercentage() !== null)
            $this->db->set('is_percentage', $entity->getIsPercentage());
        if( $entity->getFee() !== null)
            $this->db->set('fee', $entity->getFee());
        
        $this->db->set('updated_at', IappsDateTime::now()->getUnix());
        $this->db->set('updated_by', $entity->getUpdatedBy());
        
        $this->db->where('id', $entity->getId());

        if( $this->db->update('`iafb_remittance`.`corporate_service_payment_mode_fee`') )
        {
            return true;
        }
        return false;
    }

    public function remove(PaymentModeFee $entity, $isLogic = true) {
        
        if($isLogic)
        {
            $data_delete = array(
                'deleted_at' => IappsDateTime::now()->getUnix(),
                'deleted_by' => $entity->getUpdatedBy()
            );
            
            $this->db->where('id', $entity->getId());
            $this->db->where('deleted_at', NULL);
            if( $this->db->update('`iafb_remittance`.`corporate_service_payment_mode_fee`', $data_delete) )
            {
                return true;
            }
        }
        else
        {
            $this->db->where('id', $entity->getId());
            $this->db->where('deleted_at', NULL);
            if($this->db->delete('`iafb_remittance`.`corporate_service_payment_mode_fee`'))
            {
                return true;
            }
        }
        
        return false;
    }
    
    public function removeNotExists(PaymentModeFeeGroup $entity, array $fee_ids, $isLogic = true)
    {
        if($isLogic)
        {
            $data_delete = array(
                'deleted_at' => IappsDateTime::now()->getUnix(),
                'deleted_by' => $entity->getUpdatedBy()
            );
            
            $this->db->where('corporate_service_payment_mode_fee_group_id', $entity->getId());
            $this->db->where_not_in('id', $fee_ids);
            $this->db->where('deleted_at', NULL);
            if( $this->db->update('`iafb_remittance`.`corporate_service_payment_mode_fee`', $data_delete) )
            {
                return true;
            }
        }
        else
        {
            $this->db->where('corporate_service_payment_mode_fee_group_id', $entity->getId());
            $this->db->where_not_in('id', $fee_ids);
            $this->db->where('deleted_at', NULL);
            if($this->db->delete('`iafb_remittance`.`corporate_service_payment_mode_fee`'))
            {
                return true;
            }
        }
        
        return false;
    }

}
