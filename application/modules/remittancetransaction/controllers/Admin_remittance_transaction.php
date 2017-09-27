<?php

use Iapps\Common\Transaction\TransactionHistoryRepository;
use Iapps\Common\Transaction\TransactionHistoryService;
use Iapps\Common\Core\IpAddress;
use Iapps\Common\Helper\ResponseHeader;

use Iapps\Common\Helper\InputValidator;
use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\Core\S3FileUrl;


use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransaction;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransactionRepository;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransactionService;

use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransactionItem;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransactionItemRepository;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransactionItemService;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransactionItemServiceFactory;

use Iapps\Common\SystemCode\SystemCode;
use Iapps\Common\SystemCode\SystemCodeService;
use Iapps\RemittanceService\Common\SystemCodeServiceFactory;

use Iapps\Common\Helper\DateTimeHelper;
use Iapps\RemittanceService\Common\FunctionCode;
use Iapps\Common\Microservice\AccountService\AccessType;
use Iapps\Common\Microservice\DeliveryService\DeliveryClient;

use Iapps\RemittanceService\RemittanceRecord\AdminRemittancePayment;

class Admin_remittance_transaction extends Admin_Base_Controller
{
    protected $_service;

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
        $this->_service->setDeliveryClient(DeliveryClient::ADMIN);

    }

    public function getTransactionListForUserByRefIDArr()
    {
        //if (!$user_id = $this->_getUserProfileId(FunctionCode::ADMIN_LIST_REMITTANCE_TRANSACTION_FOR_OTHERS, AccessType::READ)) {
        if (!$user_id = $this->_getUserProfileId(FunctionCode::ADMIN_LIST_TRANSACTION_FOR_OTHERS, AccessType::READ)) {

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
        //if (!$user_id = $this->_getUserProfileId(FunctionCode::ADMIN_LIST_REMITTANCE_TRANSACTION_FOR_OTHERS, AccessType::READ)) {
        if (!$user_id = $this->_getUserProfileId(FunctionCode::ADMIN_LIST_TRANSACTION_FOR_OTHERS, AccessType::READ)) {

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


    public function getTransactionHistoryDetailByRefId()
    {
        if (!$user_id = $this->_getUserProfileId(FunctionCode::ADMIN_GET_REMITTANCE_TRANSACTION_DETAIL, AccessType::READ)) {
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
        //if (!$user_id = $this->_getUserProfileId(FunctionCode::ADMIN_LIST_REMITTANCE_TRANSACTION_FOR_OTHERS, AccessType::READ)) {
        if (!$user_id = $this->_getUserProfileId(FunctionCode::ADMIN_LIST_TRANSACTION_FOR_OTHERS, AccessType::READ)) {
                return false;
        }

        $user_profile_id = $this->input->post("user_profile_id");

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

    public function voidTransaction()
    {
        if (!$user_id = $this->_getUserProfileId(FunctionCode::ADMIN_VOID, AccessType::WRITE)) {
            return false;
        }

        if( !$this->is_required($this->input->post(), array('transaction_id')) )
        {
            return false;
        }

        $transaction_id = $this->input->post('transaction_id');
        $adminPaymentInterface = new AdminRemittancePayment();
        if( $result = $this->_service->void($transaction_id, $adminPaymentInterface) )
        {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(),array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
}