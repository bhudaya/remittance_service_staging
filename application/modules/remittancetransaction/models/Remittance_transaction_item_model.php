<?php


use Iapps\Common\Transaction\TransactionItem;
use Iapps\RemittanceService\RemittanceTransaction\IRemittanceTransactionItemDataMapper;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransactionItem;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransactionItemCollection;
use Iapps\Common\Core\IappsDateTime;
use Iapps\RemittanceService\RemittanceTransaction\ItemType;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Remittance_transaction_item_model
 *
 * @author lichao
 */
class Remittance_transaction_item_model extends Base_Model
                               implements IRemittanceTransactionItemDataMapper{
    //put your code here
    
    public function map(stdClass $data)
    {
        $entity = new RemittanceTransactionItem();

        if( isset($data->id) )
            $entity->setId($data->id);

        if( isset($data->item_type_id) )
            $entity->getItemType()->setId($data->item_type_id);

        if( isset($data->item_type_code))
            $entity->getItemType()->setCode($data->item_type_code);

        if( isset($data->item_type_name))
            $entity->getItemType()->setDisplayName($data->item_type_name);

        if( isset($data->item_type_group_id))
            $entity->getItemType()->getGroup()->setId($data->item_type_group_id);

        if( isset($data->item_type_group_code))
            $entity->getItemType()->getGroup()->setId($data->item_type_group_code);

        if( isset($data->item_type_group_name))
            $entity->getItemType()->getGroup()->setId($data->item_type_group_name);

        if( isset($data->item_id) )
            $entity->setItemId($data->item_id);

        if( isset($data->name) )
            $entity->setName($data->name);
        if( isset($data->description) )
            $entity->setDescription($data->description);
        if( isset($data->quantity) )
            $entity->setQuantity($data->quantity);
        if( isset($data->refunded_quantity) )
            $entity->setRefundedQuantity($data->refunded_quantity);
        if( isset($data->unit_price) )
            $entity->setUnitPrice($data->unit_price);
        if( isset($data->net_amount) )
            $entity->setNetAmount($data->net_amount);

        if( isset($data->line_no) )
            $entity->setLineNumber($data->line_no);
        if( isset($data->transaction_id) )
            $entity->setTransactionId($data->transaction_id);
        if( isset($data->ref_transaction_item_id) )
            $entity->setRefTransactionItemId($data->ref_transaction_item_id);

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
    
    protected function _select()
    {
        $this->db->select('ti.id,
                           ti.item_type_id,
                           sc.code as item_type_code,
                           sc.display_name as item_type_name,
                           scg.id as item_type_group_id,
                           scg.code as item_type_group_code,
                           scg.display_name as item_type_group_name,
                           ti.item_id,
                           ti.name,
                           ti.description,
                           ti.quantity,
                           ti.refunded_quantity,
                           ti.unit_price,
                           ti.net_amount,
                           ti.line_no,
                           ti.transaction_id,
                           ti.ref_transaction_item_id,
                           ti.created_at,
                           ti.created_by,
                           ti.updated_at,
                           ti.updated_by,
                           ti.deleted_at,
                           ti.deleted_by
                        ');
    }

    public function findById($id, $deleted = false)
    {
        $this->_select();
        $this->db->from('iafb_remittance.transaction_item ti');
        $this->db->join('iafb_remittance.system_code sc', 'ti.item_type_id = sc.id');
        $this->db->join('iafb_remittance.system_code_group scg', 'sc.system_code_group_id = scg.id');
        $this->db->where('scg.code', ItemType::getSystemGroupCode());
        if(!$deleted)
        {
            $this->db->where('ti.deleted_at', NULL);
        }
        $this->db->where('ti.id', $id);

        $query = $this->db->get();
        if( $query->num_rows() > 0 )
        {
            return $this->map($query->row());
        }

        return false;
    }
    
    public function findByTransactionId($transactionID,$deleted=false)
    {
        $this->_select();
        $this->db->from('iafb_remittance.transaction_item ti');
        $this->db->join('iafb_remittance.system_code sc', 'ti.item_type_id = sc.id');
        $this->db->join('iafb_remittance.system_code_group scg', 'sc.system_code_group_id = scg.id');
        #$this->db->where('scg.code', ItemType::getSystemGroupCode());
        #$this->db->where('sc.code', ItemType::CORPORATE_SERVICE_FEE);

        if(!$deleted)
        {
            $this->db->where('ti.deleted_at', NULL);
        }
        $this->db->where('ti.transaction_id', $transactionID);

        $query = $this->db->get();
        if( $query->num_rows() > 0 )
        {
            return $this->mapCollection($query->result(), new RemittanceTransactionItemCollection(), $query->num_rows());
        }

        return false;
    }

    public function insert(TransactionItem $transactionItem)
    {
        $this->db->set('id', $transactionItem->getId());
        $this->db->set('item_type_id', $transactionItem->getItemType()->getId());
        $this->db->set('item_id', $transactionItem->getItemId());
        $this->db->set('name', $transactionItem->getName());
        $this->db->set('description', $transactionItem->getDescription());
        $this->db->set('quantity', $transactionItem->getQuantity());
        $this->db->set('refunded_quantity', $transactionItem->getRefundedQuantity());
        $this->db->set('unit_price', $transactionItem->getUnitPrice());
        $this->db->set('net_amount', $transactionItem->getNetAmount());
        $this->db->set('line_no', $transactionItem->getLineNumber());
        $this->db->set('transaction_id', $transactionItem->getTransactionId());
        $this->db->set('ref_transaction_item_id', $transactionItem->getRefTransactionItemId());
        $this->db->set('created_at', IappsDateTime::now()->getUnix());
        $this->db->set('created_by', $transactionItem->getCreatedBy());

        if( $this->db->insert('iafb_remittance.transaction_item') )
        {
            return true;
        }

        return false;
    }

    public function update(TransactionItem $item)
    {
        if($item->getRefundedQuantity() != NULL) {
            $this->db->set('refunded_quantity', $item->getRefundedQuantity());
        }

        $this->db->set('updated_at', IappsDateTime::now()->getUnix());
        $this->db->set('updated_by', $item->getUpdatedBy());
        $this->db->where('id', $item->getId());

        $this->db->update('iafb_remittance.transaction_item');
        if( $this->db->affected_rows() > 0 )
        {
            return true;
        }

        return false;
    }



    public function findByParam(TransactionItem $config, $limit, $page)
    {
        $init_predicate = true;
        $offset = ($page - 1) * $limit;
        $this->db->start_cache(); //to cache active record query


        $this->_select();
        $this->db->from('iafb_remittance.transaction_item ti');
        $this->db->join('iafb_remittance.system_code sc', 'ti.item_type_id = sc.id');
        $this->db->join('iafb_remittance.system_code_group scg', 'sc.system_code_group_id = scg.id');
        $this->db->where('scg.code', ItemType::getSystemGroupCode());
        $this->db->where('ti.deleted_at', NULL);

        if($config->getTransactionID()) {
            $this->db->like('ti.transaction_id', $config->getTransactionID());
        }

        $this->db->stop_cache();
        $total = $this->db->count_all_results(); //to get total num of result w/o limit
        $this->db->limit($limit, $offset);
        $query = $this->db->get();

        $this->db->flush_cache();
        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new RemittanceTransactionItemCollection(), $total);
        }

        return false;
    }


    public function findByTransactionIdArrAndParam(TransactionItem $item, $transaction_id_arr)
    {
        $this->_select();        
        $this->db->from('iafb_remittance.transaction_item ti');
        $this->db->join('iafb_remittance.system_code sc', 'ti.item_type_id = sc.id');
        $this->db->join('iafb_remittance.system_code_group scg', 'sc.system_code_group_id = scg.id');
        $this->db->where('scg.code', ItemType::getSystemGroupCode());
        $this->db->where('ti.deleted_at', NULL);
        $this->db->where_in('ti.transaction_id', $transaction_id_arr);        
        $query = $this->db->get();

        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new RemittanceTransactionItemCollection(), $query->num_rows());
        }

        return false;
    }



    public function updateAgentId(TransactionItem $item)
    {
        $updated_at = IappsDateTime::now()->getUnix();

        $this->db->set('agent_id', $item->getAgentId());
        $this->db->set('is_commission', $item->getIsCommission());
        $this->db->set('updated_at', $updated_at);
        $this->db->set('updated_by', $item->getUpdatedBy());

        $this->db->where('id', $item->getId());
        $this->db->update('iafb_remittance.transaction_item');
        if( $this->db->affected_rows() > 0 )
        {
            $item->getUpdatedAt()->setDateTimeUnix($updated_at);
            return true;
        }

        return false;
    }
    
    
    /*

    public function findByTransactionIdArrAndParam(TransactionItem $item, $transaction_id_arr)
    {

    }


    public function updateAgentId(TransactionItem $item)
    {
      
    }

    */
}
