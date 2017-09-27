<?php

use Iapps\Common\DepositTracker\DepositTracker;
use Iapps\Common\DepositTracker\IDepositTrackerUserDataMapper;
use Iapps\Common\DepositTracker\DepositTrackerUserCollection;
use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\Microservice\PaymentService\PaymentService;
use Iapps\Common\DepositTracker\DepositTrackerUser;
use Iapps\Common\Helper\GuidGenerator;



class Deposit_tracker_user_model extends Base_Model implements IDepositTrackerUserDataMapper
{


    public function map(stdClass $data)
    {
        $entity = new DepositTrackerUser();
        if ( isset($data->deposit_tracker_id) ) {
            $entity->setDepositId($data->deposit_tracker_id);
        }

        if ( isset($data->user_profile_id) ) {
            $entity->setUserProfileId($data->user_profile_id);
        }

        if ( isset($data->history_id) ) {
            $entity->setHistoryId($data->history_id);
            if (isset($data->tracker_id) && !empty($data->tracker_id)){
                $entity->setUserProfileId($data->tracker_id);
                $entity->setUserTrackerId($data->tracker_id);
            }
            if (isset($data->tracker_id) && empty($data->tracker_id)){
                $entity->setUserProfileId('');
            }
        }

        if ( isset($data->tracker_status) ) {
            $entity->setTrackerStatus($data->tracker_status);
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
        return $entity;
    }

    protected function _prepareSelect()
    {
        $this->db->select(' id as tracker_id,
                            deposit_tracker_id,
                            user_profile_id,
                            tracker_status,
                            created_at,
                            created_by,
                            updated_at,
                            updated_by');
        $this->db->from('iafb_remittance.deposit_tracker_user');
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

    public function findByDepositId($id)
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

    public function insert(DepositTracker $depositdata,$tracker)
    {
        $this->db->set('id', GuidGenerator::generate());
        $this->db->set('deposit_tracker_id',$depositdata->getId());
        $this->db->set('user_profile_id', $tracker);
        $this->db->set('tracker_status', 'active');
        $this->db->set('created_by', $depositdata->getCreatedBy());
        $this->db->set('created_at', IappsDateTime::now()->getUnix());
        if( $this->db->insert('iafb_remittance.deposit_tracker_user') )
        {
            return true;
        }

        return false;
    }

    public function update($config)
    {
        $this->db->set('service_provider_id', $config->getServiceProviderId());
        // $this->db->set('module_code', $config->getModuleCode());
        $this->db->set('country_currency_code', $config->getCountryCurrencyCode());
        $this->db->set('min_threshold', $config->getMinThreshold());
        $this->db->set('balance', $config->getBalance());
        $this->db->set('updated_at', IappsDateTime::now()->getUnix());
        $this->db->set('updated_by', $config->getUpdatedBy());
        $this->db->where('id', $config->getId());

        if( $this->db->update('iafb_remittance.deposit_tracker') )
        {
            return true;
        }

        return false;
    }

    public function getDepositTrackerUserList($limit, $page)
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

    public function findByDepositIdAndUserId($depositid, $userid) {
        $this->_prepareSelect();
        $this->db->where('deposit_tracker_id', $depositid);
        $this->db->where('user_profile_id',$userid);
        $this->db->where('tracker_status','active');
        $query = $this->db->get();
        if( $query->num_rows() > 0 )
        {
            return true;
        }
        return false;
    }
    
    public function getHistoryTracker($depositid,$createdat)
    {
        $this->db->select('*');
        $this->db->where('deposit_tracker_id',$depositid);
        $this->db->where('created_at',$createdat);
        $this->db->where('tracker_status','inactive');
        $this->db->from('iafb_remittance.deposit_tracker_user');
        $query = $this->db->get();
        $total = $this->db->count_all_results();
        if( $query->num_rows() > 0 )
        {
           return $this->mapCollection($query->result(), new DepositTrackerUserCollection(), $total );
        }
        return false;

    }


    public function getTrackerUserByHistoryId($historyid)
    {
        $this->db->where('history_id',$historyid);
        $this->db->where('status','active');
        $this->db->from('iafb_remittance.deposit_history_user');
        $query = $this->db->get();
        $total = $this->db->count_all_results();
        if($query->num_rows() > 0){
            return $this->mapCollection($query->result(), new DepositTrackerUserCollection(), $total);
        }
        return false;
    }

}
