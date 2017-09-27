<?php

namespace Iapps\RemittanceService\Recipient;

use Iapps\Common\AuditLog\AuditLogAction;
use Iapps\Common\Core\IappsBaseService;
use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\Helper\GuidGenerator;
use Iapps\Common\Microservice\AccountService\AccountService;
use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\Common\Microservice\AccountService\MobileNumberObj;
use Iapps\Common\Microservice\CountryService\CountryServiceFactory;
use Iapps\Common\Microservice\PaymentService\SystemPaymentServiceFactory;
use Iapps\RemittanceService\Attribute\RecipientAttributeCollection;
use Iapps\RemittanceService\Common\CacheKey;
use Iapps\RemittanceService\Common\MessageCode;
use Iapps\RemittanceService\Common\NameMatcher;
use Iapps\RemittanceService\RemittanceCompanyUser\RemittanceCompanyUserService;
use Iapps\RemittanceService\ValueObject\EncryptedFieldFactory;
use Iapps\RemittanceService\Attribute\RecipientAttributeServiceFactory;
use Iapps\RemittanceService\RemittanceRecord\RemittanceRecordServiceFactory;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransactionServiceFactory;
use Iapps\RemittanceService\Attribute\AttributeCode;
use Iapps\RemittanceService\Attribute\AttributeValue;
use Iapps\RemittanceService\Attribute\AttributeValueServiceFactory;
use Iapps\RemittanceService\RecipientCollectionInfo\RecipientCollectionInfoServiceFactory;
use Iapps\RemittanceService\RecipientCollectionInfo\RecipientCollectionInfo;
use Iapps\RemittanceService\WorldCheck\WorldCheckServiceFactory;
use Iapps\RemittanceService\WorldCheck\WorldCheckStatus;
use Iapps\RemittanceService\RemittanceCompanyRecipient\RemittanceCompanyRecipientServiceFactory;

class RecipientService extends IappsBaseService{
    
    private $_serviceRecipientAttribute;
    
    private function _getServiceRecipientAttribute()
    {
        if(!$this->_serviceRecipientAttribute)
        {
            $this->_serviceRecipientAttribute = RecipientAttributeServiceFactory::build();
        }
        $this->_serviceRecipientAttribute->setUpdatedBy($this->getUpdatedBy());
        $this->_serviceRecipientAttribute->setIpAddress($this->getIpAddress());
        return $this->_serviceRecipientAttribute;
    }

    public function getRecipient($recipient_id)
    {
        return $this->getRepository()->findById($recipient_id);
    }
    
    public function getByRecipientUserProfileId($recipient_user_profile_id)
    {
        if($collection = $this->getRepository()->findByRecipientUserProfileId($recipient_user_profile_id)) 
        {
            $this->setResponseCode(MessageCode::CODE_RECIPIENT_LIST_FOUND);
            
            $recipientAttrService = RecipientAttributeServiceFactory::build();
            if( $attributeInfo = $recipientAttrService->getByRecipientIds($collection->getResult()->getIds()) )
                $collection->getResult()->joinRecipientAttribute($attributeInfo);
            return $collection;
        }

        $this->setResponseCode(MessageCode::CODE_RECIPIENT_LIST_NOT_FOUND);
        return false;
    }

    public function findByMobileNumber(MobileNumberObj $mobileNumber)
    {
        $filter = new Recipient();
        $filter->getRecipientDialingCode()->setValue($mobileNumber->getDialingCode());
        $filter->getRecipientMobileNumber()->setValue($mobileNumber->getMobileNumber());
        return $this->getRepository()->findByParam($filter, NULL, MAX_VALUE, 1);
    }

    public function updateRecipient(Recipient $recipient)
    {
        $recipient->setUpdatedBy($this->getUpdatedBy());
        if( $this->getRepository()->update($recipient) )
        {
            $this->_removeCached($recipient);
            return $recipient;
        }

        return false;
    }

