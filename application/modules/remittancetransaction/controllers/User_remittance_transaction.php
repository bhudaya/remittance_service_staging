<?php

use Iapps\RemittanceService\RemittanceRecord\RemittanceRecordRepository;
use Iapps\RemittanceService\RemittanceRecord\RemittanceRecordService;
use Iapps\RemittanceService\RemittanceTransaction\PaymentInfoValidator;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\Common\Microservice\AccountService\FunctionCode;
use Iapps\Common\Microservice\AccountService\AccessType;
use Iapps\Common\Microservice\AccountService\SessionType;


use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransactionItemRepository;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransactionItemService;

use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransactionRepository;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransactionService;

use Iapps\Common\SystemCode\SystemCode;
use Iapps\Common\SystemCode\SystemCodeService;
use Iapps\RemittanceService\Common\SystemCodeServiceFactory;

use Iapps\Common\Core\IappsDateTime;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransaction;


class User_remittance_transaction extends User_Base_Controller{

    protected $_remittance_service;

    const LIST_REMITTANCE_TRANSACTION = 'list_remittance_transaction';


    function __construct()
    {
        parent::__construct();

        $this->load->model('remittancetransaction/remittance_transaction_item_model');
        $repoItem = new RemittanceTransactionItemRepository($this->remittance_transaction_item_model);
        $this->_remittance_transaction_item_service = new RemittanceTransactionItemService($repoItem);

        $this->_system_code_service = SystemCodeServiceFactory::build();

        $this->load->model('remittancetransaction/remittance_transaction_model');
        $repo = new RemittanceTransactionRepository($this->remittance_transaction_model);
        $this->_service = new RemittanceTransactionService($repo, $this->_remittance_transaction_item_service, $this->_system_code_service);
    }

    public function getTransactionHistoryListByDate()
    {
        if (!$user_id = $this->_getUserProfileId()) {
                return false;
        }

        $config = new \Iapps\Common\Transaction\Transaction();

        $this->_service->setUpdatedBy($user_id);

        $page = $this->input->get("page");
        $limit = $this->input->get("limit");

        $date_from= $this->input->get('date_from') ? $this->input->get('date_from') : NULL;
        if ($date_from){
            $config->setDateFrom(IappsDateTime::fromString($date_from. ' 00:00:00' ));
        }
        $date_to= $this->input->get('date_to') ? $this->input->get('date_to') : NULL;
        if ($date_to){
            $config->setDateTo(IappsDateTime::fromString($date_to. ' 23:59:59' ));
        }

        $config->setUserProfileId($user_id);
        $config->setTransactionID($this->input->get("transactionID"));

        $page = $this->_getPage();
        $limit = $this->_getLimit();

        if( $object = $this->_service->getTransactionHistoryListByDate($config, $limit, $page ) )
        {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(),array('result' => $object->result, 'total' => $object->total));
            return true;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getTransactionHistoryDetailByTransactionId()
    {
        if (!$user_id = $this->_getUserProfileId()) {
                return false;
        }

        if (!$this->is_required($this->input->get(), array('transaction_id'))) {
            return false;
        }

        $page = $this->_getPage();
        $limit = $this->_getLimit();

        $transaction_id = $this->input->get("transaction_id");

        $transaction = new RemittanceTransaction();
        $transaction->setId($transaction_id);
        $this->_service->setUpdatedBy($user_id);

        if ($object = $this->_service->getTransactionDetail($transaction, $limit, $page)) {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $object));
            return true;
        }
        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }


    public function getTransactionHistoryDetailByRefId()
    {
        if (!$user_id = $this->_getUserProfileId()) {
                return false;
        }

        if (!$this->is_required($this->input->get(), array('transactionID'))) {
            return false;
        }

        $page = $this->_getPage();
        $limit = $this->_getLimit();

        $transactionID = $this->input->get("transactionID");

        $transaction = new \Iapps\Common\Transaction\Transaction();
        $transaction->setTransactionID($transactionID);
        $this->_service->setUpdatedBy($user_id);

        if ($object = $this->_service->getTransactionDetail($transaction, $limit, $page)) {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $object));
            return true;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getTransactionListByRefIDArr()
    {

        $user_profile_id = $this->input->post("user_profile_id");

        if (!$user_id = $this->_getUserProfileId()) {

            return false;
        }

        //print_r($user_id);

        $config = new \Iapps\Common\Transaction\Transaction();
        $this->_service->setUpdatedBy($user_id);

        $page = $this->input->post("page");
        $limit = $this->input->post("limit");

        $config->setUserProfileId($user_profile_id);

        $transactionIDs = $this->input->post("transactionIDs");



        $page = $this->_getPage();
        $limit = $this->_getLimit();

        if( $object = $this->_service->getTransactionListForUserByRefIDArr($config, $transactionIDs) )
        {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(),array('result' => $object->result->toArray(), 'total' => $object->total));
            return true;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

}