<?php

namespace Iapps\RemittanceService\RemittanceCompanyUser;

use Iapps\Common\Core\IappsBaseEntity;
use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\Helper\GuidGenerator;
use Iapps\Common\Microservice\AccountService\User;
use Iapps\Common\Microservice\AccountService\UserStatus;
use Iapps\Common\SystemCode\SystemCode;
use Iapps\RemittanceService\RemittanceCompany\RemittanceCompany;

class RemittanceCompanyUser extends IappsBaseEntity{

    protected $country_code;
    protected $remittance_company;
    protected $user;
    protected $customerID;
    protected $user_status;
    protected $completed_by;
    protected $completed_by_name;
    protected $completed_at;
    protected $verified_by;
    protected $verified_by_name;
    protected $verified_at;
    protected $rejected_by;
    protected $rejected_by_name;
    protected $rejected_at;
    protected $verified_rejected_remark;
    protected $third_party_customerID;

    function __construct()
    {
        parent::__construct();

        $this->completed_at = new IappsDateTime();
        $this->verified_at = new IappsDateTime();
        $this->rejected_at = new IappsDateTime();
        $this->user_status = new SystemCode();
        $this->user = new User();
        $this->remittance_company = new RemittanceCompany();
    }

    public function setCountryCode($country_code)
    {
        $this->country_code = $country_code;
        return $this;
    }

    public function getCountryCode()
    {
        return $this->country_code;
    }

    public function setRemittanceCompany(RemittanceCompany $remittanceCompany)
    {
        $this->remittance_company = $remittanceCompany;
        return $this;
    }