    public function getRecipientDetail($recipient_id, $isArray = true)
    {
        if ($recipient = $this->getRecipient($recipient_id)) {

            if( $recipient instanceof Recipient )
            {
                if( $isArray )
                {
                    $result = $recipient->getSelectedField(array('id','recipient_user_profile_id','recipient_type', 'recipient_dialing_code', 'recipient_mobile_number', 'recipient_alias', 'photo_image_url', 'is_active', 'is_international'));

                    $result['id_number'] = NULL;
                    $result['id_type'] = NULL;
                    $result['full_name'] = NULL;
                    $result['nationality'] = NULL;
                    $result['relationship_to_sender'] = NULL;
                    $result['id_type_id'] = NULL;
                    $result['nationality_id'] = NULL;
                    $result['relationship_to_sender_id'] = NULL;
                    $result['remittance_purpose_id'] = NULL;
                    $result['remittance_purpose'] = NULL;
                    $result['income_source'] = NULL;
                    $result['income_source_id'] = NULL;
                    $result['residing_address'] = NULL;
                    $result['residing_country_code'] = NULL;
                    $result['residing_province_code'] = NULL;
                    $result['residing_city_code'] = NULL;
                    $result['residing_postal_code'] = NULL;
                    $result['bank_info'] = NULL;
                    $result['payment_code'] = NULL;
                }

                $attr_serv = RecipientAttributeServiceFactory::build();
                $attr_serv->setUpdatedBy($this->getUpdatedBy());
                $attr_serv->setIpAddress($this->getIpAddress());
                if( $arrtCollection = $attr_serv->getAllRecipientAttribute($recipient->getId()) )
                {
                    $recipient->setAttributes($arrtCollection);

                    if( $isArray )
                    {
                        foreach ($arrtCollection as $attr) {

                            if ($attr->getAttribute()->getCode() == AttributeCode::ID_NUMBER) {
                                $result['id_number'] = $attr->getValue();
                            }else if ($attr->getAttribute()->getCode() == AttributeCode::ID_TYPE) {
                                $result['id_type_id'] = $attr->getAttributeValueId();
                                $result['id_type'] = $attr->getValue();
                            }else if ($attr->getAttribute()->getCode() == AttributeCode::FULL_NAME) {
                                $result['full_name'] = $attr->getValue();
                            }else if ($attr->getAttribute()->getCode() == AttributeCode::NATIONALITY) {
                                $result['nationality_id'] = $attr->getAttributeValueId();
                                $result['nationality'] = $attr->getValue();
                            }else if ($attr->getAttribute()->getCode() == AttributeCode::RELATIONSHIP_TO_SENDER) {
                                $result['relationship_to_sender_id'] = $attr->getAttributeValueId();
                                $result['relationship_to_sender'] = $attr->getValue();
                            }else if ($attr->getAttribute()->getCode() == AttributeCode::SOURCE_OF_INCOME) {
                                $result['income_source_id'] = $attr->getAttributeValueId();
                                $result['income_source'] = $attr->getValue();
                            }else if ($attr->getAttribute()->getCode() == AttributeCode::PURPOSE_OF_REMITTANCE) {
                                $result['remittance_purpose_id'] = $attr->getAttributeValueId();
                                $result['remittance_purpose'] = $attr->getValue();
                            }else if ($attr->getAttribute()->getCode() == AttributeCode::RESIDENTIAL_ADDRESS) {
                                $result['residing_address'] = $attr->getValue();
                            }else if ($attr->getAttribute()->getCode() == AttributeCode::RESIDENTIAL_COUNTRY) {
                                $result['residing_country_code'] = $attr->getValue();
                            }else if ($attr->getAttribute()->getCode() == AttributeCode::RESIDENTIAL_PROVINCE) {
                                $result['residing_province_code'] = $attr->getValue();
                            }else if ($attr->getAttribute()->getCode() == AttributeCode::RESIDENTIAL_CITY) {
                                $result['residing_city_code'] = $attr->getValue();
                            }else if ($attr->getAttribute()->getCode() == AttributeCode::RESIDENTIAL_POST_CODE) {
                                $result['residing_postal_code'] = $attr->getValue();
                            }
                        }
                    }

                }

                $recipientCollectionServive = RecipientCollectionInfoServiceFactory::build();

                $infos = $recipientCollectionServive->getRepository()->findByRecipientId($recipient_id);

                if ($infos) {
                    $recipient->setCollectionInfos($infos->result);

                    if( $isArray )
                    {
                        $result['payment_code'] = $infos->result->current()->getPaymentCode();
                        $result['bank_info'] = json_decode($infos->result->current()->getOption()->getValue(),true);
                    }
                }

                $this->setResponseCode(MessageCode::CODE_RECIPIENT_FOUND);

                if( $isArray )
                {
                    $results[] = $result;
                    return $results;
                }
                else
                {
                    return $recipient;
                }
            }
        }

        $this->setResponseCode(MessageCode::CODE_RECIPIENT_NOT_FOUND);
        return false;
    }
    
