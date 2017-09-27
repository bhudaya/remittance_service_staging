<?php

namespace Iapps\RemittanceService\UserRiskLevel;

use Iapps\Common\Core\IappsBaseDataMapper;

interface UserRiskLevelDataMapper extends IappsBaseDataMapper{

    public function findByUserProfileId($user_profile_id);
    public function update(UserRiskLevel $userRiskLevel);
    public function insert(UserRiskLevel $userRiskLevel);
    public function updateApprovalStatus(UserRiskLevel $userRiskLevel);
    public function findAllUserRiskLevel($limit, $page);
    public function checkHasPendingStatusRequest($user_profile_id);
    public function findByPar(UserRiskLevel $userRiskLevel);
    public function checkHasApprovedAndIsActiveRequest($user_profile_id);
}