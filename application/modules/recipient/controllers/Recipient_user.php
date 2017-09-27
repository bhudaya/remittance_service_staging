<?php

use Iapps\RemittanceService\Recipient\RecipientRepository;
use Iapps\RemittanceService\Recipient\RecipientService;
use Iapps\RemittanceService\Recipient\LocalRecipientService;
use Iapps\Common\Core\IpAddress;
use Iapps\Common\Microservice\AccountService\FunctionCode;
use Iapps\Common\Microservice\AccountService\AccessType;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\RemittanceService\Attribute\AttributeCode;
use Iapps\RemittanceService\Recipient\RecipientType;
use Iapps\RemittanceService\Common\RecipientPhotoImageS3Uploader;
use Iapps\Common\Helper\GuidGenerator;
use Iapps\RemittanceService\Common\MessageCode;
use Iapps\RemittanceService\Attribute\RecipientAttributeCollection;
use Iapps\RemittanceService\Attribute\AttributeValue;
use Iapps\RemittanceService\Attribute\Attribute;
use Iapps\Common\Helper\InputValidator;

class Recipient_user extends Base_Controller{

    protected $_recipient_serv;
    function __construct()
    {
        parent::__construct();

        $this->load->model('recipient/Recipient_model');
        $repo = new RecipientRepository($this->Recipient_model);
        $this->_recipient_serv = new RecipientService($repo);

        $this->_recipient_serv->setIpAddress(IpAddress::fromString($this->_getIpAddress()));
        
        $this->_service_audit_log->setTableName('iafb_remittance.recipient');
    }

    //override
    protected function _get_admin_id($function = NULL, $access_type = NULL)
    {
        return $this->_getUserProfileId();
    }