    public function getRecipientDetailByIdArray(array $recipient_ids)
    {
        if( $collection = $this->getRecipientByParam(new Recipient(), $recipient_ids, 1000, 1) )
        {
            $rows = array();
            foreach ($collection->result as $eachRecipient) {
                
                $remitCoRecipientService = RemittanceCompanyRecipientServiceFactory::build();
                if($remitCoRecipientColl = $remitCoRecipientService->listByRecipient($eachRecipient->getId(),  1, MAX_VALUE, false)) {
                    $eachRecipient->setRemittanceCompanyRecipients($remitCoRecipientColl);
                }

                $result = $eachRecipient->getSelectedField(array('id', 'recipient_type', 'recipient_user_profile_id',
                    'recipient_dialing_code', 'recipient_mobile_number',
                    'recipient_alias', 'photo_image_url', 'is_active',
                    'recipient_residential_country','recipient_residential_province','recipient_residential_city',
                    'is_international', 'last_sent_at', 'last_edited_at', 'created_at', 'attributes'));

                $result['status'] = NULL;
                if($eachRecipient->getRemittanceCompanyRecipients()) {
                    $temps = array();
                    foreach ($eachRecipient->getRemittanceCompanyRecipients() AS $remRec) {
                        $temp = $remRec->getSelectedField(array('id', 'recipient_status', 'face_to_face_verified_at', 'face_to_face_verified_by'));
                        $temp['remittance_company'] = $remRec->getRemittanceCompany()->getSelectedField(array('service_provider_id', 'uen', 'mas_license_no'));
                        $temp['remittance_company']['name'] = $remRec->getRemittanceCompany()->getCompanyInfo()->getName();
                        $temp['remittance_company']['logo'] = $remRec->getRemittanceCompany()->getCompanyInfo()->getProfileImageUrl();

                        $temps[] = $temp;
                    }
                    $result['status'] = $temps;
                }

                $result['id_number'] = NULL;
                $result['id_type'] = NULL;
                $result['full_name'] = NULL;
                $result['nationality'] = NULL;
                $result['relationship_to_sender'] = NULL;
                $result['id_type_id'] = NULL;
                $result['nationality_id'] = NULL;
                $result['relationship_to_sender_id'] = NULL;
                $result['remittance_purpose_id'] = NULL;
                $result['remittance_purpose'] = NULL;
                $result['income_source'] = NULL;
                $result['income_source_id'] = NULL;
                $result['residing_address'] = NULL;
                $result['residing_country_code'] = NULL;
                $result['residing_province_code'] = NULL;
                $result['residing_city_code'] = NULL;
                $result['residing_postal_code'] = NULL;
                $result['bank_info'] = NULL;
                $result['payment_code'] = NULL;
                
                
                $attr_serv = $this->_getServiceRecipientAttribute();
                $attr_serv->setUpdatedBy($this->getUpdatedBy());
                $attr_serv->setIpAddress($this->getIpAddress());
                if( $arrtCollection = $attr_serv->getAllRecipientAttribute($eachRecipient->getId()) )
                {
                    $eachRecipient->setAttributes($arrtCollection);

                    foreach ($arrtCollection as $attr) {

                        if ($attr->getAttribute()->getCode() == AttributeCode::ID_NUMBER) {
                            $result['id_number'] = $attr->getValue();
                        }else if ($attr->getAttribute()->getCode() == AttributeCode::ID_TYPE) {
                            $result['id_type_id'] = $attr->getAttributeValueId();
                            $result['id_type'] = $attr->getValue();
                        }else if ($attr->getAttribute()->getCode() == AttributeCode::FULL_NAME) {
                            $result['full_name'] = $attr->getValue();
                        }else if ($attr->getAttribute()->getCode() == AttributeCode::NATIONALITY) {
                            $result['nationality_id'] = $attr->getAttributeValueId();
                            $result['nationality'] = $attr->getValue();
                        }else if ($attr->getAttribute()->getCode() == AttributeCode::RELATIONSHIP_TO_SENDER) {
                            $result['relationship_to_sender_id'] = $attr->getAttributeValueId();
                            $result['relationship_to_sender'] = $attr->getValue();
                        }else if ($attr->getAttribute()->getCode() == AttributeCode::SOURCE_OF_INCOME) {
                            $result['income_source_id'] = $attr->getAttributeValueId();
                            $result['income_source'] = $attr->getValue();
                        }else if ($attr->getAttribute()->getCode() == AttributeCode::PURPOSE_OF_REMITTANCE) {
                            $result['remittance_purpose_id'] = $attr->getAttributeValueId();
                            $result['remittance_purpose'] = $attr->getValue();
                        }else if ($attr->getAttribute()->getCode() == AttributeCode::RESIDENTIAL_ADDRESS) {
                            $result['residing_address'] = $attr->getValue();
                        }else if ($attr->getAttribute()->getCode() == AttributeCode::RESIDENTIAL_COUNTRY) {
                            $result['residing_country_code'] = $attr->getValue();
                        }else if ($attr->getAttribute()->getCode() == AttributeCode::RESIDENTIAL_PROVINCE) {
                            $result['residing_province_code'] = $attr->getValue();
                        }else if ($attr->getAttribute()->getCode() == AttributeCode::RESIDENTIAL_CITY) {
                            $result['residing_city_code'] = $attr->getValue();
                        }else if ($attr->getAttribute()->getCode() == AttributeCode::RESIDENTIAL_POST_CODE) {
                            $result['residing_postal_code'] = $attr->getValue();
                        }
                    }

                }// end of if get all recipient attributes
                
                $rows[] = $result;
            }// end of foreach recipients
            
            $collection->result = $rows;
            
            $this->setResponseCode(MessageCode::CODE_RECIPIENT_LIST_FOUND);
            return $collection;
        }
        
        $this->setResponseCode(MessageCode::CODE_RECIPIENT_LIST_NOT_FOUND);
        return false;
    }

