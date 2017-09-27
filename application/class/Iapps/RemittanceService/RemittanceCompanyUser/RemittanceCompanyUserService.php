<?php

namespace Iapps\RemittanceService\RemittanceCompanyUser;

use Iapps\Common\Core\IappsBaseService;
use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\Microservice\AccountService\AccountBaseMicroservice;
use Iapps\Common\Microservice\AccountService\AccountService;
use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\Common\Microservice\AccountService\AgentAccountServiceFactory;
use Iapps\Common\Microservice\AccountService\User;
use Iapps\Common\Microservice\AccountService\UserStatus;
use Iapps\Common\Microservice\CountryService\CountryServiceFactory;
use Iapps\Common\Microservice\UserCreditService\IUserCreditGetterInterface;
use Iapps\Common\Microservice\UserCreditService\OFACStatus;
use Iapps\Common\Microservice\UserCreditService\SystemUserCreditServiceFactory;
use Iapps\Common\Microservice\UserCreditService\WorldCheckStatus;
use Iapps\RemittanceService\Common\IncrementIDAttribute;
use Iapps\RemittanceService\Common\IncrementIDServiceFactory;
use Iapps\RemittanceService\Common\Logger;
use Iapps\RemittanceService\Common\MessageCode;
use Iapps\RemittanceService\Common\SystemCodeServiceFactory;
use Iapps\RemittanceService\RemittanceCompany\RemittanceCompany;
use Iapps\RemittanceService\RemittanceCompany\RemittanceCompanyServiceFactory;
use Iapps\Common\Microservice\UserCreditService\WorldCheck;

class RemittanceCompanyUserService extends IappsBaseService{

    protected $_accServ = NULL;
    protected $_usercreditServ = NULL;
    public function setAccountService(AccountBaseMicroservice $accountService)
    {
        $this->_accServ = $accountService;
        return $this;
    }

    public function setUserCreditService(IUserCreditGetterInterface $usercreditServ)
    {
        $this->_usercreditServ = $usercreditServ;
        return $this;
    }

    protected function _getUserCreditService()
    {
        if( $this->_usercreditServ == NULL )
            $this->setUserCreditService(SystemUserCreditServiceFactory::build());

        return $this->_usercreditServ;
    }

    protected function _getAccountService()
    {
        if( $this->_accServ == NULL )
            $this->setAccountService(AccountServiceFactory::build());

        return $this->_accServ;
    }

    public function findById($id)
    {
        return $this->getRepository()->findById($id);
    }
    /*
     * Create a remittance profile
     */
    public function createProfile(RemittanceCompany $remittanceCompany, User $user)
    {
        if( $remittanceProfile = $this->_createProfile($remittanceCompany, $user) )
        {
            if( $remittanceProfile->getUserStatus()->getCode() == RemittanceCompanyUserStatus::READY_FOR_KYC AND
                $remittanceCompany->getRequiredFaceToFaceVerification() === '0' )
            {
                if( !$this->completeProfile($user, $remittanceCompany) )
                    return false;
            }

            $this->setResponseCode(MessageCode::CODE_ADD_REMITTANCE_PROFILE_SUCCESS);
            return $remittanceProfile;
        }

        $this->setResponseCode(MessageCode::CODE_ADD_REMITTANCE_PROFILE_FAILED);
        return false;
    }

    protected function _createProfile(RemittanceCompany $remittanceCompany, User $user)
    {
        $remittanceProfile = RemittanceCompanyUser::create($remittanceCompany, $user);
        $this->_generateCustomerID($remittanceProfile);
        $this->_extractUserStatus($remittanceProfile);

        $remittanceProfile->setCreatedBy($this->getUpdatedBy());
        if( $this->getRepository()->insert($remittanceProfile) )
            return $remittanceProfile;

        return false;
    }

