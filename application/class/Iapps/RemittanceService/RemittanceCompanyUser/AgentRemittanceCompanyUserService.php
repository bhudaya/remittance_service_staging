<?php

namespace Iapps\RemittanceService\RemittanceCompanyUser;

use Iapps\Common\Microservice\AccountService\AgentAccountServiceFactory;
use Iapps\Common\Microservice\AccountService\UserStatus;
use Iapps\RemittanceService\Common\Logger;
use Iapps\RemittanceService\Common\UserAuthorizationChecker;
use Iapps\RemittanceService\RemittanceCompany\RemittanceCompanyServiceFactory;
use Iapps\Common\Core\IappsBasicBaseService;
use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\RemittanceService\Common\MessageCode;

class AgentRemittanceCompanyUserService extends IappsBasicBaseService{

    protected $unauthorized = false;

    public function setUnauthorized($flag)
    {
        $this->unauthorized = $flag;
        return $this;
    }

    public function getUnauthorized()
    {
        return $this->unauthorized;
    }

    public function completeUserProfile($user_profile_id)
    {
        //get user
        if( !$user = $this->_getUser($user_profile_id) )
            return false;

        //removed user authorization check 
        /*
        if( $user->getLastEditedBy() != $this->getUpdatedBy() )
        {//required to check user authorization
            if( !UserAuthorizationChecker::check($user_profile_id) )
            {
                $this->setUnauthorized(true);
                return false;
            }

            $this->setUnauthorized(false);
        }*/

        $completing = false;
        //get agent and upline
        if( $remittanceCompany = $this->_getRemittanceCompany() )
        {
            $serv = RemittanceCompanyUserServiceFactory::build();
            $serv->setIpAddress($this->getIpAddress());
            $serv->setUpdatedBy($this->getUpdatedBy());
            $serv->setAccountService(AgentAccountServiceFactory::build());

            Logger::debug('Remittance.completingRemittanceProfile : ' . $user->getId());
            if( $serv->completeProfile($user, $remittanceCompany) )
                $completing = true;
            else
                $this->setResponseCode($serv->getResponseCode());
        }
        else
        {
            //complete main profile
            if( $user->getUserStatus()->getCode() == RemittanceCompanyUserStatus::READY_FOR_KYC )
            {//complete main profile
                Logger::debug('Remittance.completingMainProfile : ' . $user->getId());

                $agentAccServ = AgentAccountServiceFactory::build();
                if( $agentAccServ->completeUserProfile($user_profile_id) )
                    $completing = true;
                else
                    $this->setResponseCode(MessageCode::CODE_COMPLETE_PROFILE_FAILED);
            }
        }

        if( $completing == true )
        {
            $this->setResponseCode(MessageCode::CODE_COMPLETE_PROFILE_SUCCESS);
            return true;
        }

        if( !$this->getResponseCode() )
            $this->setResponseCode(MessageCode::CODE_COMPLETE_PROFILE_FAILED);
        return false;
    }

    public function getUserProfile($user_profile_id)
    {
        //get user
        if( !$user = $this->_getUser($user_profile_id) )
            return false;

        //get remittance profile
        if( $remittanceCompany = $this->_getRemittanceCompany() )
        {
            $serv = RemittanceCompanyUserServiceFactory::build();
            $serv->setIpAddress($this->getIpAddress());
            $serv->setUpdatedBy($this->getUpdatedBy());

            Logger::debug('Remittance.completingRemittanceProfile : ' . $user->getId());
            if( $remittanceProfile = $serv->getByCompanyAndUser($remittanceCompany, $user_profile_id) )
            {
                $this->setResponseCode(MessageCOde::CODE_GET_REMITTANCE_PROFILE_SUCCESS);
                return $remittanceProfile->getSelectedField(array(
                    'id',
                    'customerID',
                    'user_status',
                    'completed_at',
                    'completed_by',
                    'verified_at',
                    'verified_by'
                ));
            }
        }

        if( !$this->getResponseCode() )
            $this->setResponseCode(MessageCOde::CODE_GET_REMITTANCE_PROFILE_FAILED);
        return false;
    }

    protected function _getMainAgent()
    {
        $acc_serv = AccountServiceFactory::build();
        if( $structure = $acc_serv->getAgentUplineStructure() )
        {
            if( $upline = $structure->first_upline )
            {
                if( $upline->getRoles()->hasRole(array('main_agent')) )
                {
                    return $upline->getUser();
                }
            }

            if( $upline = $structure->second_upline )
            {
                if( $upline->getRoles()->hasRole(array('main_agent')) )
                {
                    return $upline->getUser();
                }
            }
        }

        return false;
    }

    protected function _getRemittanceCompany()
    {
        if( $mainAgent = $this->_getMainAgent() )
        {
            $rcServ = RemittanceCompanyServiceFactory::build();
            return $rcServ->getByServiceProviderId($mainAgent->getId());
        }

        $this->setResponseCode(MessageCode::CODE_REMCO_NOT_FOUND);
        return false;
    }

    protected function _getUser($user_profile_id)
    {
        //get user
        $accServ = AgentAccountServiceFactory::build();
        if( $user = $accServ->getUser($user_profile_id) )
            return $user;

        $this->setResponseCode(MessageCode::CODE_USER_NOT_FOUND);
        return false;
    }
}