    public function getRecipientDetailWithUserInfo($recipient_id)
    {
        if( $recipient = $this->getRecipientDetail($recipient_id, false) )
        {
            if( $recipient instanceof Recipient )
            {
                $accServ = AccountServiceFactory::build();

                $user_info = null;
                if( $recipient->getRecipientUser()->getId() )
                {
                    if( $user = $accServ->getUserProfile($recipient->getRecipientUser()->getId()) )
                    {
                        $user_info = $user->getSelectedField(array('accountID', 'user_status','email', 'verified_by', 'verified_by_name','full_name'));
                    }
                }

                $result = $recipient->jsonSerialize();
                $result['attributes'] = $recipient->getAttributes()->toList();
                if( $code = $recipient->getAttributes()->hasAttribute(AttributeCode::RESIDENTIAL_PROVINCE) )
                {
                    $countryService = CountryServiceFactory::build();
                    if($province = $countryService->getProvinceInfo($code) )
                        $result['attributes']['residential_province_name'] = $province->getName();
                    else
                        $result['attributes']['residential_province_name'] = null;
                }
                if( $code = $recipient->getAttributes()->hasAttribute(AttributeCode::RESIDENTIAL_CITY) )
                {
                    $countryService = CountryServiceFactory::build();
                    if( $city = $countryService->getCityInfo($code) )
                        $result['attributes']['residential_city_name'] = $city->getName();
                    else
                        $result['attributes']['residential_city_name'] = null;
                }
                $result['user_info'] = $user_info;
                $this->setResponseCode(MessageCode::CODE_RECIPIENT_FOUND);
                return $result;
            }
        }

        $this->setResponseCode(MessageCode::CODE_RECIPIENT_NOT_FOUND);
        return false;
    }

