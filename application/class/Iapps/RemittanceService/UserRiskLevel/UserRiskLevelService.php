<?php

namespace Iapps\RemittanceService\UserRiskLevel;

use Iapps\Common\Core\IappsBaseService;
use Iapps\RemittanceService\Common\MessageCode;
use Iapps\Common\Helper\GuidGenerator;
use Iapps\Common\AuditLog\AuditLogAction;
use Iapps\RemittanceService\UserRiskLevel\UserRiskLevel;
use Iapps\RemittanceService\UserRiskLevel\UserRiskLevelStatusValidator;
use Iapps\RemittanceService\UserRiskLevel\UserRiskLevelStatus;
use Iapps\RemittanceService\UserRiskLevel\UserRiskLevelApprovalStatus;
use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\Microservice\AccountService\AccountService;
use Iapps\Common\Microservice\AccountService\User;
use Iapps\RemittanceService\WorldCheck\WorldCheckServiceFactory;
use Iapps\Common\Microservice\AccountService\AccountServiceFactory;

class UserRiskLevelService extends IappsBaseService{

    public function getAllUserRiskLevel($limit, $page)
    {
        if( $collection = $this->getRepository()->findAllUserRiskLevel($limit, $page) )
        {   
            $accountSer = AccountServiceFactory::build();
            $user = $collection->result;
            $results = array();

            foreach ($user as $info) {
                
                if ($userInfo = $accountSer->getUser($accountID = NULL, $info->getUserProfileId())) {
                    
                    $result = $info->getSelectedField(array('id','unactive_risk_level', 'active_risk_level', 'level_changed_reason', 'approval_status','created_at', 'updated_at', 'deleted_at','deleted_by'));

                    $result['user_profile_id'] = $userInfo->getId();
                    $result['accountID'] = $userInfo->getAccountID();
                    $result['user_type'] = $userInfo->getUserType();
                    $result['email'] = $userInfo->getEmail();
                    $result['mobile_no'] = $userInfo->getMobileNumber();
                    $result['name'] = $userInfo->getName();

                    if ($createdBy = $accountSer->getUser($accountID = NULL, $info->getCreatedBy())) {
                        $result['created_by'] = $createdBy->getName();
                    }else{
                        $result['created_by'] = NULL;
                    }

                    if ($updatedBy = $accountSer->getUser($accountID = NULL, $info->getUpdatedBy())) {
                        $result['updated_by'] = $updatedBy->getName();
                    }else{
                        $result['updated_by'] = NULL;
                    }

                    $results[] = $result;
                }
            }
            $this->setResponseCode(MessageCode::CODE_GET_USER_RISK_LEVEL_SUCCESS);
            return $results;
        }

        $this->setResponseCode(MessageCode::CODE_GET_USER_RISK_LEVEL_FAIL);
        return false;
    }


    public function getUserRiskLevelByUserProfiledId($user_profile_id)
    {   

        $userRiskLevel = new UserRiskLevel();
        $userRiskLevel->setUserProfileId($user_profile_id);
        $userRiskLevel->setApprovalStatus(UserRiskLevelApprovalStatus::PENDING);
        $userRiskLevel->setIsActive(0);

        $info = NULL;
        if( !$info = $this->getRepository()->findByPar($userRiskLevel) ){

            $userRiskLevel->setUserProfileId($user_profile_id);
            $userRiskLevel->setApprovalStatus(UserRiskLevelApprovalStatus::APPROVED);
            $userRiskLevel->setIsActive(1);
            $info = $this->getRepository()->findByPar($userRiskLevel);
        }

        if( $info )
        {
            $result = $info->result->current()->getSelectedField(array('id','user_profile_id','unactive_risk_level', 'active_risk_level', 'level_changed_reason', 'approval_status','created_at', 'updated_at', 'created_by','updated_by','deleted_at','deleted_by'));

            $accountSer = AccountServiceFactory::build();
            if ($userInfo = $accountSer->getUser($accountID = NULL, $info->result->current()->getUserProfileId())) {

                if ($createdBy = $accountSer->getUser($accountID = NULL, $info->result->current()->getCreatedBy())) {
                    $result['created_by'] = $createdBy->getName();
                }else{
                    $result['created_by'] = NULL;
                }

                if ($updatedBy = $accountSer->getUser($accountID = NULL, $info->result->current()->getUpdatedBy())) {
                    $result['updated_by'] = $updatedBy->getName();
                }else{
                    $result['updated_by'] = NULL;
                }
            }

            $this->setResponseCode(MessageCode::CODE_GET_USER_RISK_LEVEL_SUCCESS);
            return $result;
        }

        $this->setResponseCode(MessageCode::CODE_GET_USER_RISK_LEVEL_FAIL);
        return false;
    }

