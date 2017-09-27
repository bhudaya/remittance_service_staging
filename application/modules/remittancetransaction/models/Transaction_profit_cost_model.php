<?php

use Iapps\RemittanceService\RemittanceTransaction\ITransactionProfitCostDataMapper;
use Iapps\RemittanceService\RemittanceTransaction\TransactionProfitCost;
use Iapps\RemittanceService\RemittanceTransaction\TransactionProfitCostCollection;
use Iapps\Common\Core\IappsDateTime;

class Transaction_profit_cost_model extends Base_Model
                                       implements ITransactionProfitCostDataMapper{

    public function map(\stdClass $data)
    {
        $entity = new TransactionProfitCost();

        if( isset($data->profit_sharing_id) )
            $entity->setId($data->profit_sharing_id);

        if( isset($data->transaction_id) )
            $entity->setTransactionId($data->transaction_id);

        if( isset($data->type) )
            $entity->setType($data->type);

        if( isset($data->item_id) )
            $entity->setItemId($data->item_id);

        if( isset($data->beneficiary_party_id) )
            $entity->setBeneficiaryPartyId($data->beneficiary_party_id);

        if( isset($data->country_currency_code) )
            $entity->setCountryCurrencyCode($data->country_currency_code);

        if( isset($data->amount) )
            $entity->setAmount($data->amount);

        if( isset($data->created_at) )
            $entity->getCreatedAt()->fromUnix($data->created_at);

        if( isset($data->created_by) )
            $entity->setCreatedBy($data->created_by);

        if( isset($data->updated_at) )
            $entity->getUpdatedAt()->fromUnix($data->updated_at);

        if( isset($data->updated_by) )
            $entity->setUpdatedBy($data->updated_by);

        if( isset($data->deleted_at) )
            $entity->getDeletedAt()->fromUnix($data->deleted_at);

        if( isset($data->deleted_by) )
            $entity->setDeletedBy($data->deleted_by);

        return $entity;
    }

    public function findById($id, $deleted = false)
    {
        $this->db->select('id as profit_sharing_id,
                           transaction_id,
                           type,
                           item_id,
                           beneficiary_party_id,
                           country_currency_code,
                           amount,
                           created_at,
                           created_by,
                           updated_at,
                           updated_by,
                           deleted_at,
                           deleted_by');
        $this->db->from('iafb_remittance.transaction_profit_cost');
        $this->db->where('id', $id);
        if( !$deleted )
            $this->db->where('deleted_at', null);

        $query = $this->db->get();

        if( $query->num_rows() > 0 )
        {
            return $this->map($query->row());
        }

        return false;
    }

    public function findByParam(TransactionProfitCost $profitCost, $limit, $page)
    {
        $total = 0;
        if( $limit AND $page )
        {
            $offset = ($page - 1) * $limit;
            $this->db->start_cache();
        }

        $this->db->select('id as profit_sharing_id,
                           transaction_id,
                           type,
                           item_id,
                           beneficiary_party_id,
                           country_currency_code,
                           amount,
                           created_at,
                           created_by,
                           updated_at,
                           updated_by,
                           deleted_at,
                           deleted_by');
        $this->db->from('iafb_remittance.transaction_profit_cost');
        $this->db->where('deleted_at', null);

        if( $profitCost->getId() )
            $this->db->where('id', $profitCost->getId());

        if( $profitCost->getTransactionId() )
            $this->db->where('transaction_id', $profitCost->getTransactionId());

        if( $profitCost->getType() )
            $this->db->where('type', $profitCost->getType());

        if( $profitCost->getItemId() )
            $this->db->where('item_id', $profitCost->getItemId());

        if( $profitCost->getBeneficiaryPartyId() )
            $this->db->where('beneficiary_party_id', $profitCost->getBeneficiaryPartyId() );

        if( $profitCost->getCountryCurrencyCode() )
            $this->db->where('country_currency_code', $profitCost->getCountryCurrencyCode());

        if( $limit AND $page )
        {
            $this->db->stop_cache();
            $total = $this->db->count_all_results(); //to get total num of result w/o limit
            $this->db->limit($limit, $offset);
        }


        $query = $this->db->get();

        if( $limit AND $page )
            $this->db->flush_cache();
        else
            $total = $query->num_rows();

        if( $query->num_rows() > 0 )
        {
            return $this->mapCollection($query->result(), new TransactionProfitCostCollection(), $total);
        }

        return false;
    }

    public function insert(TransactionProfitCost $profitCost)
    {
        $created_at = IappsDateTime::now();
        $this->db->set('id', $profitCost->getId());
        $this->db->set('transaction_id', $profitCost->getTransactionId());
        $this->db->set('type', $profitCost->getType());
        $this->db->set('item_id', $profitCost->getItemId());
        $this->db->set('beneficiary_party_id', $profitCost->getBeneficiaryPartyId());
        $this->db->set('country_currency_code', $profitCost->getCountryCurrencyCode());
        $this->db->set('amount', $profitCost->getAmount());
        $this->db->set('created_at', $created_at->getUnix());
        $this->db->set('created_by', $profitCost->getCreatedBy());

        if( $this->db->insert('iafb_remittance.transaction_profit_cost') )
        {
            $profitCost->setCreatedAt($created_at);
            return $profitCost;
        }

        return false;
    }

    public function update(TransactionProfitCost $profitCost)
    {
        $updated_at = IappsDateTime::now();
        if( $profitCost->getTransactionId() )
            $this->db->set('transaction_id', $profitCost->getTransactionId());

        if( $profitCost->getType() )
            $this->db->set('type', $profitCost->getType() );

        if( $profitCost->getItemId() )
            $this->db->set('item_id', $profitCost->getItemId() );

        if( $profitCost->getBeneficiaryPartyId() )
            $this->db->set('beneficiary_party_id', $profitCost->getBeneficiaryPartyId());

        if( $profitCost->getCountryCurrencyCode() )
            $this->db->set('country_currency_code', $profitCost->getCountryCurrencyCode());

        if( $profitCost->getAmount() )
            $this->db->set('amount', $profitCost->getAmount());

        $this->db->set('updated_at', $updated_at->getUnix());
        $this->db->set('updated_by', $profitCost->getUpdatedBy());

        $this->db->where('id', $profitCost->getId());
        $this->db->update('iafb_remittance.transaction_profit_cost');

        if( $this->db->affected_rows() > 0 )
        {
            $profitCost->setUpdatedAt($updated_at);
            return $profitCost;
        }

        return false;
    }
}