    public function getRemittanceCompany()
    {
        return $this->remittance_company;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
        return $this;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setCustomerID($customerID)
    {
        $this->customerID = $customerID;
        return $this;
    }

    public function getCustomerID()
    {
        return $this->customerID;
    }

    public function setUserStatus(SystemCode $user_status)
    {
        $this->user_status = $user_status;
        return $this;
    }

    public function getUserStatus()
    {
        return $this->user_status;
    }

    public function setCompletedBy($completed_by)
    {
        $this->completed_by = $completed_by;
        return $this;
    }

    public function getCompletedBy()
    {
        return $this->completed_by;
    }

    public function setCompletedByName($completed_by_name)
    {
        $this->completed_by_name = $completed_by_name;
        return $this;
    }

    public function getCompletedByName()
    {
        return $this->completed_by_name;
    }

    public function setCompletedAt(IappsDateTime $completed_at)
    {
        $this->completed_at = $completed_at;
        return $this;
    }

    public function getCompletedAt()
    {
        return $this->completed_at;
    }

    public function setVerifiedBy($verified_by)
    {
        $this->verified_by = $verified_by;
        return $this;
    }

    public function getVerifiedBy()
    {
        return $this->verified_by;
    }

    public function setVerifiedByName($verified_by_name)
    {
        $this->verified_by_name = $verified_by_name;
        return $this;
    }

    public function getVerifiedByName()
    {
        return $this->verified_by_name;
    }

    public function setVerifiedAt(IappsDateTime $verified_at)
    {
        $this->verified_at = $verified_at;
        return $this;
    }

    public function getVerifiedAt()
    {
        return $this->verified_at;
    }

    public function setRejectedAt(IappsDateTime $rejected_at)
    {
        $this->rejected_at = $rejected_at;
        return $this;
    }

    public function getRejectedAt()
    {
        return $this->rejected_at;
    }

    public function setRejectedBy($rejected_by)
    {
        $this->rejected_by = $rejected_by;
        return $this;
    }

    public function getRejectedBy()
    {
        return $this->rejected_by;
    }

    public function setRejectedByName($rejected_by_name)
    {
        $this->rejected_by_name = $rejected_by_name;
        return $this;
    }

    public function getRejectedByName()
    {
        return $this->rejected_by_name;
    }

    public function setVerifiedRejectedRemark($remark)
    {
        $this->verified_rejected_remark = $remark;
        return $this;
    }

    public function getVerifiedRejectedRemark()
    {
        return $this->verified_rejected_remark;
    }

    public function setThirdPartyCustomerID($third_party_customerID)
    {
        $this->third_party_customerID = $third_party_customerID;
        return $this;
    }

    public function getThirdPartyCustomerID()
    {
        return $this->third_party_customerID;
    }


    public function jsonSerialize()
    {
        $json = parent::jsonSerialize();

        $json['country_code'] = $this->getCountryCode();
        $json['remittance_company_id'] = $this->getRemittanceCompany()->getId();
        $json['user_profile_id'] = $this->getUser()->getId();
        $json['customerID'] = $this->getCustomerID();
        $json['third_party_customerID'] = $this->getThirdPartyCustomerID();
        $json['user_status'] = $this->getUserStatus()->getCode();
        $json['completed_by'] = $this->getCompletedBy();
        $json['completed_at'] = $this->getCompletedAt()->getString();
        $json['verified_by'] = $this->getVerifiedBy();
        $json['verified_at'] = $this->getVerifiedAt()->getString();
        $json['rejected_by'] = $this->getRejectedBy();
        $json['rejected_at'] = $this->getRejectedAt()->getString();
        $json['verified_rejected_remark'] = $this->getVerifiedRejectedRemark();

        return $json;
    }

    public function complete(User $agent)
    {
        $this->getUserStatus()->setCode(RemittanceCompanyUserStatus::COMPLETED);
        $this->setCompletedAt(IappsDateTime::now());
        $this->setCompletedBy($agent->getId());
        $this->setVerifiedAt(new IappsDateTime());
        $this->setVerifiedBy(NULL);
        $this->setRejectedAt(new IappsDateTime());
        $this->setRejectedBy(NULL);
        $this->setVerifiedRejectedRemark(NULL);

        return $this;
    }

    public function verify(User $admin, $pass, $remark = NULL)
    {
        if( $pass )
            $status = RemittanceCompanyUserStatus::VERIFIED;
        else
            $status = RemittanceCompanyUserStatus::FAILED_VERIFY;

        $this->getUserStatus()->setCode($status);
        $this->setVerifiedRejectedRemark($remark);

        if( $pass )
        {
            $this->setVerifiedAt(IappsDateTime::now());
            $this->setVerifiedBy($admin->getId());
            $this->setRejectedAt((new IappsDateTime()));
            $this->setRejectedBy(NULL);
        }
        else
        {
            $this->setRejectedAt(IappsDateTime::now());
            $this->setRejectedBy($admin->getId());
			$this->setVerifiedAt((new IappsDateTime()));
            $this->setVerifiedBy(NULL);
        }

        return $this;
    }
    
    public function changeStatus($newStatus, $remark, $changedBy)
    {
        if( $newStatus == $this->getUserStatus()->getCode() )
            return false;   //nothing change
        
        $admin = new User();
        $admin->setId($changedBy);
        
        if( $newStatus == RemittanceCompanyUserStatus::COMPLETED )
        {
            $this->getUserStatus()->setCode($newStatus);
            $this->setVerifiedAt(( new IappsDateTime() ));
            $this->setVerifiedBy(NULL);
        }
        elseif( $newStatus == RemittanceCompanyUserStatus::FAILED_VERIFY )
        {            
            $this->verify($admin, false, $remark);                        
        }
        elseif( $newStatus == RemittanceCompanyUserStatus::VERIFIED )
        {
            $this->verify($admin, true, $remark);                        
        }
        else
            return false;
        
        return $this;
    }

    public static function create(RemittanceCompany $company, User $user)
    {
        $remittanceUser = new RemittanceCompanyUser();
        $remittanceUser->setId(GuidGenerator::generate());
        $remittanceUser->setCountryCode($user->getHostCountryCode());
        $remittanceUser->setUser($user);
        $remittanceUser->setRemittanceCompany($company);
        if( $user->getUserStatus()->getCode() !== UserStatus::UNVERIFIED )
            $remittanceUser->resetStatusToReadyForKYC();
        else //this should not happen
            $remittanceUser->getUserStatus()->setCode(RemittanceCompanyUserStatus::FAILED_VERIFY);

        return $remittanceUser;
    }

    public function generateCustomerID($countryNo, $runningNo)
    {
        $country_prefix = '1' . str_pad($countryNo, 2, '0', STR_PAD_LEFT);
        $customerID = $this->getRemittanceCompany()->getCompanyCode() . $country_prefix . $runningNo;
        $this->setCustomerID($customerID);
        return $this;
    }

    public function isProfileEdited()
    {
        $last_edited = !$this->getUser()->getLastEditedAt()->isNull() ? $this->getUser()->getLastEditedAt()->getUnix() : 0;
        $last_completed = !$this->getCompletedAt()->isNull() ? $this->getCompletedAt()->getUnix() : 0;

        //if edit after complete, this is considered edited
        return ($last_edited > $last_completed);
    }

    public function isCompleted()
    {
        if( $this->getUserStatus()->getCode() == RemittanceCompanyUserStatus::COMPLETED )
        {
            //make sure its not edited
            return !$this->isProfileEdited();
        }

        return false;
    }

    public function isVerified()
    {
        if( $this->getUserStatus()->getCode() == RemittanceCompanyUserStatus::VERIFIED )
        {
            //make sure its not edited
            return !$this->isProfileEdited();
        }

        return false;
    }

    public function isReadyForKYC()
    {
        return ( $this->getUserStatus()->getCode() == RemittanceCompanyUserStatus::READY_FOR_KYC );
    }

    public function isFailedVerified()
    {
        return ( $this->getUserStatus()->getCode() == RemittanceCompanyUserStatus::FAILED_VERIFY );
    }

    public function isEligibleToComplete()
    {
        return
            (
            (!$this->isCompleted() AND
             !$this->isVerified() AND
             !$this->isFailedVerified())
            OR
            $this->isReadyForKYC()
            );
    }

    public function resetStatusToReadyForKYC()
    {
        $this->getUserStatus()->setCode(RemittanceCompanyUserStatus::READY_FOR_KYC);
        $this->setCompletedAt(new IappsDateTime());
        $this->setCompletedBy(NULL);
        $this->setVerifiedAt(new IappsDateTime());
        $this->setVerifiedBy(NULL);
        $this->setRejectedAt(new IappsDateTime());
        $this->setRejectedBy(NULL);
        $this->setVerifiedRejectedRemark(NULL);
        return $this;
    }
}