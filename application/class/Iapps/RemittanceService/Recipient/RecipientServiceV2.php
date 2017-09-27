<?php

namespace Iapps\RemittanceService\Recipient;

use Iapps\Common\AuditLog\AuditLogAction;
use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\Helper\GuidGenerator;
use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\Common\Microservice\AccountService\User;
use Iapps\Common\Microservice\PaymentService\PaymentModeAttributeServiceFactory;
use Iapps\Common\Microservice\PaymentService\PaymentServiceFactory;
use Iapps\RemittanceService\Attribute\RecipientAttributeCollection;
use Iapps\RemittanceService\Attribute\RecipientAttributeServiceFactory;
use Iapps\RemittanceService\Common\CacheKey;
use Iapps\RemittanceService\Common\MessageCode;
use Iapps\RemittanceService\RecipientCollectionInfo\RecipientCollectionInfoServiceFactory;
use Iapps\Common\Microservice\CountryService\CountryServiceFactory;
use Iapps\RemittanceService\Attribute\AttributeCode;
use Iapps\RemittanceService\RemittanceCompanyRecipient\RemittanceCompanyRecipientServiceFactory;
use Iapps\RemittanceService\Common\CoreConfigDataServiceFactory;
use Iapps\RemittanceService\Common\CoreConfigType;

class RecipientServiceV2 extends RecipientService{

    protected $_userProfileId;
    protected $_paymentCode;

    public function setUserProfileId($user_profile_id)
    {
        $this->_userProfileId = $user_profile_id;
        return $this;
    }

    public function getUserProfileId()
    {
        return $this->_userProfileId;
    }

    public function setPaymentCode($payment_code)
    {
        $this->_paymentCode = $payment_code;
        return $this;
    }

    public function getPaymentCode()
    {
        return $this->_paymentCode;
    }

    //rewrite getRecipientList
    public function getBasicRecipientList($user_profile_id)
	{//this get the basic recipient list
		//try to get from cache
        $cacheKey = CacheKey::REMITTANCE_RECIPIENT_LIST . $user_profile_id;

        if( !$collection = $this->getElasticCache($cacheKey) )
        {
            $filter = new Recipient();
            $filter->setUserProfileId($user_profile_id);
            $filter->setIsActive(1);
            $this->_setIsInternational($filter);
            if( $obj = $this->getRepository()->findByParam($filter, null, MAX_VALUE, 1) )
            {
                $collection = $obj->result;
                if( $collection instanceof RecipientCollection )
                {
                    $recipientAttrService = RecipientAttributeServiceFactory::build();
                    if( $attributeInfo = $recipientAttrService->getByRecipientIds($collection->getIds()) )
                        $collection->joinRecipientAttribute($attributeInfo);

                    $recipientCollectionInfoService = RecipientCollectionInfoServiceFactory::build();
                    if( $collectionInfos = $recipientCollectionInfoService->getByRecipientIds($collection->getIds()) )
                        $collection->joinCollectionInfo($collectionInfos);

                    $this->setElasticCache($cacheKey, $collection, 86400);  //cache for 1 day
                }
            }
        }

		return $collection;
	}

