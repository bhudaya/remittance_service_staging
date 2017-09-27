<?php


use Iapps\RemittanceService\RemittanceTransaction\IRemittanceTransactionDataMapper;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransaction;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransactionCollection;

use Iapps\Common\Transaction\Transaction;
use Iapps\Common\SystemCode;
use Iapps\Common\Core\IappsDateTime;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Remittance_transaction_model
 *
 * @author lichao
 */
class Remittance_transaction_model extends Base_Model
                               implements IRemittanceTransactionDataMapper{

    public function TransBegin(){
        $this->db->trans_begin();
    }

    public function TransCommit(){
        $this->db->trans_commit();
    }

    public function TransStatus(){
        $this->db->trans_status();
    }

    public function map(stdClass $data)
    {
        $entity = new RemittanceTransaction();

        if( isset($data->id) )
            $entity->setId($data->id);
        if( isset($data->transactionID) )
            $entity->setTransactionID($data->transactionID);
        if( isset($data->transaction_type_id) )
            $entity->getTransactionType()->setId($data->transaction_type_id);
        if( isset($data->transaction_type_code))
            $entity->getTransactionType()->setCode($data->transaction_type_code);
        if( isset($data->transaction_type_name))
            $entity->getTransactionType()->setDisplayName($data->transaction_type_name);
        if( isset($data->user_profile_id) )
            $entity->setUserProfileId($data->user_profile_id);
        if( isset($data->recipient_id) )
            $entity->setRecipientId($data->recipient_id);
        if( isset($data->status_id) )
            $entity->getStatus()->setId($data->status_id);
        if( isset($data->status_code))
            $entity->getStatus()->setCode($data->status_code);
        if( isset($data->country_currency_code) )
            $entity->setCountryCurrencyCode($data->country_currency_code);
        if( isset($data->description) )
            $entity->setDescription($data->description);
        if( isset($data->remark) )
            $entity->setRemark($data->remark);

        if( isset($data->confirm_payment_code) )
           $entity->setConfirmPaymentCode($data->confirm_payment_code);

        if( isset($data->confirm_collection_code) )
            $entity->setConfirmCollectionMode($data->confirm_collection_code);

        if( isset($data->ref_transaction_id) )
            $entity->setRefTransactionId($data->ref_transaction_id);
        if( isset($data->passcode) )
            $entity->setPasscode($data->passcode);
        if( isset($data->channel_id) )
            $entity->getChannel()->setId($data->channel_id);

        if( isset($data->channel_code) )
            $entity->getChannel()->setCode($data->channel_code);

        if( isset($data->expired_date) )
            $entity->setExpiredDate(IappsDateTime::fromUnix ($data->expired_date));
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
        $this->db->select('`transaction`.`id`,
                            `transaction`.`transactionID`,
                            `transaction`.`transaction_type_id`,
                            transaction_code.code as transaction_type_code,
                            `transaction`.`user_profile_id`,
                            `transaction`.`recipient_id`,
                            `transaction`.`status_id`,
                            status_code.code as status_code,
                            `transaction`.`country_currency_code`,
                            `transaction`.`remark`,
                            `transaction`.`ref_transaction_id`,
                            `transaction`.`confirm_payment_code`,
                            `transaction`.`confirm_collection_code`,
                            `transaction`.`passcode`,
                            `transaction`.`channel_id`,
                            `transaction`.`description`,
                            channel_code.code as channel_code,
                            `transaction`.`expired_date`,
                            `transaction`.`created_at`,
                            `transaction`.`created_by`,
                            `transaction`.`updated_at`,
                            `transaction`.`updated_by`,
                            `transaction`.`deleted_at`,
                            `transaction`.`deleted_by`
                          ');
        $this->db->from('`iafb_remittance`.`transaction`');
        $this->db->join('`iafb_remittance`.`system_code` as transaction_code','transaction_code.id = transaction.transaction_type_id','LEFT');
        $this->db->join('`iafb_remittance`.`system_code` as status_code','status_code.id = transaction.status_id','LEFT');
        $this->db->join('`iafb_remittance`.`system_code` as channel_code','channel_code.id = transaction.channel_id','LEFT');
        if(!$deleted)
        {
            $this->db->where('transaction.deleted_at', NULL);
            $this->db->where('transaction_code.deleted_at', NULL);
            $this->db->where('status_code.deleted_at', NULL);
            $this->db->where('channel_code.deleted_at', NULL);
        }
        $this->db->where('transaction.id', $id);

        $query = $this->db->get();

        if( $query->num_rows() > 0 )
        {
            return $this->map($query->row());
        }

        return false;
    }
    
    public function findByTransactionID($transactionID)
    {
        $this->db->select('`transaction`.`id`,
                            `transaction`.`transactionID`,
                            `transaction`.`transaction_type_id`,
                            transaction_code.code as transaction_type_code,
                            transaction_code.display_name as transaction_type_name,
                            `transaction`.`user_profile_id`,
                            `transaction`.`recipient_id`,
                            `transaction`.`status_id`,
                            status_code.code as status_code,
                            `transaction`.`country_currency_code`,
                            `transaction`.`remark`,
                            `transaction`.`ref_transaction_id`,
                            `transaction`.`confirm_payment_code`,
                            `transaction`.`confirm_collection_code`,
                            `transaction`.`passcode`,
                            `transaction`.`channel_id`,
                            `transaction`.`description`,
                            channel_code.code as channel_code,
                            `transaction`.`expired_date`,
                            `transaction`.`created_at`,
                            `transaction`.`created_by`,
                            `transaction`.`updated_at`,
                            `transaction`.`updated_by`,
                            `transaction`.`deleted_at`,
                            `transaction`.`deleted_by`
                          ');
        $this->db->from('`iafb_remittance`.`transaction`');
        $this->db->join('`iafb_remittance`.`system_code` as transaction_code','transaction_code.id = transaction.transaction_type_id','LEFT');
        $this->db->join('`iafb_remittance`.`system_code` as status_code','status_code.id = transaction.status_id','LEFT');
        $this->db->join('`iafb_remittance`.`system_code` as channel_code','channel_code.id = transaction.channel_id','LEFT');
        $this->db->where('transaction.transactionID', $transactionID);
        $this->db->where('transaction.deleted_at', NULL);
        $this->db->where('transaction_code.deleted_at', NULL);
        $this->db->where('status_code.deleted_at', NULL);
        $this->db->where('channel_code.deleted_at', NULL);

        $query = $this->db->get();
        if( $query->num_rows() > 0 )
        {
            return $this->map($query->row());
        }

        return false;
    }
    
    public function findByUserProfileId($user_profile_id)
    {
        return false;
    }
    public function findActiveExpiredTransaction()
    {
        return false;
    }

    public function findAll()
    {
//        $offset = ($page - 1) * $limit;
        $this->db->start_cache(); //to cache active record query
        $this->db->select('`id`,`transactionID`,`transaction_type_id`,
            `user_profile_id`,`status_id`,`country_currency_code`,`remark`,
            `ref_transaction_id`,`confirm_payment_code`,`confirm_collection_code`,
            `passcode`,`description`,`channel_id`,`expired_date`,
            `created_at`,`created_by`,`updated_at`,`updated_by`,`deleted_at`,`deleted_by`
            ');

        $this->db->from('iafb_remittance.transaction');
        $this->db->where('deleted_at', NULL);
        
        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit

//        $this->db->limit($limit, $offset);
        $query = $this->db->get();
        
        $this->db->flush_cache();

        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new RemittanceTransactionCollection(), $total);
        }

        return false;
    }
    
    public function updateStatus(Transaction $transaction)
    {
        $this->db->set('status_id', $transaction->getStatus()->getId());
        $this->db->set('updated_at', IappsDateTime::now()->getUnix());
        $this->db->set('updated_by', $transaction->getUpdatedBy());
        $this->db->where('id', $transaction->getId());

        if( $this->db->update('iafb_remittance.transaction') )
        {
            return true;
        }
        
        return false;
    }

    public function insert(Transaction $transaction)
    {
        if( $transaction instanceof RemittanceTransaction )
        {
            $created_at = IappsDateTime::now();

            $this->db->set('id', $transaction->getId());
            $this->db->set('transactionID', $transaction->getTransactionID());
            $this->db->set('transaction_type_id', $transaction->getTransactionType()->getId());
            $this->db->set('user_profile_id', $transaction->getUserProfileId());
            $this->db->set('recipient_id', $transaction->getRecipientId());
            $this->db->set('status_id', $transaction->getStatus()->getId());
            $this->db->set('country_currency_code', $transaction->getCountryCurrencyCode());
            $this->db->set('description', $transaction->getDescription());
            $this->db->set('remark', $transaction->getRemark());
            $this->db->set('ref_transaction_id', $transaction->getRefTransactionId());
            $this->db->set('confirm_payment_code', $transaction->getConfirmPaymentCode());
            $this->db->set('confirm_collection_code', $transaction->getConfirmCollectionMode());
            $this->db->set('passcode', $transaction->getPasscode());
            $this->db->set('channel_id', $transaction->getChannel()->getId());
            $this->db->set('expired_date', $transaction->getExpiredDate()->getUnix());
            $this->db->set('created_at', $created_at->getUnix());
            $this->db->set('created_by', $transaction->getCreatedBy());

            if( $this->db->insert('iafb_remittance.transaction') )
            {
                $transaction->setCreatedAt($created_at);
                return $transaction;
            }
        }

        return false;
    }

    public function update(Transaction $transaction)
    {
        if($transaction->getConfirmPaymentCode() != NULL) {
            $this->db->set('confirm_payment_code', $transaction->getConfirmPaymentCode());
        }

        if( $transaction->getUserProfileId() != NULL ) {
            $this->db->set('user_profile_id', $transaction->getUserProfileId());
        }

        if($transaction->getExpiredDate() != NULL) {
            $this->db->set('expired_date', $transaction->getExpiredDate()->getUnix());
        }

        if($transaction->getRefTransactionId() != NULL) {
            $this->db->set('ref_transaction_id', $transaction->getRefTransactionId());
        }

        $this->db->set('updated_at', IappsDateTime::now()->getUnix());
        $this->db->set('updated_by', $transaction->getUpdatedBy());
        $this->db->where('id', $transaction->getId());

        if( $this->db->update('iafb_remittance.transaction') )
        {
            return true;
        }

        return false;
    }



    public function findList($limit, $page, $transaction_type_id = null, $status_id_str = null, 
            $user_profile_id = null, $agent_id = null,
            $start_time = null, $end_time = null)
    {
        $offset = ($page - 1) * $limit;
        $this->db->start_cache(); //to cache active record query
        
        $this->db->select('`transaction`.`id`,
                            `transaction`.`transactionID`,
                            `transaction`.`transaction_type_id`,
                            transaction_code.code as transaction_type_code,
                            `transaction`.`user_profile_id`,
                            `transaction`.`recipient_id`,
                            `transaction`.`status_id`,
                            status_code.code as status_code,
                            `transaction`.`country_currency_code`,
                            `transaction`.`remark`,
                            `transaction`.`ref_transaction_id`,
                            `transaction`.`confirm_payment_code`,
                            `transaction`.`confirm_collection_code`,
                            `transaction`.`passcode`,
                            `transaction`.`channel_id`,
                            `transaction`.`description`,
                            channel_code.code as channel_code,
                            `transaction`.`expired_date`,
                            `transaction`.`created_at`,
                            `transaction`.`created_by`,
                            `transaction`.`updated_at`,
                            `transaction`.`updated_by`,
                            `transaction`.`deleted_at`,
                            `transaction`.`deleted_by`
                          ');
        $this->db->from('`iafb_remittance`.`transaction`');
        $this->db->join('`iafb_remittance`.`system_code` as transaction_code','transaction_code.id = transaction.transaction_type_id','LEFT');
        $this->db->join('`iafb_remittance`.`system_code` as status_code','status_code.id = transaction.status_id','LEFT');
        $this->db->join('`iafb_remittance`.`system_code` as channel_code','channel_code.id = transaction.channel_id','LEFT');

        $this->db->where('transaction.deleted_at', NULL);
        $this->db->where('transaction_code.deleted_at', NULL);
        $this->db->where('status_code.deleted_at', NULL);
        $this->db->where('channel_code.deleted_at', NULL);
        
        if (!empty($transaction_type_id)) {
            $this->db->where('transaction.transaction_type_id', $transaction_type_id);
        }
        if (!empty($status_id_str)) {
            $this->db->where_in('transaction.status_id', $status_id_str);
        }
        if (!empty($user_profile_id)) {
            $this->db->where('transaction.user_profile_id', $user_profile_id);
        }
        if (!empty($agent_id)) {
            $this->db->where('transaction.agent_id', $agent_id);
        }
        if (!empty($start_time)) {
            $this->db->where('transaction.created_at>=$start_time', false);
        }
        if (!empty($end_time)) {
            $this->db->where('transaction.created_at<=$end_time', false);
        }

        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit

        $this->db->limit($limit, $offset);
        $query = $this->db->get();
        
        $this->db->flush_cache();

        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new RemittanceTransactionCollection(), $total);
        }
        
        return false;
    }

    public function getTransactionByRecipientId($recipientId)
    {
        $this->db->start_cache(); //to cache active record query
        $this->db->select('`transaction`.`id`,
                            `transaction`.`transactionID`,
                            `transaction`.`transaction_type_id`,
                            transaction_code.code as transaction_type_code,
                            `transaction`.`user_profile_id`,
                            `transaction`.`recipient_id`,
                            `transaction`.`status_id`,
                            status_code.code as status_code,
                            `transaction`.`country_currency_code`,
                            `transaction`.`remark`,
                            `transaction`.`ref_transaction_id`,
                            `transaction`.`confirm_payment_code`,
                            `transaction`.`confirm_collection_code`,
                            `transaction`.`passcode`,
                            `transaction`.`description`,
                            `transaction`.`channel_id`,
                            channel_code.code as channel_code,
                            `transaction`.`expired_date`,
                            `transaction`.`created_at`,
                            `transaction`.`created_by`,
                            `transaction`.`updated_at`,
                            `transaction`.`updated_by`,
                            `transaction`.`deleted_at`,
                            `transaction`.`deleted_by`
                          ');

        $this->db->from('iafb_remittance.transaction');
        $this->db->join('iafb_remittance.system_code as transaction_code','transaction_code.id = transaction.transaction_type_id','LEFT');
        $this->db->join('iafb_remittance.system_code as status_code','status_code.id = transaction.status_id','LEFT');
        $this->db->join('iafb_remittance.system_code as channel_code','channel_code.id = transaction.channel_id','LEFT');
        $this->db->where('transaction.deleted_at', NULL);
        $this->db->where('transaction.recipient_id', $recipientId);

        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit

        $query = $this->db->get();
        
        $this->db->flush_cache();

        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new RemittanceTransactionCollection(), $total);
        }

        return false;
    }



    //------

    public function findByParam(Transaction $config, $limit, $page)
    {

        // 'tr`.`description`,


        $init_predicate = true;
        $offset = ($page - 1) * $limit;
        $this->db->start_cache(); //to cache active record query
        $this->db->select('`tr`.`id`,
                            `tr`.`transactionID`,
                            `tr`.`transaction_type_id`,
                            tsc.code as transaction_type_code,
                            tsc.display_name as transaction_type_name,
                            tsc.description as transaction_type_desc,
                            `tr`.`user_profile_id`,
                            `tr`.`status_id`,
                            ssc.code as status_code,
                            `tr`.`country_currency_code`,
                            `tr`.`remark`,
                            `tr`.`ref_transaction_id`,
                            `tr`.`confirm_payment_code`,
                            `tr`.`passcode`,
                            `tr`.`channel_id`,
                            `tr`.`description`,
                            csc.code as channel_code,
                            `tr`.`expired_date`,
                            `tr`.`created_at`,
                            `tr`.`created_by`,
                            `tr`.`updated_at`,
                            `tr`.`updated_by`,
                            `tr`.`deleted_at`,
                            `tr`.`deleted_by`
                          ');
        $this->db->from('`iafb_remittance`.`transaction` as tr');
        $this->db->join('`iafb_remittance`.`system_code` as tsc','tsc.id = tr.transaction_type_id','LEFT');
        $this->db->join('`iafb_remittance`.`system_code` as ssc','ssc.id = tr.status_id','LEFT');
        $this->db->join('`iafb_remittance`.`system_code` as csc','csc.id = tr.channel_id','LEFT');
        $this->db->where('tr.deleted_at', NULL);
        $this->db->where('tsc.deleted_at', NULL);
        $this->db->where('ssc.deleted_at', NULL);
        $this->db->where('csc.deleted_at', NULL);

        if($config->getId())
            $this->db->where('tr.id', $config->getId());

        if($config->getUserProfileId())
            $this->db->where('tr.user_profile_id', $config->getUserProfileId());

        if($config->getTransactionID())
            $this->db->where('tr.transactionID', $config->getTransactionID());

        if( !$config->getExpiredDate()->isNull() )
            $this->db->where('tr.expired_date <', $config->getExpiredDate()->getUnix());

        if( $config->getTransactionType()->getCode() )
            $this->db->where('tsc.code', $config->getTransactionType()->getCode());

        if( $config->getStatus()->getCode() )
            $this->db->where('ssc.code', $config->getStatus()->getCode());

        if( $config->getRefTransactionId() )
            $this->db->where('tr.ref_transaction_id', $config->getRefTransactionId());

        $this->db->order_by("tr.created_at", "desc");

        $this->db->stop_cache();
        $total = $this->db->count_all_results(); //to get total num of result w/o limit
        $this->db->limit($limit, $offset);

        $query = $this->db->get();

        //print_r($query);

        $this->db->flush_cache();
        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new RemittanceTransactionCollection(), $total);
        }

        return false;
    }





    public function findByDate(Transaction $config, $limit, $page)
    {
        $init_predicate = true;
        $offset = ($page - 1) * $limit;
        $this->db->start_cache(); //to cache active record query
        $this->db->select('`tr`.`id`,
                            `tr`.`transactionID`,
                            `tr`.`transaction_type_id`,
                            tsc.code as transaction_type_code,
                            tsc.display_name as transaction_type_name,
                            tsc.description as transaction_type_desc,
                            `tr`.`user_profile_id`,
                            `tr`.`status_id`,
                            ssc.code as status_code,
                            `tr`.`country_currency_code`,
                            `tr`.`remark`,
                            `tr`.`ref_transaction_id`,
                            `tr`.`confirm_payment_code`,
                            `tr`.`passcode`,
                            `tr`.`channel_id`,
                            `tr`.`description`,
                            csc.code as channel_code,
                            `tr`.`expired_date`,
                            `tr`.`created_at`,
                            `tr`.`created_by`,
                            `tr`.`updated_at`,
                            `tr`.`updated_by`,
                            `tr`.`deleted_at`,
                            `tr`.`deleted_by`
                          ');

        $this->db->from('`iafb_remittance`.`transaction` as tr');
        $this->db->join('`iafb_remittance`.`system_code` as tsc','tsc.id = tr.transaction_type_id','LEFT');
        $this->db->join('`iafb_remittance`.`system_code` as ssc','ssc.id = tr.status_id','LEFT');
        $this->db->join('`iafb_remittance`.`system_code` as csc','csc.id = tr.channel_id','LEFT');
        $this->db->where('tr.deleted_at', NULL);
        $this->db->where('tsc.deleted_at', NULL);
        $this->db->where('ssc.deleted_at', NULL);
        $this->db->where('csc.deleted_at', NULL);


        if($config->getDateFrom()){
            $this->db->where('tr.created_at >=', $config->getDateFrom()->getUnix());
        }

        if($config->getDateTo()){
            $this->db->where('tr.created_at <=', $config->getDateTo()->getUnix());
        }

        if($config->getTransactionID()) {
            $this->db->where('tr.transactionID', $config->getTransactionID());
        }

        $this->db->where('tr.user_profile_id', $config->getUserProfileId() );

        $this->db->order_by("tr.created_at", "desc");

        $this->db->stop_cache();
        $total = $this->db->count_all_results(); //to get total num of result w/o limit
        $this->db->limit($limit, $offset);
        $query = $this->db->get();
        $this->db->flush_cache();
        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new RemittanceTransactionCollection(), $total);
        }

        return false;
    }


    public function findByTransactionIDArrByDate(Transaction $config, $transactionID_arr , $limit ,$page)
    {
        $init_predicate = true;
        $offset = ($page - 1) * $limit;
        $this->db->start_cache(); //to cache active record query


        $this->db->select('`tr`.`id`,
                            `tr`.`transactionID`,
                            `tr`.`transaction_type_id`,
                            tsc.code as transaction_type_code,
                            tsc.display_name as transaction_type_name,
                            tsc.description as transaction_type_desc,
                            `tr`.`user_profile_id`,
                            `tr`.`status_id`,
                            ssc.code as status_code,
                            `tr`.`country_currency_code`,
                            `tr`.`remark`,
                            `tr`.`ref_transaction_id`,
                            `tr`.`confirm_payment_code`,
                            `tr`.`passcode`,
                            `tr`.`channel_id`,
                            `tr`.`description`,
                            csc.code as channel_code,
                            `tr`.`expired_date`,
                            `tr`.`created_at`,
                            `tr`.`created_by`,
                            `tr`.`updated_at`,
                            `tr`.`updated_by`,
                            `tr`.`deleted_at`,
                            `tr`.`deleted_by`
                          ');
        $this->db->from('`iafb_remittance`.`transaction` as tr');
        $this->db->join('`iafb_remittance`.`system_code` as tsc','tsc.id = tr.transaction_type_id','LEFT');
        $this->db->join('`iafb_remittance`.`system_code` as ssc','ssc.id = tr.status_id','LEFT');
        $this->db->join('`iafb_remittance`.`system_code` as csc','csc.id = tr.channel_id','LEFT');
        $this->db->where('tr.deleted_at', NULL);
        $this->db->where_in('tr.transactionID', $transactionID_arr);
        if($config->getDateFrom()){
            $this->db->where('tr.created_at >=', $config->getDateFrom()->getUnix());
        }
        if($config->getDateTo()){
            $this->db->where('tr.created_at <=', $config->getDateTo()->getUnix());
        }
        $this->db->order_by("tr.created_at", "desc");
        $this->db->stop_cache();
        $total = $this->db->count_all_results(); //to get total num of result w/o limit
        $this->db->limit($limit, $offset);

        $query = $this->db->get();
        $this->db->flush_cache();



        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new RemittanceTransactionCollection(), $total);

        }

        return false;
    }



    public function findByTransactionIDArr($transactionID_arr)
    {
        $this->db->select('`tr`.`id`,
                            `tr`.`transactionID`,
                            `tr`.`transaction_type_id`,
                            tsc.code as transaction_type_code,
                            tsc.display_name as transaction_type_name,
                            tsc.description as transaction_type_desc,
                            `tr`.`user_profile_id`,
                            `tr`.`status_id`,
                            ssc.code as status_code,
                            `tr`.`country_currency_code`,
                            `tr`.`remark`,
                            `tr`.`ref_transaction_id`,
                            `tr`.`confirm_payment_code`,
                            `tr`.`passcode`,
                            `tr`.`channel_id`,
                            `tr`.`description`,
                            csc.code as channel_code,
                            `tr`.`expired_date`,
                            `tr`.`created_at`,
                            `tr`.`created_by`,
                            `tr`.`updated_at`,
                            `tr`.`updated_by`,
                            `tr`.`deleted_at`,
                            `tr`.`deleted_by`
                          ');
        $this->db->from('`iafb_remittance`.`transaction` as tr');
        $this->db->join('`iafb_remittance`.`system_code` as tsc','tsc.id = tr.transaction_type_id','LEFT');
        $this->db->join('`iafb_remittance`.`system_code` as ssc','ssc.id = tr.status_id','LEFT');
        $this->db->join('`iafb_remittance`.`system_code` as csc','csc.id = tr.channel_id','LEFT');
        $this->db->where('tr.deleted_at', NULL);
        $this->db->where_in('tr.transactionID', $transactionID_arr);
        $query = $this->db->get();

        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new RemittanceTransactionCollection(), $query->num_rows());
        }

        return false;
    }


    public function findByIdArr(array $id_arr)
    {
        $this->db->select('`tr`.`id`,
                            `tr`.`transactionID`,
                            `tr`.`transaction_type_id`,
                            tsc.code as transaction_type_code,
                            tsc.display_name as transaction_type_name,
                            tsc.description as transaction_type_desc,
                            `tr`.`user_profile_id`,
                            `tr`.`status_id`,
                            ssc.code as status_code,
                            `tr`.`country_currency_code`,
                            `tr`.`remark`,
                            `tr`.`ref_transaction_id`,
                            `tr`.`confirm_payment_code`,
                            `tr`.`passcode`,
                            `tr`.`channel_id`,
                            `tr`.`description`,
                            csc.code as channel_code,
                            `tr`.`expired_date`,
                            `tr`.`created_at`,
                            `tr`.`created_by`,
                            `tr`.`updated_at`,
                            `tr`.`updated_by`,
                            `tr`.`deleted_at`,
                            `tr`.`deleted_by`
                          ');
        $this->db->from('`iafb_remittance`.`transaction` as tr');
        $this->db->join('`iafb_remittance`.`system_code` as tsc','tsc.id = tr.transaction_type_id','LEFT');
        $this->db->join('`iafb_remittance`.`system_code` as ssc','ssc.id = tr.status_id','LEFT');
        $this->db->join('`iafb_remittance`.`system_code` as csc','csc.id = tr.channel_id','LEFT');
        $this->db->where('tr.deleted_at', NULL);
        
        //$this->db->where_in('tr.id', $id_arr);
        //to split the arrays
        $this->db->group_start();
        $ids_chunk = array_chunk($id_arr,25);
        foreach($ids_chunk as $ids)
        {
            $this->db->or_where_in('tr.id', $ids);
        }
        $this->db->group_end();

        $query = $this->db->get();

        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new RemittanceTransactionCollection(), $query->num_rows());
        }

        return false;
    }
}
