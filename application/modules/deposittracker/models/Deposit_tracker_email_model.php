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
use Iapps\Common\DepositTracker\IDepositTrackerEmailDataMapper;
use Iapps\Common\DepositTracker\DepositTrackerEmailCollection;
use Iapps\Common\DepositTracker\DepositTrackerEmail;


class Deposit_tracker_email_model extends Base_Model implements IDepositTrackerEmailDataMapper
{

    const FLAG_SCREENED = 1;

    public function map(stdClass $data)
    {
        $entity = new DepositTrackerEmail();
        if ( isset($data->id) ) {
            $entity->setId($data->id);
        }

        if ( isset($data->history_id) ){
            $entity->setHistoryId($data->history_id);
        }

        if ( isset($data->deposit_tracker_id) ){
            $entity->setDepositTrackerId($data->deposit_tracker_id);
        }

        if ( isset($data->email) ){
            $entity->setEmail($data->email);
        }

        if ( isset($data->status) ){
            $entity->setStatus($data->status);
        }

        if ( isset($data->created_at) ){
            $entity->setCreatedAt(IappsDateTime::fromUnix($data->created_at));
        }

        if ( isset($data->created_by) ){
            $entity->setCreatedBy($data->created_by);
        }

        if ( isset($data->updated_at) ){
            $entity->setUpdatedAt(IappsDateTime::fromUnix($data->updated_at));
        }

        if ( isset($data->updated_by) ){
            $entity->setUpdatedBy($data->updated_by);
        }

        if ( isset($data->deleted_at) ){
            $entity->setDeletedAt(IappsDateTime::fromUnix($data->deleted_at));
        }

        if ( isset($data->deleted_by) ){
            $entity->setDeletedBy($data->deleted_by);
        }

        if ( isset($data->screened) ){
            $entity->setScreened(self::FLAG_SCREENED);
        }

        return $entity;
    }



    public function listEmailTracker($depositid)
    {
        $this->db->select('*');
        $this->db->where('deposit_tracker_id',$depositid);
        $this->db->where('status','active');
        $this->db->where('deleted_at', NULL);
        $this->db->from('iafb_remittance.deposit_tracker_email');
        $query = $this->db->get();
        $total = $this->db->count_all_results();
        if($query && $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new DepositTrackerEmailCollection(), $total);
        }
        return false;
    }

    public function findById($id, $deleted = false)
    {
        $this->db->select('*');
        $this->db->from('iafb_remittance.deposit_tracker_email');
        if( !$deleted )
            $this->db->where('deleted_at', NULL);
        $this->db->where('id', $id);

        $query = $this->db->get();
        if( $query->num_rows() > 0)
        {
            return $this->map($query->row());
        }

        return false;
    }


    public function getDepositTrackerEmails(DepositTracker $deposit)
    {
        $this->db->select('email');
        $this->db->where('status','active');
        $this->db->where('deposit_tracker_id',$deposit->getId());
        $this->db->where('deleted_at', NULL);
        $this->db->from('iafb_remittance.deposit_tracker_email');
        $query = $this->db->get();
        $total = $this->db->count_all_results();
        if($query && $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new DepositTrackerEmailCollection(), $total);
        }
        return false;
    }



}
