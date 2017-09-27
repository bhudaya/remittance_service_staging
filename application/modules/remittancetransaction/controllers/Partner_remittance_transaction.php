<?php

use Iapps\RemittanceService\RemittanceRecord\RemittanceRecordRepository;
use Iapps\RemittanceService\RemittanceRecord\RemittanceRecordService;
use Iapps\RemittanceService\RemittanceTransaction\PaymentInfoValidator;
use Iapps\Common\Helper\ResponseHeader;
//use Iapps\Common\Microservice\AccountService\FunctionCode;
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
use Iapps\RemittanceService\Common\FunctionCode;
use Iapps\Common\Microservice\DeliveryService\DeliveryClient;



class Partner_remittance_transaction extends Partner_Base_Controller{

    protected $_remittance_service;

    const LIST_REMITTANCE_TRANSACTION = 'list_remittance_transaction';
    const PARTNER_GET_REMITTANCE_TRANSACTION_DETAIL = 'partner_get_remittance_transaction_detail';
    const PARTNER_FINANCE_GET_REMITTANCE_TRANSACTION_DETAIL = 'partner_fin_get_remittance_transaction_detail';

    function __construct()
    {
        parent::__construct();

        $this->load->model('remittancerecord/Remittance_model');
        $repo = new RemittanceRecordRepository($this->Remittance_model);
        $this->_remittance_service = new RemittanceRecordService($repo, $this->_getIpAddress());



        $this->load->model('remittancetransaction/remittance_transaction_item_model');
        $repoItem = new RemittanceTransactionItemRepository($this->remittance_transaction_item_model);
        $this->_remittance_transaction_item_service = new RemittanceTransactionItemService($repoItem);

        $this->_system_code_service = SystemCodeServiceFactory::build();

        $this->load->model('remittancetransaction/remittance_transaction_model');
        $repo = new RemittanceTransactionRepository($this->remittance_transaction_model);
        $this->_service = new RemittanceTransactionService($repo, $this->_remittance_transaction_item_service, $this->_system_code_service);
        $this->_service->setDeliveryClient(DeliveryClient::PARTNER);

    }

    public function getTransactionHistoryDetailByRefId()
    {

        if (!$user_id = $this->_getUserProfileId(self::PARTNER_GET_REMITTANCE_TRANSACTION_DETAIL, AccessType::READ)) {
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

    public function getRelatedTransaction()
    {
        if (!$user_id = $this->_getUserProfileId(self::PARTNER_GET_REMITTANCE_TRANSACTION_DETAIL, AccessType::READ))
            return false;

        if (!$this->is_required($this->input->get(), array('transaction_id')))
            return false;

        $transaction_id = $this->input->get("transaction_id");

        $transaction = new \Iapps\Common\Transaction\Transaction();
        $transaction->setId($transaction_id);
        $this->_service->setUpdatedBy($user_id);

        if( $object = $this->_service->getRelatedTransaction($transaction) )
        {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $object));
            return true;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getFinanceRemittanceTransactionDetailByRefId()
    {

        if (!$user_id = $this->_getUserProfileId(self::PARTNER_FINANCE_GET_REMITTANCE_TRANSACTION_DETAIL, AccessType::READ)) {
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

        if ($object = $this->_service->getFinanceTransactionDetail($transaction, $limit, $page, $user_id)) {


            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $object));
            return true;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;

    }


    public function getTransactionListForUserByRefIDArr()
    {
        if (!$user_id = $this->_getUserProfileId(FunctionCode::PARTNER_LIST_TRANSACTION_FOR_OTHERS, AccessType::READ)) {

            return false;
        }

        if( !$this->is_required($this->input->post(), array('transactionIDs','agent_id')) )
        {
            return false;
        }

        $transactionID_arr = $this->input->post("transactionIDs");
        if(!is_array($transactionID_arr))
        {
            return false;
        }
        $agent_id = $this->input->post("agent_id");
        $page = $this->_getPage();
        $limit = $this->_getLimit();

        $transaction = new \Iapps\Common\Transaction\Transaction();
        $transaction->setUserProfileId($agent_id);

        if( $object = $this->_service->getTransactionListForUserByRefIDArr($transaction, $transactionID_arr) )
        {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(),array('result' => $object->result->toArray(), 'total' => $object->total));
            return true;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getTransactionHistoryDetailByTransactionId()
    {
        if (!$user_id = $this->_getUserProfileId(FunctionCode::PARTNER_LIST_TRANSACTION_FOR_OTHERS, AccessType::READ)) {

            return false;
        }

        if (!$this->is_required($this->input->get(), array('transaction_id'))) {
            return false;
        }

        $page = $this->_getPage();
        $limit = $this->_getLimit();

        $transaction_id = $this->input->get("transaction_id");

        $transaction = new \Iapps\Common\Transaction\Transaction();
        $transaction->setId($transaction_id);
        $this->_service->setUpdatedBy($user_id);

        if ($object = $this->_service->getTransactionDetail($transaction, $limit, $page)) {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $object));
            return true;
        }
        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }




}