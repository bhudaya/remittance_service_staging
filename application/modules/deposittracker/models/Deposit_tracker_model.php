<?php

use Iapps\Common\DepositTracker\DepositTracker;
use Iapps\Common\DepositTracker\IDepositTrackerDataMapper;
use Iapps\Common\DepositTracker\DepositTrackerCollection;
use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\Microservice\PaymentService\PaymentService;
use Iapps\Common\Helper\GuidGenerator;
use Iapps\Common\DepositTracker\DepositTrackerHistoryCollection;
use Iapps\Common\DepositTracker\DepositTrackerUserCollection;
use Iapps\RemittanceService\RemittanceRecord\RemittanceRecordCollection;
use Iapps\Common\DepositTracker\DepositTrackerEmailCollection;


class Deposit_tracker_model extends Base_Model implements IDepositTrackerDataMapper
{

    const FLAG_SCREENED = 1;

    public function map(stdClass $data)
    {
        $entity = new DepositTracker();
        if ( isset($data->id) ) {
            $entity->setId($data->id);
        }

        if (isset($data->deposit_tracker_id)){
            $entity->setDepositTrackerId($data->deposit_tracker_id);
            $entity->setId($data->deposit_tracker_id);
            if(isset($data->id)) {
                $entity->setHistoryId($data->id);
            }
            if(isset($data->history_id) && isset($data->deposit_tracker_id) && isset($data->email)){
                $entity->setId($data->id);
                $entity->setHistoryId2($data->history_id);
                $entity->setStatus($data->status);
            }
            if(isset($data->last_threshold_amount)){
                $entity->setId($data->id);
            }

            if(isset($data->tracker_id)){
                $entity->setId($data->id);
                if(isset($data->history_id)){
                    $entity->setHistoryId($data->history_id);
                    $entity->setHistoryId2($data->history_id);
                }
                $entity->setDepositTrackerId($data->deposit_tracker_id);
                $entity->setStatus($data->status);
                $entity->setTrackerId($data->tracker_id);
            }

        }

        if (isset($data->remittance_config_id)){
            $entity->setRemittanceConfigId($data->remittance_config_id);
        }

        if ( isset($data->service_provider_id) ) {
            $entity->setServiceProviderId($data->service_provider_id);
        }

        if ( isset($data->country_currency_code) ) {
            $entity->setCountryCurrencyCode($data->country_currency_code);
        }

        if ( isset($data->threshold_status)){
            $entity->setThresholdStatus($data->threshold_status);
        }

        if ( isset($data->threshold_amount)){
            $entity->setThresholdAmount($data->threshold_amount);
        }

        if ( isset($data->deposit_status)){
            $entity->setDepositStatus($data->deposit_status);
        }

        if ( isset($data->amount)){
            $entity->setAmount($data->amount);
        }

        if ( isset($data->balance) ) {
            $entity->setBalance($data->balance);
        }

        if (isset($data->deposit_holder)){
            $entity->setDepositHolder($data->deposit_holder);
        }

        if ( isset($data->created_at) ) {
            $entity->setCreatedAt(IappsDateTime::fromUnix($data->created_at));
        }

        if ( isset($data->created_by) ) {
            $entity->setCreatedBy($data->created_by);
        }

        if ( isset($data->updated_at) ) {
            $entity->setUpdatedAt(IappsDateTime::fromUnix($data->updated_at));
        }

        if ( isset($data->updated_by) ) {
            $entity->setUpdatedBy($data->updated_by);
        }

        if ( isset($data->deleted_at) ) {
            $entity->setDeletedAt(IappsDateTime::fromUnix($data->deleted_at));
        }

        if ( isset($data->deleted_by) ) {
            $entity->setDeletedBy($data->deleted_by);
        }

        if ( isset($data->approve_rejected_by) ){
            $entity->setApproveRejectedBy($data->approve_rejected_by);
        }

        if ( isset($data->approve_rejected_at) ){
            $entity->setApproveRejectedAt(IappsDateTime::fromUnix($data->approve_rejected_at));
        }

        if ( isset($data->last_balance) ){
            $entity->setLastBalance($data->last_balance);
        }

        if ( isset($data->last_threshold_amount) ){
            $entity->setLastThresholdAmount($data->last_threshold_amount);
        }

        if ( isset($data->last_status) ) {
            $entity->setLastStatus($data->last_status);
        }

        if ( isset($data->user_profile_id) ){
            $entity->setUserProfileId($data->user_profile_id);
        }

        if ( isset($data->email) ) {
            $entity->setEmail($data->email);
        }

        if ( isset($data->screened) ){
            $entity->setScreened($data->screened);
        }


        return $entity;
    }

