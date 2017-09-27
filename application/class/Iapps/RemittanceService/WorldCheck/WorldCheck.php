<?php

namespace Iapps\RemittanceService\WorldCheck;

use Iapps\Common\Core\IappsBaseEntity;
use Iapps\Common\SystemCode\SystemCode;

class WorldCheck extends IappsBaseEntity{
    protected $user_profile_id;
    protected $reference_no;
    protected $status;
    protected $remarks;

    function __construct()
    {
        parent::__construct();
        $this->status = new SystemCode();
    }

    public function setUserProfileId($user_profile_id)
    {
        $this->user_profile_id = $user_profile_id;
        return true;
    }

    public function getUserProfileId()
    {
        return $this->user_profile_id;
    }

    public function setReferenceNo($reference_no)
    {
        $this->reference_no = $reference_no;
        return true;
    }

    public function getReferenceNo()
    {
        return $this->reference_no;
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

    public function setRemarks($remarks)
    {
        $this->remarks = $remarks;
        return true;
    }

    public function getRemarks()
    {
        return $this->remarks;
    }

    public function jsonSerialize()
    {
        $json = parent::jsonSerialize();

        $json['user_profile_id']   = $this->getUserProfileId();
        $json['reference_no']      = $this->getReferenceNo();
        $json['status']            = $this->getStatus();
        $json['remarks']           = $this->getRemarks();
        return $json;
    }
}