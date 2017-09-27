<?php

use Iapps\Common\DepositTracker\DepositTrackerRepository;
use Iapps\Common\DepositTracker\DepositTrackerService;
use Iapps\Common\DepositTracker\DepositTrackerListener;

use Iapps\Common\DepositTracker\DepositTrackerRequestRepository;
use Iapps\Common\DepositTracker\DepositTrackerRequest;

use Iapps\Common\Core\IpAddress;
use Iapps\Common\Helper\ResponseHeader;

use Iapps\Common\DepositTracker\DepositTracker;

use Iapps\Common\DepositTracker\DepositTrackerHistory;
use Iapps\Common\DepositTracker\DepositTrackerHistoryRepository;
use Iapps\Common\DepositTracker\DepositTrackerHistoryService;

use Iapps\RemittanceService\Common\DepositTopupS3Uploader;
use Iapps\RemittanceService\Common\DepositTrackerFileUploader;
use Iapps\RemittanceService\Common\DocumentFileS3Uploader;
use Iapps\Common\Helper\GuidGenerator;
use Iapps\RemittanceService\Common\MessageCode;

use Iapps\RemittanceService\RemittanceConfig\RemittanceConfigService;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfigRepository;

use Iapps\Common\DepositTracker\DepositTrackerUserRepository;
use Iapps\Common\DepositTracker\DepositTrackerUserService;
use Iapps\Common\DepositTracker\DepositTrackerRequestService;
use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\CommunicationService\CommunicationServiceProducer;
use Iapps\RemittanceService\DepositTracker\DepositTrackerConstants;

use Iapps\Common\DepositTracker\DepositTrackerEmailRepository;
use Iapps\Common\DepositTracker\DepositTrackerEmailService;

use Iapps\Common\DepositTracker\DepositTrackerReasonsRepository;
use Iapps\Common\DepositTracker\DepositTrackerReasonsService;
use Iapps\RemittanceService\Common\DepositTrackerReasonsServiceFactory;


use Iapps\RemittanceService\Common\DepositHistoryUserServiceFactory;
use Iapps\RemittanceService\RemittanceRecord\RemittanceRecordServiceFactory;
use Iapps\RemittanceService\RemittanceRecord\RemittanceRecord;


class Deposit_tracker_admin extends Admin_Base_Controller
{

    protected $_service;
    protected $_deposit_request_service;
    protected $_deposit_history_service;
    protected $_deposit_tracker_user_service;
    protected $_remittance_config_service;

    function __construct()
    {
        parent::__construct();

        $this->load->model('deposittracker/Deposit_tracker_model');
        $repo = new DepositTrackerRepository($this->Deposit_tracker_model);
        $this->_service = new DepositTrackerService($repo);

        $this->load->model('deposittracker/Deposit_tracker_request_model');
        $request_repo = new DepositTrackerRequestRepository($this->Deposit_tracker_request_model);
        $this->_deposit_request_service = new DepositTrackerRequestService($request_repo);

        $this->load->model('deposittracker/Deposit_tracker_history_model');
        $history_repo = new DepositTrackerHistoryRepository($this->Deposit_tracker_history_model);
        $this->_deposit_history_service = new DepositTrackerHistoryService($history_repo);

        $this->load->model('remittanceconfig/Remittance_config_model');
        $remittanceconfig_repo = new RemittanceConfigRepository($this->Remittance_config_model);
        $this->_remittance_config_service = new RemittanceConfigService($remittanceconfig_repo);


        $this->load->model('deposittracker/Deposit_tracker_user_model','deposit_tracker_user_model');
        $tracker_user_repo = new DepositTrackerUserRepository($this->deposit_tracker_user_model);
        $this->_deposit_tracker_user_service = new DepositTrackerUserService($tracker_user_repo);

        $this->load->model('deposittracker/Deposit_tracker_email_model','deposit_tracker_email_model');
        $tracker_email_repo = new DepositTrackerEmailRepository($this->deposit_tracker_email_model);
        $this->_tracker_email_service = new DepositTrackerEmailService($tracker_email_repo);

        $this->_service->setIpAddress(IpAddress::fromString($this->_getIpAddress()));
    }



    /*
     * @access public
     * @usage this function will pull out
     * all the deposit tracker records without
     * limit
     */
    public function getAllDepositsForAdminMaker()
    {
        if( !$user_id = $this->_getUserProfileId("admin_list_deposit","R") ){
            return false;
        }

        if( $object = $this->_service->getAllDepositsForAdminMaker())
        {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(),array('result' => $object, 'total' => count($object)));
            return true;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }



