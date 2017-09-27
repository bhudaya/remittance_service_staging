<?php

use Iapps\RemittanceService\RemittanceRecord\RemittanceRecordRepository;
use Iapps\RemittanceService\RemittanceRecord\RemittanceRecordService;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\Common\Helper\InputValidator;
use Iapps\Common\Microservice\AccountService\SessionType;
use Iapps\RemittanceService\RemittanceRecord\RemittanceRecord;
use Iapps\RemittanceService\Common\FunctionCode;
use Iapps\Common\Microservice\AccountService\AccessType;
use Iapps\RemittanceService\RemittanceRecord\AdminRemittancePayment;
use Iapps\Common\Core\IappsDateTime;

class Remittance_admin extends Admin_Base_Controller{

    private $loginUserProfileId = null;
    
    function __construct()
    {
        parent::__construct();

        $this->load->model('remittancerecord/Remittance_model');
        $repo = new RemittanceRecordRepository($this->Remittance_model);
        $paymentInterface = new AdminRemittancePayment();
        $this->_serv = new RemittanceRecordService($repo, $this->_getIpAddress(), NULL, $paymentInterface);
    }

    public function getRemittanceTransactionDetail()
    {
        if( !$this->loginUserProfileId = $this->_getUserProfileId(FunctionCode::ADMIN_GET_REMITTANCE_TRANSACTION_DETAIL, AccessType::READ) )
            return false;
        
        if( !$this->is_required($this->input->get(),array('id')) ){
            return false;
        }
        $id = $this->input->get('id');
        if( $result = $this -> _serv->getRemittanceTransactionDetail($id) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(),array('result' => $result));
            return true;
        }
        $this->_respondWithCode($this->_serv->getResponseCode(),ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getRemittanceTransactionListByTransIDArr()
    {
        if (!$user_id = $this->_getUserProfileId(FunctionCode::ADMIN_LIST_TRANSACTION_FOR_OTHERS, AccessType::READ))
            return false;

        if( !$this->is_required($this->input->post(), array('transactionIDs')) )
        {
            return false;
        }

        $transactionID_arr = $this->input->post("transactionIDs");
        if(!is_array($transactionID_arr))
        {
            return false;
        }

        if( $object = $this->_serv->getRemittanceListByTransactionIDs($transactionID_arr) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(),array('result' => $object->result->toArray(), 'total' => $object->total));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
}