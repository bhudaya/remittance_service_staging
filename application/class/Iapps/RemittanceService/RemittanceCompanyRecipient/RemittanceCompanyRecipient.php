<?php

namespace Iapps\RemittanceService\RemittanceCompanyRecipient;

use Iapps\Common\Core\IappsBaseEntity;
use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\Helper\GuidGenerator;
use Iapps\Common\Microservice\AccountService\User;
use Iapps\RemittanceService\Recipient\Recipient;
use Iapps\Common\SystemCode\SystemCode;
use Iapps\RemittanceService\RemittanceCompany\RemittanceCompany;
use Iapps\RemittanceService\Attribute\AttributeCode;

class RemittanceCompanyRecipient extends IappsBaseEntity{

    protected $country_code;
    protected $remittance_company;
    protected $recipient;
    protected $recipient_status;
    protected $face_to_face_verified_by;
    protected $face_to_face_verified_by_name;
    protected $face_to_face_verified_at;

    function __construct()
    {
        parent::__construct();

        $this->face_to_face_verified_at = new IappsDateTime();
        $this->recipient = new Recipient();
        $this->recipient_status = new SystemCode();
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
    
    /**
     * 
     * @return RemittanceCompany
     */    
    public function getRemittanceCompany()
    {
        return $this->remittance_company;
    }

    public function setRecipient(Recipient $recipient)
    {
        $this->recipient = $recipient;
        return $this;
    }

    /**
     * 
     * @return Recipient
     */
    public function getRecipient()
    {
        return $this->recipient;
    }

    public function setRecipientStatus(SystemCode $recipient_status)
    {
        $this->recipient_status = $recipient_status;
        return $this;
    }

    /**
     * 
     * @return SystemCode
     */
    public function getRecipientStatus()
    {
        return $this->recipient_status;
    }

    public function setFaceToFaceVerifiedBy($verified_by)
    {
        $this->face_to_face_verified_by = $verified_by;
        return $this;
    }

    public function getFaceToFaceVerifiedBy()
    {
        return $this->face_to_face_verified_by;
    }

    public function setFaceToFaceVerifiedByName($verified_by_name)
    {
        $this->face_to_face_verified_by_name = $verified_by_name;
        return $this;
    }

    public function getFaceToFaceVerifiedByName()
    {
        return $this->face_to_face_verified_by_name;
    }

    public function setFaceToFaceVerifiedAt(IappsDateTime $verified_at)
    {
        $this->face_to_face_verified_at = $verified_at;
        return $this;
    }

    /**
     * 
     * @return IappsDateTime
     */
    public function getFaceToFaceVerifiedAt()
    {
        return $this->face_to_face_verified_at;
    }


    public function jsonSerialize()
    {
        $json = parent::jsonSerialize();

        $json['country_code'] = $this->getCountryCode();
        $json['remittance_company_id'] = $this->getRemittanceCompany()->getId();
        $json['recipient'] = $this->getRecipient()->getId();
        $json['recipient_status'] = $this->getRecipientStatus()->getCode();
        $json['face_to_face_verified_by'] = $this->getFaceToFaceVerifiedBy();
        $json['face_to_face_verified_at'] = $this->getFaceToFaceVerifiedAt()->getString();

        return $json;
    }

    public function faceToFaceVerify(User $agent)
    {
        $this->getRecipientStatus()->setCode(RemittanceCompanyRecipientStatus::VERIFIED);
        $this->setFaceToFaceVerifiedAt(IappsDateTime::now());
        $this->setFaceToFaceVerifiedBy($agent->getId());

        return $this;
    }

    public function resetProfile()
    {
        $this->getRecipientStatus()->setCode(RemittanceCompanyRecipientStatus::PENDING_VERIFY);
        $this->setFaceToFaceVerifiedAt(new IappsDateTime());
        $this->setFaceToFaceVerifiedBy(NULL);
        return $this;
    }

    public static function create(RemittanceCompany $company, Recipient $recipient)
    {
    	$countryCode = $recipient->getAttributes()->hasAttribute(AttributeCode::RESIDENTIAL_COUNTRY);
	
        $remittanceRecipient = new RemittanceCompanyRecipient();
        $remittanceRecipient->setId(GuidGenerator::generate());
        $remittanceRecipient->setCountryCode($countryCode);
        $remittanceRecipient->setRecipient($recipient);
        $remittanceRecipient->getRecipientStatus()->setCode(RemittanceCompanyRecipientStatus::PENDING_VERIFY);
        $remittanceRecipient->setRemittanceCompany($company);        

        return $remittanceRecipient;
    }


    public function isRecipientEdited()
    {
        $last_edited = !$this->getRecipient()->getUpdatedAt()->isNull() ? $this->getRecipient()->getLastEditedAt()->getUnix() : 0;
        $last_verified = !$this->getFaceToFaceVerifiedAt()->isNull() ? $this->getFaceToFaceVerifiedAt()->getUnix() : 0;

        //if edit after verified, this is considered edited
        return ($last_edited > $last_verified);
    }

    public function isFaceToFaceVerified()
    {
        if(!$this->getFaceToFaceVerifiedAt()->isNull() ) {
            //make sure its not edited
            return !$this->isRecipientEdited();
        } else {
            return false;
        }

    }

    public function belongsToRecipient(Recipient $recipient)
    {
        return ($this->getRecipient()->getId() == $recipient->getId());
    }
}