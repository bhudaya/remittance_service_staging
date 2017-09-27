<?php

namespace Iapps\RemittanceService\UserRiskLevel;

use Iapps\Common\Core\IappsBaseRepository;

class UserRiskLevelRepository extends IappsBaseRepository{

    public function findByUserProfileId($user_profile_id)
    {
        return $this->getDataMapper()->findByUserProfileId($user_profile_id);
    }

    public function update(UserRiskLevel $userRiskLevel)
    {
        return $this->getDataMapper()->update($userRiskLevel);
    }

    public function insert(UserRiskLevel $userRiskLevel)
    {
        return $this->getDataMapper()->insert($userRiskLevel);
    }

    public function updateApprovalStatus(UserRiskLevel $userRiskLevel)
    {
        return $this->getDataMapper()->updateApprovalStatus($userRiskLevel);
    }

    public function findAllUserRiskLevel($limit, $page)
    {
        return $this->getDataMapper()->findAllUserRiskLevel($limit, $page);
    }

    public function checkHasPendingStatusRequest($user_profile_id)
    {
        return $this->getDataMapper()->checkHasPendingStatusRequest($user_profile_id);
    }

    public function findByPar(UserRiskLevel $userRiskLevel)
    {
        return $this->getDataMapper()->findByPar($userRiskLevel);
    }
    public function checkHasApprovedAndIsActiveRequest($user_profile_id)
    {
        return $this->getDataMapper()->checkHasApprovedAndIsActiveRequest($user_profile_id);
    }
}