    //to return limited information for better performance
    //as well as better control on access control
    public function getRecipientList($user_profile_id, $service_provider_id = NULL)
    {
        //get basic list
        $collection = $this->getBasicRecipientList($user_profile_id);        

		//then combine more information
        if( $collection instanceof RecipientCollection )
        {
            $collection = $collection->sortByLastSentAndCreatedAt();
            $recipientUserCollection = NULL;
            if( $userIds = $collection->getRecipientUserProfileIds() ) {//grab more information before returning

                $accountService = AccountServiceFactory::build();
                $recipientUserCollection = $accountService->getUsers($userIds);
            }

            $result = array();
            foreach($collection AS $recipient)
            {
                if( $recipient instanceof Recipient )
                {
                    $temp = $recipient->getSelectedField(array('id', 'recipient_type', 'recipient_user_profile_id',
                        'recipient_dialing_code', 'recipient_mobile_number',
                        'recipient_alias', 'photo_image_url', 'is_active',
                        'is_international', 'last_sent_at', 'last_edited_at', 'created_at'));

                    $temp['residing_country_code'] = null;
                    if( $country_code = $recipient->getAttributes()->hasAttribute('residential_country') )
                        $temp['residing_country_code'] = $country_code;

                    if( $this->getPaymentCode() )
                    {//filter based on paymetn code
                        $requiredAttributes = $this->_getRequiredAttributes();
                        $filteredCollectionInfo = $recipient->getCollectionInfos()->getByRequiredAttribute($requiredAttributes);
                        $temp['collection_info'] = $filteredCollectionInfo->getSelectedField(array('id','country_code', 'payment_code', 'option'));
                    }
                    else
                        $temp['collection_info'] = $recipient->getCollectionInfos()->getSelectedField(array('id','country_code', 'payment_code', 'option'));

                    if( $recipientUserCollection )
                    {
                        if( $recipient->getRecipientUserProfileId() AND
                            $recipient->getPhotoImageUrl() == NULL )
                        {//if recipient has no photo, and its slide user, use his/hers slide profile image
                            if( $recipientUser = $recipientUserCollection->getById($recipient->getRecipientUserProfileId()) AND
                                $recipientUser->getProfileImageUrl())
                            {
                                $temp['photo_image_url'] = $recipientUser->getProfileImageUrl();
                            }
                        }
                    }
                    
                    if( $service_provider_id )
                    {//if given, grab remco profile
                        $temp['remittance_profile'] = NULL;
                        $remcoRecipientServ = RemittanceCompanyRecipientServiceFactory::build();
                        if( $remcoRecipient = $remcoRecipientServ->getByServiceProviderIdAndRecipient($service_provider_id, $recipient->getId()) )
                        {
                            $temp['remittance_profile'] = $remcoRecipient->getSelectedField(array('id', 'remittance_company_id','recipient_status'));
                        }
                    }

                    $result[] = $temp;
                }
            }

            if( count($result) > 0 )
            {
                $this->setResponseCode(MessageCode::CODE_RECIPIENT_LIST_FOUND);
                return $result;
            }
        }

        $this->setResponseCode(MessageCode::CODE_RECIPIENT_LIST_NOT_FOUND);
        return false;
    }

	public function getBasicRecipientDetail($recipient_id)
	{
		//try to get from cache
        $cacheKey = CacheKey::REMITTANCE_RECIPIENT_DETAIL . $recipient_id;
		if( $recipient = $this->getElasticCache($cacheKey) )
			return $recipient;

        $countrySer = CountryServiceFactory::build();
        if ($recipient = $this->getRecipient($recipient_id))
        {
            if ($recipient instanceof Recipient) {//grab more data
                if ($this->getUserProfileId()) {//check if recipient belongs to user
                	$user = new User();
					$user->setId($this->getUserProfileId());
                    if (!$recipient->belongsTo($user)) 
                    {
                        $this->setResponseCode(MessageCode::CODE_RECIPIENT_NOT_FOUND);
                        return false;
                    }
                }

                $recipientAttrService = RecipientAttributeServiceFactory::build();
                if ($attributeInfo = $recipientAttrService->getAllRecipientAttribute($recipient->getId())) {
                    foreach ($attributeInfo AS $attribute) {
                        $recipient->getAttributes()->addData($attribute);

                        if ($attribute->getAttribute()->getCode() == AttributeCode::RESIDENTIAL_COUNTRY) {
                            
                            if ($countryInfo = $countrySer->getCountryInfo($attribute->getValue())) {

                                $recipient->setRecipientResidentialCountry($countryInfo->getName());
                            }
                        }

                        if ($attribute->getAttribute()->getCode() == AttributeCode::RESIDENTIAL_PROVINCE) {
                            
                            if ($provinceInfo = $countrySer->getProvinceInfo($attribute->getValue())) {

                                $recipient->setRecipientResidentialProvince($provinceInfo->getName());
                            }
                        }

                        if ($attribute->getAttribute()->getCode() == AttributeCode::RESIDENTIAL_CITY) {
                            
                            if ($cityInfo = $countrySer->getCityInfo($attribute->getValue())) {

                                $recipient->setRecipientResidentialCity($cityInfo->getName());
                            }
                        }
                    }
                }

                $recipientCollectionServive = RecipientCollectionInfoServiceFactory::build();
                if ($collectionInfos = $recipientCollectionServive->getByRecipientId($recipient_id))
                    $recipient->setCollectionInfos($collectionInfos);

                $this->setElasticCache($cacheKey, $recipient, 86400);
				return $recipient;
            }
        }
		
		return false;
	}
	
