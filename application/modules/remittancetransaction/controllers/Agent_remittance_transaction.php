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
use Iapps\Common\Microservice\DeliveryService\DeliveryClient;


class Agent_remittance_transaction extends Base_Controller{

    protected $_remittance_service;

    const LIST_REMITTANCE_TRANSACTION = 'list_remittance_transaction';


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

        $this->_service->setDeliveryClient(DeliveryClient::AGENT);





    }

    //override
    protected function _get_admin_id($function = NULL, $access_type = NULL)
    {
        return $this->_getUserProfileId(FunctionCode::AGENT_FUNCTIONS, AccessType::WRITE, SessionType::TRANSACTION);
    }

    /*
     * sample param
     * collection_info = '{
                            "payment_code":"BT1",
                            "amount":100000,
                            "option":{"bank_code":"001","bank_account":"12345678"}
                          }'
     * payment_info = '{
                        "payment_code":"CA1",
                        "amount":21.95
                        }'
     */
    /*
    public function purchaseRemittance()
    {
        if( !$admin_id = $this->_get_admin_id() )
        {
            return false;
        }
        $this->_remittance_service->setUpdatedBy($admin_id);

        if( !$this->is_required($this->input->post(), array('user_profile_id',
                                                            'recipient_id',
                                                            'remittance_config_id',
                                                            'collection_info',
                                                            'payment_info')) )
        {
            return false;
        }

        $user_profile_id = $this->input->post('user_profile_id');
        $recipient_id = $this->input->post('recipient_id');
        $remittance_config_id = $this->input->post('remittance_config_id');
        $collection_info = $this->input->post('collection_info');
        $payment_info = $this->input->post('payment_info');
        $remark = $this->input->post('remark') ? $this->input->post('remark') : NULL;

        $collection_info = json_decode($collection_info, true);
        $payment_info = json_decode($payment_info, true);

        $collection_validator = PaymentInfoValidator::make($collection_info);
        if( $collection_validator->fails() )
            return false;

        $payment_validator = PaymentInfoValidator::make($payment_info);
        if( $payment_validator->fails() )
            return false;

        if( $result = $this->_remittance_service->purchaseRemittance($user_profile_id,
                                                                     $recipient_id,
                                                                     $remittance_config_id,
                                                                     $payment_info,
                                                                     $collection_info,
                                                                     $this->input->get_request_header(ResponseHeader::FIELD_X_AUTHORIZATION),
                                                                     $remark) )
        {
            $this->_respondWithSuccessCode($this->_remittance_service->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_remittance_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }*/


    //---- 29 06 16  

    
    public function getTransactionHistoryList()
    {
        //if (!$user_id = $this->_getUserProfileId(self::LIST_REMITTANCE_TRANSACTION, AccessType::READ)) {
        if (!$user_id = $this->_getUserProfileId()) {
                return false;
        }


        $config = new \Iapps\Common\Transaction\Transaction();
        $config->setUserProfileId($user_id);

        $this->_service->setUpdatedBy($user_id);

        $page = $this->input->get("page");
        $limit = $this->input->get("limit");



        if ($object = $this->_service->getTransactionHistoryList($config, $limit, $page)) {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $object->result->toArray(), 'total' => $object->total));
            return true;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    


    public function getTransactionHistoryListByDate()
    {

        //if (!$user_id = $this->_get_user_id(FunctionCode::LIST_BILL_TRANSACTION, AccessType::READ)) {
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

        //if (!$user_id = $this->_getUserProfileId(self::LIST_REMITTANCE_TRANSACTION, AccessType::READ)) {

        if (!$user_id = $this->_getUserProfileId()) {

                return false;
        }


        //$user_id ="d4a7ce45-8942-4b7b-9744-4a8bd79c87d7";

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

            //print_r($object->transaction);

            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $object->transaction));
            return true;
        }
        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }


    public function getTransactionHistoryDetailByRefId()
    {

        //if (!$user_id = $this->_getUserProfileId(self::LIST_REMITTANCE_TRANSACTION, AccessType::READ)) {
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


    public function getTransactionHistoryUserList()
    {
        //if (!$user_id = $this->_get_user_id(FunctionCode::LIST_BILL_TRANSACTION, AccessType::READ)) {
        if (!$user_id = $this->_getUserProfileId()) {



                return false;
        }

        $page = $this->_getPage();
        $limit = $this->_getLimit();


        $transaction = new \Iapps\Common\Transaction\Transaction();
        $transaction->setUserProfileId($user_id);
        $this->_service->setUpdatedBy($user_id);

        if ($object = $this->_service->getTransactionDetailUser($transaction)) {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $object));
            return true;
        }
        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }



    public function getTransactionHistoryUserListByDate()
    {


        //if (!$user_id = $this->_get_user_id(FunctionCode::LIST_BILL_TRANSACTION, AccessType::READ)) {
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


        if( $object = $this->_service->getTransactionDetailUserByDate($config, $limit, $page ) )
        {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(),array('result' => $object->result->toArray(), 'total' => $object->total));
            return true;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }


    // by ref ID Array
    public function getTransactionListForUserByRefIDArr()
    {

        $agent_id = $this->input->post("agent_id");

        if (!$user_id = $this->_getUserProfileId()) {

                return false;
        }

        //print_r($user_id);

        $config = new \Iapps\Common\Transaction\Transaction();
        $this->_service->setUpdatedBy($agent_id);

        $page = $this->input->post("page");
        $limit = $this->input->post("limit");

        $config->setCreatedBy($agent_id);

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