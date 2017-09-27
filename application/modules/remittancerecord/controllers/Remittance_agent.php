<?php

use Iapps\RemittanceService\RemittanceRecord\AgentRemittanceRecordServiceFactory;
use Iapps\Common\Core\IpAddress;
use Iapps\Common\Microservice\AccountService\SessionType;
use Iapps\RemittanceService\RemittanceRecord\RemittanceCalculationDirection;
use Iapps\Common\Helper\InputValidator;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\RemittanceService\RemittanceRecord\RemittanceRecord;
use Iapps\RemittanceService\RemittanceRecord\RemittanceStatus;

class Remittance_agent extends Agent_Base_Controller{

    function __construct()
    {
        parent::__construct();

        $this->_serv = AgentRemittanceRecordServiceFactory::build();
        $this->_serv->setIpAddress(IpAddress::fromString($this->_getIpAddress()));
    }

    public function request()
    {
        if( !$agentId = $this->_getUserProfileId(NULL, NULL, SessionType::TRANSACTION) )
            return false;

        if( !$this->is_required($this->input->post(), array('user_profile_id','recipient_id','remittance_config_id', 'payment_info', 'collection_info')) )
            return false;

        $user_profile_id = $this->input->post('user_profile_id');
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

        $remark = $this->input->post('remarks') ? $this->input->post('remarks') : NULL;

        if( !$this->_checkUserAuthorization($user_profile_id) )
            return false;

        $this->_serv->setUpdatedBy($agentId);
        $this->_serv->setChannelCode($this->_getChannel());
        $this->_serv->setCalcDirection($calculation_dir);
        $this->_serv->setSendAmount($sending_amount);
        if( $result = $this->_serv->request($user_profile_id, $recipient_id, $remittance_config_id,
            $payment_info, $collection_info, $remark) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array("result" => $result));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND, NULL, NULL, $this->_serv->getResponseMessage());
        return false;
    }

    public function complete()
    {
        if( !$agentId = $this->_getUserProfileId(NULL, NULL, SessionType::TRANSACTION) )
            return false;// '4fd6ae9b-42bf-4ed2-a3f3-5c1655c7f4dd';

        if( !$this->is_required($this->input->post(), array('user_profile_id','remittance_id')) )
            return false;

        $user_profile_id = $this->input->post('user_profile_id');
        $remittance_id = $this->input->post('remittance_id');

        $payment_info = NULL;
        if($this->input->post('payment_info')) {
            if (!$payment_info = json_decode($this->input->post('payment_info'), true)) {
                $errMsg = InputValidator::getInvalidParamMessage('payment_info');
                $this->_response(InputValidator::constructInvalidParamResponse($errMsg));
                return false;
            }
        }

        if( !$this->_checkUserAuthorization($user_profile_id) )
            return false;

        if( !$this->_checkLocation() )
            return false;

        $this->_serv->setUpdatedBy($agentId);
        if( $result = $this->_serv->complete($user_profile_id, $remittance_id, $payment_info) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array("result" => $result));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND, NULL, NULL, $this->_serv->getResponseMessage());
        return false;
    }

    public function cancel()
    {
        if( !$agentId = $this->_getUserProfileId(NULL, NULL, SessionType::TRANSACTION) )
            return false;// '4fd6ae9b-42bf-4ed2-a3f3-5c1655c7f4dd';

        if( !$this->is_required($this->input->post(), array('user_profile_id','remittance_id')) )
            return false;

        $user_profile_id = $this->input->post('user_profile_id');
        $remittance_id = $this->input->post('remittance_id');

        $this->_serv->setUpdatedBy($agentId);
        if( $this->_serv->cancel($user_profile_id, $remittance_id) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode());
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND, NULL, NULL, $this->_serv->getResponseMessage());
        return false;
    }


    public function getPendingRemittanceList()
    {
        if( !$agentId = $this->_getUserProfileId() )
            return false;

        if( !$this->is_required($this->input->get(), array('user_profile_id')) )
            return false;

        $forInternational = $this->input->get('for_international') ? $this->convertStringToBooleanInt($this->input->get('for_international')) : true;
        $user_profile_id = $this->input->get('user_profile_id');

        $record = new RemittanceRecord();
        $record->setSenderUserProfileId($user_profile_id);
        $record->getStatus()->setCode(RemittanceStatus::PENDING_PAYMENT);

        $page = $this->_getPage();
        $limit = $this->_getLimit();

        if( $collection = $this->_serv->getRemittanceByCreatorAndRecipient($record, $limit, $page, $forInternational) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $collection->result, 'total' => $collection->total));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

}