    public function getRecipientList($user_profile_id)
    {
        $filter = new Recipient();
        $filter->setUserProfileId($user_profile_id);
        $this->_setIsInternational($filter);
        $filter->setIsActive(true);
        if( $obj = $this->getRepository()->findByParam($filter, null, MAX_VALUE, 1) )
        {
            $collection = $obj->result;
            $collection = $collection->sortByLastSentAndCreatedAt();
            //get recipient's user info
            $accountService = AccountServiceFactory::build();
            $usersInfo = $accountService->getUsers($collection->getRecipientUserProfileIds());

            $results = array();
            foreach( $collection AS $recipient )
            {   
                $result = $recipient->getSelectedField(array('id','recipient_type','recipient_user_profile_id', 'recipient_dialing_code', 'recipient_mobile_number', 'recipient_alias', 'photo_image_url', 'is_active', 'is_international', 'last_sent_at','created_at'));
                $result['recipient_profile_image_url'] = NULL;

                if ($usersInfo) {
                    foreach( $usersInfo AS $user )
                    {
                        if( $user->getId() == $recipient->getRecipientUserProfileId() )
                        {
                            $result['recipient_profile_image_url'] = $user->getProfileImageUrl();
                        }
                    }
                }

                $trx = RemittanceTransactionServiceFactory::build();
                if ($trxInfo = $trx->getTransactionByRecipientId($recipient->getId())) {
                    $trxInfoCollection = $trxInfo->result;

                    foreach ($trxInfoCollection as $transaction) {
                        $result['transaction_id'] = $transaction->getId();
                    }
                }else{
                    $result['transaction_id'] = NULL;
                }

                $result['id_number'] = NULL;
                $result['id_type'] = NULL;
                $result['full_name'] = NULL;
                $result['nationality'] = NULL;
                $result['relationship_to_sender'] = NULL;
                $result['id_type_id'] = NULL;
                $result['nationality_id'] = NULL;
                $result['relationship_to_sender_id'] = NULL;
                $result['remittance_purpose_id'] = NULL;
                $result['remittance_purpose'] = NULL;
                $result['income_source_id'] = NULL;
                $result['income_source'] = NULL;
                $result['residing_address'] = NULL;
                $result['residing_country_code'] = NULL;
                $result['residing_province_code'] = NULL;
                $result['residing_city_code'] = NULL;
                $result['residing_postal_code'] = NULL;
                $result['bank_info'] = NULL;
                $result['payment_code'] = NULL;

                $attr_serv = RecipientAttributeServiceFactory::build();
                $attr_serv->setUpdatedBy($this->getUpdatedBy());
                $attr_serv->setIpAddress($this->getIpAddress());

                if( $arrtCollection = $attr_serv->getAllRecipientAttribute($recipient->getId()) )
                {   
                    foreach ($arrtCollection as $attr) {

                        if ($attr->getAttribute()->getCode() == AttributeCode::ID_NUMBER) {
                            $result['id_number'] = $attr->getValue();
                        }else if ($attr->getAttribute()->getCode() == AttributeCode::ID_TYPE) {
                            $result['id_type_id'] = $attr->getAttributeValueId();
                            $result['id_type'] = $attr->getValue();
                        }else if ($attr->getAttribute()->getCode() == AttributeCode::FULL_NAME) {
                            $result['full_name'] = $attr->getValue();
                        }else if ($attr->getAttribute()->getCode() == AttributeCode::NATIONALITY) {
                            $result['nationality_id'] = $attr->getAttributeValueId();
                            $result['nationality'] = $attr->getValue();
                        }else if ($attr->getAttribute()->getCode() == AttributeCode::RELATIONSHIP_TO_SENDER) {
                            $result['relationship_to_sender_id'] = $attr->getAttributeValueId();
                            $result['relationship_to_sender'] = $attr->getValue();
                        }else if ($attr->getAttribute()->getCode() == AttributeCode::SOURCE_OF_INCOME) {
                            $result['income_source_id'] = $attr->getAttributeValueId();
                            $result['income_source'] = $attr->getValue();
                        }else if ($attr->getAttribute()->getCode() == AttributeCode::PURPOSE_OF_REMITTANCE) {
                            $result['remittance_purpose_id'] = $attr->getAttributeValueId();
                            $result['remittance_purpose'] = $attr->getValue();
                        }else if ($attr->getAttribute()->getCode() == AttributeCode::RESIDENTIAL_ADDRESS) {
                            $result['residing_address'] = $attr->getValue();
                        }else if ($attr->getAttribute()->getCode() == AttributeCode::RESIDENTIAL_COUNTRY) {
                            $result['residing_country_code'] = $attr->getValue();
                        }else if ($attr->getAttribute()->getCode() == AttributeCode::RESIDENTIAL_PROVINCE) {
                            $result['residing_province_code'] = $attr->getValue();
                        }else if ($attr->getAttribute()->getCode() == AttributeCode::RESIDENTIAL_CITY) {
                            $result['residing_city_code'] = $attr->getValue();
                        }else if ($attr->getAttribute()->getCode() == AttributeCode::RESIDENTIAL_POST_CODE) {
                            $result['residing_postal_code'] = $attr->getValue();
                        }
                    }
                }

                $recipientCollectionServive = RecipientCollectionInfoServiceFactory::build();

                $infos = $recipientCollectionServive->getRepository()->findByRecipientId($recipient->getId());
                if ($infos) {
                    $result['payment_code'] = $infos->result->current()->getPaymentCode();
                    $result['bank_info'] = json_decode($infos->result->current()->getOption()->getValue(),true);
                }
            
                $results[] = $result;
            }

            $this->setResponseCode(MessageCode::CODE_RECIPIENT_LIST_FOUND);
            return $results;
        }

        $this->setResponseCode(MessageCode::CODE_RECIPIENT_LIST_NOT_FOUND);
        return false;
    }