    protected function _prepareSelect()
    {
        $this->db->select(' id as deposit_tracker_id,
                            remittance_config_id,
                            service_provider_id,
                            country_currency_code,
                            threshold_status,
                            threshold_amount,
                            deposit_status,
                            amount,
                            created_at,
                            created_by,
                            updated_at,
                            updated_by,
                            deleted_at,
                            deleted_by,
                            approve_rejected_by,
                            approve_rejected_at');
        $this->db->from('iafb_remittance.deposit_tracker');
    }


    public function findById($id, $deleted = false)
    {
        $this->_prepareSelect();

        if ( !$deleted ) {
            $this->db->where('deleted_at', NULL);
        }

        $this->db->where('id', $id);

        $query = $this->db->get();

        if ( $query && $query->num_rows() > 0)
        {
            return $this->map($query->row());
        }

        return false;
    }

    public function findDepositToUpdate($id,$deleted = NULL){
        $this->_prepareSelect();

        if ( !$deleted ) {
            $this->db->where('deleted_at', NULL);
        }

        $this->db->where('id', $id);

        $query = $this->db->get();

        if ( $query && $query->num_rows() > 0)
        {
            return $this->map($query->row());
        }

        return false;
    }


    public function getDepositTrackerList($limit, $page)
    {
        $offset = ($page - 1) * $limit;

        $this->db->start_cache(); //to cache active record query
        $this->_prepareSelect();
        $this->db->where('deleted_at', NULL);
        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit

        $this->db->limit($limit, $offset);
        $query = $this->db->get();
        $this->db->flush_cache();

        if( $query && $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new DepositTrackerCollection(), $total);
        }

        return false;
    }

    public function findByServiceProviderId($id)
    {
        $this->_prepareSelect();
        $this->db->where('service_provider_id', $id);
        $this->db->where('deleted_at', NULL);

        $query = $this->db->get();
        if( $query->num_rows() > 0 )
        {
            return $this->map($query->row());
        }

        return false;
    }

    public function findByServiceProviderAndCountryCurrencyCode($id, $currency_code, $remittance_config_id)
    {
        $this->_prepareSelect();
        $this->db->where('remittance_config_id',$remittance_config_id);
        $this->db->where('service_provider_id', $id);
        $this->db->where('country_currency_code', $currency_code);
        $this->db->where('deposit_status != ', 'rejected');
        $this->db->where('deleted_at', NULL);

        $query = $this->db->get();
        if( $query->num_rows() > 0 )
        {
            return $this->map($query->row());
        }

        return false;
    }

