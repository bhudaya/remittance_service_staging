<?php defined('BASEPATH') OR exit('No direct script access allowed');

use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransactionItemRepository;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransactionItemService;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransactionItemServiceFactory;

use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransaction;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransactionRepository;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransactionService;

use Iapps\Common\SystemCode\SystemCode;
use Iapps\Common\SystemCode\SystemCodeService;
use Iapps\RemittanceService\Common\SystemCodeServiceFactory;

use Iapps\Common\Helper\ResponseHeader;
use Iapps\Common\Helper\InputValidator;
use Iapps\Common\Helper\GuidGenerator;
use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\Transaction\TransactionRepository;
use Iapps\Common\CorporateService\CorporateService; //entity
use Iapps\RemittanceService\Attribute\AttributeCode;

use Iapps\RemittanceService\RemittanceRecord\RemittanceRecordService;
use Iapps\RemittanceService\RemittanceRecord\RemittanceRecordRepository;
use Iapps\RemittanceService\RemittanceRecord\PartnerRemittancePayment;
use Iapps\Common\Microservice\PaymentService\PaymentService;


/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Remittance_transaction
 *
 * @author lichao
 */
class Remittance_transaction extends Base_Controller{
    //put your code here
    protected $_remittance_transaction_service;
    protected $_remittance_transaction_item_service;
    protected $_system_code_service;

    const CASH_PAYMENT_CODE_GROUP = 'cash';
    
    function __construct() {
        parent::__construct();
        
//        $this->_remittance_transaction_item_service = RemittanceTransactionItemServiceFactory::build();
        
        $this->load->model('remittancetransaction/remittance_transaction_item_model');
        $repoItem = new RemittanceTransactionItemRepository($this->remittance_transaction_item_model);
        $this->_remittance_transaction_item_service = new RemittanceTransactionItemService($repoItem);
        
        $this->_system_code_service = SystemCodeServiceFactory::build();
        
        $this->load->model('remittancetransaction/remittance_transaction_model');
        $repo = new RemittanceTransactionRepository($this->remittance_transaction_model);

        $this->_remittance_transaction_service = new RemittanceTransactionService($repo, $this->_remittance_transaction_item_service, $this->_system_code_service);
        
        $this->_service_audit_log->setTableName('iafb_remittance.transaction');
    }

    public function approveTransaction()
    {
        if (!$adminId = $this->_getUserProfileId()) {
            return false;
        }

        // $adminId = '2031d426-097c-4b7c-be82-7bd496919b63';
        if( !$this->is_required($this->input->post(), array('remittance_id','reason') ) )
            return false;

        $remittanceId = $this->input->post('remittance_id');
        $remark = $this->input->post('remark');


        //$attributes
        $attributes = array();
        if( $this->input->post('reason') )
            $attributes[] = array('id' => explode(',',$this->input->post('reason'))[0], AttributeCode::APPROVE_REASON => explode(',',$this->input->post('reason'))[1]);

        if( $this->input->post('partner_system') )
            $attributes[] = array('id' => explode(',',$this->input->post('partner_system'))[0], AttributeCode::PARTNER_SYSTEM => explode(',',$this->input->post('partner_system'))[1]);

        if( $this->input->post('pin_number') )
            $attributes[] = array('id' => NULL, AttributeCode::PIN_NUMBER => $this->input->post('pin_number'));

        if( $this->input->post('reference_number') )
            $attributes[] = array('id' => NULL, AttributeCode::REFERENCE_NUMBER => $this->input->post('reference_number'));


        $this->load->model('remittancerecord/Remittance_model');
        $repo = new RemittanceRecordRepository($this->Remittance_model);
        $paymentInterface = new PartnerRemittancePayment();
        $remittanceServ = new RemittanceRecordService($repo, $this->_getIpAddress(), NULL, $paymentInterface);
        $paymentServ = new PaymentService();


        if($remittance = $remittanceServ->retrieveRemittance($remittanceId)){
            $payment_mode_group = $remittance->getInTransaction()->getConfirmPaymentCode();;
            if ($info = $paymentServ->getPaymentModeInfo($remittance->getInTransaction()->getConfirmCollectionMode())){
                $payment_mode_group = $info->getGroup();
            }
            if ($payment_mode_group == self::CASH_PAYMENT_CODE_GROUP){
                if( !$this->is_required($this->input->post(), array('pin_number', 'partner_system') ) )
                    return false;
            }
        }else{
            return false;
        }

        $remittanceServ->setUpdatedBy($adminId);
        if( $remittanceServ->approve($remittanceId, $attributes, $remark) )
        {
            $this->_respondWithSuccessCode($remittanceServ->getResponseCode());
            return true;
        }

        $this->_respondWithCode($remittanceServ->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function rejectTransaction()
    {
        if (!$adminId = $this->_getUserProfileId()) {
            return false;
        }

        // $adminId = '2031d426-097c-4b7c-be82-7bd496919b63';
        if( !$this->is_required($this->input->post(), array('remittance_id','reason') ) )
            return false;

        $remittanceId = $this->input->post('remittance_id');
        $remark = $this->input->post('remark');

        //$attributes
        $attributes = array();
        if( $this->input->post('reason') )
            $attributes[] = array('id' => explode(',',$this->input->post('reason'))[0], AttributeCode::REJECT_REASON => explode(',',$this->input->post('reason'))[1]);

        $this->load->model('remittancerecord/Remittance_model');
        $repo = new RemittanceRecordRepository($this->Remittance_model);
        $remittanceServ = new RemittanceRecordService($repo, $this->_getIpAddress());
        $remittanceServ->setUpdatedBy($adminId);
        if( $remittanceServ->reject($remittanceId, $attributes, $remark) )
        {
            $this->_respondWithSuccessCode($remittanceServ->getResponseCode());
            return true;
        }

        $this->_respondWithCode($this->_remittance_transaction_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;

    }
    
//    public function addRemittanceTransaction()
//    {
//        $transactionID = "";
//        $user_profile_id = "";
//        $corporateService = new CorporateService();
//        $channel = new SystemCode();
//        $remark = NULL;
//        $ref_transaction_id = NULL;
//        $passcode = NULL;
//        $expired_date = NULL;
//        
//        $this->_remittance_transaction_service->addRemittanceTransaction($transactionID,$);
//        
//    }
}
