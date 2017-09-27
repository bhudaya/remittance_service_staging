<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

use Iapps\RemittanceService\RemittancePaymentMode\IPaymentModeCostGroupDataMapper;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeCostGroup;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeCostGroupCollection;
use Iapps\Common\Core\IappsDateTime;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeCostGroupStatus;

/**
 * Description of Payment_mode_cost_group_model
 *
 * @author lichao
 */
class Payment_mode_cost_group_model extends Base_Model implements IPaymentModeCostGroupDataMapper {
    //put your code here
    
    public function map(\stdClass $data) {
        
        $entity = new PaymentModeCostGroup();
        
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
        
        if( isset($data->corporate_service_payment_mode_id) )
            $entity->setCorporateServicePaymentModeId($data->corporate_service_payment_mode_id);
        if( isset($data->status) )
            $entity->getStatus()->setCode($data->status);
        if( isset($data->approve_reject_remark) )
            $entity->setApproveRejectRemark($data->approve_reject_remark);
        if( isset($data->approve_reject_at) )
            $entity->setApproveRejectAt(IappsDateTime::fromUnix($data->approve_reject_at));
        if( isset($data->approve_reject_by) )
            $entity->setApproveRejectBy($data->approve_reject_by);
        if( isset($data->is_active) )
            $entity->setIsActive($data->is_active);
        if( isset($data->no_cost) )
            $entity->setNoCost($data->no_cost);
     
        return $entity;
    }
    
