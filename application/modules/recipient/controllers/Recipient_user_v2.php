<?php

use Iapps\RemittanceService\Recipient\RecipientServiceFactory;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\RemittanceService\Recipient\RecipientType;
use Iapps\RemittanceService\Attribute\RecipientAttributeCollection;
use Iapps\RemittanceService\Attribute\AttributeValue;
use Iapps\RemittanceService\Attribute\Attribute;
use Iapps\RemittanceService\Attribute\AttributeCode;
use Iapps\Common\Core\IpAddress;

class Recipient_user_v2 extends User_Base_Controller{

    function __construct()
    {
        parent::__construct();

        //load v2 service
        $this->_serv = RecipientServiceFactory::build(true, '2');
        $this->_serv->setIpAddress(IpAddress::fromString($this->_getIpAddress()));
    }

    public function getList()
    {
        if( !$userId = $this->_getUserProfileId() )
            return false;

        $payment_code = $this->input->get('payment_code') ? $this->input->get('payment_code') : NULL;  //this will filter collection infos if given
        $service_provider_id = $this->input->get('service_provider_id') ? $this->input->get('service_provider_id') : NULL;  //this will extract remco profile if given

        $this->_serv->setUpdatedBy($userId);
        $this->_serv->setPaymentCode($payment_code);
        if( $result = $this->_serv->getRecipientList($userId, $service_provider_id) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getDetail()
    {
        if( !$userId = $this->_getUserProfileId() )
            return false;

        if( !$this->is_required($this->input->get(), array('recipient_id') ) )
            return false;

        $recipient_id = $this->input->get('recipient_id');
        $payment_code = $this->input->get('payment_code') ? $this->input->get('payment_code') : NULL;  //this will filter collection infos if given

        $this->_serv->setUpdatedBy($userId);
        $this->_serv->setUserProfileId($userId);
        $this->_serv->setPaymentCode($payment_code);
        if( $result = $this->_serv->getRecipientDetail($recipient_id) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function add()
    {
        if( !$userId = $this->_getUserProfileId() )
            return false;

        if( !$this->is_required($this->input->post(), array('recipient_type','alias','relationship_to_sender', 'remittance_purpose')))
            return false;

        $recipient_type = $this->input->post('recipient_type');
        $alias = $this->input->post('alias');
        $relationship = $this->input->post('relationship_to_sender');
        $purpose = $this->input->post('remittance_purpose');
        $country_code = $this->input->post('country_code') ? $this->input->post('country_code') :  NULL;
        $collection_info = $this->input->post('collection_info') ? json_decode($this->input->post('collection_info'),true) :  array();
        $user_profile_id  = $userId;

        $dialing_code = NULL;
        $mobile_number = NULL;
        $recipient_user_profile_id = NULL;
        $photo_image_url = $this->input->post('photo_image_url') ? $this->input->post('photo_image_url') : NULL;
        $full_name = NULL;
        $nationality = NULL;
        $residing_country_code = NULL;
        $residing_province_code = NULL;
        $residing_city_code = NULL;
        $residing_address = NULL;
        $residing_postal = NULL;

        if ($recipient_type == RecipientType::NON_MEMBER) {

            if( !$this->is_required($this->input->post(), array('dialing_code','mobile_number','full_name','nationality',
                'residing_country_code', 'residing_province_code','residing_city_code', 'residing_address')))
                return false;

            $dialing_code = $this->input->post('dialing_code');
            $mobile_number = $this->input->post('mobile_number');
            $full_name = $this->input->post('full_name');
            $nationality = $this->input->post('nationality');
            $residing_country_code = $this->input->post('residing_country_code');
            $residing_province_code = $this->input->post('residing_province_code');
            $residing_city_code = $this->input->post('residing_city_code');
            $residing_address = $this->input->post('residing_address');
            $residing_postal = $this->input->post('residing_postal_code') ? $this->input->post('residing_postal_code') : NULL;
        }
        else if ($recipient_type == RecipientType::NON_KYC) {

            if( !$this->is_required($this->input->post(), array('full_name','recipient_user_profile_id',
                'residing_country_code', 'residing_province_code','residing_city_code', 'residing_address')))
                return false;

            $full_name = $this->input->post('full_name');
            $recipient_user_profile_id = $this->input->post('recipient_user_profile_id');
            $residing_country_code = $this->input->post('residing_country_code');
            $residing_province_code = $this->input->post('residing_province_code');
            $residing_city_code = $this->input->post('residing_city_code');
            $residing_address = $this->input->post('residing_address');
            $residing_postal = $this->input->post('residing_postal_code') ? $this->input->post('residing_postal_code') : NULL;
        }
        else if ($recipient_type == RecipientType::KYC) {

            if( !$this->is_required($this->input->post(), array('recipient_user_profile_id')))
                return false;

            $recipient_user_profile_id = $this->input->post('recipient_user_profile_id');
        }
        else
        {
            return false;
        }

        //$attributes
        $attributes = new RecipientAttributeCollection();

        if( $full_name )
        {
            $attributes->addData((new AttributeValue())
                ->setAttribute((new Attribute())->setCode(AttributeCode::FULL_NAME))
                ->setValue($full_name));
        }

        if( $relationship )
        {
            $attributes->addData((new AttributeValue())
                ->setAttribute((new Attribute())->setCode(AttributeCode::RELATIONSHIP_TO_SENDER))
                ->setId($relationship));
        }

        if( $nationality )
        {
            $attributes->addData((new AttributeValue())
                ->setAttribute((new Attribute())->setCode(AttributeCode::NATIONALITY))
                ->setId($nationality));
        }

        if( $purpose )
        {
            $attributes->addData((new AttributeValue())
                ->setAttribute((new Attribute())->setCode(AttributeCode::PURPOSE_OF_REMITTANCE))
                ->setId($purpose));
        }

        if( $residing_country_code )
        {
            $attributes->addData((new AttributeValue())
                ->setAttribute((new Attribute())->setCode(AttributeCode::RESIDENTIAL_COUNTRY))
                ->setValue($residing_country_code));
        }

        if( $residing_province_code )
        {
            $attributes->addData((new AttributeValue())
                ->setAttribute((new Attribute())->setCode(AttributeCode::RESIDENTIAL_PROVINCE))
                ->setValue($residing_province_code));
        }

        if( $residing_city_code )
        {
            $attributes->addData((new AttributeValue())
                ->setAttribute((new Attribute())->setCode(AttributeCode::RESIDENTIAL_CITY))
                ->setValue($residing_city_code));
        }

        if( $residing_address )
        {
            $attributes->addData((new AttributeValue())
                ->setAttribute((new Attribute())->setCode(AttributeCode::RESIDENTIAL_ADDRESS))
                ->setValue($residing_address));
        }

        if( $residing_postal )
        {
            $attributes->addData((new AttributeValue())
                ->setAttribute((new Attribute())->setCode(AttributeCode::RESIDENTIAL_POST_CODE))
                ->setValue($residing_postal));
        }

        $this->_serv->setUpdatedBy($userId);
        if( $result = $this->_serv->addRecipient(null , $user_profile_id, $dialing_code, $mobile_number, $alias, $recipient_type, $attributes, $country_code, $collection_info, $photo_image_url, $recipient_user_profile_id) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND, NULL, NULL, $this->_serv->getResponseMessage());
        return false;
    }

    public function edit()
    {
        if( !$userId = $this->_getUserProfileId() )
            return false;

        if( !$this->is_required($this->input->post(), array('recipient_id', 'recipient_type','alias','relationship_to_sender', 'remittance_purpose')))
            return false;

        $recipient_type = $this->input->post('recipient_type');
        $alias = $this->input->post('alias');
        $relationship = $this->input->post('relationship_to_sender');
        $purpose = $this->input->post('remittance_purpose');
        $user_profile_id  = $userId;
        $recipient_id = $this->input->post('recipient_id');

        $dialing_code = NULL;
        $mobile_number = NULL;
        $recipient_user_profile_id = NULL;
        $photo_image_url = $this->input->post('photo_image_url') ? $this->input->post('photo_image_url') : NULL;
        $full_name = NULL;
        $nationality = NULL;
        $residing_country_code = NULL;
        $residing_province_code = NULL;
        $residing_city_code = NULL;
        $residing_address = NULL;
        $residing_postal = NULL;

        if ($recipient_type == RecipientType::NON_MEMBER) {

            if( !$this->is_required($this->input->post(), array('dialing_code','mobile_number','full_name','nationality',
                'residing_country_code', 'residing_province_code','residing_city_code', 'residing_address')))
                return false;

            $dialing_code = $this->input->post('dialing_code');
            $mobile_number = $this->input->post('mobile_number');
            $full_name = $this->input->post('full_name');
            $nationality = $this->input->post('nationality');
            $residing_country_code = $this->input->post('residing_country_code');
            $residing_province_code = $this->input->post('residing_province_code');
            $residing_city_code = $this->input->post('residing_city_code');
            $residing_address = $this->input->post('residing_address');
            $residing_postal = $this->input->post('residing_postal_code') ? $this->input->post('residing_postal_code') : NULL;
        }
        else if ($recipient_type == RecipientType::NON_KYC) {

            if( !$this->is_required($this->input->post(), array('full_name','recipient_user_profile_id',
                'residing_country_code', 'residing_province_code','residing_city_code', 'residing_address')))
                return false;

            $full_name = $this->input->post('full_name');
            $residing_country_code = $this->input->post('residing_country_code');
            $residing_province_code = $this->input->post('residing_province_code');
            $residing_city_code = $this->input->post('residing_city_code');
            $residing_address = $this->input->post('residing_address');
            $residing_postal = $this->input->post('residing_postal_code') ? $this->input->post('residing_postal_code') : NULL;
        }
        else if ($recipient_type != RecipientType::KYC)
        {
            return false;
        }

        //$attributes
        $attributes = new RecipientAttributeCollection();

        if( $full_name )
        {
            $attributes->addData((new AttributeValue())
                ->setAttribute((new Attribute())->setCode(AttributeCode::FULL_NAME))
                ->setValue($full_name));
        }

        if( $relationship )
        {
            $attributes->addData((new AttributeValue())
                ->setAttribute((new Attribute())->setCode(AttributeCode::RELATIONSHIP_TO_SENDER))
                ->setId($relationship));
        }

        if( $nationality )
        {
            $attributes->addData((new AttributeValue())
                ->setAttribute((new Attribute())->setCode(AttributeCode::NATIONALITY))
                ->setId($nationality));
        }

        if( $purpose )
        {
            $attributes->addData((new AttributeValue())
                ->setAttribute((new Attribute())->setCode(AttributeCode::PURPOSE_OF_REMITTANCE))
                ->setId($purpose));
        }

        if( $residing_country_code )
        {
            $attributes->addData((new AttributeValue())
                ->setAttribute((new Attribute())->setCode(AttributeCode::RESIDENTIAL_COUNTRY))
                ->setValue($residing_country_code));
        }

        if( $residing_province_code )
        {
            $attributes->addData((new AttributeValue())
                ->setAttribute((new Attribute())->setCode(AttributeCode::RESIDENTIAL_PROVINCE))
                ->setValue($residing_province_code));
        }

        if( $residing_city_code )
        {
            $attributes->addData((new AttributeValue())
                ->setAttribute((new Attribute())->setCode(AttributeCode::RESIDENTIAL_CITY))
                ->setValue($residing_city_code));
        }

        if( $residing_address )
        {
            $attributes->addData((new AttributeValue())
                ->setAttribute((new Attribute())->setCode(AttributeCode::RESIDENTIAL_ADDRESS))
                ->setValue($residing_address));
        }

        if( $residing_postal )
        {
            $attributes->addData((new AttributeValue())
                ->setAttribute((new Attribute())->setCode(AttributeCode::RESIDENTIAL_POST_CODE))
                ->setValue($residing_postal));
        }

        $this->_serv->setUpdatedBy($userId);
        if( $this->_serv->editRecipient($recipient_id , $user_profile_id, $dialing_code, $mobile_number, $alias, $attributes, $photo_image_url) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode());
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

	public function deactivate()
	{
		if( !$userId = $this->_getUserProfileId() )
            return false;

        if( !$this->is_required($this->input->post(), array('recipient_id')))
            return false;
		
		$recipient_id = $this->input->post('recipient_id');
		if( $this->_serv->deactivateRecipient($userId, $recipient_id) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode());
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
	}

    public function addCollectionInfo()
    {
        if( !$userId = $this->_getUserProfileId() )
            return false;

        if( !$this->is_required($this->input->post(), array('recipient_id', 'country_code','collection_info')))
            return false;

        $recipient_id = $this->input->post('recipient_id');
        $country_code = $this->input->post('country_code');
        $collection_info = json_decode($this->input->post('collection_info'),true);

        $this->_serv->setUpdatedBy($userId);
        if( $this->_serv->addCollectionInfo($userId, $recipient_id, $country_code, $collection_info) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode());
            return true;
        }
        
        //dont tell too much information
        if( $this->_serv->getResponseCode() == '2235' )
            $this->_serv->setResponseMessage('Account holder name is invalid.');

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND, NULL, NULL, $this->_serv->getResponseMessage());
        return false;
    }

    public function editCollectionInfo()
    {
        if( !$userId = $this->_getUserProfileId() )
            return false;

        if( !$this->is_required($this->input->post(), array('collection_info_id', 'country_code','collection_info')))
            return false;

        $collection_info_id = $this->input->post('collection_info_id');
        $country_code = $this->input->post('country_code');
        $collection_info = json_decode($this->input->post('collection_info'),true);

        $this->_serv->setUpdatedBy($userId);
        if( $this->_serv->editCollectionInfo($userId, $collection_info_id, $country_code, $collection_info) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode());
            return true;
        }

        //dont tell too much information
        if( $this->_serv->getResponseCode() == '2235' )
            $this->_serv->setResponseMessage('Account holder name is invalid.');
            
        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND, NULL, NULL, $this->_serv->getResponseMessage());
        return false;
    }
}