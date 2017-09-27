<?php

namespace Iapps\RemittanceService\Recipient;

use Iapps\Common\Core\EncryptedField;
use Iapps\Common\Core\IappsBaseEntity;
use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\Core\S3FileUrl;
use Iapps\Common\Microservice\AccountService\User;
use Iapps\RemittanceService\Attribute\AttributeCode;
use Iapps\RemittanceService\Common\NameMatcher;
use Iapps\RemittanceService\Common\RecipientPhotoImageS3UploaderFactory;
use Iapps\RemittanceService\RecipientCollectionInfo\RecipientCollectionInfoCollection;
use Iapps\RemittanceService\RemittanceCompany\RemittanceCompany;
use Iapps\RemittanceService\RemittanceCompanyRecipient\RemittanceCompanyRecipientCollection;
use Iapps\RemittanceService\ValueObject\EncryptedFieldFactory;
use Iapps\RemittanceService\Attribute\RecipientAttributeCollection;
use Iapps\RemittanceService\Common\Rijndael256EncryptorFactory;
use Iapps\Common\Helper\EncryptedS3Url;

class Recipient extends IappsBaseEntity{
    
    protected $user;
    protected $recipient_type;
    protected $recipient_user;
    protected $recipient_alias;
    protected $is_active;
    protected $recipient_dialing_code;
    protected $recipient_mobile_number;
    protected $photo_image_url;
    protected $attributes;
    protected $is_international = NULL;
    protected $last_sent_at;
    protected $last_edited_at;

    protected $collection_infos;
    protected $recipient_residential_country;
    protected $recipient_residential_province;
    protected $recipient_residential_city;

    protected $remittance_company_recipients;

