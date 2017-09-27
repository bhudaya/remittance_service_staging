<?php

use Iapps\Common\Microservice\AccountService\FunctionCode;
use Iapps\Common\Microservice\AccountService\AccessType;
use Iapps\Common\DepositTracker\DepositTrackerHistoryRepository;
use Iapps\Common\DepositTracker\DepositTrackerHistoryService;
use Iapps\Common\Core\IpAddress;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\DepositTracker\DepositTrackerHistoryStatus;
use Iapps\Common\DepositTracker\DepositTrackerHistory;
use Iapps\RemittanceService\Common\DepositTrackerServiceFactory;


class Deposit_tracker_history extends Base_Controller
{

    protected $_service;

    function __construct()
    {
//        parent::__construct();
//
//        $deposit_tracker_serv = DepositTrackerServiceFactory::build();
//
//        $this->load->model('common/Deposit_tracker_history_model');
//        $repo = new DepositTrackerHistoryRepository($this->Deposit_tracker_history_model);
//        $this->_service = new DepositTrackerHistoryService($repo, $deposit_tracker_serv);
//
//        $this->_service->setIpAddress(IpAddress::fromString($this->_getIpAddress()));
    }

//    public function getDepositTrackerHistoryList()
//    {
//
//        $page = $this->_getPage();
//        $limit = $this->_getLimit();
//
//        if( $object = $this->_service->getDepositTrackerHistoryList( $limit, $page) )
//        {
//            $this->_respondWithSuccessCode($this->_service->getResponseCode(),array('result' => $object->result->toArray(), 'total' => $object->total));
//            return true;
//        }
//
//        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
//        return false;
//    }

//    public function getDepositTrackerHistoryByDepositTrackerId()
//    {
//
//        $id = $this->input->get("id");
//
//        if( $info = $this->_service->getDepositTrackerHistoryByDepositTrackerId($id) )
//        {
//            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $info));
//            return false;
//        }
//
//        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
//        return false;
//    }

//    public function getDepositTrackerHistoryInfo()
//    {
//
//        if( !$this->is_required($this->input->get(), array('id')) )
//        {
//            return false;
//        }
//
//        $id = $this->input->get("id");
//
//        if( $info = $this->_service->getDepositTrackerHistoryInfo($id) )
//        {
//            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $info));
//            return false;
//        }
//
//        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
//        return false;
//    }


//    public function addDepositTrackerHistory()
//    {
//
//        if( !$this->is_required($this->input->post(), array('deposit_tracker_id', 'amount','ref_transaction_id', 'last_balance', 'remarks')) )
//        {
//            return false;
//        }
//
//        $deposit_tracker_id = $this->input->post("deposit_tracker_id");
//        $amount             = $this->input->post("amount");
//        $ref_transaction_id = $this->input->post("ref_transaction_id");
//        $last_balance       = $this->input->post("last_balance");
//        $remarks            = $this->input->post("remarks");
//
//        $config = new DepositTrackerHistory();
//        $config->setDepositTrackerId($deposit_tracker_id);
//        $config->setAmount($amount);
//        $config->setRefTransactionId($ref_transaction_id);
//        $config->setLastBalance($last_balance);
//        $config->setRemarks($remarks);
//
//
//        if( $config = $this->_service->addDepositTrackerHistory($config) )
//        {
//            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $config));
//            return true;
//        }
//
//        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
//        return false;
//    }



}
