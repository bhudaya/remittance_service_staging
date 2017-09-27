<?php

namespace Iapps\RemittanceService\RemittanceRecord;

use Iapps\Common\Core\IappsBasicBaseService;
use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\Common\Microservice\AccountService\UserStatus;
use Iapps\RemittanceService\Common\MessageCode;
use Iapps\RemittanceService\RemittanceCompanyUser\RemittanceCompanyUser;
use Iapps\RemittanceService\RemittanceCompanyUser\RemittanceCompanyUserServiceFactory;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfig;

class UserCompulsoryRemittanceChecker extends IappsBasicBaseService{

    protected $_passed = false;

    protected function setPass()
    {
        $this->_passed = true;
        return $this;
    }

    protected function setFail()
    {
        $this->_passed = false;
        return $this;
    }

    public function fails()
    {
        return ($this->_passed === false);
    }

    public static function checkRequestEligible($user_profile_id, RemittanceConfig $remittanceConfig)
    {//check user main profile is not unverified

        $c = new UserCompulsoryRemittanceChecker();
        $c->setFail();

        $accountServ = AccountServiceFactory::build();
        if( $user = $accountServ->getUser(NULL, $user_profile_id) )
        {
            if( $user->getUserStatus()->getCode() != UserStatus::UNVERIFIED )
            {//ok
                $c->setPass();
                return $c;
            }
            else
            {
                $c->setResponseCode(MessageCode::CODE_USER_IS_NOT_QUALIFIED);
                $c->setFail();
                return $c;
            }
        }

        $c->setResponseCode(MessageCode::CODE_USER_NOT_FOUND);
        $c->setFail();
        return $c;
    }

    public static function check($user_profile_id, RemittanceConfig $remittanceConfig)
    {
        $c = new UserCompulsoryRemittanceChecker();
        $c->setFail();

        //if user is high risk profile then failed
        /* this is no longer required as this is part of prelim check
        $accountServ = AccountServiceFactory::build();

        if( $user_risk = $accountServ->getUserRiskLevelByProfileId($user_profile_id) )
        {
            if( $user_risk->active_risk_level == "high" )
            {
                $c->setResponseCode(MessageCode::CODE_USER_IS_HIGH_RISK);
                $c->setFail();
                return $c;
            }
        }*/

        $remUserServ = RemittanceCompanyUserServiceFactory::build();
        if( $remUser = $remUserServ->getByCompanyAndUser($remittanceConfig->getRemittanceCompany(), $user_profile_id) )
        {
            if( $remUser instanceof RemittanceCompanyUser )
            {
                $accountServ = AccountServiceFactory::build();
                if( $user = $accountServ->getUser(NULL, $user_profile_id) )
                {
                    $remUser->setUser($user);

                    if( !$remUser->isVerified() )
                    {
                        $c->setResponseCode(MessageCode::CODE_USER_IS_NOT_KYC);
                        $c->setFail();
                        return $c;
                    }

                    $c->setPass();
                    return $c;
                }
            }
        }

        $c->setResponseCode(MessageCode::CODE_GET_REMITTANCE_PROFILE_FAILED);
        $c->setFail();
        return $c;
    }
}