    public function getRecipientDetail($recipient_id, $isArray = true)
    {
        $recipient = $this->getBasicRecipientDetail($recipient_id);

        if( $recipient instanceof Recipient )
        {
            if( $this->getUserProfileId() )
            {
                if( !$recipient->belongsTo((new User())->setId($this->getUserProfileId())) )
                {
                    $this->setResponseCode(MessageCode::CODE_RECIPIENT_NOT_FOUND);
                    return false;
                }
            }

            $remitCoRecipientService = RemittanceCompanyRecipientServiceFactory::build();
            if($remitCoRecipientColl = $remitCoRecipientService->listByRecipient($recipient->getId(),  1, MAX_VALUE, false)) {
                $recipient->setRemittanceCompanyRecipients($remitCoRecipientColl);
            }

            $this->setResponseCode(MessageCode::CODE_RECIPIENT_FOUND);
            if( !$isArray )
                return $recipient;
            else
            {
                $result = $recipient->getSelectedField(array('id', 'recipient_type', 'recipient_user_profile_id',
                    'recipient_dialing_code', 'recipient_mobile_number',
                    'recipient_alias', 'photo_image_url', 'is_active',
                    'recipient_residential_country','recipient_residential_province','recipient_residential_city',
                    'is_international', 'last_sent_at', 'last_edited_at', 'created_at', 'attributes'));

                if( $this->getPaymentCode() )
                {//filter based on paymetn code
                    $requiredAttributes = $this->_getRequiredAttributes();
                    $filteredCollectionInfo = $recipient->getCollectionInfos()->getByRequiredAttribute($requiredAttributes);
                    $result['collection_info'] = $filteredCollectionInfo->getSelectedField(array('id','country_code', 'payment_code', 'option'));
                }
                else
                    $result['collection_info'] = $recipient->getCollectionInfos()->getSelectedField(array('id','country_code', 'payment_code', 'option'));

                $result['status'] = NULL;
                if($recipient->getRemittanceCompanyRecipients()) {
                    $temps = array();
                    foreach ($recipient->getRemittanceCompanyRecipients() AS $remRec) {
                        $temp = $remRec->getSelectedField(array('id', 'recipient_status', 'face_to_face_verified_at', 'face_to_face_verified_by'));
                        $temp['remittance_company'] = $remRec->getRemittanceCompany()->getSelectedField(array('service_provider_id', 'uen', 'mas_license_no'));
                        $temp['remittance_company']['name'] = $remRec->getRemittanceCompany()->getCompanyInfo()->getName();
                        $temp['remittance_company']['logo'] = $remRec->getRemittanceCompany()->getCompanyInfo()->getProfileImageUrl();

                        $temps[] = $temp;
                    }
                    $result['status'] = $temps;
                }

                return $result;
            }
        }

        $this->setResponseCode(MessageCode::CODE_RECIPIENT_NOT_FOUND);
        return false;
    }

    /*
     * rewrite to make this function separate from edit recipient
     */
    public function addRecipient($recipient_id, $user_profile_id, $dialing_code, $mobile_number, $alias, $recipient_type, RecipientAttributeCollection $attributes ,$country_code, array $collection_option, $photo_image_url=null, $recipient_user_profile_id=null)
    {
    	if( !$this->_checkMaxRecipientLimit($user_profile_id) )
			return false;
		
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
                    !$this->_setRecipientCollectionInfo($recipient, $country_code, $collection_option) )
                {
                    $this->getRepository()->rollbackDBTransaction();
                    if(!$this->getResponseCode())
                        $this->setResponseCode(MessageCode::CODE_ADD_RECIPIENT_FAIL);
                    return false;
                }
                
                $this->getRepository()->completeDBTransaction();
				
				$this->fireLogEvent('iafb_remittance.recipient', AuditLogAction::CREATE, $recipient->getId());
				RecipientEventProducer::publishRecipientCreated($recipient->getId());

