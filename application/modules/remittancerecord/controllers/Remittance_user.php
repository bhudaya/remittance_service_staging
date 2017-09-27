<?php

use Iapps\RemittanceService\RemittanceRecord\RemittanceRecordRepository;
use Iapps\RemittanceService\RemittanceRecord\RemittanceRecordService;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\Common\Helper\InputValidator;
use Iapps\Common\Microservice\AccountService\SessionType;
use Iapps\RemittanceService\RemittanceRecord\RemittanceRecord;
use Iapps\RemittanceService\RemittanceRecord\RemittanceCalculationDirection;
use Iapps\Common\Core\IappsDateTime;

class Remittance_user extends User_Base_Controller{

    function __construct()
    {
        parent::__construct();

        $this->load->model('remittancerecord/Remittance_model');
        $repo = new RemittanceRecordRepository($this->Remittance_model);
        $this->_serv = new RemittanceRecordService($repo, $this->_getIpAddress());
    }

    public function request()
    {
        if( !$user_id = $this->_getUserProfileId(NULL, NULL, SessionType::TRANSACTION) )
            return false;// '4fd6ae9b-42bf-4ed2-a3f3-5c1655c7f4dd';

        if( !$this->is_required($this->input->post(), array('recipient_id','remittance_config_id', 'payment_info', 'collection_info')) )
            return false;

        $recipient_id = $this->input->post('recipient_id');
        $remittance_config_id = $this->input->post('remittance_config_id');
        $calculation_dir = $this->input->post('calc_dir') ? $this->input->post('calc_dir') : RemittanceCalculationDirection::DIR_TO;
        $sending_amount = $this->input->post('send_amount') ? $this->input->post('send_amount') : NULL;
        if( !$payment_info = json_decode($this->input->post('payment_info'), true) )
        {
            $errMsg = InputValidator::getInvalidParamMessage('payment_info');
            $this->_response(InputValidator::constructInvalidParamResponse($errMsg));
            return false;
        }
        if( !$collection_info = json_decode($this->input->post('collection_info'), true) )
        {
            $errMsg = InputValidator::getInvalidParamMessage('collection_info');
            $this->_response(InputValidator::constructInvalidParamResponse($errMsg));
            return false;
        }

        /*
        if( !$additional_info = json_decode($this->input->post('additional_info'), true) )
        {
            $errMsg = InputValidator::getInvalidParamMessage('additional_info');
            $this->_response(InputValidator::constructInvalidParamResponse($errMsg));
            return false;
        }*/

        $remark = $this->input->post('remarks') ? $this->input->post('remarks') : NULL;
        $is_home_collection = $this->input->post('is_home_collection') ? (bool)$this->convertStringToBooleanInt($this->input->post('is_home_collection')) : false;

        $this->_serv->setUpdatedBy($user_id);
        $this->_serv->setChannelCode($this->_getChannel());
        $this->_serv->setCalcDirection($calculation_dir);
        $this->_serv->setSendAmount($sending_amount);
        if( $result = $this->_serv->request($user_id, $recipient_id, $remittance_config_id,
                                    $payment_info, $collection_info, $remark, true, $is_home_collection) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array("result" => $result));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND, NULL, NULL, $this->_serv->getResponseMessage());
        return false;
    }

    public function complete()
    {
        if( !$user_id = $this->_getUserProfileId(NULL, NULL, SessionType::TRANSACTION) )
            return false;// '4fd6ae9b-42bf-4ed2-a3f3-5c1655c7f4dd';

        if( !$this->is_required($this->input->post(), array('remittance_id')) )
            return false;

        $remittance_id = $this->input->post('remittance_id');

        $this->_serv->setUpdatedBy($user_id);
        if( $result = $this->_serv->complete($user_id, $remittance_id) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array("result" => $result));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND, NULL, NULL, $this->_serv->getResponseMessage());
        return false;
    }

    public function cancel()
    {
        if( !$user_id = $this->_getUserProfileId(NULL, NULL, SessionType::TRANSACTION) )
            return false;// '4fd6ae9b-42bf-4ed2-a3f3-5c1655c7f4dd';

        if( !$this->is_required($this->input->post(), array('remittance_id')) )
            return false;

        $remittance_id = $this->input->post('remittance_id');

        $this->_serv->setUpdatedBy($user_id);
        if( $this->_serv->cancel($user_id, $remittance_id) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode());
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND, NULL, NULL, $this->_serv->getResponseMessage());
        return false;
    }

    public function getRemittanceList()
    {
        if( !$user_id = $this->_getUserProfileId() )
            return false;

        $this->_serv->setUpdatedBy($user_id);

        $forInternational = $this->input->get('for_international') ? $this->convertStringToBooleanInt($this->input->get('for_international')) : true;

        $record = new RemittanceRecord();
        $record->setSenderUserProfileId($user_id);
        $record->getRecipient()->setRecipientUserProfileId($user_id);
        $page = $this->_getPage();
        $limit = $this->_getLimit();

        if( $collection = $this->_serv->getRemittanceByCreatorAndRecipientCache($record, $limit, $page, $forInternational) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $collection->result, 'total' => $collection->total));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    

}