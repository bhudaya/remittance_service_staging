<?php

namespace Iapps\RemittanceService\RemittanceCompanyUser;

use Iapps\Common\Core\IappsBaseEntityCollection;
use Iapps\Common\Core\IappsBasicBaseService;
use Iapps\Common\Core\PaginatedResult;
use Iapps\Common\Microservice\AccountService\PartnerAccountServiceFactory;
use Iapps\Common\Microservice\AccountService\User;
use Iapps\Common\Microservice\AccountService\UserStatus;
use Iapps\Common\Microservice\UserCreditService\OFACStatus;
use Iapps\Common\Microservice\UserCreditService\PartnerUserCreditServiceFactory;
use Iapps\Common\Microservice\UserCreditService\RiskLevelStatus;
use Iapps\Common\Microservice\UserCreditService\WorldCheckStatus;
use Iapps\RemittanceService\Common\Logger;
use Iapps\RemittanceService\Common\MessageCode;
use Iapps\RemittanceService\RemittanceCompany\RemittanceCompanyServiceFactory;
use Iapps\RemittanceService\Common\SystemCodeServiceFactory;
use Iapps\RemittanceService\RemittanceCompanyUser\RemittanceCompanyUserStatus;
use Iapps\Common\Microservice\UserCreditService\SystemUserCreditServiceFactory;

class PartnerRemittanceCompanyUserService extends IappsBasicBaseService{

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
                $remittanceProfile = RemcoUserNameExtractor::extractByEntity($remittanceProfile);

                $result = $user->getSelectedField(array(
                    'id', 'created_at', 'created_by', 'created_by_name',
                    'accountID', 'user_type', 'user_status', 'mobile_no', 'mobile_verified_at', 'email',
                    'email_verified_at', 'name', 'full_name', 'dob', 'gender', 'host_identity', 'host_address',
                    'host_country_code', 'profile_image_url', 'photo_image_url', 'id_front_image_url', 'id_back_image_url',
                    'verified_at', 'verified_by', 'completed_at', 'completed_by', 'last_edited_at', 'last_edited_by'
                ));

                $result['user_attributes'] = $user->getAttributes()->getSelectedField(array(
                    "id",
                    "attribute_id",
                    "attribute_value_id",
                    "value",
                    "attribute_code",
                    "attribute_name"
                ));

                $result['remittance_profile'] = $remittanceProfile->getSelectedField(array(
                                                                'id',
                                                                'customerID',
                                                                'user_status',
                                                                'completed_at',
                                                                'completed_by',
                                                                'verified_at',
                                                                'verified_by',
                                                                'rejected_at',
                                                                'rejected_by',
                                                                'verified_rejected_remark'
                                                            ));
                $result['remittance_profile']['completed_by_name'] = $remittanceProfile->getCompletedByName();
                $result['remittance_profile']['verified_by_name'] = $remittanceProfile->getVerifiedByName();
                $result['remittance_profile']['rejected_by_name'] = $remittanceProfile->getRejectedByName();

                $ucServ = PartnerUserCreditServiceFactory::build();
                $ucServ->setServiceProviderId($remittanceCompany->getServiceProviderId());
                $result['user_credit'] = array();
                if( !$wcStat = $ucServ->getWorldCheckStatus($user_profile_id) )
                {
                    $wcArr = array();
                    $wcArr['status'] = WorldCheckStatus::PENDING;
                    $wcArr['reference_no'] = NULL;
                }
                else
                {
                    $wcArr = $wcStat->getSelectedField(array('status','reference_no'));
                }

                if( !$riskStat = $ucServ->getRiskLevelStatus($user_profile_id) )
                    $riskStat = RiskLevelStatus::PENDING;
                
                $sysucServ = SystemUserCreditServiceFactory::build();
                $sysucServ->setServiceProviderId($remittanceCompany->getServiceProviderId());
                
                $ofacStat = null;
                $watchlistStatus = null;
                if( $watchlist = $sysucServ->getWatchlistStatusList(array($user_profile_id)) )
                {
                    foreach($watchlist AS $watch)
                    {
                        if( $watch->user_profile_id == $user_profile_id )
                        {
                            $ofacStat = $watch->status->ofac->status;
                            $watchlistStatus = $watch->status->watchlist->status;
                            break;
                        }
                    }
                }                        

                $result['user_credit']['worldcheck'] = $wcArr;
                $result['user_credit']['ofac'] = $ofacStat;
                $result['user_credit']['risk_level'] = $riskStat;
                $result['user_credit']['watchlist'] = $watchlistStatus;