    public function findByParam(DepositTracker $config, $limit, $page)
    {
        $init_predicate = true;
        $offset = ($page - 1) * $limit;
        $this->db->start_cache(); //to cache active record query
        $this->_prepareSelect();
        $this->db->where('deleted_at', NULL);
        if($config->getServiceProviderId()) {
            $init_predicate = false;
            $this->db->like('service_provider_id', $config->getServiceProviderId());
        }

        if($config->getCountryCurrencyCode()) {
            $init_predicate = false;
            $this->db->like('country_currency_code', $config->getCountryCurrencyCode());
        }

        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit

        $this->db->limit($limit, $offset);
        $query = $this->db->get();

        $this->db->flush_cache();

        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new DepositTrackerCollection(), $total);
        }

        return false;
    }


    public function insert(DepositTracker $config)
    {
        $this->TransStart();
        $trackeremail = $config->getTrackerEmail();
        $this->db->set('id', $config->getId());
        $this->db->set('remittance_config_id',$config->getRemittanceConfigId());
        $this->db->set('service_provider_id', $config->getServiceProviderId());
        $this->db->set('country_currency_code', $config->getCountryCurrencyCode());
        $threshold_amount = number_format(str_replace(",","",$config->getThresholdAmount()),2,".","");
        $this->db->set('threshold_amount',$threshold_amount);
        $this->db->set('threshold_status',$config->getThresholdStatus());
        $this->db->set('deposit_status',$config->getDepositStatus());
        $this->db->set('deposit_holder',$config->getDepositHolder());
        $this->db->set('amount', $config->getAmount());
        $this->db->set('created_by', $config->getCreatedBy());
        $this->db->set('created_at', IappsDateTime::now()->getUnix());
        if( $this->db->insert('iafb_remittance.deposit_tracker') )
        {

            if(!empty($trackeremail)){
                $cleanemail = str_replace(" ", "", $trackeremail);
                $emailarray = explode(",",$trackeremail);
                $counter = 0;
                foreach($emailarray as $email){
                    if($this->insertEmail($config,$email)){
                        $counter++;
                    }
                }
                if($counter == sizeof($emailarray)){
                    $this->TransComplete();
                    return true;
                }
            }
            $this->TransComplete();
            return true;
        }
        $this->TransRollback();
        return false;
    }

    //this one is used during add deposit
    public  function insertEmail(DepositTracker $config, $email)
    {
        $this->db->set("id", GuidGenerator::generate());
        $this->db->set("deposit_tracker_id", $config->getId());
        $this->db->set("email", $email);
        $this->db->set("status", "pending");
        $this->db->set("screened",self::FLAG_SCREENED);
        $this->db->set("created_at", IappsDateTime::now()->getUnix());
        $this->db->set("created_by", $config->getCreatedBy());
        if($this->db->insert('iafb_remittance.deposit_tracker_email'))
        {
            return true;
        }
        return false;
    }

    public function resetTrackers($config)
    {
        $this->db->set('tracker_status',"inactive");
        $this->db->set('updated_at', IappsDateTime::now()->getUnix());
        $this->db->set('updated_by', $config->getUpdatedBy());
        $this->db->where("deposit_tracker_id", $config->getId());
        $this->db->update('iafb_remittance.deposit_tracker_user');

    }

    public function resetTrackerEmail(DepositTracker $config)
    {
        $this->db->set('status','inactive');
        $this->db->set('updated_by',$config->getUpdatedBy());
        $this->db->set('updated_at',IappsDateTime::now()->getUnix());
        $this->db->where('deposit_tracker_id',$config->getId());
        $this->db->update('iafb_remittance.deposit_tracker_email');

    }


    public function deactivateTrackers(DepositTracker $config)
    {
        $this->db->set('tracker_status','inactive');
        $this->db->where('deposit_tracker_id',$config->getId());
        if($this->db->update('iafb_remittance.deposit_tracker_user'))
        {
            return true;
        }
        return false;
    }


    public function replenishTrackerEmail(DepositTracker $config)
    {
        $this->TransStart();
        $trackeremail = $config->getTrackerEmail();
        $this->resetTrackerEmail($config);
        if(!empty($trackeremail)){
            $cleanemail = str_replace(" ", "", $trackeremail);
            $emailarray = explode(",",$cleanemail);
            $counter = 0;
            foreach($emailarray as $email){
                if($this->insertEmail($config,$email)){
                    $counter++;
                }
            }
            if($counter == sizeof($emailarray)){
                $this->TransComplete();
                return true;
            }
        }
        $this->TransRollback();
        return true;
    }


    public function update(DepositTracker $config)
    {
        $this->TransStart();
        $trackeremail = $config->getTrackerEmail();
        $amount = str_replace(",","",$config->getThresholdAmount());
        $amount = str_replace(".","",$amount);
        $this->db->set('threshold_amount', $amount);
        $this->db->set('threshold_status','normal');
        $this->db->set('deposit_status',$config->getDepositStatus());
        $this->db->set('updated_at', IappsDateTime::now()->getUnix());
        $this->db->set('updated_by', $config->getUpdatedBy());
        $this->db->where('id', $config->getId());

        if( $this->db->update('iafb_remittance.deposit_tracker') )
        {
            if(!empty($config->getTrackers())){
                $this->resetTrackers($config);
                if(!empty($trackeremail)){
                    $this->resetTrackerEmail($config);
                    $cleanemail = str_replace(" ", "", $trackeremail);
                    $emailarray = explode(",",$trackeremail);
                    $counter = 0;
                    foreach($emailarray as $email){
                        if($this->insertEmail($config,$email)){
                            $counter++;
                        }
                    }
                    if($counter == sizeof($emailarray)){
                        $this->TransComplete();
                        return true;
                    }
                }
                $this->TransComplete();
                return true;
            }
            $this->TransComplete();
            return true;
        }
        $this->TransRollback();
        return false;
    }

    public function approveDeposit($config)
    {
        $this->db->set('deposit_status',$config->getDepositStatus());
        $this->db->set('updated_at', IappsDateTime::now()->getUnix());
        $this->db->set('updated_by', $config->getUpdatedBy());
        $this->db->set('approve_rejected_by', $config->getUpdatedBy());
        $this->db->set('approve_rejected_at', IappsDateTime::now()->getUnix());
        $this->db->where('id', $config->getId());
        if($this->db->update('iafb_remittance.deposit_tracker'))
        {
            return true;
        }
        return false;
    }

    public function rejectDeposit($config)
    {
        $this->db->set('deposit_status',$config->getDepositStatus());
        $this->db->set('updated_at', IappsDateTime::now()->getUnix());
        $this->db->set('updated_by', $config->getUpdatedBy());
        $this->db->set('approve_rejected_by', $config->getUpdatedBy());
        $this->db->set('approve_rejected_at', IappsDateTime::now()->getUnix());
        $this->db->where('id', $config->getId());
        if($this->db->update('iafb_remittance.deposit_tracker'))
        {
            return true;
        }

        return false;
    }


    public function getDepositList($limit,$page){
        $offset = ($page - 1) * $limit;

        $this->db->start_cache(); //to cache active record query
        $this->db->select('*');
        $this->db->where('deposit_status','approved');
        $this->db->from('iafb_remittance.deposit_tracker');
        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit

        $this->db->limit($limit, $offset);
        $query = $this->db->get();
        $this->db->flush_cache();
        if( $query && $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(),new DepositTrackerCollection(),$total);
        }

        return false;

    }

    public function getPendingDepositList($limit, $page){
        $offset = ($page - 1) * $limit;

        $this->db->start_cache(); //to cache active record query
        $this->db->select('*');
        $this->db->where('deposit_status','pending');
        $this->db->order_by('created_at','desc');
        $this->db->from('iafb_remittance.deposit_tracker');
        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit

        $this->db->limit($limit, $offset);
        $query = $this->db->get();
        $this->db->flush_cache();
        if( $query && $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new DepositTrackerCollection(), $total);
        }

        return false;

    }

    public function getTrackersByDepositId($depositid){

        $this->db->select('
                user_profile_id,
                deposit_tracker_id
               ');
        $this->db->where('deposit_tracker_id',$depositid);
        $this->db->where('tracker_status','active');
        $this->db->from('iafb_remittance.deposit_tracker_user');
        $query = $this->db->get();
        $total = $this->db->count_all_results();
        if($query && $query->num_rows() > 0) {
            return $this->mapCollection($query->result(), new DepositTrackerUserCollection(), $total);
        }

    }

    public function getDepositByDepositId($depositid)
    {
        $this->db->select('*');
        $this->db->where('id',$depositid);
        $this->db->order_by('created_at','desc');
        $this->db->from('iafb_remittance.deposit_tracker');
        $query = $this->db->get();
        $total = $this->db->count_all_results();
        if($query && $query->num_rows() > 0 ){
            return $this->map($query->row(), new DepositTrackerCollection(), $total);
        } else {
            return false;
        }

    }


    public function deductAmount($amount,$config)
    {
        $this->db->set('amount',$amount);
        $this->db->where('id',$config->getDepositTrackerId());
        if($this->db->update('iafb_remittance.deposit_tracker')){
            return true;
        }
        return false;
    }

    public function findDepositByRemittanceConfigId($id)
    {
        $this->db->select('*');
        $this->db->where('remittance_config_id',$id);
        $this->db->from('iafb_remittance.remittance');
        $query = $this->db->get();
        $total = $this->db->count_all_results();
        if( $query && $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new RemittanceRecordCollection(), $total);
        }

        return false;
    }


    public function getByConfigId(DepositTracker $deposit)
    {
        $this->db->select('*');
        $this->db->where('remittance_config_id',$deposit->getRemittanceConfigId());
        $this->db->where('deposit_status',$deposit->getDepositStatus());
        $this->db->from('iafb_remittance.deposit_tracker');
        $query = $this->db->get();
        $total = $this->db->count_all_results();
        if($query && $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new RemittanceRecordCollection() , $total);
        }
        return false;
    }


    public function getDepositByParam($limit,$page,$remittanceconfigid,$depositholder)
    {
        $offset = ($page - 1) * $limit;

        $this->db->start_cache(); //to cache active record query
        $this->db->select('*');
        $this->db->where('deposit_status','approved');
        if(!empty($remittanceconfigid) && !is_null($remittanceconfigid)) {
            $this->db->where('remittance_config_id', $remittanceconfigid);
        }
        if(!empty($depositholder) && !is_null($depositholder)) {
            $this->db->where('deposit_holder', $depositholder);
        }
        $this->db->from('iafb_remittance.deposit_tracker');
        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit

        $this->db->limit($limit, $offset);
        $query = $this->db->get();
        $this->db->flush_cache();
        if( $query && $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new DepositTrackerCollection(), $total );
        }

        return false;

    }


    public function getTopupByParam($limit,$page,$remittanceconfigid,$depositholder)
    {
        $offset = ($page - 1) * $limit;

        $this->db->start_cache(); //to cache active record query
        $this->db->select('*');
        $this->db->where('deposit_status','approved');
        if(!empty($remittanceconfigid) && !is_null($remittanceconfigid)) {
            $this->db->where('remittance_config_id', $remittanceconfigid);
        }
        if(!empty($depositholder) && !is_null($depositholder)) {
            $this->db->where('deposit_holder', $depositholder);
        }
        $this->db->from('iafb_remittance.deposit_request');
        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit

        $this->db->limit($limit, $offset);
        $query = $this->db->get();
        $this->db->flush_cache();
        if( $query && $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new DepositTrackerCollection(), $total);
        }

        return false;

    }


    public function getPendingDepositByParam($limit, $page, $remittanceconfigid,$depositholder){
        $offset = ($page - 1) * $limit;

        $this->db->start_cache(); //to cache active record query
        $this->db->select('*');
        $this->db->where('deposit_status','pending');
        if(!empty($remittanceconfigid) && !is_null($remittanceconfigid)) {
            $this->db->where('remittance_config_id', $remittanceconfigid);
        }
        if(!empty($depositholder) && !is_null($depositholder)) {
            $this->db->where('deposit_holder', $depositholder);
        }
        $this->db->order_by('created_at','desc');
        $this->db->from('iafb_remittance.deposit_tracker');
        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit

        $this->db->limit($limit, $offset);
        $query = $this->db->get();
        $this->db->flush_cache();
        if( $query && $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new DepositTrackerCollection(), $total);
        }

        return false;

    }


    public function getAllDepositsForAdminMaker()
    {
        $this->db->start_cache(); //to cache active record query
        $this->db->select('*');
        $this->db->from('iafb_remittance.deposit_tracker');
        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit
        $query = $this->db->get();
        $this->db->flush_cache();
        if( $query && $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new DepositTrackerCollection(), $total);
        }

        return false;
    }


    public function insertDepositConfig(DepositTracker $config)
    {

        $this->TransStart();
        $historyid = GuidGenerator::generate();
        $this->db->set('id',$historyid);
        $this->db->set('deposit_tracker_id',$config->getId());
        $this->db->set('last_threshold_amount',$config->getThresholdAmount());
        $this->db->set('last_status',$config->getTrackerStatus());
        $this->db->set('created_at',IappsDateTime::now()->getUnix());
        $this->db->set('created_by',$config->getCreatedBy());
        $this->db->set('updated_by',$config->getUpdatedBy());
        $this->db->set('updated_at',$config->getHistoryUpdatedAt());
        $this->db->set('approve_rejected_by',$config->getApproveRejectedBy());
        $this->db->set('approve_rejected_at',IappsDateTime::now()->getUnix());
        if($this->db->insert('iafb_remittance.deposit_tracker_history'))
        {
            if(!empty($config->getTrackers()))
            {
                foreach($config->getTrackers() as $tracker)
                {
                    $this->insertDepositConfigTracker($tracker,$historyid,$config);
                }
            }

            if($config->getTrackerStatus() == 'approved'){
                $this->updateCurrentDeposit($config);
                $this->flagPreviousHistory($config);
                $this->updateFirstEmailTrackers($config,$historyid);
                $this->flagPreviousEmails($config);
                $this->insertActiveTrackerEmails($config,$historyid);
                $this->TransComplete();
                return true;
            }

            if($config->getTrackerStatus() == 'rejected'){
                $this->flagPreviousHistory($config);
                $this->updateFirstEmailTrackers($config,$historyid);
                $this->flagPreviousEmails($config);
                $this->TransComplete();
                return true;
            }
            $this->TransComplete();
            return true;
        }
        $this->TransRollback();
        return false;
    }


    private function insertActiveTrackerEmails(DepositTracker $config,$historyid)
    {
        $emails = explode(",",$config->getTrackerEmail());
        $emails = str_replace(" ","",$emails);
        foreach($emails as $email){
            $this->db->set('id',GuidGenerator::generate());
            $this->db->set('history_id',$historyid);
            $this->db->set('deposit_tracker_id',$config->getId());
            $this->db->set('email',$email);
            $this->db->set('status','active');
            $this->db->set('created_at',IappsDateTime::now()->getUnix());
            $this->db->set('created_by',$config->getCreatedBy());
            $this->db->insert('iafb_remittance.deposit_tracker_email');
        }
    }


    private function updateFirstEmailTrackers(DepositTracker $config,$historyid)
    {
        $this->db->set('history_id',$historyid);
        $this->db->where('history_id',null);
        $this->db->where('deposit_tracker_id', $config->getId());
        $this->db->update('iafb_remittance.deposit_tracker_email');
    }


    public function insertDepositConfigTracker($tracker,$historyid,DepositTracker $config)
    {
        $this->db->set('id',GuidGenerator::generate());
        $this->db->set('history_id',$historyid);
        $this->db->set('tracker_id',$tracker);
        $this->db->set('deposit_tracker_id',$config->getId());
        $this->db->set('status','active');
        $this->db->set('created_by',$config->getCreatedBy());
        $this->db->set('created_at',IappsDateTime::now()->getUnix());
        $this->db->insert('iafb_remittance.deposit_history_user');
    }

    public function updateCurrentDeposit(DepositTracker $config)
    {
        $this->db->set('threshold_amount',$config->getThresholdAmount());
        $this->db->set('updated_by', $config->getUpdatedBy());
        $this->db->set('updated_at', IappsDateTime::now()->getUnix());
        $this->db->set('approve_rejected_by', $config->getApproveRejectedBy());
        $this->db->set('approve_rejected_at', IappsDateTime::now()->getUnix());
        $this->db->where('id', $config->getId());
        $this->db->update('iafb_remittance.deposit_tracker');
    }

    public function flagPreviousHistory(DepositTracker $config)
    {
        $this->db->set('screened', self::FLAG_SCREENED);
        $this->db->where('id', $config->getHistoryId());
        $this->db->update('iafb_remittance.deposit_tracker_history');
    }

    public function flagPreviousEmails(DepositTracker $config)
    {
        $this->db->set('status','inactive');
        $this->db->where('deposit_tracker_id',$config->getId());
        $this->db->where('status','active');
        $this->db->update('iafb_remittance.deposit_tracker_email');
    }


    public function hasPendingConfig($deposit_tracker_id)
    {
        $this->db->select('*');
        $this->db->where('deposit_tracker_id', $deposit_tracker_id);
        $this->db->order_by('created_at desc');
        $this->db->limit(1,0);
        $this->db->from('iafb_remittance.deposit_tracker_history');
        $query = $this->db->get();
        if($query && $query->num_rows() > 0)
        {
            $result = $query->result();
            if($result[0]->last_status == 'pending'){
                return true;
            }
            return false;
        }
        return false;
    }


    public function listConfig($limit,$page){
        $offset = ($page - 1) * $limit;

        $this->db->start_cache(); //to cache active record query
        $this->db->select('*');
        $this->db->where('last_status','pending');
//        $this->db->where('screened  !=  ',self::FLAG_SCREENED, false);
        $this->db->where('screened IS NULL', null, false);
        $this->db->from('iafb_remittance.deposit_tracker_history');
        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit

        $this->db->limit($limit, $offset);
        $query = $this->db->get();
        $this->db->flush_cache();
        if( $query && $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new DepositTrackerHistoryCollection() ,$total);
        }

        return false;

    }

    public function listConfigByParam($limit,$page,$remittance_config_id,$depositholder)
    {
        $offset = ($page - 1) * $limit;

        $this->db->start_cache(); //to cache active record query
        $this->db->select('*');
        $this->db->where('last_status','pending');
//        $this->db->where('screened  !=  ',self::FLAG_SCREENED, false);
        $this->db->where('screened IS NULL', null, false);
        $this->db->from('iafb_remittance.deposit_tracker_history');
        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit

        $this->db->limit($limit, $offset);
        $query = $this->db->get();
        $this->db->flush_cache();
        if( $query && $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new DepositTrackerHistoryCollection(), $total);
        }

        return false;
    }


    public function getConfig($historyid)
    {
        $this->db->select('*');
        $this->db->where('id', $historyid);
        $this->db->from('iafb_remittance.deposit_tracker_history');
        $query = $this->db->get();
        $total = $this->db->count_all_results();
        if($query && $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new DepositTrackerHistoryCollection(), $total);
        }
        return false;
    }


    public function getAllConfig($limit,$page,$depositid)
    {
        $offset = ($page - 1) * $limit;

        $this->db->start_cache(); //to cache active record query
        $this->db->select('*');
        $this->db->where('deposit_tracker_id',$depositid);
        $this->db->order_by('created_at desc');
        $this->db->from('iafb_remittance.deposit_tracker_history');
        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit

        $this->db->limit($limit, $offset);
        $query = $this->db->get();
        $this->db->flush_cache();
        if( $query && $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new DepositTrackerHistoryCollection(), $total); //return $query->result();
        }

        return false;
    }


    public function getDepositTrackerEmails(DepositTracker $deposit)
    {
        $this->db->select('email');
        $this->db->where('status','active');
        $this->db->where('deposit_tracker_id',$deposit->getId());
        $this->db->from('iafb_remittance.deposit_tracker_email');
        $query = $this->db->get();
        $total = $this->db->count_all_results();
        if($query && $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new DepositTrackerEmailCollection(), $total);
        }
        return false;
    }


    public function getHistoryByDepositId($depositid)
    {
        $this->db->select('*');
        $this->db->where('deposit_tracker_id',$depositid);
        $this->db->where('last_status','approved');
        $this->db->order_by('created_at desc');
        $this->db->from('iafb_remittance.deposit_tracker_history');
        $this->db->limit(1,0);
        $query = $this->db->get();
        $total = $this->db->count_all_results();
        if($query && $query->num_rows() > 0){
            $result = $query->result();
            $result[0]->trackers = $this->getHistoryTrackers($result[0]->id);
            return $this->mapCollection($result, new DepositTrackerHistoryCollection(), $total);
        }
        return false;
    }


    public function getHistoryTrackers($historyid)
    {
        $this->db->select('*');
        $this->db->where('history_id',$historyid);
        $this->db->from('iafb_remittance.deposit_history_user');
        $query = $this->db->get();
        $total = $this->db->count_all_results();
        if($query && $query->num_rows() > 0){
            return  $this->mapCollection($query->result(), new DepositTrackerHistoryCollection(), $total);
        }
        return false;
    }

    public function getConfigList($limit,$page,$depositid,$historyid)
    {

        $offset = ($page - 1) * $limit;
        $this->db->select('*');
        $this->db->where('deposit_tracker_id',$depositid);
        $this->db->from('iafb_remittance.deposit_tracker_history');
        $this->db->limit($limit, $offset);
        $query = $this->db->get();
        $total = $this->db->count_all_results();
        if($query && $query->num_rows() > 0){
            return  $this->mapCollection($query->result(), new DepositTrackerCollection(), $total);//return $query->result();
        }
        return false;

    }

    public function getHistoryUser($limit,$page,$depositid,$historyid)
    {
//        $offset = ($page - 1) * $limit;
        $this->db->select('*');
        $this->db->where('deposit_tracker_id',$depositid);
        $this->db->from('iafb_remittance.deposit_history_user');
//        $this->db->limit($limit, $offset);
        $query = $this->db->get();
        $total = $this->db->count_all_results();
        if($query && $query->num_rows() > 0){
            return $this->mapCollection($query->result(), new DepositTrackerHistoryCollection(), $total);
        }
        return false;
    }


    public function getPreviousUser($depositid)
    {

        $this->db->select('*')->from('iafb_remittance.deposit_history_user');
        $this->db->where("history_id = (select id from iafb_remittance.deposit_tracker_history where deposit_tracker_id = '$depositid' 
        and last_status = 'approved' order by created_at desc limit 1)");
        $query = $this->db->get();
        $total = $this->db->count_all_results();
        if($query && $query->num_rows() > 0){
            return $this->mapCollection($query->result(), new DepositTrackerCollection(), $total);
        }
        return false;
    }


    public function getPreviousEmail($depositid)
    {

        $this->db->select('*')->where('status','pending')
        ->where('screened',self::FLAG_SCREENED)
        ->order_by('created_at desc')
        ->from('iafb_remittance.deposit_tracker_email');
        $this->db->where("history_id = (select id from iafb_remittance.deposit_tracker_history where deposit_tracker_id = '$depositid' and last_status = 'pending' order by created_at desc limit 1)");
        $query = $this->db->get();
        $total = $this->db->count_all_results();
        if($query && $query->num_rows() > 0){
            return $this->mapCollection($query->result(), new DepositTrackerEmailCollection(),$total);
        }
        return false;
    }


    public function getEmailByDepositId($depositid)
    {
        $this->db->select('*');
        $this->db->where('deposit_tracker_id',$depositid);
        $this->db->where('status','active');
        $this->db->from('iafb_remittance.deposit_tracker_email');
        $query = $this->db->get();
        $total = $this->db->count_all_results();
        if($query && $query->num_rows() > 0){
            return $this->mapCollection($query->result(), new DepositTrackerEmailCollection(), $total);
        }
        return false;
    }


    public function getConfigByDeposit($depositid)
    {
        $this->db->select('*');
        $this->db->where('deposit_tracker_id', $depositid);
        $this->db->order_by('created_at','desc');
        $this->db->limit(1);
        $this->db->from('iafb_remittance.deposit_tracker_history');
        $query = $this->db->get();
        $total = $this->db->count_all_results();
        if($query && $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new DepositTrackerHistoryCollection(), $total);
        }
        return false;
    }


    public function getPendingTrackerEmail(DepositTracker $config)
    {
        $this->db->select('*');
        $this->db->where('deposit_tracker_id',$config->getId());
//        $this->db->where('history_id', null);
        $this->db->where('screened',self::FLAG_SCREENED);
        $this->db->where('status','pending');
        $this->db->from('iafb_remittance.deposit_tracker_email');
        $query = $this->db->get();
        $total = $this->db->count_all_results();
        if($query && $query->num_rows() > 0){
            return $this->mapCollection($query->result(), new DepositTrackerHistoryCollection(), $total);
        }
        return false;

    }


    public function getLastApprovedEmail(Deposittracker $config)
    {
        if($this->isNew($config)){

            $this->db->select('*')->where('status','active')
                ->order_by('created_at desc')
                ->from('iafb_remittance.deposit_tracker_email');
            $this->db->where("history_id = (select id from iafb_remittance.deposit_tracker_history where deposit_tracker_id = '".
            $config->getId()."' and last_status = 'approved' order by created_at desc limit 1)");
            $query = $this->db->get();
            $total = $this->db->count_all_results();
            if($query && $query->num_rows() > 0){
                return $this->mapCollection($query->result(), new DepositTrackerCollection(), $total);
            }
            return false;


        } else {

            $this->db->select('*')->where('status', 'inactive')
                ->where('screened', null)
                ->order_by('created_at desc')
                ->from('iafb_remittance.deposit_tracker_email');
            $this->db->where("history_id = (select id from iafb_remittance.deposit_tracker_history where deposit_tracker_id = '" .
                $config->getId() . "' and last_status = 'approved' order by created_at desc limit 1)");
            $query = $this->db->get();
            $total = $this->db->count_all_results();
            if ($query && $query->num_rows() > 0) {
                return $this->mapCollection($query->result(), new DepositTrackerCollection(), $total);
            }
            return false;
        }

    }


    /*
     * this function is just used for checking if the new deposit
     * is first time added.
     */
    private function isNew(DepositTracker $config){
        $this->db->select('*');
        $this->db->where('deposit_tracker_id',$config->getId());
        $this->db->from('iafb_remittance.deposit_tracker_history');
        $query = $this->db->get();
        if($query && ($query->num_rows() > 0 && $query->num_rows() < 2))
        {
            return true;
        }
        return false;

    }


    public function getApprovedDepositHolders()
    {
        $this->db->select('deposit_holder');
        $this->db->where('deposit_status','approved');
        $this->db->from('iafb_remittance.deposit_tracker');
        $query = $this->db->get();
        $total = $this->db->count_all_results();
        if($query && $query->num_rows() > 0){
            return $this->mapCollection($query->result(), new DepositTrackerCollection(), $total);
        }
    }


    public function historyCheck(DepositTracker $config)
    {
        $this->db->select('*');
        $this->db->where('deposit_tracker_id',$config->getDepositTrackerId());
        $this->db->from('iafb_remittance.deposit_tracker_history');
        $query = $this->db->get();
        $total = $this->db->count_all_results();
        if($query && $query->num_rows > 0){
            return $this->mapCollection($query->result(), new DepositTrackerHistoryCollection() , $total);
        }
        return false;
    }

    public function isDepositNew(DepositTracker $config)
    {
        $this->db->select('*');
        $this->db->where('deposit_tracker_id',$config->getDepositTrackerId());
        $this->db->from('iafb_remittance.deposit_tracker_history');
        $query = $this->db->get();
        $total = $this->db->count_all_results();
        return $this->mapCollection($query->result(), new DepositTrackerCollection(), $total);
    }


    public function getLastApprovedConfig($depositid)
    {
        $this->db->select('*')
//            ->where('last_status', 'approved')
            ->where('screened', null)
            ->where('deposit_tracker_id',$depositid)
            ->order_by('created_at desc')
            ->limit(1)
            ->from('iafb_remittance.deposit_tracker_history');
        $query = $this->db->get();;
        $total = $this->db->count_all_results();
        if ($query && $query->num_rows() > 0) {
            return $this->mapCollection($query->result(), new DepositTrackerHistoryCollection(), $total);
        }
        return false;
    }


    public function getLastApprovedUsers($historyid, $depositid=null)
    {
        $this->db->select('*');
        $this->db->where('history_id',$historyid);
        if(!is_null($depositid)){
            $this->db->where('deposit_tracker_id',$depositid);
        }
        $this->db->where('status','active');
        $this->db->from('iafb_remittance.deposit_history_user');
        $query = $this->db->get();
        $total = $this->db->count_all_results();
        if($query && $query->num_rows() > 0){
            return $this->mapCollection($query->result(), new DepositTrackerCollection(), $total);
        }
        return false;
    }

    /*
     * this function will get all the
     * current email addresss that is
     * tracking the deposit
     */
    public function getActiveEmailsForNotification($depositid)
    {
        $this->db->select('*');
        $this->db->where('deposit_tracker_id',$depositid);
        $this->db->where('status','active');
        $this->db->where('screened',NULL);
        $this->db->from('iafb_remittance.deposit_tracker_email');
        $query = $this->db->get();
        $total = $this->db->count_all_results();
        if($query && $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new DepositTrackerCollection(), $total);
        }

    }


    public function getLastStatusByDepositId($depositid)
    {
        $this->db->select('*');
        $this->db->where('deposit_tracker_id',$depositid);
        $this->db->from('iafb_remittance.deposit_tracker_history');
        $this->db->order_by('created_at','desc');
        $query = $this->db->get();
        $total = $this->db->count_all_results();
        if($query && $query->num_rows() > 0)
        {
            return $this->map($query->row(), new DepositTrackerCollection(), $total);
        }


    }


    public function getOrganization($userid){}
    public function listEmailTracker($depositid){}
    public function getDepositReason($type, $action, $action_owner){}


}