    public function _setIsInternational(Recipient $recipient)
    {
        $recipient->setIsInternational(1);
        return $recipient;
    }

    public function _assignRecipientUserProfileId(Recipient $recipient)
    {//only non member required the tagging
        if( $recipient->getRecipientType() == RecipientType::NON_MEMBER )
        {
            $dialing_code = $recipient->getRecipientDialingCode()->getValue();
            $mobile_number = $recipient->getRecipientMobileNumber()->getValue();

            if( $dialing_code AND $mobile_number )
            {
                $accServ = AccountServiceFactory::build();
                if( $recipientUser = $accServ->searchUser($dialing_code, $mobile_number) )
                {//if its slide user
                    //get its profile
                    if( $recipientUser = $accServ->getUser(NULL, $recipientUser->getId()))
                    {
                        $recipient->isMatched($recipientUser);
                    }
                }
            }
        }

        return $recipient;
    }

    /*
     * this function is changed to check the recipient is slide user
     */
    public function addRecipient($recipient_id, $user_profile_id, $dialing_code, $mobile_number, $alias, $recipient_type, RecipientAttributeCollection $attributes ,$payment_code, array $bank_info, $photo_image_url=null, $recipient_user_profile_id=null)
    {
        if ($recipient_id) {
            // update recipient
            if ($oldRecipient = $this->getRecipient($recipient_id)) {
                if( $oldRecipient instanceof Recipient )
                {
                    //update on top of old recipient
                    $recipient = clone($oldRecipient);
                    $recipient->setId($recipient_id);
                    $recipient->setUserProfileId($user_profile_id);
                    $recipient->setRecipientDialingCode(clone($oldRecipient->getRecipientDialingCode()));
                    $recipient->setRecipientMobileNumber(clone($oldRecipient->getRecipientMobileNumber()));
                    $recipient->setRecipientAlias($alias);
                    $recipient->activate();
                    $recipient->setRecipientType($recipient_type);
                    $recipient->setPhotoImageUrl($photo_image_url);
                    $recipient->setUpdatedBy($this->getUpdatedBy());
                    $recipient->setAttributes($attributes);
                    $recipient->setLastEditedAt(IappsDateTime::now());
                    $this->_setIsInternational($recipient);

                    //check if its changing mobile number
                    if( $dialing_code AND $mobile_number )
                    {
                        if( !$oldRecipient->isMobileNumber($dialing_code, $mobile_number) )
                        {
                            if( $oldRecipient->hasTaggedToUser() )
                            {
                                $this->setResponseCode(MessageCode::CODE_RECIPIENT_CANNOT_EDIT_MOBILE_NUMBER);
                                return false;
                            }

                            $recipient->getRecipientDialingCode()->setValue($dialing_code);
                            $recipient->getRecipientMobileNumber()->setValue($mobile_number);

                            if( !$this->_checkRecipientExists($recipient) )
                                return false;

                            //assign user profile id
                            $this->_assignRecipientUserProfileId($recipient);
                        }
                    }

                    $v = $this->_getRecipientValidator()->make($recipient);
                    if( !$v->fails() )
                    {
                        $this->getRepository()->startDBTransaction();

                        if( $this->getRepository()->update($recipient) ) {

                            //collection info cannot be updated here, due
                            if( !$this->_setRecipientAttribute($recipient) )
                            {
                                $this->getRepository()->rollbackDBTransaction();
                                if(!$this->getResponseCode())
                                    $this->setResponseCode(MessageCode::CODE_UPDATE_RECIPIENT_FAIL);
                                return false;
                            }

                            $this->getRepository()->completeDBTransaction();
                            $this->_removeCached($recipient);
                            $this->fireLogEvent('iafb_remittance.recipient', AuditLogAction::UPDATE, $oldRecipient->getId(), $oldRecipient);
                            $obj = $this->getRecipientDetail($oldRecipient->getId());
                            return $obj;
                        }

                        $this->getRepository()->rollbackDBTransaction();
                    }

                    $this->setResponseCode(MessageCode::CODE_UPDATE_RECIPIENT_FAIL);
                    return false;
                }
            }
        }else{
            $recipient = new Recipient();

            $recipient->setId(GuidGenerator::generate());
            $recipient->setUserProfileId($user_profile_id);            
            $recipient->getRecipientDialingCode()->setValue($dialing_code);
            $recipient->getRecipientMobileNumber()->setValue($mobile_number);
            $recipient->setRecipientAlias($alias);
            $recipient->activate();
            $recipient->setRecipientType($recipient_type);
            $recipient->setPhotoImageUrl($photo_image_url);
            $recipient->setCreatedBy($this->getUpdatedBy());
            $recipient->setAttributes($attributes);
            $recipient->setRecipientUserProfileId($recipient_user_profile_id);

            $this->_assignRecipientUserProfileId($recipient);
            $this->_setIsInternational($recipient);

            $v = $this->_getRecipientValidator()->make($recipient);
            if( !$v->fails() )
            {
                //check existing recipient
                if( !$this->_checkRecipientExists($recipient ) )
                    return false;

                $this->getRepository()->startDBTransaction();
                if( $this->getRepository()->insert($recipient) )
                {
                    if( !$this->_setRecipientAttribute($recipient) OR
                        !$this->_setRecipientCollectionInfo($recipient, NULL, $bank_info) )
                    {
                        $this->getRepository()->rollbackDBTransaction();
                        if(!$this->getResponseCode())
                            $this->setResponseCode(MessageCode::CODE_ADD_RECIPIENT_FAIL);
                        return false;
                    }

                    $this->fireLogEvent('iafb_remittance.recipient', AuditLogAction::CREATE, $recipient->getId());
                    $this->getRepository()->completeDBTransaction();

                    $this->_removeCached($recipient);
                    $obj = $this->getRecipientDetail($recipient->getId());
                    $this->setResponseCode(MessageCode::CODE_ADD_RECIPIENT_SUCCESS);
                    return $obj;
                }

                $this->getRepository()->rollbackDBTransaction();
                $this->setResponseCode(MessageCode::CODE_ADD_RECIPIENT_FAIL);
                return false;
            }

            $this->setResponseCode(MessageCode::CODE_ADD_RECIPIENT_FAIL);
            return false;
        }
    }