    public function getUserProfileAndRiskLevelByUserProfileId($user_profile_id)
    {
        $resultObject = new \StdClass;
        $resultObject->user_profile = null;
        $resultObject->user_risk_level = 0;

        $account_serv = AccountServiceFactory::build();
        if( $userProfile = $account_serv->getUser(NULL, $user_profile_id) )
        {
            $resultObject->user_profile = $userProfile;
            if( $riskLevelInfo = $this->getRepository()->findByUserProfileId($user_profile_id) )
            {
                $resultObject->user_risk_level = $riskLevelInfo;
            }

            $this->setResponseCode(MessageCode::CODE_GET_USER_RISK_LEVEL_SUCCESS);
            return $resultObject;
        }

        $this->setResponseCode(MessageCode::CODE_GET_USER_RISK_LEVEL_FAIL);
        return false;
    }

    public function updateUserRiskLevel(UserRiskLevel $userRiskLevel, $admin_id)
    {
        if (!$statusObj = $this->_checkStatus($userRiskLevel->getUnActiveRiskLevel()))
            return false;

        if ($this->getRepository()->checkHasPendingStatusRequest($userRiskLevel->getUserProfileId())) {
            return false;
        }

            // need insert             
        $userRiskLevel->setId(GuidGenerator::generate());
        $userRiskLevel->setCreatedBy($admin_id);
        $userRiskLevel->setApprovalStatus(UserRiskLevelApprovalStatus::PENDING);
        $userRiskLevel->setIsActive(0);

        if ( $this->getRepository()->insert($userRiskLevel) ) {
            
            $this->fireLogEvent('iafb_remittance.user_risk_level', AuditLogAction::CREATE, $userRiskLevel->getId(), NULL);
            $this->setResponseCode(MessageCode::CODE_USER_RISK_LEVEL_UPDATE_SUCCESS);
            return $userRiskLevel;
        }

        $this->setResponseCode(MessageCode::CODE_USER_RISK_LEVEL_UPDATE_FAIL);
        return false;
    }

    public function updateUserRiskLevelStatus(UserRiskLevel $userRiskLevel)
    {
        if( !($userRiskLevel->getApprovalStatus() == UserRiskLevelApprovalStatus::APPROVED || $userRiskLevel->getApprovalStatus() == UserRiskLevelApprovalStatus::REJECTED) )
            return false;


        if ( $info = $this->getRepository()->findById($userRiskLevel->getId()) ) {

            if ($info->getApprovalStatus() == UserRiskLevelApprovalStatus::PENDING) {

                $commit_trans = false;
                $this->getRepository()->startDBTransaction();

                if ($userRiskLevel->getApprovalStatus() == UserRiskLevelApprovalStatus::APPROVED) {

                    if ($old = $this->getRepository()->checkHasApprovedAndIsActiveRequest($info->getUserProfileId())) {
                        $old->setIsActive(0);
                        if ( !$this->getRepository()->update($old) ) {
                            $this->getRepository()->rollbackDBTransaction();
                            $this->setResponseCode(MessageCode::CODE_APPROVAL_USER_RISK_LEVEL_FAIL);
                            return false;
                        }
                    }
                    
                    $userRiskLevel->setIsActive(1);
                    $userRiskLevel->setActiveRiskLevel($info->getUnActiveRiskLevel());
                    $userRiskLevel->setUnActiveRiskLevel($info->getActiveRiskLevel());

                    if ( !$this->getRepository()->update($userRiskLevel) ) {
                        $this->getRepository()->rollbackDBTransaction();
                        $this->setResponseCode(MessageCode::CODE_APPROVAL_USER_RISK_LEVEL_FAIL);
                        return false;
                    }
                    $commit_trans = true;
                    $this->fireLogEvent('iafb_remittance.user_risk_level', AuditLogAction::UPDATE, $userRiskLevel->getId(), NULL);
                }

                if ($userRiskLevel->getApprovalStatus() == UserRiskLevelApprovalStatus::REJECTED) {
                    $userRiskLevel->setIsActive(0);
                    if ( !$this->getRepository()->update($userRiskLevel) ) {
                        $this->getRepository()->rollbackDBTransaction();
                        $this->setResponseCode(MessageCode::CODE_APPROVAL_USER_RISK_LEVEL_FAIL);
                        return false;
                    }
                    
                    $commit_trans = true;
                    $this->fireLogEvent('iafb_remittance.user_risk_level', AuditLogAction::UPDATE, $userRiskLevel->getId(), NULL);
                }

                if($commit_trans)
                {   
                    $this->setResponseCode(MessageCode::CODE_APPROVAL_USER_RISK_LEVEL_SUCCESS);
                    //commit db trans
                    $this->getRepository()->completeDBTransaction();
                    return true;
                }

                $this->setResponseCode(MessageCode::CODE_APPROVAL_USER_RISK_LEVEL_FAIL);
                return false;
            }
        }
    }
    
    protected function _checkStatus($code)
    {
        if( $status = UserRiskLevelStatusValidator::validate($code) )
        {
            return $status;
        }

        $this->setResponseCode(MessageCode::CODE_INVALID_USER_RISK_LEVEL_STATUS);
        return false;
    }
}