    /*
     * @access public
     * @usage this function is used
     * to insert deposit tracker record
     */
    public function addDeposit()
    {

        if( !$user_id = $this->_getUserProfileId("admin_add_deposit","W") ){
            return false;
        }

        $service_provider_id   = $this->input->post("serviceproviderid");
        $remittance_config_id  = $this->input->post("channelprimarykey");
        $country_currency_code = $this->input->post("currency");
        $threshold_amount      = $this->input->post("thresholdamount");
        $deposit_holder        = $this->input->post("depositholder");
        $trackers              = $this->input->post("trackers");
        $deposit_status        = $this->input->post("depositstatus");
        $userid                = $this->input->post('userid');
        $email                 = $this->input->post('trackeremail');

        $config = new DepositTracker();
        $config->setServiceProviderId($service_provider_id);
        $config->setCountryCurrencyCode($country_currency_code);
        $config->setThresholdStatus(DepositTrackerConstants::LOWTHRESHOLDSTATUS);
        $config->setThresholdAmount($threshold_amount);
        $config->setRemittanceConfigId($remittance_config_id);
        $config->setDepositHolder($deposit_holder);
        $config->setDepositStatus($deposit_status);
        $config->setCreatedBy($user_id);
        $config->setTrackerEmail($email);

        if(!empty($trackers)){
            $config->setTrackers($trackers);
        }

        if( $config = $this->_service->addDepositTracker($config) )
        {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $config));
            return true;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND, "","",$this->_service->getResponseMessage());
        return false;
    }


    /*
     * @access public
     * @usage this function is used
     * to get the deposit tracker record
     * based from the given deposit tracker id
     * and given realdata flag, the realdata flag
     * is used to fetch the data from the deposit
     * tracker table , if it's not given, then
     * we fetch the record from the deposit tracker
     * history
     */
    public function viewDeposit()
    {
        if( !$user_id = $this->_getUserProfileId("admin_view_deposit","R")){
            return false;
        }


        $depositid = $this->input->get('depositid');
        $realdata = $this->input->get('realdata'); //flag
        $config = new DepositTracker();
        $config->setId($depositid);

        $oldDeposit = $this->_service->getDepositByDepositId($depositid);

        if($config = $this->_service->viewDeposit($config,$realdata));
        {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $config));

            return true;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND, "","",$this->_service->getResponseMessage());
        return false;

    }


    /*
     * @access public
     * @usage this function is used
     * to fetch all the deposit tracker record
     * based from the  given remittance_config_id
     * and deposit holder
     */
    public function listDeposit()
    {

        if( !$user_id = $this->_getUserProfileId("admin_list_deposit","R") ){
            return false;
        }

        $limit = $this->input->get('limit');
        $page =  $this->input->get('page');
        $remittance_config_id = $this->input->get('remittance_config_id');
        $depositholder = $this->input->get('depositholder');

        if(!empty($remittance_config_id) || !empty($depositholder)){

            if($object = $this->_service->getDepositByParam($limit,$page,$remittance_config_id,$depositholder)){
                $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $object, 'total' => count($object)));
                return true;
            }

        } else {

            if ($object = $this->_service->getDepositList($limit, $page)) {
                $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $object, 'total' => count($object)));
                return true;
            }
            $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
            return false;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }


    /*
     * @access public
     * @usage this function
     * is used for getting all the pending
     * deposit tracker records
     */
    public function listPendingDeposit()
    {
        if( !$user_id = $this->_getUserProfileId("admin_list_pending_deposit","R") ){
            return false;
        }

        $this->_service->setUpdatedBy($user_id);
        $page = $this->input->get('page');//$this->_getPage();
        $limit = $this->input->get('limit');//$this->_getLimit();
        $remittance_config_id = $this->input->get('remittance_config_id');
        $depositholder = $this->input->get('depositholder');


        if(!empty($remittance_config_id) || !empty($depositholder)){
            $data = '';
            if($object = $this->_service->getPendingDepositByParam($limit,$page,$remittance_config_id,$depositholder)){
                if(isset($object->result)){
                    $data = $object->result;
                } else {
                    $data = $object;
                }
                $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $data, 'total' => count($data)));
                return true;
            }

        } else {
            $data = '';
            if ($object = $this->_service->getPendingDepositList($limit, $page)) {
                if(isset($object->result)){
                    $data = $object->result;
                } else {
                    $data = $object;
                }
                $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $data, 'total' => count($data)));
                return true;
            }
            $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
            return false;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }



    /*
     * @access public
     * @usage this function is used
     * for editing the deposit tracker record
     * based from the given
     * deposit tracker id, threshold amount,
     * tracker users and email trackers
     */
    public function editDeposit()
    {
        if( !$user_id = $this->_getUserProfileId("admin_update_deposit","W") ){
            return false;
        }
        $deposit_tracker_id    = $this->input->post("deposittrackerid");
        $threshold_amount      = $this->input->post("thresholdamount");
        $trackers              = $this->input->post("trackers");
        $emailtrackers         = $this->input->post("emailtrackers");
        $config = new DepositTracker();
        $config->setId($deposit_tracker_id);
        $config->setThresholdAmount($threshold_amount);
        $config->setTrackers($trackers);
        $config->setDepositStatus(DepositTrackerConstants::DEPOSIT_STATUS_PENDING);
        $config->setUpdatedBy($user_id);
        $config->setTrackerEmail($emailtrackers);
        if($this->_service->hasPendingConfig($deposit_tracker_id)) //only one pending config is allowed
        {
            $this->_service->setResponseMessage(DepositTrackerConstants::MESSAGE_EXISTING_PENDING_CONFIG);
            $this->_respondWithCode(DepositTrackerConstants::PUT_DATA_FAILED,ResponseHeader::HEADER_NOT_FOUND, "","", $this->_service->getResponseMessage());
            return false;
        }

        $oldDeposit = $this->_service->getDepositByDepositId($deposit_tracker_id);
        $config->setApproveRejectedAt($oldDeposit->getApproveRejectedAt()->getUnix());
        $config->setApproveRejectedBy($oldDeposit->getApproveRejectedBy());
        $config->setCreatedBy($oldDeposit->getCreatedBy());
        $config->setHistoryUpdatedAt($oldDeposit->getUpdatedAt()->getUnix());
        $config->setAmount($oldDeposit->getAmount());
        if($this->_service->editDepositTracker($config,$oldDeposit) )
        {
            $this->_respondWithSuccessCodeAndCustomMessage($this->_service->getResponseCode(), $this->_service->getResponseMessage(), $config);
            return true;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND, "","",$this->_service->getResponseMessage());
        return false;
    }


    /*
     * @access public
     * @usage this function is used for
     * approving the existing pending deposit
     * tracker record based from the given
     * deposit tracker id , trackers, status,
     * threshold amount. or if the history was
     * provided, we update the deposit tracker
     * history table
     */
    public function approveDeposit()
    {
        if( !$user_id = $this->_getUserProfileId("admin_approve_deposit","W") ){
            return false;
        }

        $deposit_tracker_id = $this->input->post('depositid');
        $trackers = $this->input->post('trackers');
        $status = $this->input->post('status');
        $thresholdamount = $this->input->post('thresholdamount');
        $historyid = '';
        if(null != $this->input->post('historyid')){
            $historyid = $this->input->post('historyid');
        }
        $config = new DepositTracker();
        $config->setId($deposit_tracker_id);
        $config->setDepositStatus(DepositTrackerConstants::APPROVED);
        $config->setUpdatedBy($user_id);

        $oldDeposit = $this->_service->getDepositByDepositId($deposit_tracker_id);


        if( $config = $this->_service->approveDeposit($config) )
        {

            $depositConfig = new DepositTracker();
            $depositConfig->setId($deposit_tracker_id);
            $depositConfig->setThresholdAmount($thresholdamount);
            $depositConfig->setTrackerStatus($status);
            $depositConfig->setCreatedBy($oldDeposit->getCreatedBy());
            $depositConfig->setTrackers($trackers);
            $depositConfig->setHistoryUpdatedAt($oldDeposit->getUpdatedAt()->getUnix());
            $depositConfig->setUpdatedBy($oldDeposit->getUpdatedBy());
            $depositConfig->setApproveRejectedBy($user_id);
            $depositConfig->setHistoryId($historyid);

            $pendingEmail = $this->_service->getPendingTrackerEmail($depositConfig);
            $pendingEmailArray = array();
            $pendingEmailString = '';
            if(!empty($pendingEmail)){
                foreach($pendingEmail as $record){
                    $pendingEmailArray[] = trim($record->getEmail());
                }
            }
            $pendingEmailString = implode(",",$pendingEmailArray);
            $depositConfig->setTrackerEmail($pendingEmailString);
            $this->_service->insertDepositConfig($depositConfig);
            $this->_respondWithSuccessCode($this->_service->getResponseCode());
            return true;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND, "","",$this->_service->getResponseMessage());
        return false;

    }


    /*
     * @access public
     * @usage this funciton is used for
     * approving the processed deposit
     * deduction request based from the
     * given deduction id, deposit tracker id,
     * and reason.
     */
    public function approveDeduction()
    {
        if( !$user_id = $this->_getUserProfileId("admin_approve_deduction","W") ){
            return false;
        }

        $deductionid = $this->input->post('deductionid');
        $deposit_tracker_id = $this->input->post('depositid');
        $reason = $this->input->post('reason');
        $config = new DepositTrackerRequest();
        $config->setId($deductionid);
        $config->setDepositTrackerId($deposit_tracker_id);
        $config->setStatus(DepositTrackerConstants::APPROVED);
        $config->setApprovedRejectedBy($user_id);
        $config->setUpdatedBy($user_id);
        $config->setApprovedRejectedRemarks($reason);


        $oldDeposit = $this->_service->getDepositByDepositId($deposit_tracker_id);
        $difference = '';
        $deductiondata = $this->_deposit_request_service->getDeduction($deductionid);
        $difference = $oldDeposit->getAmount() - $deductiondata->getAmount();
        $threshold_amount = $oldDeposit->getThresholdAmount();
        if($difference < $threshold_amount && $difference < 0) {
//            $difference = $oldDeposit->amount - $deductiondata[0]->amount;
            $message = DepositTrackerConstants::MESSAGE_DEDUCTION_FAILED;
            $code = MessageCode::CODE_APPROVE_DEDUCTION_FAILED;
            $emailArray = $this->_service->getActiveEmailsForNotification($oldDeposit->getId());
//            $this->fireLowBalanceNotification($emailArray,$deposit_tracker_id,$deductiondata);
            $this->_respondWithSuccessCodeAndCustomMessage($code, $message, '');
            return false;
        }

//        $difference = $oldDeposit->amount - $deductiondata[0]->amount;
        $config->setAmount($deductiondata->getAmount());
        if($this->_deposit_request_service->approveDeduction($config))
        {

            $this->_service->deductAmount($difference,$deductiondata);
//            $this->_deposit_history_service->insertHistory($oldDeposit);
//            $this->_respondWithSuccessCode($this->_deposit_request_service->getResponseCode(), $this->_deposit_request_service->getResponseMessage(), $config);
            $code = MessageCode::CODE_APPROVE_DEDUCTION_SUCCESS;
            $message = 'Deduction Approve Success';
            $this->_respondWithSuccessCodeAndCustomMessage($code, $message, '');
            return true;
        }

        $this->_respondWithCode($this->_deposit_request_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND, "","",$this->_deposit_request_service->getResponseMessage());
        return false;

    }

    /*
     * @access private
     * @param array emailArray
     * @param string deposit id
     * @param obj $deduction record data
     * @usage this function is used for
     * call the low balance email notification
     * function
     */
    private function fireLowBalanceNotification($emailArray,$depositid,$deductiondata)
    {

        $deposit = $this->_service->getDepositByDepositId($depositid);
        $difference = $deposit->getAmount() - $deductiondata[0]->amount;
        $threshold = $deposit->getThresholdAmount();

        $emails = $this->_deposit_request_service->getDepositTrackerEmails($depositid);
        $this->_deposit_request_service->fireEmailNotification($emails,$deposit,$difference); //notification for low deposit balance

    }


    /*
     * @access public
     * @usage this function is used
     * for rejecting the deposit tracker
     * request record based from the given
     * depositid, threshold amount, trackers
     * and status.
     */
    public function rejectDeposit()
    {
        if( !$user_id = $this->_getUserProfileId("admin_reject_deposit","W") ){
            return false;
        }
        $trakers = '';
        $deposit_tracker_id = $this->input->post('depositid');
        $thresholdamount = $this->input->post('thresholdamount');
        if(null != $this->input->post('trackers')) {
            $trackers = $this->input->post('trackers');
        }
        $status = $this->input->post('status');



        $historyid = '';
        if(null != $this->input->post('historyid')){
            $historyid = $this->input->post('historyid');
        }
        $config = new DepositTracker();
        $config->setId($deposit_tracker_id);
        $config->setDepositStatus(DepositTrackerConstants::DEPOSIT_STATUS_REJECTED);
        $config->setUpdatedBy($user_id);



        $oldDeposit = $this->_service->getDepositByDepositId($deposit_tracker_id);
        if(!empty($trackers)) {
            $config->setTrackers($trackers);
            $oldDeposit->setTrackers($trackers);
        }

        if($this->isDepositNew($deposit_tracker_id)){
            if($config = $this->_service->rejectDeposit($config)){
                $depositConfig = new DepositTracker();
                $depositConfig->setId($deposit_tracker_id);
                $depositConfig->setThresholdAmount($thresholdamount);
                $depositConfig->setTrackerStatus(DepositTrackerConstants::DEPOSIT_STATUS_REJECTED);
                $depositConfig->setCreatedBy($user_id);
                if(!empty($trackers)) {
                    $depositConfig->setTrackers($trackers);
                }
                $depositConfig->setDepositStatus(DepositTrackerConstants::DEPOSIT_STATUS_REJECTED);
                $depositConfig->setUpdatedBy($oldDeposit->getUpdatedBy());
                $depositConfig->setApproveRejectedBy($user_id);
                $depositConfig->setHistoryUpdatedAt($oldDeposit->getUpdatedAt()->getUnix());
                $depositConfig->setHistoryId($historyid);
//            $this->_deposit_history_service->insertHistory($oldDeposit,$trackers);
                $this->_service->insertDepositConfig($depositConfig);


                $this->_respondWithSuccessCodeAndCustomMessage($this->_service->getResponseCode(), $this->_service->getResponseMessage(), $config);
                return true;
            } else {
                $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND, "","",$this->_service->getResponseMessage());
                return false;
            }

        } else {

            $depositConfig = new DepositTracker();
            $depositConfig->setId($deposit_tracker_id);
            $depositConfig->setThresholdAmount($thresholdamount);
            $depositConfig->setTrackerStatus(DepositTrackerConstants::DEPOSIT_STATUS_REJECTED);
            $depositConfig->setCreatedBy($user_id);
            if(!empty($trackers)) {
                $depositConfig->setTrackers($oldDeposit->getTrackers());
            }
            $depositConfig->setDepositStatus(DepositTrackerConstants::DEPOSIT_STATUS_REJECTED);
            $depositConfig->setUpdatedBy($oldDeposit->getUpdatedBy());
            $depositConfig->setApproveRejectedBy($user_id);
            $depositConfig->setHistoryId($historyid);
            $depositConfig->setHistoryUpdatedAt($oldDeposit->getUpdatedAt()->getUnix());

//            $this->_deposit_history_service->insertHistory($oldDeposit,$trackers);
            $this->_service->insertDepositConfig($depositConfig);

            $this->_service->setResponseMessage(DepositTrackerConstants::REJECT_DEPOSIT_SUCCESS_MESSAGE);
            $this->_service->setResponseCode(MessageCode::CODE_REJECT_DEPOSIT_SUCCESS);
            $this->_respondWithSuccessCodeAndCustomMessage($this->_service->getResponseCode(), $this->_service->getResponseMessage(), $config);
            return true;


        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND, "","",$this->_service->getResponseMessage());
        return false;


    }

    /*
     * @access public
     * @usage this function is used for
     * adding deposit topup record
     * based from the given userid,
     * deposit id, amount, bank
     * ,transferreference number,
     * photo, s3photoname, date of transfer
     */
    public function addTopup()
    {

        if( !$user_id = $this->_getUserProfileId("admin_create_topup","W") ){
            return false;
        }

        $userid = $this->input->post('userid');
        $depositid = $this->input->post('depositid');
        $amount = $this->input->post('amount');
        $bank   = $this->input->post('bank');
        $transrefno = $this->input->post('transferno');
        $transferphoto = $this->input->post('photo');
        $photoname = $this->input->post('photoname');
        $s3photoname = $this->input->post('s3photoname');
        $transdate = $this->input->post('date_of_transfer');
        $createdby = $this->input->post('userid');


        $config = new DepositTrackerRequest();
        $config->setDepositTrackerId($depositid);
        $config->setType('topup');
        $config->setStatus('pending');
        $config->setAmount($amount);
        $config->setBank($bank);
        $config->setTransReferenceNum($transrefno);
        $config->setTransProofUrl($transferphoto);
        $config->setPhotoName($photoname);
        $config->setS3PhotoName($s3photoname);
        $config->setTransDate($transdate);
        $config->setCreatedBy($user_id);


        if($result = $this->_deposit_request_service->addTopup($config)){
            $this->_respondWithSuccessCode($this->_deposit_request_service->getResponseCode(), array('result' => $config));
            return true;
        }

        $this->_respondWithCode($this->_deposit_request_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND, "","",$this->_deposit_request_service->getResponseMessage());
        return false;

    }

    /*
     * @access public
     * @usage this function is used
     * for uploading topup photo and pdf files
     * to amazon s3
     */
    public function topupUpload()
    {

        if( !$user_id = $this->_getUserProfileId("admin_create_topup","W") ){
            return false;
        }

        $img_name = GuidGenerator::generate();

        $s3PhotoImage = new DepositTopupS3Uploader($img_name);
        $s3PhotoImage->setS3Folder('topup_photo/');
        if ($s3PhotoImage->uploadtoS3('photo')) {
            $result = $s3PhotoImage->getUrl();
            $result['photoname'] = $_FILES['photo']['name'];
            $result['s3photoname'] = $s3PhotoImage->getFileName();
            $result['type'] = 'photo';
            $this->_respondWithSuccessCodeAndCustomMessage(MessageCode::CODE_PHOTO_UPLOAD_SUCCESS, 'Photo Uploaded', array('result' => $result));
            return true;
        } else {

            if(mime_content_type($_FILES['photo']['tmp_name']) == 'application/pdf' || mime_content_type($_FILES['photo']['type']) == 'application/octet-stream'){
                $pdfname = GuidGenerator::generate();
                $pdf = new DepositTrackerFileUploader($pdfname);
                $pdf->setUploadPath('./upload/document/');
                $pdf->setAllowedType('jpg|png|pdf|doc|docx');
                $pdf->setS3Folder('remittance/topup/document/');
                $pdf->uploadtoS3('photo');
                $result['photoname'] = $_FILES['photo']['name'];
                $result['s3photoname'] = $pdf->getFileName();
                $result['url'] = $pdf->getUrl();
                $result['type'] = 'pdf';
                $this->_respondWithSuccessCodeAndCustomMessage(MessageCode::CODE_PHOTO_UPLOAD_SUCCESS,'Photo Uploaded', array('result' => $result));
                return true;

            }

            $this->_respondWithCode(MessageCode::CODE_PHOTO_UPLOAD_FAILED);
            return false;
        }
        $this->_respondWithCode(MessageCode::CODE_PHOTO_UPLOAD_FAILED);
        return false;

    }



    /*
     * @access public
     * @usage this function is used for
     * pulling out all the topup records
     * based from the given deposit id,
     * limit and user id
     */
    public function listTopup()
    {

        if( !$user_id = $this->_getUserProfileId("admin_list_topup","R") ){
            return false;
        }

        $depositid = $this->input->get('depositid');
        $page = $this->input->get('page');//$this->_getPage();
        $limit = $this->input->get('limit');//$this->_getLimit();
        $userid = $this->input->get('userid');
        if( $object = $this->_deposit_request_service->getTopupList($limit, $page,$depositid,$userid))
        {
            $this->_respondWithSuccessCode($this->_deposit_request_service->getResponseCode(),array('result' => $object, 'total' => count($object->result->toArray())));
            return true;
        }

        $this->_respondWithCode($this->_deposit_request_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }



    /*
     * @access public
     * @usage this function is used for
     * pulling all the deposit tracker history
     * from the deposit_tracker_history table
     * records based from the given deposit id,
     * limit, page
     */
    public function listHistory()
    {

        if( !$user_id = $this->_getUserProfileId("admin_view_deposit","R") ){
            return false;
        }

        $depositid = $this->input->get('depositid');
        $page = $this->_getPage();//$this->input->get('page');//$this->_getPage();
        $limit = $this->_getLimit();//$this->input->get('limit');//$this->_getLimit();
        if( $object = $this->_deposit_history_service->getHistoryList( $limit, $page,$depositid))
        {
            $this->_respondWithSuccessCode($this->_deposit_history_service->getResponseCode(),array('result' => $object, 'total' => count($object)));
            return true;
        }

        $this->_respondWithCode($this->_deposit_history_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }


    /*
     * @access public
     * @usage this function is used
     * for pulling the user trackers on
     * each of the deposit tracker based
     * from the given deposit id. these
     * trackers varies from a readl deposit
     * data or a history deposit data
     */
    public function getHistoryTracker()
    {
        if( !$user_id = $this->_getUserProfileId("admin_view_deposit","R") ){
            return false;
        }

        $depositid = $this->input->get('depositid');
        $createdat = $this->input->get('createdat');
        if($object = $this->_deposit_tracker_user_service->getHistoryTracker($depositid,$createdat))
        {
            $this->_respondWithSuccessCode($this->_deposit_tracker_user_service->getResponseCode(), array('result' => $object, 'total' => count($object)));
            return true;
        }
        $this->_respondWithCode($this->_deposit_tracker_user_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }


    /*
     * @access public
     * @usage this function is used
     * for pulling out the old/previous deposit
     * tracker based from the given history id
     */
    public function getOldTrackers()
    {

        if( !$user_id = $this->_getUserProfileId("admin_view_deposit","R") ){
            return false;
        }

        $historyid = $this->input->get('historyid');
        $historyUserFactory = DepositHistoryUserServiceFactory::build();
        $object = $historyUserFactory->getOldTrackers($historyid);
        if($object) //$this->_deposit_history_service->getOldTrackers($historyid))
        {
            $this->_respondWithSuccessCode($historyUserFactory->getResponseCode(), array('result' => $object, 'total' => count($object)));
            return true;
        }
        $this->_respondWithCode($historyUserFactory->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }


    /*
     * @access public
     * @usage this function is used
     * to pull out all the transactions that
     * belongs to the given deposit id.
     * transaction is either a topup , deduction
     * or the real time deduction coming from
     * the listener
     */
    public function listTransaction()
    {

        if( !$user_id = $this->_getUserProfileId("admin_view_deposit","R")){
            return false;
        }

        $depositid = $this->input->get('depositid');
        $page = $this->input->get('page');
        $limit = $this->input->get('limit');//$this->_getLimit();
        if( $object = $this->_deposit_request_service->getTransactionList( $limit, $page,$depositid))
        {
            $this->_respondWithSuccessCode($this->_deposit_request_service->getResponseCode(),array('result' => $object, 'total' => count($object)));
            return true;
        }

        $this->_respondWithCode($this->_deposit_request_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }


    /*
     * @access public
     * @usage this function is used
     * for pulling out the topup data
     * based from the given topup id
     */
    public function viewTopup()
    {

        if( !$user_id = $this->_getUserProfileId("admin_view_topup","R")){
            return false;
        }

        $topupid = $this->input->get('id');

        if($obj = $this->_deposit_request_service->getTopup($topupid))
        {
            $this->_respondWithSuccessCode($this->_deposit_request_service->getResponseCode(),array('result' => $obj));
            return true;
        }
        $this->_respondWithCode($this->_deposit_request_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }



    /*
     * @access public
     * @usage this function is used for
     * rejecting a pending topup request
     * based from the given topupid, deposit id,
     * reason for the admin panel
     */
    public function rejectTopup()
    {
        if( !$user_id = $this->_getUserProfileId("admin_reject_topup","W")){
            return false;
        }


        $topupid = $this->input->post('topupid');
        $deposit_tracker_id = $this->input->post('depositid');
        $reason  = $this->input->post('reason');
        $rejecttopup_reason = $this->input->post('rejecttopup_reason');
        $config  = new DepositTrackerRequest();
        $config->setId($topupid);
        $config->setUpdatedBy($user_id);
        $config->setStatus(DepositTrackerConstants::DEPOSIT_STATUS_REJECTED);
        $config->setDepositTrackerId($deposit_tracker_id);
        $config->setUpdatedBy($user_id);
        $config->setApprovedRejectedBy($user_id);
        if(isset($reason) && !empty($reason)) {
            $config->setApprovedRejectedRemarks($reason);
        }


        if( $config = $this->_deposit_request_service->rejectTopup($config) )
        {
            $this->_respondWithSuccessCode($this->_deposit_request_service->getResponseCode(), array('result' => $config));
            return true;
        }

        $this->_respondWithCode($this->_deposit_request_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND, "","",$this->_deposit_request_service->getResponseMessage());
        return false;


    }



    /*
     * @access public
     * @usage this function is used
     * for rejecting a pending deduction
     * request record based from the given
     * deduction id, deposit id, status, reason
     */
    public function rejectDeduction()
    {
        if( !$user_id = $this->_getUserProfileId("admin_reject_deduction","W") ){
            return false;
        }

        $deductionid = $this->input->post('deductionid');
        $status = $this->input->post('status');
        $depositid = $this->input->post('depositid');
        $reason = $this->input->post('reason');
        $rejector = $this->input->post('userid');
        $config = new DepositTrackerRequest();
        $config->setDepositTrackerId($depositid);
        $config->setId($deductionid);
        $config->setStatus($status);
        $config->setApprovedRejectedBy($rejector);

        if(isset($reason) && !empty($reason)) {
            $config->setApprovedRejectedRemarks($reason);
        }
        if( $config = $this->_deposit_request_service->rejectDeduction($config))
        {
            $this->_respondWithSuccessCode($this->_deposit_request_service->getResponseCode(), array('result' => $config));
            return true;
        }

        $this->_respondWithCode($this->_deposit_request_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND, "","",$this->_deposit_request_service->getResponseMessage());
        return false;



    }


    /*
     * @access public
     * @usage this function is used
     * for cancelling a pending topup request
     * record based from the given depositid,
     * topup id
     */
    public function cancelTopup()
    {
        if( !$user_id = $this->_getUserProfileId("admin_cancel_topup","W") ){
            return false;
        }
        $deposit_tracker_id = $this->input->post('depositid');
        $topupid            = $this->input->post('topupid');
        $config = new DepositTrackerRequest();
        $config->setId($topupid);
        $config->setDepositTrackerId($deposit_tracker_id);
        $config->setUpdatedBy($user_id);
        $config->setStatus(DepositTrackerConstants::DEPOSIT_STATUS_CANCELLED);

        if( $config = $this->_deposit_request_service->cancelTopup($config) )
        {
            $this->_respondWithSuccessCode($this->_deposit_request_service->getResponseCode(), array('result' => $config));
            return true;
        }

        $this->_respondWithCode($this->_deposit_request_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND, "","",$this->_deposit_request_service->getResponseMessage());
        return false;


    }


    /*
     * @access public
     * @usage this function is used
     * for pulling out all the deduction records
     * based from the given deposit id, page, limit
     */
    public function listDeduction()
    {
        if( !$user_id = $this->_getUserProfileId("admin_list_deduction","R")){
            return false;
        }


        $depositid = $this->input->get('depositid');

        $page = $this->input->get('page');//$this->_getPage();
        $limit = $this->input->get('limit');//$this->_getLimit();
        if( $object = $this->_deposit_request_service->getDeductionList( $limit, $page,$depositid) )
        {
            $this->_respondWithSuccessCode($this->_deposit_request_service->getResponseCode(),array('result' => $object, 'total' => count($object->result->toArray())));
            return true;
        }

        $this->_respondWithCode($this->_deposit_request_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }


    /*
     * @access public
     * @usage this function is used for
     * pulling all the pending deduction
     * records based from the given page and limit
     */
    public function listPendingDeduction()
    {
        if( !$user_id = $this->_getUserProfileId("admin_list_deduction","R")){
            return false;
        }

        $page = $this->input->get('page');
        $limit = $this->input->get('limit');

        if ($object = $this->_deposit_request_service->getPendingDeduction($limit, $page)) {
            $this->_respondWithSuccessCode($this->_deposit_request_service->getResponseCode(), array('result' => $object, 'total' => count($object)));
            return true;
        }
        $this->_respondWithCode($this->_deposit_request_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }



    /*
     * @access public
     * @usage this function is used for
     * add deduction record based from the
     * given deposit id, amount, purpose, userid
     */
    public function addDeduction()
    {

        if( !$user_id = $this->_getUserProfileId("admin_add_deduction","W") ){
            return false;
        }

        $depositid = $this->input->post('depositid');
        $amount = $this->input->post('amount');
        $purpose   = $this->input->post('purpose');
        $createdby = $this->input->post('userid');

        $config = new DepositTrackerRequest();
        $config->setDepositTrackerId($depositid);
        $config->setType('deduction');
        $config->setStatus('pending');
        $config->setAmount($amount);
        $config->setPurpose($purpose);
        $config->setCreatedBy($createdby);
        $config->setUpdatedBy($user_id);

        if($purpose == 'transaction'){
            if(!$this->_deposit_request_service->addDeduction($config)){
                $this->_respondWithSuccessCodeAndCustomMessage($this->_deposit_request_service->getResponseCode(), $this->_deposit_request_service->getResponseMessage(), $config);
                return false;
            }
        } else if ($purpose == 'settlement') {
            if ($result = $this->_deposit_request_service->addDeduction($config)) {
                $this->_respondWithSuccessCode($this->_deposit_request_service->getResponseCode(), array('result' => $result));
                return true;
            } else {
                $this->_respondWithCode($this->_deposit_request_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND, "","",$this->_deposit_request_service->getResponseMessage());
                return false;
            }
        }

        $this->_respondWithCode($this->_deposit_request_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND, "","",$this->_deposit_request_service->getResponseMessage());
        return false;


    }


    /*
     * @access public
     * @usage this function is used
     * for pulling out the deduction record
     * based from the given deduction id
     */
    public function viewDeduction()
    {
        if( !$user_id = $this->_getUserProfileId("admin_view_deduction","R")){
            return false;
        }


        $deductionid = $this->input->get('id');
        if($obj = $this->_deposit_request_service->getDeduction($deductionid))
        {
            $this->_respondWithSuccessCode($this->_deposit_request_service->getResponseCode(),array('result' => $obj));
            return true;
        }
        $this->_respondWithCode($this->_deposit_request_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }



    /*
     * @access public
     * @usage this function is used for
     * cancelling a pending deduciton request
     * record based form the given deposit id,
     * deduction id and reason
     */
    public function cancelDeduction()
    {
        if( !$user_id = $this->_getUserProfileId("admin_cancel_deduction","W") ){
            return false;
        }
        $deposit_tracker_id = $this->input->post('depositid');
        $deductionid  = $this->input->post('deductionid');
        $reason       = $this->input->post('reason');
        $config = new DepositTrackerRequest();
        $config->setId($deductionid);
//        $config->setDepositTrackerId($deposit_tracker_id);
        $config->setApprovedRejectedRemarks($reason);
        $config->setUpdatedBy($user_id);
        $config->setStatus(DepositTrackerConstants::DEPOSIT_STATUS_CANCELLED);
        $config->setDepositTrackerId($deposit_tracker_id);

        if( $config = $this->_deposit_request_service->cancelDeduction($config) )
        {
            $this->_respondWithSuccessCode($this->_deposit_request_service->getResponseCode(), array('result' => $config));
            return true;
        }

        $this->_respondWithCode($this->_deposit_request_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND, "","",$this->_deposit_request_service->getResponseMessage());
        return false;


    }

    /*
     * @access public
     * @usage this function is used
     * to pull out all the email trackers
     * based from the given deposit id
     * this function is for display only
     */
    public function listEmailTracker()
    {
        if( !$user_id = $this->_getUserProfileId("admin_view_deposit","R") ){
            return false;
        }

//        if( !$user_id = $this->_getUserProfileId("deposit_tracker_common_access","R") ){
//            return false;
//        }

        $depositid = $this->input->get('depositid');
        if($list = $this->_tracker_email_service->listEmailTracker($depositid))
        {
            $this->_respondWithSuccessCodeAndCustomMessage(DepositTrackerConstants::GET_DATA_SUCCESS,DepositTrackerConstants::MESSAGE_GET_DATA_SUCCESS,array('result' => $list->result->toArray()));
            return true;
        }
        return false;

    }

    /*
     * @access public
     * @usage this function is used
     * to pull out all the deposit configuration
     * data based from the given remittance config id
     * and deposit holder, this function is actually
     * used at the approve pending deposit,it is
     * the one that displays the previous configuration
     * of the particular deposit tracker record
     */
    public function listConfig()
    {
        if( !$user_id = $this->_getUserProfileId("admin_list_deposit","R") ){
            return false;
        }

        $limit = $this->input->get('limit');
        $page =  $this->input->get('page');
        $remittance_config_id = $this->input->get('remittance_config_id');
        $depositholder = $this->input->get('depositholder');

        if(!empty($remittance_config_id) && !empty($depositholder)){

            if($object = $this->_service->listConfigByParam($limit,$page,$remittance_config_id,$depositholder)){
                $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $object, 'total' => count($object)));
                return true;
            }

        } else {

            if ($object = $this->_service->listConfig($limit, $page)) {
                if(isset($object->result) && !empty($object->result)){
                    $object  = $object->result->toArray();
                }
                $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $object, 'total' => count($object)));
                return true;
            }
            $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
            return false;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    /*
     * @access public
     * @usage this function is used to pull out
     * the particular configuration of a deposit tracker
     * record based from the given history id
     */
    public function getConfig()
    {
        if( !$user_id = $this->_getUserProfileId("admin_list_deposit","R") ){
            return false;
        }

        $limit = $this->input->get('limit');
        $page =  $this->input->get('page');
        $historyid = $this->input->get('historyid');

        if ($object = $this->_service->getConfig($historyid)) {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $object, 'total' => count($object)));
            return true;
        }
        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;


    }


    /*
     * @access public
     * @usage this function is used
     * for pulling all the configuaration
     * of a deposit tracker based from the given
     * deposit id
     */
    public function getAllConfig()
    {
        if( !$user_id = $this->_getUserProfileId("admin_list_deposit","R") ){
            return false;
        }

        $limit = $this->input->get('limit');
        $page =  $this->input->get('page');
        $depositid = $this->input->get('depositid');
        if ($object = $this->_service->getAllConfig($limit, $page, $depositid)) {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $object->result, 'total' => count($object)));
            return true;
        }
        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;

    }

    /*
     * @access public
     * @usage this function is used
     * for pulling out all the configuration
     * of a deposit based from the given depositid
     * and history id
     */
    public function getConfigList()
    {
        if( !$user_id = $this->_getUserProfileId("admin_list_deposit","R") ){
            return false;
        }

        $limit = $this->input->get('limit');
        $page =  $this->input->get('page');
        $depositid = $this->input->get('depositid');
        $historyid = $this->input->get('historyid');
        if ($object = $this->_service->getConfigList($limit, $page, $depositid,$historyid)) {
            if(isset($object->result) && !empty($object->result)){
                $object = $object->result->toArray();
                $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $object, 'total' => count($object)));
                return true;
            }

        }
        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;


    }

    /*
     * @access public
     * @usage this function is used
     * for pulling out all the trackers
     * of a deposit based from the given
     * deposit id and historyid
     */
    public function getHistoryUser(){

        if( !$user_id = $this->_getUserProfileId("admin_history_user","R") ){
            return false;
        }
        $limit = $this->input->get('limit');
        $page =  $this->input->get('page');
        $depositid = $this->input->get('depositid');
        $historyid = $this->input->get('historyid');
        if ($object = $this->_service->getHistoryUser($limit, $page, $depositid,$historyid)) {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $object, 'total' => count($object)));
            return true;
        }
        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    /*
     * @access public
     * @usage this function is used
     * for pulling out all the previous user
     * of a deposit based from the given deposit id
     */
    public function getPreviousUser()
    {

        if( !$user_id = $this->_getUserProfileId("admin_history_user","R") ){
            return false;
        }

        $depositid = $this->input->get('depositid');
        if ($object = $this->_service->getPreviousUser($depositid)) {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $object, 'total' => count($object)));
            return true;
        }
        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }


    /*
     * @access public
     * @usage this function is used
     * to pull out all the previous email trackers
     * of a deposit based from the given depositid
     */
    public function getPreviousEmail()
    {

        if( !$user_id = $this->_getUserProfileId("admin_history_email","R") ){
            return false;
        }

        $depositid = $this->input->get('depositid');
        if ($object = $this->_service->getPreviousEmail($depositid)) {

            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $object, 'total' => count($object)));
            return true;
        }
        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }


    /*
     * @access public
     * @usage this function is used for pulling
     * out the email tracker of a deposit record
     * based from the given deposit id
     */
    public function getEmailByDepositId()
    {

        if( !$user_id = $this->_getUserProfileId("admin_deposit_email","R") ){
            return false;
        }
        $depositid = $this->input->get('depositid');
        if ($object = $this->_service->getEmailByDepositId($depositid)) {

            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $object, 'total' => count($object)));
            return true;
        }
        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;

    }

    /*
     * @access public
     * @usage this function isused for
     * getting the config of the deposit based
     * from the given deposit id
     */
    public function getConfigByDeposit()
    {

        if( !$user_id = $this->_getUserProfileId("admin_list_deposit","R") ){
            return false;
        }

        $depositid = $this->input->get('depositid');
        if ($object = $this->_service->getConfigByDeposit($depositid)) {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $object, 'total' => count($object)));
            return true;
        }
        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    /*
     * @access public
     * @usage this function is used for
     * pulling out the pending tracker emails
     * based from the given deposit id
     */
    public function getPendingTrackerEmail()
    {
        if( !$user_id = $this->_getUserProfileId("admin_deposit_email","R") ){
            return false;
        }


        $depositid = $this->input->get('depositid');
        $config = new DepositTracker();
        $config->setId($depositid);
        if ($object = $this->_service->getPendingTrackerEmail($config)) {
            $this->_service->setResponseCode(MessageCode::CODE_GET_TRACKERS_SUCCESS);
            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $object, 'total' => count($object)));
            return true;
        }
        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    /*
     * @access public
     * @usage this function is used to pull
     * out the last approved email based from
     * the givn deposit id. it's actually used
     * in the update deposit config page of the
     * admin panel
     */
    public function getLastApprovedEmail()
    {
        if( !$user_id = $this->_getUserProfileId("admin_history_email","R") ){
            return false;
        }

        $depositid = $this->input->get('depositid');
        $config = new DepositTracker();
        $config->setId($depositid);
        if ($object = $this->_service->getLastApprovedEmail($config)) {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $object, 'total' => count($object)));
            return true;
        }
        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    /*
     * @access public
     * @usage this function is used
     * for pulling out the deposit holders
     * who already belong to an approved
     * deposit tracker data, this one
     * is used for the logic in the drop
     * down menu when adding or updating
     * and existing deposit tracker record
     */
    public function getApprovedDepositHolders()
    {
        if( !$user_id = $this->_getUserProfileId("admin_deposit_holder","R") ){
            return false;
        }
        if ($object = $this->_service->getApprovedDepositHolders()) {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $object, 'total' => count($object)));
            return true;
        }
        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }


    /*
     * @access public
     * @usage this function is used
     * for getting the data from the deposit
     * tracker history table
     */
    public function historyCheck()
    {

        if( !$user_id = $this->_getUserProfileId("deposit_tracker_common_access","R") ){
            return false;
        }

        $depositid = $this->input->get('depositid');
        $config = new DepositTracker();
        $config->setDepositTrackerId($depositid);
        if ($object = $this->_service->historyCheck($config)) {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $object, 'total' => count($object)));
            return true;
        }
        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;

    }

    /*
     * @access private
     * @usage this function is used
     * for checking the deposit tracker data
     * if it's a new record or not.
     * it's an internal function.
     */
    private function isDepositNew($depositid)
    {
        $config = new DepositTracker();
        $config->setDepositTrackerId($depositid);
        $object = $this->_service->isDepositNew($config);
        if(empty($object)){
            return true;
        }
        return false;
    }




    /*
     * @access public
     * @usage this function is used
     * for getting the deposit tracker reason
     * based from the type, action, action owner
     * this function is used in the popup dropdown
     * options of topup and deduction
     */
    public function getDepositReason()
    {

        if( !$user_id = $this->_getUserProfileId("deposit_tracker_common_access","R") ){
            return false;
        }

        $type = $this->input->get('type');
        $action = $this->input->get('action');
        $action_owner = $this->input->get('action_owner');

        $reasonFactory = DepositTrackerReasonsServiceFactory::build();
        if($object = $reasonFactory->getDepositReason($type,$action,$action_owner)){
            $this->_respondWithSuccessCode($reasonFactory->getResponseCode(), array('result' => $object, 'total' => count($object)));
            return true;
        }
        $this->_respondWithCode($reasonFactory->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }




}