                return $result;
            }
        }

        if( !$this->getResponseCode() )
            $this->setResponseCode(MessageCOde::CODE_GET_REMITTANCE_PROFILE_FAILED);
        return false;

    }

    public function getUserList($page, $limit, User $filter = NULL)
    {
        $reUserFilters = new RemittanceCompanyUserCollection();

        //get remittance profile
        if( $remittanceCompany = $this->_getRemittanceCompany() )
        {
            $accServ = PartnerAccountServiceFactory::build();
            $userList = new IappsBaseEntityCollection();

            //if additional user filter is given, search from account service first
            if( $filter )
            {
                //do not search country code to account service
                $country_code = $filter->getHostCountryCode();
                $filter->setHostCountryCode(NULL);

                $code = NULL;
                if ($filter->getUserStatus()->getCode()) {
                    $systemCodeSer = SystemCodeServiceFactory::build();
                    if (!$code = $systemCodeSer->getByCode($filter->getUserStatus()->getCode(),RemittanceCompanyUserStatus::getSystemGroupCode())) {

                        $this->setResponseCode(MessageCOde::CODE_GET_REMITTANCE_PROFILE_FAILED);
                        return false;
                    }
                }

                if( $filter->getAccountID() OR $filter->getEmail() OR $filter->getMobileNumberObj()->getCombinedNumber()
                    OR $filter->getHostIdentityCard() OR $filter->getFullName() )
                {
                    if( !$filteredUser = $accServ->searchUserByFilter(1, MAX_VALUE, $filter) )
                    {
                        $this->setResponseCode(MessageCOde::CODE_GET_REMITTANCE_PROFILE_FAILED);
                        return false;
                    }

                    $userList = clone($filteredUser);
                    foreach( $filteredUser->getIds() AS $userId )
                    {
                        $reUserFilter = new RemittanceCompanyUser();
                        $reUserFilter->getUser()->setId($userId);
                        $reUserFilter->setCountryCode($country_code);
                        if ($code != NULL) {
                            $reUserFilter->setUserStatus($code);
                        }
                        $reUserFilters->addData($reUserFilter);
                    }
                }
                else
                {
                    $reUserFilter = new RemittanceCompanyUser();
                    $reUserFilter->setCountryCode($country_code);
                    if ($code != NULL) {
                        $reUserFilter->setUserStatus($code);
                    }                    
                    $reUserFilters->addData($reUserFilter);
                }
            }

            //search remittance user
            $serv = RemittanceCompanyUserServiceFactory::build();
            $serv->setIpAddress($this->getIpAddress());
            $serv->setUpdatedBy($this->getUpdatedBy());

            if( $reUsers = $serv->listByRemittanceCompany($remittanceCompany, $reUserFilters) )
            {
                $this->setResponseCode($serv->getResponseCode());

                if( count($userList) <= 0 )
                {//get from account service if havent gotten yet
                    if( $userIds = $reUsers->getResult()->getUserProfileIds() AND count($userIds) > 0 )
                        $userList = $accServ->getUsers($userIds);
                }

                //combined result
                if( $userList instanceof IappsBaseEntityCollection AND
                    count($userList) > 0 )
                    $reUsers->getResult()->joinUser($userList);

                //paginate re users
                $paginatedReUsers = $reUsers->getResult()->pagination($limit, $page);

                //construct json
                $result = new PaginatedResult();
                $result->setTotal($paginatedReUsers->getTotal());
                $resultArray = array();
                foreach( $paginatedReUsers->getResult() AS $reUser)
                {
                    $temp['user_profile'] = $reUser->getUser()->getSelectedField(array(
                        'id', 'accountID', 'name',
                        'full_name', 'email', 'mobile_no',
                        'profile_image_url', 'user_type', 'user_status', 'created_at',
                        'verified_at', 'host_address', 'completed_at', 'last_edited_at'
                    ));

                    $temp['remittance_profile'] = $reUser->getSelectedField(array(
                        'id',
                        'customerID',
                        'user_status',
                        'created_at',
                        'completed_at',
                        'completed_by',
                        'verified_at',
                        'verified_by'
                    ));

                    $resultArray[] = $temp;
                }

                $result->setResult($resultArray);

                return $result;
            }
            else
                $this->setResponseCode($serv->getResponseCode());
        }

        if( !$this->getResponseCode() )
            $this->setResponseCode(MessageCOde::CODE_GET_REMITTANCE_PROFILE_FAILED);
        return false;
    }

    /*
    public function verifyUserProfile($user_profile_id, $status, $remark = NULL)
    {
        //get user
        if( !$user = $this->_getUser($user_profile_id) )
            return false;

        //get agent and upline
        if( $remittanceCompany = $this->_getRemittanceCompany() )
        {
            $serv = RemittanceCompanyUserServiceFactory::build();
            $serv->setIpAddress($this->getIpAddress());
            $serv->setUpdatedBy($this->getUpdatedBy());

            Logger::debug('Remittance.verifyingRemittanceProfile : ' . $user->getId());
            $ucServ = PartnerUserCreditServiceFactory::build();
            $ucServ->setServiceProviderId($remittanceCompany->getServiceProviderId());
            if( $serv->verifyProfile($user, $remittanceCompany, $status, $ucServ, $remark) )
            {//call to verify main profile
                if( $user->getUserStatus()->getCode() == UserStatus::COMPLETED OR
                    $user->getUserStatus()->getCode() == UserStatus::FAILED_VERIFY )
                {
                    $accServ = PartnerAccountServiceFactory::build();
                    $accServ->verifyUser($user_profile_id, $status);
                }

                $this->setResponseCode($serv->getResponseCode());
                return true;
            }
            else
                $this->setResponseCode($serv->getResponseCode());

        }

        return false;
    }
    */

    protected function _getMainAgent()
    {
        $acc_serv = PartnerAccountServiceFactory::build();
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
        $accServ = PartnerAccountServiceFactory::build();
        if( $user = $accServ->getUserProfile($user_profile_id) )
            return $user;

        $this->setResponseCode(MessageCode::CODE_USER_NOT_FOUND);
        return false;
    }
}