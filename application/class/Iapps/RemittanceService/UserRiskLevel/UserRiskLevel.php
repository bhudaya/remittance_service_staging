<?php

namespace Iapps\RemittanceService\UserRiskLevel;

use Iapps\Common\Core\IappsBaseEntity;
use Iapps\Common\SystemCode\SystemCode;

class UserRiskLevel extends IappsBaseEntity{
    protected $user_profile_id;
    protected $unactive_risk_level;
    protected $active_risk_level;
    protected $level_changed_reason;
    protected $approval_status;
    protected $is_active;


    function __construct()
    {
        parent::__construct();
        // $this->unactive_risk_level = new SystemCode();
        // $this->active_risk_level = new SystemCode();
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

    public function setUnActiveRiskLevel($unactive_risk_level)
    {
        $this->unactive_risk_level = $unactive_risk_level;
        return true;
    }

    public function getUnActiveRiskLevel()
    {
        return $this->unactive_risk_level;
    }

    public function setActiveRiskLevel($active_risk_level)
    {
        $this->active_risk_level = $active_risk_level;
        return true;
    }

    public function getActiveRiskLevel()
    {
        return $this->active_risk_level;
    }

    public function setLevelChangedReason($level_changed_reason)
    {
        $this->level_changed_reason = $level_changed_reason;
        return true;
    }

    public function getLevelChangedReason()
    {
        return $this->level_changed_reason;
    }

    public function setApprovalStatus($approval_status)
    {
        $this->approval_status = $approval_status;
        return true;
    }

    public function getApprovalStatus()
    {
        return $this->approval_status;
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

        $json['user_profile_id']   = $this->getUserProfileId();
        $json['unactive_risk_level']        = $this->getUnActiveRiskLevel();
        $json['active_risk_level']        = $this->getActiveRiskLevel();
        $json['level_changed_reason']      = $this->getLevelChangedReason();
        $json['approval_status']           = $this->getApprovalStatus();
        $json['is_active']           = $this->getIsActive();
        return $json;
    }
}