    public function recheckStatusAfterProfileEdited($user_profile_id)
    {
       if( $remittanceProfiles = $this->listByUser($user_profile_id, 1, MAX_VALUE, false) )
       {
            foreach($remittanceProfiles AS $remittanceProfile)
            {
                if( $remittanceProfile instanceof RemittanceCompanyUser )
                {
                    if( !$remittanceProfile->isReadyForKYC() AND
                        $remittanceProfile->isProfileEdited() AND
                        $remittanceProfile->getUser()->getUserStatus()->getCode() !== UserStatus::UNVERIFIED )
                    {//profile edited

                        Logger::debug('Remittance.resetting to ready for kyc : ' . $remittanceProfile->getUser()->getId());
                        $remittanceProfile->resetStatusToReadyForKYC();
                        $this->_extractUserStatus($remittanceProfile);

                        $remittanceProfile->setUpdatedBy($this->getUpdatedBy());
                        if( !$this->getRepository()->update($remittanceProfile, false) )
                        {
                            $this->setResponseCode(MessageCode::CODE_COMPLETE_PROFILE_FAILED);
                            return false;
                        }
                    }
                    elseif( $remittanceProfile->getUser()->getUserStatus()->getCode() == UserStatus::UNVERIFIED)
                    {//just in case for some reason profile goes backward to unverified
                        Logger::debug('Remittance.resetting to failed verified : ' . $remittanceProfile->getUser()->getId());
                        $remittanceProfile->getUserStatus()->setCode(RemittanceCompanyUserStatus::FAILED_VERIFY);
                        $this->_extractUserStatus($remittanceProfile);
                        if( !$this->getRepository()->update($remittanceProfile, false) )
                        {
                            $this->setResponseCode(MessageCode::CODE_COMPLETE_PROFILE_FAILED);
                            return false;
                        }
                    }

                    if( $remittanceProfile->isReadyForKYC() )
                    {
                        if( $remittanceProfile->getRemittanceCompany()->getRequiredFaceToFaceVerification() === '0' )
                        {
                            Logger::debug('Remittance.autoCompletingMainProfile : ' . $remittanceProfile->getUser()->getId());
                            if( !$this->completeProfile($remittanceProfile->getUser(), $remittanceProfile->getRemittanceCompany()) )
                            {
                                return false;
                            }
                        }
                    }
                }
            }
        }

        $this->setResponseCode(MessageCode::CODE_COMPLETE_PROFILE_SUCCESS);
        return true;
    }

    public function completeProfile(User $user, RemittanceCompany $remittanceCompany)
    {
        if( !$accServ = $this->_getAccountService() )
        {//should not happen!
            $this->setResponseCode(MessageCode::CODE_COMPLETE_PROFILE_FAILED);
            return false;
        }

        if( $user->getUserStatus()->getCode() == UserStatus::UNVERIFIED)
        {
            Logger::debug('Remittance.completeProfile main profile is unverified : ' . $user->getId());
            $this->setResponseCode(MessageCode::CODE_COMPLETE_PROFILE_FAILED);
            return false;
        }


        if( !$remittanceProfile = $this->getByCompanyAndUser($remittanceCompany, $user->getId()) )
        {//create profile for them
            if( !$remittanceProfile = $this->_createProfile($remittanceCompany, $user) )
            {
                $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_PROFILE_FAILED);
                return false;
            }
        }

        if( $remittanceProfile instanceof RemittanceCompanyUser )
        {
            $remittanceProfile->setUser($user);
            if( $remittanceProfile->isEligibleToComplete() )
            {
				$completedBy = new User();
				$completedBy->setId($this->getUpdatedBy());
                $remittanceProfile->complete($completedBy);
                $this->_extractUserStatus($remittanceProfile);

                //update
                $remittanceProfile->setUpdatedBy($this->getUpdatedBy());
                if( $this->getRepository()->update($remittanceProfile, false) )
                {
                    //complete main profile
                    if( $user->getUserStatus()->getCode() != UserStatus::VERIFIED )
                    {//complete main profile
                        Logger::debug('Remittance.completingMainProfile : ' . $user->getId());
                        $accServ->completeUserProfile($user->getId());
                    }

                    //publish remittance profile created
                    RemittanceCompanyUserEventProducer::publishStatusChanged($remittanceProfile);

                    $this->setResponseCode(MessageCode::CODE_COMPLETE_PROFILE_SUCCESS);
                    return true;
                }
            }
            else
                $this->setResponseCode(MessageCode::CODE_PROFILE_IS_ALREADY_COMPLETED);
        }

