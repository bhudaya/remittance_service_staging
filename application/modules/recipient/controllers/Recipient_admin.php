<?php

use Iapps\Common\Helper\ResponseHeader;
use Iapps\Common\Helper\InputValidator;
use Iapps\Common\Microservice\AccountService\SessionType;
use Iapps\RemittanceService\Common\FunctionCode;
use Iapps\Common\Microservice\AccountService\AccessType;
use Iapps\Common\Core\IappsDateTime;
use Iapps\RemittanceService\Recipient\RecipientRepository;
use Iapps\RemittanceService\Recipient\RecipientService;
use Iapps\Common\Core\IpAddress;

class Recipient_admin extends Admin_Base_Controller{

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

    public function getRecipientDetail()
    {
        if( !$adminId = $this->_getUserProfileId(FunctionCode::ADMIN_GET_REMITTANCE_TRANSACTION_DETAIL, AccessType::READ) )
            return false;

        $this->_recipient_serv->setUpdatedBy($adminId);

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
}