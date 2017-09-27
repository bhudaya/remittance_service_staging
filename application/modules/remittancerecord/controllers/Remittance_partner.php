<?php

use Iapps\RemittanceService\RemittanceRecord\RemittanceRecordRepository;
use Iapps\RemittanceService\RemittanceRecord\PartnerRemittanceRecordService;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\Common\Helper\InputValidator;
use Iapps\Common\Microservice\AccountService\SessionType;
use Iapps\RemittanceService\RemittanceRecord\RemittanceRecord;
use Iapps\RemittanceService\Common\FunctionCode;
use Iapps\Common\Microservice\AccountService\AccessType;
use Iapps\RemittanceService\RemittanceRecord\PartnerRemittancePayment;
use Iapps\Common\Core\IappsDateTime;

class Remittance_partner extends Partner_Base_Controller{
    
    function __construct()
    {
        parent::__construct();

        $this->load->model('remittancerecord/Remittance_model');
        $repo = new RemittanceRecordRepository($this->Remittance_model);
        $paymentInterface = new PartnerRemittancePayment();
        $this->_serv = new PartnerRemittanceRecordService($repo, $this->_getIpAddress(), NULL, $paymentInterface);
    }

    public function completeCashOut()
    {
        if( !$user_id = $this->_getUserProfileId(FunctionCode::PARTNER_COMPLETE_REMITTANCE_CASH_OUT, AccessType::WRITE) )
            return false;

        if( !$this->is_required($this->input->post(), array('remittance_id')) )
            return false;

        $remittance_id = $this->input->post('remittance_id');

        $this->_serv->setUpdatedBy($user_id);
        if( $result = $this->_serv->completeCashOut($remittance_id) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array("result" => $result));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    
    public function getRemittanceTransactionList()
    {
        if( !$admin_id = $this->_getUserProfileId(FunctionCode::PARTNER_LIST_REMITTANCE_TRANSACTION, AccessType::READ) )
            return false;

        $this->_serv->setUpdatedBy($admin_id);
        
        $limit = $this->input->get("limit") ? $this->input->get('limit') : 10;
        $page = $this->input->get("page") ? $this->input->get('page') : 1;

        $start_time = $this->input->get('start_time') ? $this->input->get('start_time') : null;
        $end_time = $this->input->get('end_time') ? $this->input->get('end_time') : null;
        //$prelim_check_status = $this->input->get('prelim_check_status')?$this->input->get('prelim_check_status'):null;
        $remittance_transaction_id = $this->input->get('remittance_transaction_id')?$this->input->get('remittance_transaction_id'):null;
        $remittance_status = $this->input->get('remittance_status')?$this->input->get('remittance_status'):null;
        $is_nff = $this->input->get('is_nff')? $this->convertStringToBooleanInt($this->input->get('is_nff')) :null;
        $accountID = $this->input->get('accountID')?$this->input->get('accountID'):null;
        $approval_status = $this->input->get('status')?$this->input->get('status'):null;
        $approval_required = !is_null($this->input->get('approval_required'))?$this->input->get('approval_required'):null;
        
        if(!empty($start_time))
        {
            $start_time = IappsDateTime::fromString($start_time)->getUnix();
        }
        if(!empty($end_time))
        {
            $end_time = IappsDateTime::fromString($end_time)->getUnix();
        }
        
        if( !$main_agent = $this->_getMainAgent())
        {
            $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
            return false;
        }
        $service_provider_id = $main_agent->getId();
                
        if( $info = $this->_serv->getListRemittanceTransactionWithServiceProvider($limit, $page, $service_provider_id, $start_time, $end_time, $approval_required, $approval_status, $remittance_transaction_id,$remittance_status,true, $accountID, $is_nff) )
        {
//            $this->_respondWithSuccessCode($this->_remittance_record_service->getResponseCode(), array('result'=>$result->result));
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $info->getResult(),'total' => $info->getTotal()));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getRemittanceTransactionDetail()
    {
        if( !$admin_id = $this->_getUserProfileId(FunctionCode::PARTNER_GET_REMITTANCE_TRANSACTION_DETAIL, AccessType::READ) )
            return false;                

        $this->_serv->setUpdatedBy($admin_id);

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

    public function getFinanceRemittanceTransactionList()
    {
        if( !$admin_id = $this->_getUserProfileId(FunctionCode::PARTNER_FINANCE_LIST_REMITTANCE_TRANSACTION, AccessType::READ) )
            return false;
        $this->_serv->setUpdatedBy($admin_id);

        $limit = $this->input->get("limit") ? $this->input->get('limit') : 10;
        $page = $this->input->get("page") ? $this->input->get('page') : 1;

        $start_time = $this->input->get('start_time') ? $this->input->get('start_time') : null;
        $end_time = $this->input->get('end_time') ? $this->input->get('end_time') : null;

        $status = $this->input->get('status')?$this->input->get('status'):null;

        $reference_no = $this->input->get('reference_no')?$this->input->get('reference_no'):null;
        $bank_account_no = $this->input->get('bank_account_no')?$this->input->get('bank_account_no'):null;
        $transaction_no = $this->input->get('transaction_no')?$this->input->get('transaction_no'):null;

        $collection_type = NULL;

        if ($this->input->get('collection_type') == 'All') {
            $collection_type = NULL;

        }elseif ($this->input->get('collection_type') == 'bank') {
            $collection_type = "BT1";

        }elseif ($this->input->get('collection_type') == 'cash_pickup') {
            $collection_type = "CP1";
        }


        if(!empty($start_time))
        {
            $start_time = IappsDateTime::fromString($start_time)->getUnix();
        }
        if(!empty($end_time))
        {
            $end_time = IappsDateTime::fromString($end_time)->getUnix();
        }
        
        if( $result = $this->_serv->getFinanceListRemittanceTransaction($limit, $page, $start_time, $end_time, $status, $reference_no, $bank_account_no, $collection_type, $transaction_no, $admin_id) )
        {
//            $this->_respondWithSuccessCode($this->_remittance_record_service->getResponseCode(), array('result'=>$result->result));
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result->data,'total' => $result->total));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
}