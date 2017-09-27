<?php

use Iapps\RemittanceService\Recipient\AgentRecipientServiceFactory;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\RemittanceService\Recipient\RecipientType;
use Iapps\Common\Helper\InputValidator;
use Iapps\RemittanceService\Attribute\RecipientAttributeCollection;
use Iapps\RemittanceService\Attribute\AttributeValue;
use Iapps\RemittanceService\Attribute\Attribute;
use Iapps\RemittanceService\Attribute\AttributeCode;
use Iapps\Common\Core\IpAddress;

class Recipient_agent extends Agent_Base_Controller{

    function __construct()
    {
        parent::__construct();

        //load v2 service
        $this->_serv = AgentRecipientServiceFactory::build();
        $this->_serv->setIpAddress(IpAddress::fromString($this->_getIpAddress()));
    }

    public function getList()
    {
        if( !$agent_id = $this->_getUserProfileId() )
            return false;

        if( !$this->is_required($this->input->get(), array('user_profile_id') ) )
            return false;
        
        $service_provider_id = NULL;
        if( $mainAgent = $this->_getMainAgent() )
            $service_provider_id = $mainAgent->getId();

        $user_profile_id = $this->input->get('user_profile_id');
        $payment_code = $this->input->get('payment_code') ? $this->input->get('payment_code') : NULL;  //this will filter collection infos if given

        $this->_serv->setUpdatedBy($agent_id);
        $this->_serv->setPaymentCode($payment_code);
        if( $result = $this->_serv->getRecipientList($user_profile_id, $service_provider_id) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getDetail()
    {
        if( !$agent_id = $this->_getUserProfileId() )
            return false;

        if( !$this->is_required($this->input->get(), array('user_profile_id', 'recipient_id') ) )
            return false;

        $user_profile_id = $this->input->get('user_profile_id');
        $recipient_id = $this->input->get('recipient_id');
        $payment_code = $this->input->get('payment_code') ? $this->input->get('payment_code') : NULL;  //this will filter collection infos if given

        //no OTP required as per CR PDM-A001-FS-003-CR001-Rev 0.1.docx
        //if( !$this->_checkUserAuthorization($user_profile_id) )
        //    return false;

        $this->_serv->setUpdatedBy($agent_id);
        $this->_serv->setUserProfileId($user_profile_id);
        $this->_serv->setPaymentCode($payment_code);
        if( $result = $this->_serv->getRecipientDetail($recipient_id) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function listCollectionInfo()
    {
        if( !$agent_id = $this->_getUserProfileId() )
            return false;

        if( !$this->is_required($this->input->get(), array('user_profile_id', 'recipient_id') ) )
            return false;

        $user_profile_id = $this->input->get('user_profile_id');
        $recipient_id = $this->input->get('recipient_id');
        $payment_code = $this->input->get('payment_code') ? $this->input->get('payment_code') : NULL;  //this will filter collection infos if given

        $this->_serv->setUpdatedBy($agent_id);
        $this->_serv->setUserProfileId($user_profile_id);
        $this->_serv->setPaymentCode($payment_code);
        if( $result = $this->_serv->listCollectionInfo($recipient_id) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function add()
    {
        if( !$agent_id = $this->_getUserProfileId() )
            return false;

        if( !$this->is_required($this->input->post(),
            array('user_profile_id', 'alias','dialing_code','mobile_number','full_name',
                  'relationship_to_sender','nationality','remittance_purpose',
                  'residing_country_code', 'residing_province_code',
                  'residing_city_code', 'residing_address')))
            return false;

        $recipient_type = RecipientType::NON_MEMBER;
        $country_code = $this->input->post('country_code') ? $this->input->post('country_code') : NULL;
        $collection_option = $this->input->post('option') ? json_decode($this->input->post('option'), true) : array();
        $user_profile_id = $this->input->post('user_profile_id');
        $alias = $this->input->post('alias');
        $dialing_code = $this->input->post('dialing_code');
        $mobile_number = $this->input->post('mobile_number');

        //$attributes
        $attributes = new RecipientAttributeCollection();

        $attributes->addData((new AttributeValue())
                ->setAttribute((new Attribute())->setCode(AttributeCode::FULL_NAME))
                ->setValue($this->input->post('full_name')));


        $attributes->addData((new AttributeValue())
                ->setAttribute((new Attribute())->setCode(AttributeCode::RELATIONSHIP_TO_SENDER))
                ->setId($this->input->post('relationship_to_sender')));

        $attributes->addData((new AttributeValue())
                ->setAttribute((new Attribute())->setCode(AttributeCode::NATIONALITY))
                ->setId($this->input->post('nationality')));

        $attributes->addData((new AttributeValue())
                ->setAttribute((new Attribute())->setCode(AttributeCode::PURPOSE_OF_REMITTANCE))
                ->setId($this->input->post('remittance_purpose')));

        $attributes->addData((new AttributeValue())
                ->setAttribute((new Attribute())->setCode(AttributeCode::RESIDENTIAL_COUNTRY))
                ->setValue($this->input->post('residing_country_code')));

        $attributes->addData((new AttributeValue())
                ->setAttribute((new Attribute())->setCode(AttributeCode::RESIDENTIAL_PROVINCE))
                ->setValue($this->input->post('residing_province_code')));

        $attributes->addData((new AttributeValue())
                ->setAttribute((new Attribute())->setCode(AttributeCode::RESIDENTIAL_CITY))
                ->setValue($this->input->post('residing_city_code')));

        $attributes->addData((new AttributeValue())
                ->setAttribute((new Attribute())->setCode(AttributeCode::RESIDENTIAL_ADDRESS))
                ->setValue($this->input->post('residing_address')));

        if( $this->input->post('residing_postal_code') )
            $attributes->addData((new AttributeValue())
                ->setAttribute((new Attribute())->setCode(AttributeCode::RESIDENTIAL_POST_CODE))
                ->setValue($this->input->post('residing_postal_code')));

        $this->_serv->setUpdatedBy($agent_id);
        if( $result = $this->_serv->addRecipient(null , $user_profile_id, $dialing_code, $mobile_number, $alias, $recipient_type, $attributes,$country_code, $collection_option) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function edit()
    {
        if( !$agent_id = $this->_getUserProfileId() )
            return false;

        if( !$this->is_required($this->input->post(),
            array('user_profile_id', 'recipient_id', 'alias','dialing_code','mobile_number','full_name',
                'relationship_to_sender','nationality',
                'remittance_purpose',
                'residing_country_code', 'residing_province_code',
                'residing_city_code', 'residing_address')))
            return false;

        $user_profile_id = $this->input->post('user_profile_id');
        $recipient_id = $this->input->post('recipient_id');
        $alias = $this->input->post('alias');
        $dialing_code = $this->input->post('dialing_code');
        $mobile_number = $this->input->post('mobile_number');

        //no user authorization needed as per PDM-A001-FS-003-CR003 v0.8
        //if( !$this->_checkUserAuthorization($user_profile_id) )
        //    return false;

        //$attributes
        $attributes = new RecipientAttributeCollection();

        $attributes->addData((new AttributeValue())
            ->setAttribute((new Attribute())->setCode(AttributeCode::FULL_NAME))
            ->setValue($this->input->post('full_name')));


        $attributes->addData((new AttributeValue())
            ->setAttribute((new Attribute())->setCode(AttributeCode::RELATIONSHIP_TO_SENDER))
            ->setId($this->input->post('relationship_to_sender')));

        $attributes->addData((new AttributeValue())
            ->setAttribute((new Attribute())->setCode(AttributeCode::NATIONALITY))
            ->setId($this->input->post('nationality')));

        $attributes->addData((new AttributeValue())
            ->setAttribute((new Attribute())->setCode(AttributeCode::PURPOSE_OF_REMITTANCE))
            ->setId($this->input->post('remittance_purpose')));

        $attributes->addData((new AttributeValue())
            ->setAttribute((new Attribute())->setCode(AttributeCode::RESIDENTIAL_COUNTRY))
            ->setValue($this->input->post('residing_country_code')));

        $attributes->addData((new AttributeValue())
            ->setAttribute((new Attribute())->setCode(AttributeCode::RESIDENTIAL_PROVINCE))
            ->setValue($this->input->post('residing_province_code')));

        $attributes->addData((new AttributeValue())
            ->setAttribute((new Attribute())->setCode(AttributeCode::RESIDENTIAL_CITY))
            ->setValue($this->input->post('residing_city_code')));

        $attributes->addData((new AttributeValue())
            ->setAttribute((new Attribute())->setCode(AttributeCode::RESIDENTIAL_ADDRESS))
            ->setValue($this->input->post('residing_address')));

        if( $this->input->post('residing_postal_code') )
            $attributes->addData((new AttributeValue())
                ->setAttribute((new Attribute())->setCode(AttributeCode::RESIDENTIAL_POST_CODE))
                ->setValue($this->input->post('residing_postal_code')));

        $this->_serv->setUpdatedBy($agent_id);
        if( $this->_serv->editRecipient($recipient_id , $user_profile_id, $dialing_code, $mobile_number, $alias, $attributes) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode());
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

	public function deactivate()
	{
		if( !$agent_id = $this->_getUserProfileId() )
            return false;		

        if( !$this->is_required($this->input->post(), array('user_profile_id', 'recipient_id')))
            return false;
		
		$user_id = $this->input->post('user_profile_id');
		$recipient_id = $this->input->post('recipient_id');
		$this->_serv->setUpdatedBy($agent_id);
		if( $this->_serv->deactivateRecipient($user_id, $recipient_id) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode());
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
	}

    public function addCollectionInfo()
    {
        if( !$agentId = $this->_getUserProfileId() )
            return false;

        if( !$this->is_required($this->input->post(), array('user_profile_id','recipient_id', 'country_code','collection_info')))
            return false;

        $user_profile_id = $this->input->post('user_profile_id');
        $recipient_id = $this->input->post('recipient_id');
        $country_code = $this->input->post('country_code');
        $collection_info = json_decode($this->input->post('collection_info'),true);

        //user authorization is not required as per SKYPE
        //[26/5/17, 6:02:29 PM] Phoebe Xie: Hi chun yap, agent app > send overseas > select recipient > add/edit bank account ( collection info), can we remove otp required? I realised its a mistake as we have a OTP required to execute transactions.
        //if( !$this->_checkUserAuthorization($user_profile_id) )
        //    return false;
        
        $this->_serv->setUpdatedBy($agentId);
        if( $this->_serv->addCollectionInfo($user_profile_id, $recipient_id, $country_code, $collection_info) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode());
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND, NULL, NULL, $this->_serv->getResponseMessage());
        return false;
    }

    public function editCollectionInfo()
    {
        if( !$agentId = $this->_getUserProfileId() )
            return false;

        if( !$this->is_required($this->input->post(), array('user_profile_id','collection_info_id', 'country_code','collection_info')))
            return false;

        $user_profile_id = $this->input->post('user_profile_id');
        $collection_info_id = $this->input->post('collection_info_id');
        $country_code = $this->input->post('country_code');
        $collection_info = json_decode($this->input->post('collection_info'),true);

        //user authorization is not required as per SKYPE
        //[26/5/17, 6:02:29 PM] Phoebe Xie: Hi chun yap, agent app > send overseas > select recipient > add/edit bank account ( collection info), can we remove otp required? I realised its a mistake as we have a OTP required to execute transactions.
        //if( !$this->_checkUserAuthorization($user_profile_id) )
        //    return false;

        $this->_serv->setUpdatedBy($agentId);
        if( $this->_serv->editCollectionInfo($user_profile_id, $collection_info_id, $country_code, $collection_info) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode());
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND, NULL, NULL, $this->_serv->getResponseMessage());
        return false;
    }
}