    function __construct()
    {
        parent::__construct();

        $this->recipient_dialing_code = EncryptedFieldFactory::build();
        $this->recipient_mobile_number = EncryptedFieldFactory::build();
        $this->attributes = new RecipientAttributeCollection();
        $this->recipient_user = new User(); //if recipient is a user
        $this->collection_infos = new RecipientCollectionInfoCollection();
        $this->last_sent_at = new IappsDateTime();
        $this->last_edited_at = new IappsDateTime();
        $this->remittance_company_recipients = new RemittanceCompanyRecipientCollection();
        $this->user = new User();
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

    public function setUserProfileId($user_profile_id)
    {
        $this->getUser()->setId($user_profile_id);
        return $this;
    }

    public function getUserProfileId()
    {
        return $this->getUser()->getId();
    }

    public function setRecipientType($recipient_type)
    {
        $this->recipient_type = $recipient_type;
        return $this;
    }

    public function getRecipientType()
    {
        return $this->recipient_type;
    }

    public function setIsInternational($isInternational)
    {
        $this->is_international = $isInternational;
        return $this;
    }

    public function getIsInternational()
    {
        return $this->is_international;
    }

    public function setRecipientUserProfileId($recipient_user_profile_id)
    {
        $this->recipient_user->setId($recipient_user_profile_id);
        return $this;
    }

    public function getRecipientUserProfileId()
    {
        return $this->recipient_user->getId();
    }

    public function setRecipientUser(User $user)
    {
        $this->recipient_user = $user;
        return $this;
    }

    public function getRecipientUser()
    {
        return $this->recipient_user;
    }

    public function setRecipientAlias($recipient_alias)
    {
        $this->recipient_alias = $recipient_alias;
        return $this;
    }

    public function getRecipientAlias()
    {
        return $this->recipient_alias;
    }

    public function setIsActive($is_active)
    {
        $this->is_active = $is_active;
        return $this;
    }

    public function getIsActive()
    {
        return $this->is_active;
    }

    public function setRecipientDialingCode(EncryptedField $recipient_dialing_code)
    {
        $this->recipient_dialing_code = $recipient_dialing_code;
        return $this;
    }

    /**
     * 
     * @return EncryptedField
     */
    public function getRecipientDialingCode()
    {
        return $this->recipient_dialing_code;
    }

    public function setRecipientMobileNumber(EncryptedField $recipient_mobile_number)
    {
        $this->recipient_mobile_number = $recipient_mobile_number;
        return $this;
    }

    /**
     * 
     * @return EncryptedField
     */
    public function getRecipientMobileNumber()
    {
        return $this->recipient_mobile_number;
    }

    public function setPhotoImageUrl($url)
    {
        if( $this->photo_image_url instanceof S3FileUrl)
        {
            $this->photo_image_url->setUrl($url);
            return $this;
        }
        else
        {
            $s3 = RecipientPhotoImageS3UploaderFactory::build($url);
            $encryptor = Rijndael256EncryptorFactory::build();
            $this->photo_image_url = new EncryptedS3Url($s3, $encryptor);
            $this->photo_image_url->setUrl($url);
            return $this;
        }
    }

    public function getPhotoImageUrl()
    {
        return $this->photo_image_url;
    }

    public function activate()
    {
        $this->setIsActive(1);
        return $this;
    }

    public function setAttributes(RecipientAttributeCollection $collection)
    {
        $this->attributes = $collection;
        return $this;
    }

    /**
     * 
     * @return RecipientAttributeCollection
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    public function setCollectionInfos(RecipientCollectionInfoCollection $collection)
    {
        $this->collection_infos = $collection;
        return $this;
    }

    public function getCollectionInfos()
    {
        return $this->collection_infos;
    }

    public function setLastSentAt(IappsDateTime $dt)
    {
        $this->last_sent_at = $dt;
        return $this;
    }

    /**
     * 
     * @return IappsDateTime
     */
    public function getLastSentAt()
    {
        return $this->last_sent_at;
    }

    public function setLastEditedAt(IappsDateTime $dt)
    {
        $this->last_edited_at = $dt;
        return $this;
    }

    /**
     * 
     * @return IappsDateTime
     */
    public function getLastEditedAt()
    {
        return $this->last_edited_at;
    }

    public function setRecipientResidentialCountry($recipient_residential_country)
    {
        $this->recipient_residential_country = $recipient_residential_country;
        return $this;
    }

    public function getRecipientResidentialCountry()
    {
        return $this->recipient_residential_country;
    }

    public function setRecipientResidentialProvince($recipient_residential_province)
    {
        $this->recipient_residential_province = $recipient_residential_province;
        return $this;
    }

    public function getRecipientResidentialProvince()
    {
        return $this->recipient_residential_province;
    }

    public function setRecipientResidentialCity($recipient_residential_city)
    {
        $this->recipient_residential_city = $recipient_residential_city;
        return $this;
    }

    public function getRecipientResidentialCity()
    {
        return $this->recipient_residential_city;
    }

    public function setRemittanceCompanyRecipients(RemittanceCompanyRecipientCollection $remittanceCompanyRecipientColl)
    {
        $this->remittance_company_recipients = $remittanceCompanyRecipientColl;
        return $this;
    }

    public function getRemittanceCompanyRecipients()
    {
        return $this->remittance_company_recipients;
    }

    public function jsonSerialize()
    {
        $json = parent::jsonSerialize();

        $json['user_profile_id'] = $this->getUserProfileId();
        $json['recipient_type'] = $this->getRecipientType();
        $json['recipient_user_profile_id'] = $this->getRecipientUserProfileId();
        $json['recipient_dialing_code'] = $this->getRecipientDialingCode()->getValue();
        $json['recipient_mobile_number'] = $this->getRecipientMobileNumber()->getValue();
        $json['recipient_alias'] = $this->getRecipientAlias();
        $json['photo_image_url'] = $this->getPhotoImageUrl();
        $json['is_active'] = (bool)$this->getIsActive();
        $json['is_international'] = (bool)$this->getIsInternational();
        $json['attributes'] = $this->getAttributes();
        $json['recipient_residential_country'] = $this->getRecipientResidentialCountry();
        $json['recipient_residential_province'] = $this->getRecipientResidentialProvince();
        $json['recipient_residential_city'] = $this->getRecipientResidentialCity();
        $json['last_sent_at'] = $this->getLastSentAt()->getString();
        $json['last_edited_at'] = $this->getLastEditedAt()->getString();

        return $json;
    }

    public function isSlideUser()
    {
        return ($this->getRecipientUser()->getId() != NULL);
    }

    public function isMobileNumber($dialing_code, $mobile_number)
    {
        return( $this->getRecipientDialingCode()->getValue() == $dialing_code AND
                $this->getRecipientMobileNumber()->getValue() == $mobile_number );
    }

    public function hasTaggedToUser()
    {
        return ($this->getRecipientUserProfileId() != NULL);
    }

    public function belongsTo(User $user)
    {
        return $this->getUserProfileId() == $user->getId();
    }

    public function isMatched(User $user)
    {
        if( $this->isMobileNumber($user->getMobileNumberObj()->getDialingCode(), $user->getMobileNumberObj()->getMobileNumber()))
        {
            if( $this->getIsInternational() )
            {
                if( $this->getRecipientType() == RecipientType::NON_MEMBER )
                {
                    if( $fullName = $this->getAttributes()->hasAttribute(AttributeCode::FULL_NAME) )
                    {
                        if( NameMatcher::execute($user->getFullName(), $fullName ) )
                        {
                            $this->setRecipientUser($user);
                            return $this;
                        }
                    }
                }
            }
            else
            {
                $this->setRecipientUser($user);
                return $this;
            }
        }

        return false;
    }
}