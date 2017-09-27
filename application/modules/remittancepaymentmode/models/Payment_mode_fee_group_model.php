<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

use Iapps\RemittanceService\RemittancePaymentMode\IPaymentModeFeeGroupDataMapper;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeFeeGroup;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeFeeGroupCollection;
use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\CorporateService\FeeType;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeFeeGroupStatus;

/**
 * Description of Payment_mode_fee_group_model
 *
 * @author lichao
 */
class Payment_mode_fee_group_model extends Base_Model implements IPaymentModeFeeGroupDataMapper {
    //put your code here
    
    public function map(\stdClass $data) {
        
        $entity = new PaymentModeFeeGroup();
        
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
        if( isset($data->fee_type_id) )
            $entity->getFeeType()->setId($data->fee_type_id);
        if( isset($data->fee_type) )
            $entity->getFeeType()->setCode($data->fee_type);
        if( isset($data->name) )
            $entity->setName($data->name);
        
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
        
        return $entity;
    }
    
    public function getLastFeeGroupInfo(array $paymentModeIds, $status = NULL)
    {
        $this->db->select('fg.id,
                            fg.corporate_service_payment_mode_id,
                            fg.fee_type_id,
                            scf.code as fee_type,
                            fg.name,
                            fg.status,
                            fg.approve_reject_remark,
                            fg.approve_reject_at,
                            fg.approve_reject_by,
                            fg.is_active,
                            fg.created_at,
                            fg.created_by,
                            fg.updated_at,
                            fg.updated_by,
                            fg.deleted_at,
                            fg.deleted_by');

        $this->db->from('iafb_remittance.corporate_service_payment_mode_fee_group fg');
        $this->db->join('iafb_remittance.system_code scf', 'fg.fee_type_id = scf.id');
        $this->db->join('iafb_remittance.system_code_group scgf', 'scf.system_code_group_id = scgf.id');
        $this->db->where('scgf.code', FeeType::getSystemGroupCode());
        $this->db->where('fg.deleted_at', NULL);
        
        if($paymentModeIds != NULL && count($paymentModeIds) > 0)
            $this->db->where_in('fg.corporate_service_payment_mode_id', $paymentModeIds);
        else
            $this->db->where('1=2', NULL, FALSE);
        
        if($status)
            $this->db->where('fg.status', $status);
        
        if($status == PaymentModeFeeGroupStatus::CODE_APPROVED)
            $this->db->order_by('fg.approve_reject_at','DESC');
//        else if($status == PaymentModeFeeGroupStatus::CODE_PENDING)
//            $this->db->order_by('fg.created_at','DESC');
        else
            $this->db->order_by('fg.created_at','DESC');
        $this->db->limit(1);

        $query = $this->db->get();
        if($query != false && $query->num_rows() > 0)
        {
            return $this->map($query->row());
        }

        return false;
    }
    
    public function findListByCorporrateServicePaymentModeIds($limit, $page,array $corporateServicePaymentModeIds = NULL, array $paymentModeFeeGroupIds = NULL, $isActive = NULL, $status = NULL){
        
        $offset = ($page - 1) * $limit;
        $this->db->start_cache(); //to cache active record query
        $this->db->select('fg.id,
                            fg.corporate_service_payment_mode_id,
                            fg.fee_type_id,
                            scf.code as fee_type,
                            fg.name,
                            fg.status,
                            fg.approve_reject_remark,
                            fg.approve_reject_at,
                            fg.approve_reject_by,
                            fg.is_active,
                            fg.created_at,
                            fg.created_by,
                            fg.updated_at,
                            fg.updated_by,
                            fg.deleted_at,
                            fg.deleted_by');

        $this->db->from('iafb_remittance.corporate_service_payment_mode_fee_group fg');
        $this->db->join('iafb_remittance.system_code scf', 'fg.fee_type_id = scf.id');
        $this->db->join('iafb_remittance.system_code_group scgf', 'scf.system_code_group_id = scgf.id');
        $this->db->where('fg.deleted_at', NULL);
        $this->db->where('scgf.code', FeeType::getSystemGroupCode());
        if( $corporateServicePaymentModeIds !== NULL )
            $this->db->where_in('fg.corporate_service_payment_mode_id', $corporateServicePaymentModeIds);
        if( $paymentModeFeeGroupIds !== NULL )
            $this->db->where_in('fg.id', $paymentModeFeeGroupIds);
        if($isActive !== NULL)
            $this->db->where('fg.is_active', $isActive);
        if($status !== NULL)
            $this->db->where('fg.status', $status);
        
        if($status == PaymentModeFeeGroupStatus::CODE_APPROVED)
            $this->db->order_by('`fg`.`approve_reject_at`', 'DESC');
        else if($status == PaymentModeFeeGroupStatus::CODE_PENDING)
        {
            $this->db->order_by('`fg`.`updated_at` DESC,`fg`.`created_at` DESC', NULL, FALSE);
        }
        
        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit

        $this->db->limit($limit, $offset);
        $query = $this->db->get();
        $this->db->flush_cache();
        if($query != false && $query->num_rows() > 0)
        {
           return $this->mapCollection($query->result(), new PaymentModeFeeGroupCollection(), $total);
        }

        return false;
    }

    public function findActiveByCorporateServicePaymentModeId($corporate_service_payment_mode_id)
    {
        $this->db->select('fg.id,
                           fg.corporate_service_payment_mode_id,
                           fg.fee_type_id,
                           scf.code as fee_type,
                           fg.name,
                           fg.status,
                           fg.approve_reject_remark,
                           fg.approve_reject_at,
                           fg.approve_reject_by,
                           fg.is_active,
                           fg.created_at,
                           fg.created_by,
                           fg.updated_at,
                           fg.updated_by,
                           fg.deleted_at,
                           fg.deleted_by');

        $this->db->from('iafb_remittance.corporate_service_payment_mode_fee_group fg');
        $this->db->join('iafb_remittance.system_code scf', 'fg.fee_type_id = scf.id');
        $this->db->join('iafb_remittance.system_code_group scgf', 'scf.system_code_group_id = scgf.id');
        $this->db->where('fg.deleted_at', NULL);
        $this->db->where('scgf.code', FeeType::getSystemGroupCode());

        $this->db->where('fg.corporate_service_payment_mode_id', $corporate_service_payment_mode_id);
        $this->db->where('fg.is_active', 1);
        $this->db->where('fg.status', PaymentModeFeeGroupStatus::CODE_APPROVED);

        $this->db->limit(1);

        $query = $this->db->get();
        if($query != false && $query->num_rows() > 0)
        {
            return $this->map($query->row());
        }

        return false;
    }

    public function findById($id, $deleted = false) {
        $this->db->select('fg.id,
                            fg.corporate_service_payment_mode_id,
                            fg.fee_type_id,
                            scf.code as fee_type,
                            fg.name,
                            fg.status,
                            fg.approve_reject_remark,
                            fg.approve_reject_at,
                            fg.approve_reject_by,
                            fg.is_active,
                            fg.created_at,
                            fg.created_by,
                            fg.updated_at,
                            fg.updated_by,
                            fg.deleted_at,
                            fg.deleted_by');

        $this->db->from('iafb_remittance.corporate_service_payment_mode_fee_group fg');
        $this->db->join('iafb_remittance.system_code scf', 'fg.fee_type_id = scf.id');
        $this->db->join('iafb_remittance.system_code_group scgf', 'scf.system_code_group_id = scgf.id');
        $this->db->where('scgf.code', FeeType::getSystemGroupCode());
        
        if(!$deleted)
            $this->db->where('fg.deleted_at', NULL);
        
        $this->db->where('fg.id', $id);

        $query = $this->db->get();
        if($query != false && $query->num_rows() > 0)
        {
            return $this->map($query->row());
        }

        return false;
    }

    public function insert(PaymentModeFeeGroup $entity) {
        
        $this->db->set('id', $entity->getId());
        $this->db->set('corporate_service_payment_mode_id', $entity->getCorporateServicePaymentModeId());
        $this->db->set('fee_type_id', $entity->getFeeType()->getId());
        $this->db->set('name', $entity->getName());
        $this->db->set('status', $entity->getStatus()->getCode());
        $this->db->set('approve_reject_remark', $entity->getApproveRejectRemark());
        $this->db->set('approve_reject_at', $entity->getApproveRejectAt()->getUnix());
        $this->db->set('approve_reject_by', $entity->getApproveRejectBy());
        
        if($entity->getIsActive() !== NULL)
            $this->db->set('is_active', $entity->getIsActive());
        
        $this->db->set('created_at', IappsDateTime::now()->getUnix());
        $this->db->set('created_by', $entity->getCreatedBy());

        if( $this->db->insert('`iafb_remittance`.`corporate_service_payment_mode_fee_group`') )
        {
            return true;
        }

        return false;
    }

    public function update(PaymentModeFeeGroup $entity) {
        
        $this->db->set('id', $entity->getId());
        
        if( $entity->getCorporateServicePaymentModeId() !== null)
            $this->db->set('corporate_service_payment_mode_id', $entity->getCorporateServicePaymentModeId());
        
        if( $entity->getFeeType() !== null && $entity->getFeeType()->getId() !== null)
            $this->db->set('fee_type_id', $entity->getFeeType()->getId());
        if( $entity->getName() !== null)
            $this->db->set('name', $entity->getId());
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
        
        $this->db->set('updated_at', IappsDateTime::now()->getUnix());
        $this->db->set('updated_by', $entity->getUpdatedBy());
        
        $this->db->where('id', $entity->getId());

        if( $this->db->update('`iafb_remittance`.`corporate_service_payment_mode_fee_group`') )
        {
            return true;
        }
        return false;
    }

    public function updateStatus(PaymentModeFeeGroup $entity) {
        
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
        
        // update status: no need update updated_by and updated_at
//        $this->db->set('updated_at', IappsDateTime::now()->getUnix());
//        $this->db->set('updated_by', $entity->getUpdatedBy());
        
        $this->db->where('id', $entity->getId());

        if( $this->db->update('`iafb_remittance`.`corporate_service_payment_mode_fee_group`') )
        {
            return true;
        }
        return false;
    }
    
    public function remove(PaymentModeFeeGroup $entity, $isLogic = true)
    {
        if($isLogic)
        {
            $data_delete = array(
                'deleted_at' => IappsDateTime::now()->getUnix(),
                'deleted_by' => $entity->getUpdatedBy()
            );
            
            $this->db->where('id', $entity->getId());
            $this->db->where('deleted_at', NULL);
            if( $this->db->update('`iafb_remittance`.`corporate_service_payment_mode_fee_group`', $data_delete) )
            {
                return true;
            }
        }
        else
        {
            $this->db->where('id', $entity->getId());
            $this->db->where('deleted_at', NULL);
            if($this->db->delete('`iafb_remittance`.`corporate_service_payment_mode_fee_group`'))
            {
                return true;
            }
        }
        
        return false;
    }

}
