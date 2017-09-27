<?php

namespace Iapps\RemittanceService\RemittanceProfitSharing;

use Iapps\Common\Core\IappsBaseEntity;
use Iapps\Common\Core\IappsDateTime;

class RemittanceCorpServProfitSharing extends IappsBaseEntity{

    protected $corporate_service_id;
    protected $status;
    protected $approve_reject_remark;
    protected $approve_reject_at;
    protected $approve_reject_by;
    protected $is_active;

    protected $parties;


    function __construct()
    {
        parent::__construct();

        $this->approve_reject_at = new IappsDateTime();
        $this->parties = new RemittanceProfitSharingPartyCollection();

    }

    public function setCorporateServiceId($corporate_service_id)
    {
        $this->corporate_service_id = $corporate_service_id;
        return true;
    }

    public function getCorporateServiceId()
    {
        return $this->corporate_service_id;
    }


    public function setStatus($status)
    {
        $this->status = $status;
        return true;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setApproveRejectRemark($approve_reject_remark)
    {
        $this->approve_reject_remark = $approve_reject_remark;
        return true;
    }

    public function getApproveRejectRemark()
    {
        return $this->approve_reject_remark;
    }

    public function setApproveRejectAt($approve_reject_at)
    {
        $this->approve_reject_at = $approve_reject_at;
        return true;
    }

    public function getApproveRejectAt()
    {
        return $this->approve_reject_at;
    }

    public function setApproveRejectBy($approve_reject_by)
    {
        $this->approve_reject_by = $approve_reject_by;
        return true;
    }

    public function getApproveRejectBy()
    {
        return $this->approve_reject_by;
    }

    public function setIsActive($is_active)
    {
        $this->is_active = $is_active;
        return true;
    }

    public function getIsActive()
    {
        return $this->is_active;
    }

    

    public function jsonSerialize()
    {
        $json = parent::jsonSerialize();

        $json['corporate_service_id']   = $this->getCorporateServiceId();
        $json['status']                 = $this->getStatus();
        $json['approve_reject_remark']  = $this->getApproveRejectRemark();
        $json['approve_reject_at']      = $this->getApproveRejectAt()->getString();
        $json['approve_reject_by']      = $this->getApproveRejectBy();
        $json['is_active']              = $this->getIsActive();
        
        return $json;
    }

    public function setParties(RemittanceProfitSharingPartyCollection $parties)
    {
        $this->parties = $parties;
        return $this;
    }

    public function getParties()
    {
        return $this->parties;
    }

    public function calculateProfitSharing($profit)
    {
        foreach( $this->getParties() AS $party)
        {
            $party->calculateSharedProfit($profit);
        }

        return $this;
    }
}