    protected function _validateCollectionOption($country_code, array $collectionInfo)
    {
        $paymentService = SystemPaymentServiceFactory::build();
        if( $result = $paymentService->validateCollectionInfo(NULL, $country_code, $collectionInfo) )
                return $result;
        
        //$this->setResponseCode(MessageCode::CODE_COLLECTION_INFO_VALIDATE_FAILED);
        if( isset($paymentService->getLastResponse()['status_code']) )
            $this->setResponseCode($paymentService->getLastResponse()['status_code']);
        
        if( isset($paymentService->getLastResponse()['message']) )
            $this->setResponseMessage($paymentService->getLastResponse()['message']);
        
        return false;
    }

    protected function _setRecipientAttribute(Recipient $recipient)
    {
        $attr_serv = RecipientAttributeServiceFactory::build();
        $attr_serv->setUpdatedBy($this->getUpdatedBy());
        $attr_serv->setIpAddress($this->getIpAddress());

        foreach($recipient->getAttributes() as $arrtibuteV)
        {
            //update attributes
            if( !$attr_serv->setRecipientAttribute($recipient->getId(), $arrtibuteV) )
                return false;
        }

        return true;
    }

    protected function _setRecipientCollectionInfo(Recipient $recipient, $country_code, array $collection_info)
    {
        if( count($collection_info) > 0 )
        {
            if( !$country_code )
            {
                if( !$country_code = $recipient->getAttributes()->hasAttribute(AttributeCode::RESIDENTIAL_COUNTRY) )
                {
                    $country_code = $recipient->getRecipientUser()->getHostCountryCode();
                }
            }

            //validate collection info
            if( $country_code )
            {
                //map the collection info param
                if( isset($collection_info['account_no']) )
                    $collection_info['bank_account'] = $collection_info['account_no'];

                if( !$this->_validateCollectionOption($country_code, $collection_info) )
                    return false;
            }
            else
                return false;

            $recipientCollectionService = RecipientCollectionInfoServiceFactory::build();
            $recipientCollectionService->setUpdatedBy($this->getUpdatedBy());
            $recipientCollectionService->setIpAddress($this->getIpAddress());

            if( !$recipientCollectionService->addRecipientCollectionInfo($recipient, $country_code, $collection_info) )
            {
                $this->setResponseCode($recipientCollectionService->getResponseCode());
                return false;
            }

            $this->_removeCached($recipient);
        }

        return true;
    }