    public function getLastCostGroupInfo(array $paymentModeIds, $status = NULL)
    {
        $this->db->select('`payment_mode_cost_group`.`id`,
                            `payment_mode_cost_group`.`corporate_service_payment_mode_id`,
                            `payment_mode_cost_group`.`status`,
                            `payment_mode_cost_group`.`approve_reject_remark`,
                            `payment_mode_cost_group`.`approve_reject_at`,
                            `payment_mode_cost_group`.`approve_reject_by`,
                            `payment_mode_cost_group`.`is_active`,
                            `payment_mode_cost_group`.`no_cost`,
                            `payment_mode_cost_group`.`created_at`,
                            `payment_mode_cost_group`.`created_by`,
                            `payment_mode_cost_group`.`updated_at`,
                            `payment_mode_cost_group`.`updated_by`,
                            `payment_mode_cost_group`.`deleted_at`,
                            `payment_mode_cost_group`.`deleted_by`
                        ');

        $this->db->from('`iafb_remittance`.`payment_mode_cost_group`');
        $this->db->where('`payment_mode_cost_group`.`deleted_at`', NULL);
        
        if( $paymentModeIds != NULL && count($paymentModeIds) > 0)
            $this->db->where_in('payment_mode_cost_group.corporate_service_payment_mode_id', $paymentModeIds);
        else
            $this->db->where('1=2', NULL, FALSE);
        
        if($status)
            $this->db->where('payment_mode_cost_group.status', $status);
        
        if($status == PaymentModeCostGroupStatus::CODE_APPROVED)
            $this->db->order_by('payment_mode_cost_group.approve_reject_at','DESC');
//        else if($status == PaymentModeCostGroupStatus::CODE_PENDING)
//            $this->db->order_by('payment_mode_cost_group.created_at','DESC');
        else
            $this->db->order_by('payment_mode_cost_group.created_at','DESC');
        
        $this->db->limit(1);

        $query = $this->db->get();
        if($query != false && $query->num_rows() > 0)
        {
            return $this->map($query->row());
        }

        return false;
    }
    
    public function findListByCorporrateServicePaymentModeIds($limit, $page,array $corporateServicePaymentModeIds = NULL, array $paymentModeCostGroupIds = NULL, $isActive = NULL, $status = NULL, $noCost = NULL){
        
        $offset = ($page - 1) * $limit;
        $this->db->start_cache(); //to cache active record query
        $this->db->select('`payment_mode_cost_group`.`id`,
                            `payment_mode_cost_group`.`corporate_service_payment_mode_id`,
                            `payment_mode_cost_group`.`status`,
                            `payment_mode_cost_group`.`approve_reject_remark`,
                            `payment_mode_cost_group`.`approve_reject_at`,
                            `payment_mode_cost_group`.`approve_reject_by`,
                            `payment_mode_cost_group`.`is_active`,
                            `payment_mode_cost_group`.`no_cost`,
                            `payment_mode_cost_group`.`created_at`,
                            `payment_mode_cost_group`.`created_by`,
                            `payment_mode_cost_group`.`updated_at`,
                            `payment_mode_cost_group`.`updated_by`,
                            `payment_mode_cost_group`.`deleted_at`,
                            `payment_mode_cost_group`.`deleted_by`
                        ');

        $this->db->from('`iafb_remittance`.`payment_mode_cost_group`');
        
        $this->db->where('`payment_mode_cost_group`.`deleted_at`', NULL);
        
        if($corporateServicePaymentModeIds !== NULL)
            $this->db->where_in('`payment_mode_cost_group`.`corporate_service_payment_mode_id`', $corporateServicePaymentModeIds);
        if($paymentModeCostGroupIds !== NULL)
            $this->db->where_in('`payment_mode_cost_group`.`id`', $paymentModeCostGroupIds);
        if($isActive !== NULL)
            $this->db->where('`payment_mode_cost_group`.`is_active`', $isActive);
        if($status !== NULL)
            $this->db->where('`payment_mode_cost_group`.`status`', $status);
        if($noCost !== NULL)
            $this->db->where('`payment_mode_cost_group`.`no_cost`', $noCost);
        
        if($status == PaymentModeCostGroupStatus::CODE_APPROVED)
            $this->db->order_by('`payment_mode_cost_group`.`approve_reject_at`', 'DESC');
        else if($status == PaymentModeCostGroupStatus::CODE_PENDING)
        {
            $this->db->order_by('`payment_mode_cost_group`.`updated_at` DESC,`payment_mode_cost_group`.`created_at` DESC', NULL, FALSE);
        }
        
        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit

        $this->db->limit($limit, $offset);
        $query = $this->db->get();
        $this->db->flush_cache();
        if($query != false && $query->num_rows() > 0)
        {
           return $this->mapCollection($query->result(), new PaymentModeCostGroupCollection(), $total);
        }

        return false;
    }

    public function findById($id, $deleted = false) {
        $this->db->select('`payment_mode_cost_group`.`id`,
                            `payment_mode_cost_group`.`corporate_service_payment_mode_id`,
                            `payment_mode_cost_group`.`status`,
                            `payment_mode_cost_group`.`approve_reject_remark`,
                            `payment_mode_cost_group`.`approve_reject_at`,
                            `payment_mode_cost_group`.`approve_reject_by`,
                            `payment_mode_cost_group`.`is_active`,
                            `payment_mode_cost_group`.`no_cost`,
                            `payment_mode_cost_group`.`created_at`,
                            `payment_mode_cost_group`.`created_by`,
                            `payment_mode_cost_group`.`updated_at`,
                            `payment_mode_cost_group`.`updated_by`,
                            `payment_mode_cost_group`.`deleted_at`,
                            `payment_mode_cost_group`.`deleted_by`
                        ');

        $this->db->from('`iafb_remittance`.`payment_mode_cost_group`');
        
        if(!$deleted)
            $this->db->where('`payment_mode_cost_group`.`deleted_at`', NULL);
        
        $this->db->where('`payment_mode_cost_group`.`id`', $id);

        $query = $this->db->get();
        if($query != false && $query->num_rows() > 0)
        {
            return $this->map($query->row());
        }

        return false;
    }

    public function insert(PaymentModeCostGroup $entity) {
        
        $this->db->set('id', $entity->getId());
        $this->db->set('corporate_service_payment_mode_id', $entity->getCorporateServicePaymentModeId());
        
        $this->db->set('status', $entity->getStatus()->getCode());
        $this->db->set('approve_reject_remark', $entity->getApproveRejectRemark());
        $this->db->set('approve_reject_at', $entity->getApproveRejectAt()->getUnix());
        $this->db->set('approve_reject_by', $entity->getApproveRejectBy());
        
        if($entity->getIsActive() !== NULL)
            $this->db->set('is_active', $entity->getIsActive());
        if($entity->getNoCost() !== NULL)
            $this->db->set('no_cost', $entity->getNoCost());
        
        $this->db->set('created_at', IappsDateTime::now()->getUnix());
        $this->db->set('created_by', $entity->getCreatedBy());

        if( $this->db->insert('`iafb_remittance`.`payment_mode_cost_group`') )
        {
            return true;
        }

        return false;
    }

    public function update(PaymentModeCostGroup $entity) {
        
        $this->db->set('id', $entity->getId());
        
        if( $entity->getCorporateServicePaymentModeId() !== null)
            $this->db->set('corporate_service_payment_mode_id', $entity->getCorporateServicePaymentModeId());
        
        if( $entity->getStatus() !== null && $entity->getStatus()->getCode() !== null)
            $this->db->set('status', $entity->getStatus()->getCode());
        if( $entity->getApproveRejectRemark() !== null)
            $this->db->set('approve_reject_remark', $entity->getApproveRejectRemark());
        if( $entity->getApproveRejectAt() !== null)
            $this->db->set('approve_reject_at', $entity->getApproveRejectAt()->getUnix());
        if( $entity->getApproveRejectBy() !== null)
            $this->db->set('approve_reject_by', $entity->getApproveRejectBy());
        if( $entity->getIsActive() !== null)
            $this->db->set('is_active', $entity->getIsActive());
        if($entity->getNoCost() !== NULL)
            $this->db->set('no_cost', $entity->getNoCost());
        
        $this->db->set('updated_at', IappsDateTime::now()->getUnix());
        $this->db->set('updated_by', $entity->getUpdatedBy());
        
        $this->db->where('id', $entity->getId());

        if( $this->db->update('`iafb_remittance`.`payment_mode_cost_group`') )
        {
            return true;
        }
        return false;
    }

    public function updateStatus(PaymentModeCostGroup $entity) {
        
        $this->db->set('id', $entity->getId());
        
        if( $entity->getStatus() !== null && $entity->getStatus()->getCode() !== null)
            $this->db->set('status', $entity->getStatus()->getCode());
        if($entity->getIsActive() !== null)
            $this->db->set('is_active', $entity->getIsActive());
        if( $entity->getApproveRejectRemark() !== null)
            $this->db->set('approve_reject_remark', $entity->getApproveRejectRemark());
        if( $entity->getApproveRejectAt() !== null)
            $this->db->set('approve_reject_at', $entity->getApproveRejectAt()->getUnix());
        if( $entity->getApproveRejectBy() !== null)
            $this->db->set('approve_reject_by', $entity->getApproveRejectBy());
        
        // update status; no need update updated_by and updated_at
//        $this->db->set('updated_at', IappsDateTime::now()->getUnix());
//        $this->db->set('updated_by', $entity->getUpdatedBy());
        
        $this->db->where('id', $entity->getId());

        if( $this->db->update('`iafb_remittance`.`payment_mode_cost_group`') )
        {
            return true;
        }
        return false;
    }
    
    public function remove(PaymentModeCostGroup $entity, $isLogic = true)
    {
        if($isLogic)
        {
            $data_delete = array(
                'deleted_at' => IappsDateTime::now()->getUnix(),
                'deleted_by' => $entity->getUpdatedBy()
            );
            
            $this->db->where('id', $entity->getId());
            $this->db->where('deleted_at', NULL);
            if( $this->db->update('`iafb_remittance`.`payment_mode_cost_group`', $data_delete) )
            {
                return true;
            }
        }
        else
        {
            $this->db->where('id', $entity->getId());
            $this->db->where('deleted_at', NULL);
            if($this->db->delete('`iafb_remittance`.`payment_mode_cost_group`'))
            {
                return true;
            }
        }
        
        return false;
    }

}