        if( !$this->getResponseCode() )
            $this->setResponseCode(MessageCode::CODE_COMPLETE_PROFILE_FAILED);
        return false;
    }

    public function verifyProfile(User $user, RemittanceCompany $remittanceCompany)
    {
        if( !$usercreditGetter = $this->_getUserCreditService() OR
            !$accServ = $this->_getAccountService() )
        {//should not happen!
            $this->setResponseCode(MessageCode::CODE_VERIFY_PROFILE_FAILED);
            return false;
        }

        if( $remittanceProfile = $this->getByCompanyAndUser($remittanceCompany, $user->getId()) )
        {
            if( $remittanceProfile instanceof RemittanceCompanyUser )
            {
                $remittanceProfile->setUser($user);
                
                //only completed, failed_verified, verified to proceed
                if( !$remittanceProfile->isCompleted() AND
                    !$remittanceProfile->isFailedVerified() AND
                    !$remittanceProfile->isVerified() )
                {
                    $this->setResponseCode(MessageCode::CODE_VERIFY_PROFILE_FAILED);
                    return false;
                }
                                                
                Logger::debug('Remittance.verifying user : ' . $remittanceProfile->getUser()->getId());
                
                //get status
                list($ucStatus, $updated_at, $remarks, $approved_by) = $this->_getUserCreditStatus($remittanceProfile);

                $tobeStatus = NULL;
                $status;
                if( $ucStatus == 'fail' )
                {
                    $status = 'fail';
                    $tobeStatus = RemittanceCompanyUserStatus::FAILED_VERIFY;
                }
                elseif( $ucStatus == 'pending' )
                {
                    $tobeStatus = RemittanceCompanyUserStatus::COMPLETED;
                }
                elseif( $ucStatus == 'pass' )
                {
                    $status = 'pass';
                    $tobeStatus = RemittanceCompanyUserStatus::VERIFIED;                     
                }
                
                if( $remittanceProfile->changeStatus($tobeStatus, $remarks, $approved_by) )
                {//status changes                    
                    $this->_extractUserStatus($remittanceProfile);
                    $remittanceProfile->setUpdatedBy($this->getUpdatedBy());
                    
                    if( $this->getRepository()->update($remittanceProfile, false) )
                    {
                        //update main profile status
                        if( $user->getUserStatus()->getCode() != UserStatus::VERIFIED AND
                            ($tobeStatus == RemittanceCompanyUserStatus::FAILED_VERIFY OR
                             $tobeStatus == RemittanceCompanyUserStatus::VERIFIED) )
                        {
                            Logger::debug('Remittance.verifying main profile of user : ' . $remittanceProfile->getUser()->getId());
                            $accServ->verifyUser($user->getId(), $status);
                        }

                        //publish remittance profile verified
                        RemittanceCompanyUserEventProducer::publishStatusChanged($remittanceProfile);

                        $this->setResponseCode(MessageCode::CODE_VERIFY_PROFILE_SUCCESS);
                        return true;
                    } 
                }
                else
                {
                    //nothing to change
                    Logger::debug('Remittance.verifying nothing to change for: ' . $remittanceProfile->getUser()->getId());
                    $this->setResponseCode(MessageCode::CODE_VERIFY_PROFILE_SUCCESS);
                    return true;
                }
            }
        }

        $this->setResponseCode(MessageCode::CODE_VERIFY_PROFILE_FAILED);
        return false;
    }
    
    protected function _getUserCreditStatus(RemittanceCompanyUser $remittanceProfile)
    {        
        $usercreditGetter = $this->_getUserCreditService();
        $remittanceCompany = $remittanceProfile->getRemittanceCompany();
        $remarks = NULL;
        $approvedBy = $this->getUpdatedBy();
        $usercreditGetter->setServiceProviderId($remittanceCompany->getServiceProviderId());
        
        //get statuses
        $watchListStatus = $usercreditGetter->getWatchlistStatus($remittanceProfile->getUser()->getId());
        $acuityStatus = $usercreditGetter->getWorldCheckStatus($remittanceProfile->getUser()->getId());
        if( $remittanceProfile->getRemittanceCompany()->getRequiredAcuityCheck() )
        {                            
            if( $acuityStatus instanceof WorldCheck )
            {
                $last_updated = 0;
                $remarks = $acuityStatus->getRemark();
                $approvedBy = $acuityStatus->getApprovedRejectedBy();
                
                if( !$acuityStatus->getUpdatedAt()->isNull() )
                    $last_updated = $acuityStatus->getUpdatedAt()->getUnix();
                else
                    $last_updated = $acuityStatus->getCreatedAt()->getUnix();
                                    
                if( $acuityStatus->getStatus() == WorldCheckStatus::PASS )
                    $result = array('pass', $last_updated);
                elseif( $acuityStatus->getStatus() == WorldCheckStatus::PENDING )
                    $result = array('pending', $last_updated);
                elseif( $acuityStatus->getStatus() == WorldCheckStatus::FAIL )
                    $result = array('fail', $last_updated);
            }
            else
                $result = array('pending', 0);   //consider as pending if can't get            
        }
        else
        {
            if( isset($watchListStatus->watchlist->status) )
            {
                $last_updated = 0;
                if( isset($watchListStatus->watchlist->last_updated_at) )
                    $last_updated = IappsDateTime::fromString($watchListStatus->watchlist->last_updated_at)->getUnix();                    
                
                if( $watchListStatus->watchlist->status == 'pass' )
                    $result = array('pass', $last_updated);
                elseif( $watchListStatus->watchlist->status == 'pending' )
                    $result = array('pending', $last_updated);
                elseif( $watchListStatus->watchlist->status == 'fail' )
                    $result = array('fail', $last_updated);
            }
            else
                $result = array('pending', 0);
        }
        
        $result[] = $remarks;
        $result[] = $approvedBy;
        return $result;
    }    
    
    public function getByCustomerID($customerID)
    {
        if( $remcoUser = $this->getRepository()->findByCustomerID($customerID) )
        {
            $remcoServ = RemittanceCompanyServiceFactory::build();
            if( $remco = $remcoServ->getById($remcoUser->getRemittanceCompany()->getId()) )
            {
                $remcoUser->setRemittanceCompany($remco);
                return $remcoUser;
            }
        }
        
        return false;
    }

    public function getByCompanyAndUser(RemittanceCompany $company, $user_profile_id)
    {
        $filter = new RemittanceCompanyUser();
        $filter->getUser()->setId($user_profile_id);
        $filter->getRemittanceCompany()->setId($company->getId());

        if( $info = $this->getRepository()->findByFilter($filter) )
        {
            $info->result->rewind();
            if( $result = $info->result->current() AND
                $result instanceof RemittanceCompanyUser )
            {
                $result->setRemittanceCompany($company);
                return $result;
            }
        }

        $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_PROFILE_FAILED);
        return false;
    }
    
    public function getByCompanyAndUsers(RemittanceCompany $company, array $user_profile_ids)
    {
        $filters = new RemittanceCompanyUserCollection();
        foreach( $user_profile_ids AS $user_profile_id)
        {
            $filter = new RemittanceCompanyUser();
            $filter->getUser()->setId($user_profile_id);
            $filter->getRemittanceCompany()->setId($company->getId());
            
            $filters->addData($filter);
        }
        
        if( $info = $this->getRepository()->findByFilters($filters) )
        {
            foreach($info->result AS $result)
            {
                $result->setRemittanceCompany($company);
            }
            
            return $info->result;            
        }

        $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_PROFILE_FAILED);
        return false;
    }

    public function listByUser($user_profile_id, $page = 1, $limit = MAX_VALUE, $isArray = true)
    {
        $accServ = $this->_getAccountService();
        if( $user = $accServ->getUser(null, $user_profile_id) )
        {
            $filter = new RemittanceCompanyUser();
            $filter->getUser()->setId($user_profile_id);

            if( $info = $this->getRepository()->findByFilter($filter) )
            {
                $remUserList = $info->getResult();
                //get company
                $companyServ = RemittanceCompanyServiceFactory::build();
                if( $companyList = $companyServ->getList(1, MAX_VALUE, false) )
                    $remUserList->joinRemittanceCompany($companyList);

                foreach($remUserList AS $remUser)
                {
                    $remUser->setUser($user);
                }

                $remUserList = $remUserList->pagination($limit, $page)->getResult();
                $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_PROFILE_SUCCESS);
                if( $isArray )
                {
                    $result = array();

                    foreach($remUserList AS $remUser)
                    {
                        $temp = $remUser->getSelectedField(array('id', 'customerID', 'user_status', 'completed_at', 'completed_by', 'verified_at', 'verified_by'));
                        $temp['remittance_company'] = $remUser->getRemittanceCompany()->getSelectedField(array('service_provider_id','uen', 'mas_license_no'));
                        $temp['remittance_company']['name'] = $remUser->getRemittanceCompany()->getCompanyInfo()->getName();
                        $temp['remittance_company']['logo'] = $remUser->getRemittanceCompany()->getCompanyInfo()->getProfileImageUrl();

                        $result[] = $temp;
                    }

                    return $result;
                }
                else
                    return $remUserList;
            }
        }

        $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_PROFILE_FAILED);
        return false;
    }

    public function listByRemittanceCompany(RemittanceCompany $company, RemittanceCompanyUserCollection $filters)
    {
        if( count($filters) <= 0 )
        {
            $filter = new RemittanceCompanyUser();
            $filters->addData($filter); //to make sure at least one filter
        }

        $filters->setRemittanceCompany($company);

        if( $info = $this->getRepository()->findByFilters($filters) )
        {
            $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_PROFILE_SUCCESS);
            return $info;
        }

        $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_PROFILE_FAILED);
        return false;
    }

    public function updateProfile(User $user, RemittanceCompany $remittanceCompany, $third_party_customerID = null){
        if( $remittanceProfile = $this->getByCompanyAndUser($remittanceCompany, $user->getId()) )
        {
            if( $remittanceProfile instanceof RemittanceCompanyUser )
            {
                $remittanceProfile->setUser($user);
                $remittanceProfile->setThirdPartyCustomerID($third_party_customerID);

                $this->_extractUserStatus($remittanceProfile);

                //update
                $remittanceProfile->setUpdatedBy($this->getUpdatedBy());
                if( $this->getRepository()->update($remittanceProfile) )
                {
                    return $remittanceProfile;
                }
            }
        }

        return false;
    }

    protected function _generateCustomerID(RemittanceCompanyUser $user)
    {
        if( $countryCode = $user->getUser()->getHostCountryCode() AND            $remcoCode = $user->getRemittanceCompany()->getCompanyCode() )
        {
            if( $countryInfo = $this->_getCountryInfo($countryCode) )
            {
                //get increment id
                $inc_serv = IncrementIDServiceFactory::build();
                $inc_serv->setNoOfDigit(7);
                //continous mode
                if( $id = $inc_serv->getRawIncrementID( $remcoCode . $countryInfo->getCode() . IncrementIDAttribute::CUSTOMER_ID, true) )
                {
                    return $user->generateCustomerID($countryInfo->getCountryNo(), $id);
                }
            }
        }

        return false;
    }

    protected function _getCountryInfo($country_code)
    {
        $country_serv = CountryServiceFactory::build();
        return $country_serv->getCountryInfo($country_code);
    }

    protected function _extractUserStatus(RemittanceCompanyUser $user)
    {
        $sys_serv = SystemCodeServiceFactory::build();
        if( !$userstatus = $sys_serv->getByCode($user->getUserStatus()->getCode(), RemittanceCompanyUserStatus::getSystemGroupCode()) )
        {
            $this->setResponseCode(MessageCode::CODE_INVALID_SYSTEM_CODE);
            return false;
        }

        $user->setUserStatus($userstatus);
        return true;
    }
}