    public function getRecipientByIds(array $recipient_ids)
    {
        if($collection = $this->getRepository()->findByIds($recipient_ids) )
        {
            $this->setResponseCode(MessageCode::CODE_RECIPIENT_LIST_FOUND);
            
            $recipientAttrService = RecipientAttributeServiceFactory::build();
            if( $attributeInfo = $recipientAttrService->getByRecipientIds($collection->getResult()->getIds()) )
                $collection->getResult()->joinRecipientAttribute($attributeInfo);
            return $collection;
        }

        $this->setResponseCode(MessageCode::CODE_RECIPIENT_LIST_NOT_FOUND);
        return false;
    }
    
    public function getRecipientByParam(Recipient $recipient, array $recipient_id_arr = NULL, $limit = 100, $page = 1)
    {
        if ($collection = $this->getRepository()->findByParam($recipient, $recipient_id_arr, $limit, $page)) {
            $this->setResponseCode(MessageCode::CODE_RECIPIENT_LIST_FOUND);
            
            $recipientAttrService = RecipientAttributeServiceFactory::build();
            if( $attributeInfo = $recipientAttrService->getByRecipientIds($collection->getResult()->getIds()) )
                $collection->getResult()->joinRecipientAttribute($attributeInfo);
            return $collection;
        }

        $this->setResponseCode(MessageCode::CODE_RECIPIENT_LIST_NOT_FOUND);
        return false;
    }

    protected function _checkRecipientExists(Recipient $recipient)
    {
        if( $recipient->getRecipientUserProfileId() )
        {
            $filter = new Recipient();
            $filter->setUserProfileId($recipient->getUserProfileId());
            $filter->setRecipientUserProfileId($recipient->getRecipientUserProfileId());
            $filter->setIsInternational(1);
            $filter->setIsActive(1);
            if( $info = $this->getRepository()->findByParam($filter, NULL, 1, 1) )
            {
                $this->setResponseCode(MessageCode::CODE_RECIPIENT_EXISTS);
                return false;
            }

            return true;
        }
        elseif( $recipient->getRecipientDialingCode()->getValue() AND
                $recipient->getRecipientMobileNumber()->getValue() )
        {
            if( $existRecipientInfo = $this->getRepository()->findByMobileNumber($recipient->getUserProfileId(), $recipient->getRecipientDialingCode()->getValue(), $recipient->getRecipientMobileNumber()->getValue()) )
            {
                $existRecipients = $existRecipientInfo->result;
                foreach($existRecipients AS $existRecipient)
                {
                    if( (bool) $existRecipient->getIsInternational() === true AND $existRecipient->getIsActive() == true)
                    {
                        $this->setResponseCode(MessageCode::CODE_RECIPIENT_EXISTS);
                        return false;
                    }
                }
            }

            return true;
        }

        $this->setResponseCode(MessageCode::CODE_INVALID_RECIPIENT);
        return false;
    }

    protected function _checkRecipientType($code)
    {
        if( $obj = RecipientValidator::validateRecipientType($code) )
        {
            return $obj;
        }

        $this->setResponseCode(MessageCode::CODE_INVALID_RECIPIENT_TYPE);
        return false;
    }

    protected function _getRecipientValidator()
    {
        return new RecipientValidator();
    }

    protected function _removeCached(Recipient $recipient)
    {
        $cacheKey = CacheKey::REMITTANCE_RECIPIENT_LIST . $recipient->getUserProfileId();
        $this->deleteElastiCache($cacheKey);

        $cacheKey = CacheKey::REMITTANCE_RECIPIENT_DETAIL . $recipient->getId();
        $this->deleteElastiCache($cacheKey);
    }

    public function getRecipientByHashedMobileNumber($hashed_dialing_code, $hashed_mobile_number)
    {
        if( $obj = $this->getRepository()->findByHashedMobileNumber($hashed_dialing_code, $hashed_mobile_number) )
        {
            $collection = $obj->result;
            if( $collection instanceof RecipientCollection )
            {
                $recipientAttrService = RecipientAttributeServiceFactory::build();
                if( $attributeInfo = $recipientAttrService->getByRecipientIds($collection->getIds()) )
                    $collection->joinRecipientAttribute($attributeInfo);
            }

            return $collection;
        }

        return false;
    }
}