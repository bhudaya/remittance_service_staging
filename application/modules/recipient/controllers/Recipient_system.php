<?php

use Iapps\Common\Helper\ResponseHeader;
use Iapps\Common\Helper\InputValidator;
use Iapps\Common\Microservice\AccountService\SessionType;
use Iapps\RemittanceService\Common\FunctionCode;
use Iapps\Common\Microservice\AccountService\AccessType;
use Iapps\Common\Core\IappsDateTime;
use Iapps\RemittanceService\Recipient\RecipientRepository;
use Iapps\RemittanceService\Recipient\RecipientServiceV2;
use Iapps\Common\Core\IpAddress;

class Recipient_system extends System_Base_Controller{

    protected $_recipient_serv;
    function __construct()
    {
        parent::__construct();

        $this->load->model('recipient/Recipient_model');
        $repo = new RecipientRepository($this->Recipient_model);
        $this->_recipient_serv = new RecipientServiceV2($repo);

        $this->_recipient_serv->setIpAddress(IpAddress::fromString($this->_getIpAddress()));

        $this->_service_audit_log->setTableName('iafb_remittance.recipient');
    }

    /*
     * A proper function to get with access token
     */
    public function getRecipient()
    {
        if( !$systemUser = $this->_getUserProfileId(FunctionCode::SYSTEM_GET_REMITTANCE_INFO, AccessType::READ) )
            return false;

        if( !$this->is_required($this->input->get(), array('recipient_id') ) )
            return false;

        $recipient_id  = $this->input->get('recipient_id');

        if( $result = $this->_recipient_serv->getRecipientDetailWithUserInfo($recipient_id) )
        {
            $this->_respondWithSuccessCode($this->_recipient_serv->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_recipient_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getRecipientDetail()
    {
        if( !$this->is_required($this->input->get(), array('recipient_id') ) )
            return false;

        $recipient_id  = $this->input->get('recipient_id');

        if( $result = $this->_recipient_serv->getRecipientDetail($recipient_id, true) )
        {
            $this->_respondWithSuccessCode($this->_recipient_serv->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_recipient_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    
    public function getRecipientDetailByIds()
    {
        if( !$this->is_required($this->input->post(), array('recipient_ids') ) )
            return false;

        $recipient_ids  = $this->input->post('recipient_ids');
        
        $recipient_ids = json_decode($recipient_ids, true);
//        $recipient_ids = explode(',', $recipient_ids);
        if(!is_array($recipient_ids))
            $recipient_ids = array($recipient_ids);

        if( $result = $this->_recipient_serv->getRecipientDetailByIdArray($recipient_ids) )
        {
            $this->_respondWithSuccessCode($this->_recipient_serv->getResponseCode(), array('result' => $result->result, 'total' => $result->total));
            return true;
        }

        $this->_respondWithCode($this->_recipient_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
}