                $this->_removeCached($recipient);
                $this->setResponseCode(MessageCode::CODE_ADD_RECIPIENT_SUCCESS);
                return $recipient->getSelectedField(array('id'));
            }

            $this->getRepository()->rollbackDBTransaction();
            $this->setResponseCode(MessageCode::CODE_ADD_RECIPIENT_FAIL);
            return false;
        }

        $this->setResponseCode(MessageCode::CODE_ADD_RECIPIENT_FAIL);
        return false;
    }

    public function editRecipient($recipient_id, $user_profile_id, $dialing_code, $mobile_number, $alias, RecipientAttributeCollection $attributes, $photo_image_url=null)
    {
        if ($existingRecipient = $this->getRecipient($recipient_id))
        {
            if( $existingRecipient instanceof Recipient )
            {
                if( $existingRecipient->belongsTo((new User())->setId($user_profile_id)))
                {
                    //update on top of old recipient
                    //deep clone
                    $recipient = unserialize( serialize($existingRecipient) );
                    $recipient->setRecipientAlias($alias);
                    $recipient->activate();
                    $recipient->setPhotoImageUrl($photo_image_url);
                    $recipient->setUpdatedBy($this->getUpdatedBy());
                    $recipient->setAttributes($attributes);
                    $recipient->setLastEditedAt(IappsDateTime::now());
                    $this->_setIsInternational($recipient);

                    //check if its changing mobile number
                    if( $dialing_code AND $mobile_number )
                    {
                        if( !$existingRecipient->isMobileNumber($dialing_code, $mobile_number) )
                        {
                            if( $existingRecipient->hasTaggedToUser() )
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

                            if( !$this->_setRecipientAttribute($recipient) )
                            {
                                $this->getRepository()->rollbackDBTransaction();
                                if(!$this->getResponseCode())
                                    $this->setResponseCode(MessageCode::CODE_UPDATE_RECIPIENT_FAIL);
                                return false;
                            }

                            //invalidate face to face recipient verification
                            $remCoRecipientService = RemittanceCompanyRecipientServiceFactory::build();
                            $remCoRecipientService->checkProfilesStatus($recipient_id);


                            $this->getRepository()->completeDBTransaction();
                            $this->_removeCached($recipient);
                            $this->fireLogEvent('iafb_remittance.recipient', AuditLogAction::UPDATE, $recipient->getId(), $existingRecipient);
							RecipientEventProducer::publishRecipientChanged($recipient->getId());

                            $this->setResponseCode(MessageCode::CODE_UPDATE_RECIPIENT_SUCCESS);
                            return true;
                        }

                        $this->getRepository()->rollbackDBTransaction();
                    }
                }
            }
        }

        $this->setResponseCode(MessageCode::CODE_UPDATE_RECIPIENT_FAIL);
        return false;
    }

	public function deactivateRecipient($user_profile_id, $recipient_id)
	{
		if( $recipient = $this->getRepository()->findById($recipient_id) )
		{
			if( $recipient instanceof Recipient )
			{
				$user = new User();
				$user->setId($user_profile_id);
				if( $recipient->belongsTo($user) AND $recipient->getIsActive() == 1)
				{
					$recipient->setIsActive(0);
					if( $this->getRepository()->update($recipient) )
					{
						$this->setResponseCode(MessageCode::CODE_UPDATE_RECIPIENT_SUCCESS);
						return true;
					}
				}
			}			
		}
		
		$this->setResponseCode(MessageCode::CODE_RECIPIENT_NOT_FOUND);
		return false;
	}

    //only return collection info list
    public function listCollectionInfo($recipient_id)
    {
        if( $recipientDetail = $this->getRecipientDetail($recipient_id) )
        {
            if( count($recipientDetail['collection_info']) > 0  )
            {
                $this->setResponseCode(MessageCode::CODE_GET_COLLECTION_INFO_SUCCESS);
                return $recipientDetail['collection_info'];
            }
            else
                $this->setResponseCode(MessageCode::CODE_GET_COLLECTION_INFO_FAILED);
        }

        if( !$this->getResponseCode() )
            $this->setResponseCode(MessageCode::CODE_GET_COLLECTION_INFO_FAILED);
        return false;
    }

    public function addCollectionInfo($user_profile_id, $recipient_id, $country_code, array $collection_info)
    {
        if ($existingRecipient = $this->getRecipient($recipient_id))
        {
            if ($existingRecipient instanceof Recipient)
            {
                if ($existingRecipient->belongsTo((new User())->setId($user_profile_id)))
                {
                    $recipient = unserialize( serialize($existingRecipient) );
                    $recipient->setLastEditedAt(IappsDateTime::now());
                    $recipient->setUpdatedBy($this->getUpdatedBy());

                    $this->getRepository()->startDBTransaction();
                    if( $this->getRepository()->update($recipient) ) {
                        if( $this->_setRecipientCollectionInfo($existingRecipient, $country_code, $collection_info) )
                        {
                            //invalidate face to face recipient verification
                            $remCoRecipientService = RemittanceCompanyRecipientServiceFactory::build();
                            $remCoRecipientService->checkProfilesStatus($recipient_id);
                            
                            $this->_removeCached($recipient);
                            $this->getRepository()->completeDBTransaction();
							
							RecipientEventProducer::publishRecipientChanged($recipient->getId());
                            $this->setResponseCode(MessageCode::CODE_ADD_COLLECTION_INFO_SUCCESS);
                            return true;
                        }
                    }

                    $this->getRepository()->rollbackDBTransaction();
                    if( !$this->getResponseCode() )
                        $this->setResponseCode(MessageCode::CODE_ADD_COLLECTION_INFO_FAILED);
                    return false;
                }
            }
        }

        $this->setResponseCode(MessageCode::CODE_RECIPIENT_NOT_FOUND);
        return false;
    }

    public function editCollectionInfo($user_profile_id, $collection_info_id, $country_code, array $collection_info)
    {
        $collectionInfoServ = RecipientCollectionInfoServiceFactory::build();
        $collectionInfoServ->setUpdatedBy($this->getUpdatedBy());
        $collectionInfoServ->setIpAddress($this->getIpAddress());

        if( $collectionInfo = $collectionInfoServ->findById($collection_info_id) )
        {
            $recipient_id = $collectionInfo->getRecipientId();
            if ($existingRecipient = $this->getRecipient($recipient_id)) {
                if ($existingRecipient instanceof Recipient) {
                    if ($existingRecipient->belongsTo((new User())->setId($user_profile_id))) {

                        $recipient = unserialize( serialize($existingRecipient) );
                        $recipient->setLastEditedAt(IappsDateTime::now());
                        $recipient->setUpdatedBy($this->getUpdatedBy());

                        $this->getRepository()->startDBTransaction();
                        if( $this->getRepository()->update($recipient) ) {
                            //map the collection info param
                            if( isset($collection_info['account_no']) )
                                $collection_info['bank_account'] = $collection_info['account_no'];

                            if( !$this->_validateCollectionOption($country_code, $collection_info) )
                                return false;

                            if( $collectionInfoServ->editRecipientCollectionInfo($collection_info_id, $country_code, $collection_info) )
                            {
                                //invalidate face to face recipient verification
                                $remCoRecipientService = RemittanceCompanyRecipientServiceFactory::build();
                                $remCoRecipientService->checkProfilesStatus($recipient_id);
                                
                                $this->_removeCached($existingRecipient);

                                $this->getRepository()->completeDBTransaction();
								RecipientEventProducer::publishRecipientChanged($recipient->getId());
                                $this->setResponseCode(MessageCode::CODE_EDIT_COLLECTION_INFO_SUCCESS);
                                return true;
                            }
                        }

                        $this->getRepository()->rollbackDBTransaction();
                        $this->setResponseCode(MessageCode::CODE_EDIT_COLLECTION_INFO_FAILED);
                        return false;
                    }
                }
            }
        }

        $this->setResponseCode(MessageCode::CODE_RECIPIENT_NOT_FOUND);
        return false;
    }

    protected function _getRequiredAttributes()
    {
        $requiredAttributes = array();

        $paymentServ = PaymentModeAttributeServiceFactory::build();
        if( $attributes = $paymentServ->getInfo($this->getPaymentCode()) )
        {
            $requiredAttributes = $attributes;
        }

        return $requiredAttributes;
    }
	
	protected function _checkMaxRecipientLimit($user_profile_id)
	{
		$configServ = CoreConfigDataServiceFactory::build();
		if( $limit = $configServ->getConfig(CoreConfigType::MAX_RECIPIENT) AND $limit > 0)
		{
			//get existing number of recipient
			if( $collection = $this->getBasicRecipientList($user_profile_id) )
			{
				if( count($collection) >= $limit )
				{//reaching limit
					$this->setResponseCode(MessageCode::CODE_MAX_LIMIT_REACHED);
					$this->setResponseMessage('You are only allowed to have up to ' . $limit . ' recipients. To add a new recipient, delete an existing recipient.');
					return false;
				}
			}
		}
		
		return true;
	}
}