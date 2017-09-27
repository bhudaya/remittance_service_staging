<?php

namespace Iapps\RemittanceService\WorldCheck;

use Iapps\Common\Core\IappsBaseService;
use Iapps\RemittanceService\Common\MessageCode;
use Iapps\Common\Helper\GuidGenerator;
use Iapps\Common\AuditLog\AuditLogAction;
use Iapps\RemittanceService\WorldCheck\WorldCheck;
use Iapps\RemittanceService\WorldCheck\WorldCheckStatusValidator;
use Iapps\RemittanceService\WorldCheck\WorldCheckStatus;
use Iapps\Common\Core\IappsDateTime;

use Iapps\Common\Microservice\AccountService\AccountServiceFactory;

class WorldCheckService extends IappsBaseService{
    
    public function getWorldCheckProfileInfo($user_profile_id)
    {
        if( $info = $this->getRepository()->findById($user_profile_id) )
        {
            $this->setResponseCode(MessageCode::CODE_GET_WORLD_CHECK_PROFILE_SUCCESS);
            return $info;
        }

        $this->setResponseCode(MessageCode::CODE_GET_WORLD_CHECK_PROFILE_FAIL);
        return false;
    }

    public function getWorldCheckProfileInfoByUserProfileIDArr($user_profile_id_arr)
    {
        if( $info = $this->getRepository()->findByUserProfileIDArr($user_profile_id_arr) )
        {
            $this->setResponseCode(MessageCode::CODE_GET_WORLD_CHECK_PROFILE_SUCCESS);
            return $info;
        }

        $this->setResponseCode(MessageCode::CODE_GET_WORLD_CHECK_PROFILE_FAIL);
        return false;
    }

    public function saveWorldCheckProfile(WorldCheck $worldCheck, $admin_id)
    {
        if( !$statusObj = $this->_checkStatus($worldCheck->getStatus()) )
            return false;

        $accServ = AccountServiceFactory::build();

        if ( $this->getRepository()->findById($worldCheck->getUserProfileId()) ) {

            // world check is exists
            $commit_trans = false;
            $this->getRepository()->startDBTransaction();

            $worldCheck->setUpdatedBy($admin_id);

            if ( $this->getRepository()->updateWorldCheckProfile($worldCheck) ) {
                
                $commit_trans = true;
                $this->fireLogEvent('iafb_remittance.worldcheck', AuditLogAction::UPDATE, $worldCheck->getId(), NULL);
            }

            if($commit_trans)
            {
                $this->setResponseCode(MessageCode::CODE_WORLD_CHECK_SAVE_SUCCESS);
                //commit db trans
                $this->getRepository()->completeDBTransaction();
                //$accServ->verifyUser($worldCheck->getUserProfileId(), $worldCheck->getStatus());
                return true;
            }

            //roll back db trans
            $this->getRepository()->rollbackDBTransaction();
            $this->setResponseCode(MessageCode::CODE_WORLD_CHECK_SAVE_FAIL);
            return false;

        }else{
            // need insert 
            $worldCheck->setId(GuidGenerator::generate());
            $worldCheck->setCreatedBy($admin_id);

            if ( $this->getRepository()->insertWorldCheckProfile($worldCheck) ) {
                
                $this->fireLogEvent('iafb_remittance.worldcheck', AuditLogAction::CREATE, $worldCheck->getId(), NULL);
                $this->setResponseCode(MessageCode::CODE_WORLD_CHECK_SAVE_SUCCESS);
                //$accServ->verifyUser($worldCheck->getUserProfileId(), $worldCheck->getStatus());
                return $worldCheck;

            }

            //roll back db trans
            $this->getRepository()->rollbackDBTransaction();
            $this->setResponseCode(MessageCode::CODE_WORLD_CHECK_SAVE_FAIL);
            return false;   

        }

        $this->setResponseCode(MessageCode::CODE_WORLD_CHECK_SAVE_FAIL);
        return false;
    }


    
    protected function _checkStatus($code)
    {
        if( $status = WorldCheckStatusValidator::validate($code) )
        {
            return $status;
        }

        $this->setResponseCode(MessageCode::CODE_INVALID_WORLD_CHECK_STATUS);
        return false;
    }
}