    public function getUserRecipientList()
    {
        if( !$admin_id = $this->_get_admin_id() )
        {
            return false;
        }

        // $admin_id = '65faa8b1-3b58-4627-966e-1dae0e955ae6';
        $this->_recipient_serv->setUpdatedBy($admin_id);

        $user_profile_id  = $admin_id;

        if( $result = $this->_recipient_serv->getRecipientList($user_profile_id) )
        {
            $this->_respondWithSuccessCode($this->_recipient_serv->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_recipient_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getLocalRecipientList()
    {
        if( !$user_id = $this->_getUserProfileId() )
            return false;

        $repo = new RecipientRepository($this->Recipient_model);
        $this->_recipient_serv = new LocalRecipientService($repo, $this->_getIpAddress());
        $this->_recipient_serv->setUpdatedBy($user_id);

        if( $result = $this->_recipient_serv->getRecipientList($user_id) )
        {
            $this->_respondWithSuccessCode($this->_recipient_serv->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_recipient_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function addUserRecipient()
    {
        if( !$admin_id = $this->_get_admin_id() )
        {
            return false;
        }

        // $admin_id = "65faa8b1-3b58-4627-966e-1dae0e955ae6";
        $this->_recipient_serv->setUpdatedBy($admin_id);

        if( !$this->is_required($this->input->post(), array('recipient_type') ) )
            return false;

        $recipient_type = $this->input->post('recipient_type');
        $recipient_id  = $this->input->post('id');

        if ($recipient_type == RecipientType::NON_MEMBER) {
            
            if( !$this->is_required($this->input->post(), array('alias','dialing_code','mobile_number','full_name',
                                                                'relationship_to_sender','nationality')))
                return false;
        }
        if ($recipient_type == RecipientType::NON_KYC) {

            if( !$this->is_required($this->input->post(), array('alias','full_name',
                                                    'relationship_to_sender', 'user_profile_id')))
                 return false;
        }

        if ($recipient_type == RecipientType::KYC) {

            if( !$this->is_required($this->input->post(), array('alias','relationship_to_sender', 'user_profile_id')))
                 return false;
        }

        if( !$this->is_required($this->input->post(), array('payment_code','remittance_purpose') ) )
            return false;


        $payment_code = $this->input->post('payment_code');

        if ($payment_code == 'BT1' || $payment_code == 'BT3' || $payment_code=='BT5') {
            // is bank transfer , bank_info is  required.
            if( !$this->is_required($this->input->post(), array('bank_info') ) )
                return false;

            if( !$bank_info = json_decode($this->input->post('bank_info'), true) )
            {
                $errMsg = InputValidator::getInvalidParamMessage('bank_info');
                $this->_response(InputValidator::constructInvalidParamResponse($errMsg));
                return false;
            }
        }


        $user_profile_id  = $admin_id;
        $alias = $this->input->post('alias');
        $dialing_code = $this->input->post('dialing_code');
        $mobile_number = $this->input->post('mobile_number');
        $bank_info = $this->input->post('bank_info') ? json_decode($this->input->post('bank_info'), true) : array();
        $recipient_user_profile_id = $this->input->post('user_profile_id');
        $photo_image_url = $this->input->post('photo_image_url');

        //$attributes
        $attributes = new RecipientAttributeCollection();

        if( $this->input->post('full_name') )
            $attributes->addData((new AttributeValue())
                                ->setAttribute((new Attribute())->setCode(AttributeCode::FULL_NAME))
                                ->setValue($this->input->post('full_name')));

        if( $this->input->post('id_type') )
            $attributes->addData((new AttributeValue())
                                ->setAttribute((new Attribute())->setCode(AttributeCode::ID_TYPE))
                                ->setId(explode(',',$this->input->post('id_type'))[0])
                                ->setValue(explode(',',$this->input->post('id_type'))[1]));

        if( $this->input->post('id_number') )
            $attributes->addData((new AttributeValue())
                                ->setAttribute((new Attribute())->setCode(AttributeCode::ID_NUMBER))
                                ->setValue($this->input->post('id_number')));

        if( $this->input->post('nationality') )
            $attributes->addData((new AttributeValue())
                                ->setAttribute((new Attribute())->setCode(AttributeCode::NATIONALITY))
                                ->setId(explode(',',$this->input->post('nationality'))[0])
                                ->setValue(explode(',',$this->input->post('nationality'))[1]));

        if( $this->input->post('relationship_to_sender'))
            $attributes->addData((new AttributeValue())
                                ->setAttribute((new Attribute())->setCode(AttributeCode::RELATIONSHIP_TO_SENDER))
                                ->setId(explode(',',$this->input->post('relationship_to_sender'))[0])
                                ->setValue(explode(',',$this->input->post('relationship_to_sender'))[1]));

        if( $this->input->post('income_source') )
            $attributes->addData((new AttributeValue())
                                ->setAttribute((new Attribute())->setCode(AttributeCode::SOURCE_OF_INCOME))
                                ->setId(explode(',',$this->input->post('income_source'))[0])
                                ->setValue(explode(',',$this->input->post('income_source'))[1]));
        
        if( $this->input->post('remittance_purpose') )
            $attributes->addData((new AttributeValue())
                                ->setAttribute((new Attribute())->setCode(AttributeCode::PURPOSE_OF_REMITTANCE))
                                ->setId(explode(',',$this->input->post('remittance_purpose'))[0])
                                ->setValue(explode(',',$this->input->post('remittance_purpose'))[1]));

        if( $this->input->post('residing_country_code') )
            $attributes->addData((new AttributeValue())
                                ->setAttribute((new Attribute())->setCode(AttributeCode::RESIDENTIAL_COUNTRY))
                                ->setValue($this->input->post('residing_country_code')));

        if( $this->input->post('residing_province_code') )
            $attributes->addData((new AttributeValue())
                                ->setAttribute((new Attribute())->setCode(AttributeCode::RESIDENTIAL_PROVINCE))
                                ->setValue($this->input->post('residing_province_code')));

        if( $this->input->post('residing_city_code') )
            $attributes->addData((new AttributeValue())
                                ->setAttribute((new Attribute())->setCode(AttributeCode::RESIDENTIAL_CITY))
                                ->setValue($this->input->post('residing_city_code')));

        if( $this->input->post('residing_address') )
            $attributes->addData((new AttributeValue())
                                ->setAttribute((new Attribute())->setCode(AttributeCode::RESIDENTIAL_ADDRESS))
                                ->setValue($this->input->post('residing_address')));

        if( $this->input->post('residing_postal_code') )
            $attributes->addData((new AttributeValue())
                                ->setAttribute((new Attribute())->setCode(AttributeCode::RESIDENTIAL_POST_CODE))
                                ->setValue($this->input->post('residing_postal_code')));

        if( $result = $this->_recipient_serv->addRecipient($recipient_id , $user_profile_id, $dialing_code, $mobile_number, $alias, $recipient_type, $attributes,$payment_code, $bank_info, $photo_image_url, $recipient_user_profile_id) )
        {
            $this->_respondWithSuccessCode($this->_recipient_serv->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_recipient_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function addLocalRecipient()
    {
        if( !$user_id = $this->_getUserProfileId() )
            return false;

        if( !$this->is_required($this->input->post(), array('recipient_type', 'dialing_code', 'mobile_number', 'alias') ) )
            return false;

        $repo = new RecipientRepository($this->Recipient_model);
        $this->_recipient_serv = new LocalRecipientService($repo, $this->_getIpAddress());
        $this->_recipient_serv->setUpdatedBy($user_id);


        $recipient_type = $this->input->post('recipient_type');
        $alias = $this->input->post('alias');
        $dialing_code = $this->input->post('dialing_code');
        $mobile_number = $this->input->post('mobile_number');
        $photo_image_url = $this->input->post('photo_image_url');
        $recipient_id  = $this->input->post('id') ? $this->input->post('id') : NULL;

        if( $result = $this->_recipient_serv->addRecipient($recipient_id , $user_id, $dialing_code, $mobile_number, $alias, $recipient_type, new RecipientAttributeCollection(), null, array(), $photo_image_url) )
        {
            $this->_respondWithSuccessCode($this->_recipient_serv->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_recipient_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getUserRecipientDetail()
    {
        if( !$admin_id = $this->_get_admin_id() )
        {
            return false;
        }

        // $admin_id = '65faa8b1-3b58-4627-966e-1dae0e955ae6';

        $this->_recipient_serv->setUpdatedBy($admin_id);

        if( !$this->is_required($this->input->post(), array('recipient_id') ) )
            return false;

        $recipient_id  = $this->input->post('recipient_id');

        if( $result = $this->_recipient_serv->getRecipientDetail($recipient_id) )
        {
            $this->_respondWithSuccessCode($this->_recipient_serv->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_recipient_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function uploadPhoto()
    {
        if( !$admin_id = $this->_get_admin_id() )
        {
            return false;
        }

        if( !$this->is_required($_FILES, array('photo')) )
        {
            return false;
        }
        $s3Image = new RecipientPhotoImageS3Uploader(GuidGenerator::generate());
        if( $s3Image->uploadtoS3('photo') )
        {
            $this->_respondWithSuccessCode(MessageCode::CODE_PHOTO_UPLOAD_SUCCESS, array('result' => $s3Image->getFileName()));
        }
        else
        {
            $this->_respondWithCode(MessageCode::CODE_PHOTO_UPLOAD_FAILED);
            return false;
        }

        $this->_respondWithCode($this->_recipient_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
}