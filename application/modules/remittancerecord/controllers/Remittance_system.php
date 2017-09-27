<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
use \Iapps\RemittanceService\RemittanceRecord\RemittanceRecordRepository;
use \Iapps\RemittanceService\RemittanceRecord\RemittanceRecordService;
use Iapps\Common\Core\IappsDateTime;
use \Iapps\Common\Helper\ResponseHeader;
use Iapps\RemittanceService\Common\FunctionCode;
use Iapps\Common\Microservice\AccountService\AccessType;
use Iapps\RemittanceService\RemittanceRecord\RemittanceRecord;
use Iapps\Common\Helper\RequestHeader;
use Iapps\RemittanceService\Common\MessageCode;

/**
 * Description of Remittance
 *
 * @author lichao
 */
class Remittance_system extends System_Base_Controller{
    //put your code here
    
    protected $_remittance_record_service;
    
    function __construct() {
        parent::__construct();

        $this->load->model('remittancerecord/Remittance_model');
        $repo = new RemittanceRecordRepository($this->Remittance_model);
        $this->_remittance_record_service = new RemittanceRecordService($repo);

        $this->_service_audit_log->setTableName('iafb_remittance.remittance');
    }

    /*
     * A proper function to get remittance record with access control
     */
    public function getRemittanceRecord()
    {
        if( !$systemUser = $this->_getUserProfileId(FunctionCode::SYSTEM_GET_REMITTANCE_INFO, AccessType::READ) )
            return false;

        if( !$this->is_required($this->input->get(),array('id')) ){
            return false;
        }

        $id = $this->input->get('id');
        RequestHeader::set(ResponseHeader::FIELD_X_AUTHORIZATION, $this->clientToken);
        if( $result = $this -> _remittance_record_service->getRemittanceTransactionDetail($id) )
        {
            $this->_remittance_record_service->setResponseCode(MessageCode::CODE_GET_REMITTANCE_TRANSACTION_SUCCESS);
            $this->_respondWithSuccessCode($this->_remittance_record_service->getResponseCode(),array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_remittance_record_service->getResponseCode(),ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getRemittanceRecordById()
    {
        if( !$systemUser = $this->_getUserProfileId() )
            return false;
        
        if( !$this->is_required($this->input->get(),array('id')) ){
            return false;
        }
        $id = $this->input->get('id');
        
        RequestHeader::set(ResponseHeader::FIELD_X_AUTHORIZATION, $this->clientToken);
        if( $result = $this -> _remittance_record_service->retrieveRemittance($id) )
        {
            $this->_remittance_record_service->setResponseCode(MessageCode::CODE_GET_REMITTANCE_TRANSACTION_SUCCESS);
            $this->_respondWithSuccessCode($this->_remittance_record_service->getResponseCode(),array('result' => $result));
            return true;
        }
        $this->_respondWithCode($this->_remittance_record_service->getResponseCode(),ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getRemittanceInfo()
    {
        if( !$systemUser = $this->_getUserProfileId() )
            return false;

        if( !$this->is_required($this->input->post(), array('id')) )
            return false;

        $remittance_id = $this->input->post('id');

        RequestHeader::set(ResponseHeader::FIELD_X_AUTHORIZATION, $this->clientToken);
        if( $collection = $this->_remittance_record_service->getRemittanceInfoByRemittanceId($remittance_id) )
        {
            $this->_respondWithSuccessCode($this->_remittance_record_service->getResponseCode(), array('result' => $collection));
            return true;
        }

        $this->_respondWithCode($this->_remittance_record_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getSenderRemittanceInfo()
    {
        if( !$systemUser = $this->_getUserProfileId() )
            return false;

        if( !$this->is_required($this->input->post(), array('sender_user_profile_id','date_from','date_to')) )
            return false;

        $sender_user_profile_id = $this->input->post('sender_user_profile_id');
        $date_from = $this->input->post('date_from');
        $date_to = $this->input->post('date_to');

        $record = new RemittanceRecord();
        $record->setSenderUserProfileId($sender_user_profile_id);

        RequestHeader::set(ResponseHeader::FIELD_X_AUTHORIZATION, $this->clientToken);
        if( $collection = $this->_remittance_record_service->getSenderRemittanceInfo($record,$date_from,$date_to) )
        {
            $this->_respondWithSuccessCode($this->_remittance_record_service->getResponseCode(), array('result' => $collection->result, 'total' => $collection->total));
            return true;
        }

        $this->_respondWithCode($this->_remittance_record_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getRecipientRemittanceInfo()
    {
        if( !$systemUser = $this->_getUserProfileId() )
            return false;

        if( !$this->is_required($this->input->post(), array('recipient_id','date_from','date_to')) )
            return false;

        $recipient_id = $this->input->post('recipient_id');
        $date_from = $this->input->post('date_from');
        $date_to = $this->input->post('date_to');


        $record = new RemittanceRecord();
        $record->getRecipient()->setId($recipient_id);
        RequestHeader::set(ResponseHeader::FIELD_X_AUTHORIZATION, $this->clientToken);

        if( $collection = $this->_remittance_record_service->getRecipientRemittanceInfo($record,$date_from,$date_to) )
        {
            $this->_respondWithSuccessCode($this->_remittance_record_service->getResponseCode(), array('result' => $collection->result, 'total' => $collection->total));
            return true;
        }

        $this->_respondWithCode($this->_remittance_record_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
}