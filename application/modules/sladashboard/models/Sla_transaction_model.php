<?php

use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\Core\IappsBaseDataMapper;
use Iapps\RemittanceService\SlaDashboard\SlaDashboardTransactionDataMapper;
use Iapps\RemittanceService\RemittanceRecord\RemittanceStatus;

class Sla_transaction_model extends Base_Model implements SlaDashboardTransactionDataMapper{

    public function map(\stdClass $data)
    {

    }

    public function findById($id, $deleted = false)
    {
        
    }

    public function getSLATotalRemittanceTransactionStatus($channelFilter = null,$date_from,$date_to,$sla_remittance_on_time)
    {

        $sqlCondition = '';

        if ($channelFilter) {
            
            $sqlCondition = "and r.remittance_configuration_id in (".$channelFilter.")";
        }

        $sql = "Select 
                       Sum(
                            Case r.approval_status 
                            When 'approved' Then 1
                            When 'rejected' then 1
                            Else 0
                            End
                       ) as total_completed_remittance_transactions,
                       Sum(
                            Case approved_rejected_at
                            When null then 0
                            ELSE
                                Case 
                                    WHEN ABS(r.approved_rejected_at - r.paid_at) / 60 <= $sla_remittance_on_time Then 1
                                    Else 0
                                    End
                            End
                       ) as total_completed_remittance_transactions_within_sla,
                       Sum(
                            Case ssc.code 
                            When 'processing' Then 1
                            Else 0
                            End
                       ) as total_pending_remittance_transactions,
                       Sum(
                            Case ssc.code 
                            When 'delivering' Then 1
                            Else 0
                            End
                       ) as total_pending_collection_remittance_transactions,
                       
                Count(*) as total_remittance_transactions
                from iafb_remittance.remittance r 
                        left join iafb_remittance.system_code as ssc on ssc.id = r.status_id
                where 
                r.paid_at > $date_from
                and r.paid_at <= $date_to
                ".$sqlCondition."
                ";
        
        $query = $this->db->query($sql);

        if ($query->num_rows() > 0)
        {
            return $query->result();
        }

        return false;
    }

    public function getOverTimeAndWaringTime($channelFilter = null,$now,$sla_remittance_on_time,$sla_remittance_warning_time)
    {
        $sqlCondition = '';

        if ($channelFilter) {
            
            $sqlCondition = "and r.remittance_configuration_id in (".$channelFilter.")";
        }

        $sql = "Select 
                   Sum(
                        Case paid_at
                        When null then 0
                        ELSE
                            Case 
                                WHEN ABS($now - r.paid_at) / 60 >= $sla_remittance_on_time Then 1
                                Else 0
                                End
                        End
                   ) as over_time_transaction,
                   Sum(
                        Case paid_at
                        When null then 0
                        ELSE
                            Case 
                                WHEN $sla_remittance_on_time > ABS($now - r.paid_at) / 60 >= $sla_remittance_warning_time Then 1
                                Else 0
                                End
                        End
                   ) as waring_time_transaction

                from iafb_remittance.remittance r 
                left join iafb_remittance.system_code as ssc on ssc.id = r.status_id
                where ssc.code = 'processing'
                ".$sqlCondition."
                ";

        $query = $this->db->query($sql);

        if ($query->num_rows() > 0)
        {
            return $query->result();
        }

        return false;
    }

    public function getSLAListPendingTransaction($channelFilter = null,$date_from,$date_to)
    {

        $this->db->start_cache(); //to cache active record query

        $this->db->select('t.id as transaction_id,
                           t.transactionID,
                           r.remittanceID,
                           r.paid_at,
                           r.from_amount as amount,
                           t.country_currency_code,
                          ');
        $this->db->from('iafb_remittance.remittance r');
        $this->db->join('iafb_remittance.transaction t','t.id = r.in_transaction_id');
        $this->db->join('iafb_remittance.system_code ssc','ssc.id = r.status_id','LEFT');
        $this->db->where('ssc.code', RemittanceStatus::PROCESSING);

        if ($channelFilter) {

            $this->db->where_in('r.remittance_configuration_id', $channelFilter);
        }

        // $this->db->where('r.created_at >', $date_from);
        // $this->db->where('r.created_at <=', $date_to);
        $this->db->group_by('t.id , t.transactionID , r.created_at , r.from_amount , t.country_currency_code');
        $this->db->order_by('r.paid_at','ASC');

        $this->db->stop_cache();
        // $this->db->limit(10);
        $query = $this->db->get();
        $this->db->flush_cache();

        if( $query->num_rows() > 0)
        {
            return $query->result();
        }

        return false;
    }
    
    public function getAllPendingCollection($channelFilter = null)
    {
        $this->db->start_cache(); //to cache active record query

        $this->db->select('t.id as transaction_id,
                           t.transactionID,
                           r.remittanceID,
                           r.paid_at,
                           r.from_amount as amount,
                           t.country_currency_code,
                          ');
        $this->db->from('iafb_remittance.remittance r');
        $this->db->join('iafb_remittance.transaction t','t.id = r.in_transaction_id');
        $this->db->join('iafb_remittance.system_code ssc','ssc.id = r.status_id','LEFT');
        $this->db->where('ssc.code', RemittanceStatus::DELIVERING);

        if ($channelFilter) {

            $this->db->where_in('r.remittance_configuration_id', $channelFilter);
        }

        // $this->db->where('r.created_at >', $date_from);
        // $this->db->where('r.created_at <=', $date_to);
        $this->db->group_by('t.id , t.transactionID , r.created_at , r.from_amount , t.country_currency_code');
        $this->db->order_by('r.paid_at','ASC');

        $this->db->stop_cache();
        $query = $this->db->get();
        $this->db->flush_cache();

        if( $query->num_rows() > 0)
        {
            return $query->result();
        }

        return false;
    }
}