<?php

use Iapps\RemittanceService\RemittanceProfitSharing\RemittanceProfitSharingPartyDataMapper;
use Iapps\RemittanceService\RemittanceProfitSharing\RemittanceProfitSharingParty;

use Iapps\Common\Core\IappsDateTime;

class Remittance_profti_sharing_party_model extends Base_Model
                       implements RemittanceProfitSharingPartyDataMapper{

    public function map(\stdClass $data)
    {
        $entity = new RemittanceProfitSharingParty();

        if( isset($data->id) )
            $entity->setId($data->id);

        if( isset($data->corporate_service_profit_sharing_id) )
            $entity->setCorporateServProfitSharingId($data->corporate_service_profit_sharing_id);

        if( isset($data->corporate_id) )
            $entity->setCorporateId($data->corporate_id);

        if( isset($data->percentage) )
            $entity->setPercentage($data->percentage);

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
            $entity->setDeletedBy($data->deleted_b);

        return $entity;
    }

    public function findById($id, $deleted = false)
    {
        
    }

    public function insert(RemittanceProfitSharingParty $profitSharingParty)
    {
        $this->db->set('id', $profitSharingParty->getId());
        $this->db->set('corporate_service_profit_sharing_id', $profitSharingParty->getCorporateServProfitSharingId());
        $this->db->set('corporate_id', $profitSharingParty->getCorporateId());
        $this->db->set('percentage', $profitSharingParty->getPercentage());
        $this->db->set('created_at', IappsDateTime::now()->getUnix());
        $this->db->set('created_by', $profitSharingParty->getCreatedBy());

        if( $this->db->insert('iafb_remittance.profit_sharing_party') )
        {
            return true;
        }

        return false;
    }

    public function findAllByCorporateServProfitSharingId($collection, RemittanceProfitSharingParty $profitSharingParty)
    {
        $total = 0;
        $this->db->start_cache();
        $this->db->select('*');

        $this->db->from('iafb_remittance.profit_sharing_party csps');
        $this->db->where('csps.corporate_service_profit_sharing_id',$profitSharingParty->getCorporateServProfitSharingId());
        $this->db->where('deleted_at', NULL);
        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit
        $query = $this->db->get();
        $this->db->flush_cache();

        if( $query->num_rows() > 0 )
        {
            return $this->mapCollection($query->result(), $collection, $total);
        }

        return false;
    }

    public function updateStatus(RemittanceRecord $record)
    {
        $this->db->set('status_id', $record->getStatus()->getId());
        $this->db->set('paid_at', $record->getPaidAt()->getUnix());
        $this->db->set('redeemed_at', $record->getRedeemedAt()->getUnix());
        $this->db->set('updated_at', IappsDateTime::now()->getUnix());
        $this->db->set('updated_by', $record->getUpdatedBy());
        $this->db->where('id', $record->getId());

        if( $this->db->update('iafb_remittance.remittance') )
        {
            return true;
        }

        return false;
    }
}