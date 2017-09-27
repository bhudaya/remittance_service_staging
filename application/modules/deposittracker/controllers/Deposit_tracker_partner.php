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


class Deposit_tracker_partner extends Partner_Base_Controller
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
    * @usage this function will get all the
    * remittance configuration record
    * based from the given
    * limit
    */
    public function getAllRemittanceConfig()
    {
        if( !$user_id = $this->_getUserProfileId("partner_list_remittance","R") ){
            return false;
        }

        $limit = $this->_getLimit();
        $page = $this->_getPage();

        if ($object = $this->_remittance_config_service->getAllRemittanceConfig($limit, $page)) {

            $result_array = $object->result != null ? $object->result->toArray() : null;

            $this->_respondWithSuccessCode($this->_remittance_config_service->getResponseCode(), array('result' => $result_array, 'total' => $object->total));
            return true;
        }

        $this->_respondWithCode($this->_remittance_config_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
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
    public function partnerViewDeposit()
    {
        if(!$user_id = $this->_getUserProfileId("partner_view_deposit","R") ){
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
     * @usage this function is used for
     * pulling the deposit tracker records
     * for the partner web
     */
    public function listDepositPartner()
    {

        if( !$user_id = $this->_getUserProfileId("partner_list_deposit","R") ){
            return false;
        }


        $limit = $this->input->get('limit');
        $page =  $this->input->get('page');
        $remittance_config_id = $this->input->get('remittance_config_id');
        $depositholder = $this->input->get('depositholder');
        $userid = $this->input->get('userid');
        $depositholders = $this->input->get('depositholders');

        if(!empty($remittance_config_id) || !empty($depositholder)){

            if($object = $this->_service->getDepositByParam($limit,$page,$remittance_config_id,$depositholder)){
                $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $object, 'total' => count($object)));
                return true;
            }

        } else {
            if ($object = $this->_service->getPartnerDepositList($limit, $page,$userid,$depositholders)) {
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
     * @usage this function will
     * pull out the transaction
     * under the deposit based
     * from the given depositid
     * for the partner panel
     */
    public function partnerListTransaction()
    {

        if(!$user_id = $this->_getUserProfileId("partner_list_transaction","R") ){
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
    * @usage this function is used for
    * pulling all the pending topup records
    * based from the given deposit id, limit,
    * page, and userid
    */
    public function listPendingTopup()
    {

        if( !$user_id = $this->_getUserProfileId("partner_list_pending_topup","R") ){
            return false;
        }
        $depositid = $this->input->get('depositid');
        $page = $this->input->get('page');//$this->_getPage();
        $limit = $this->input->get('limit');//$this->_getLimit();
        $userid = $this->input->get('userid');
        if( $object = $this->_deposit_request_service->getPendingTopupList( $limit, $page,$depositid, $userid))
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
  * pulling out all the topup records
  * based from the given deposit id,
  * limit and user id
  */
    public function listTopup()
    {

        if( !$user_id = $this->_getUserProfileId("partner_list_topup","R") ){
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
     * @usage this function
     * will pull the deposit topup
     * data for the partner panel
     */
    public function partnerViewTopup()
    {

        if( !$user_id = $this->_getUserProfileId("partner_view_topup","R")){
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
     * reason for the partner panel
     */
    public function partnerRejectTopup()
    {
        if(!$user_id = $this->_getUserProfileId("partner_reject_topup","R")){
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
    * @usage this function is used for
    * approving the existing pending topup
    * request record based from the given
    * depositid, topup id, readon, amount,
    * approvetopup_reason.
    */
    public function approveTopup()
    {
        if(!$user_id = $this->_getUserProfileId("partner_approve_topup","W")){
            return false;
        }

        $deposit_tracker_id = $this->input->post('depositid');
        $topupid            = $this->input->post('topupid');
        $reason             = $this->input->post('reason');
        $amount             = $this->input->post('amount');
        $approvetopup_reason = $this->input->post('approvetopup_reason');
        $config = new DepositTrackerRequest();
        $config->setId($topupid);
        $config->setDepositTrackerId($deposit_tracker_id);
        $config->setUpdatedBy($user_id);
        $config->setApprovedRejectedBy($user_id);
        $config->setAmount($amount);
        $config->setApprovedRejectedRemarks($reason);
        $config->setApproveTopupReason($approvetopup_reason);

        if($approvetopup_reason == 777){
            $config->setApprovedRejectedRemarks($reason);
        } else {
            $config->setApprovedRejectedRemarks($approvetopup_reason);
        }

        $config->setStatus(DepositTrackerConstants::APPROVED);

        if( $config = $this->_deposit_request_service->approveTopup($config) )
        {
            $this->_respondWithSuccessCode($this->_deposit_request_service->getResponseCode(), array('result' => $config));
//            $this->_respondWithSuccessCodeAndCustomMessage($this->_deposit_request_service->getResponseCode(), $this->_deposit_request_service->getResponseMessage(), $config);
            return true;
        }

        $this->_respondWithCode($this->_deposit_request_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND, "","",$this->_deposit_request_service->getResponseMessage());
        return false;

    }


    /*
     * @access public
     * @usage this function
     * will pull out the deduction list
     * based from the deposit id for
     * the panel panel
     */
    public function partnerListDeduction()
    {
        if( !$user_id = $this->_getUserProfileId("partner_list_deduction","R")){
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
     * @usage this function will
     * pull out the data for the
     * deduction abased from the given
     * deduction id for the partner panel
     */
    public function partnerViewDeduction()
    {
        if( !$user_id = $this->_getUserProfileId("partner_view_deduction","R")){
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
     * @usage this function is used
     * for uploading deduction photo
     * and pdf files to amazon s3
     */
    public function deductionUpload()
    {

        if( !$user_id = $this->_getUserProfileId("partner_upload_deduction","W") ){
            return false;
        }

        $img_name = GuidGenerator::generate();
        $s3PhotoImage = new DepositTopupS3Uploader($img_name);
        $s3PhotoImage->setS3Folder('deduction_photo/');
        if($s3PhotoImage->uploadtoS3('photo')){
            $result = $s3PhotoImage->getUrl();
            $result['photoname'] = $_FILES['photo']['name'];
            $result['s3photoname'] = $s3PhotoImage->getFileName();
            $this->_respondWithSuccessCodeAndCustomMessage(MessageCode::CODE_PHOTO_UPLOAD_SUCCESS,'Photo Uploaded', array('result' => $result));
            return true;
        } else {

            if(mime_content_type($_FILES['photo']['tmp_name']) == 'application/pdf' || mime_content_type($_FILES['photo']['type']) == 'application/octet-stream') {
                $pdfname = GuidGenerator::generate();
                $pdf = new DepositTrackerFileUploader($pdfname);
                $pdf->setUploadPath('./upload/document/');
                $pdf->setAllowedType('jpg|png|pdf');
                $pdf->setS3Folder('remittance/deduction/document/');
                $pdf->uploadtoS3('photo');
                $result['photoname'] = $_FILES['photo']['name'];
                $result['s3photoname'] = $pdf->getFileName();
                $result['url'] = $pdf->getUrl();
                $result['type'] = 'pdf';
                $this->_respondWithSuccessCodeAndCustomMessage(MessageCode::CODE_PHOTO_UPLOAD_SUCCESS, 'Photo Uploaded', array('result' => $result));
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
     * @usage this funciton is used for
     * processing the existing deduction request
     * record based from the given
     * deposit id, deductionid, reason, porcessdate,
     * processby , photo, photoname, s3phtooname, reason,
     * status, bank, transfer reference number
     */
    public function processDeduction()
    {

        if( !$user_id = $this->_getUserProfileId("partner_process_deduction","W") ){
            return false;
        }


        $deposit_tracker_id = $this->input->post('depositid');
        $deductionid        = $this->input->post('deductionid');
        $reason             = $this->input->post('reason');
        $deductionamount    = $this->input->post('deductionamount');
        $processdate        = $this->input->post('processdate');
        $processby          = $this->input->post('processby');
        $photo              = $this->input->post('photo');
        $photoname          = $this->input->post('photoname');
        $s3photoname        = $this->input->post('s3photoname');
        $reason             = $this->input->post('reason');
        $status             = $this->input->post('status');
        $bank               = $this->input->post('bank');
        $transferno         = $this->input->post('transferno');
        $processdate        = \Iapps\Common\Core\IappsDateTime::fromString($processdate);
        $config = new DepositTrackerRequest();
        $config->setId($deductionid);
        $config->setDepositTrackerId($deposit_tracker_id);
        $config->setUpdatedBy($user_id);
        $config->setApprovedRejectedBy($user_id);
        $config->setProcessedBy($user_id);
        $config->setProcessedAt($processdate);
        $config->setAmount($deductionamount);
        $config->setTransProofUrl($photo);
        $config->setPhotoName($photoname);
        $config->setS3PhotoName($s3photoname);
        $config->setUpdatedBy($user_id);
        $config->setBank($bank);
        $config->setTransReferenceNum($transferno);
        if(isset($reason) && !empty($reason)) {
            $config->setApprovedRejectedRemarks($reason);
        }

        switch($status){
            case 'approved':
                $config->setStatus(DepositTrackerConstants::APPROVED);
                break;
            case 'rejected':
                $config->setStatus(DepositTrackerConstants::DEPOSIT_STATUS_REJECTED);
                break;
        }


        if( $config = $this->_deposit_request_service->processDeduction($config) )
        {
            if($status == 'approved') {
                $this->_respondWithSuccessCode($this->_deposit_request_service->getResponseCode(), array('result' => $config));
                return true;
            } else if ($status == 'rejected'){
//               $this->_respondWithSuccessCode($this->_deposit_request_service->getResponseCode(), array('result' => $config));
                $this->_respondWithSuccessCodeAndCustomMessage($this->_deposit_request_service->getResponseCode(), $this->_deposit_request_service->getResponseMessage(), $config);
                return true;
            }
        }

        $this->_respondWithSuccessCodeAndCustomMessage($this->_deposit_request_service->getResponseCode(), $this->_deposit_request_service->getResponseMessage(), $config);
//        $this->_respondWithCode($this->_deposit_request_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND, "","",$this->_deposit_request_service->getResponseMessage());
        return false;
    }



    /*
     * @access public
     * @usage this function is pulling
     * out all the pending topup records
     * based from the given remittance config id,
     * deposit holder and userid , it's meant
     * for the partner web
     */
    public function getPartnerPendingTopup()
    {

        if( !$user_id = $this->_getUserProfileId("partner_view_topup","R") ){
            return false;
        }

        $limit = $this->input->get('limit');
        $page = $this->input->get('page');
        $remittance_config_id = $this->input->get('channelid');
        $depositholder = $this->input->get('depositholder');
        $userid = $this->input->get('userid');

        if(!empty($remittance_config_id) || !empty($depositholder)) {
            $data = '';
            if ($object = $this->_deposit_request_service->getPartnerPendingTopup($limit, $page, $userid)) {
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
            if ($object = $this->_deposit_request_service->getPartnerPendingTopup($limit, $page, $userid)) {
                if(isset($object->result)){
                    $data = $object->result;
                } else {
                    $data = $object;
                }
                $this->_respondWithSuccessCode($this->_deposit_request_service->getResponseCode(), array('result' => $data, 'total' => count($data)));
                return true;
            }
        }
        $this->_respondWithCode($this->_deposit_request_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }



    /*
     * @access public
     * @usage this function is used for
     * pulling out the pending deduction records
     * for the partner web
     */
    public function partnerListPendingDeduction()
    {
        if( !$user_id = $this->_getUserProfileId("partner_list_pending_deduction","R")){
            return false;
        }


        $page = $this->input->get('page');
        $limit = $this->input->get('limit');
        $userid = $this->input->get('userid');

        if ($object = $this->_deposit_request_service->partnerListPendingDeduction($limit, $page,$userid)) {
            $this->_respondWithSuccessCode($this->_deposit_request_service->getResponseCode(), array('result' => $object, 'total' => count($object)));
            return true;
        }
        $this->_respondWithCode($this->_deposit_request_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
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
     * @access public
     * @usage this function is used for
     * pulling out the last approved configuration
     * of the particular deposit tracker record
     * based from the given deposit id
     */
    public function getLastApprovedConfig()
    {

        if( !$user_id = $this->_getUserProfileId("partner_view_history","R") ){
            return false;
        }

        $depositid = $this->input->get('depositid');

        if ($object = $this->_service->getLastApprovedConfig($depositid)) {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $object, 'total' => count($object)));
            return true;
        }
        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }


    /*
     * @access public
     * @usage this function is used for
     * pulling out the deposit tracker users
     * based from the given deposit id or history id
     */
    public function getLastApprovedUsers()
    {
        if( !$user_id = $this->_getUserProfileId("partner_view_history","R") ){
            return false;
        }

        $depositid = $this->input->get('depositid');
        $historyid = $this->input->get('historyid');

        if ($object = $this->_service->getLastApprovedUsers($historyid,$depositid)) {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $object, 'total' => count($object)));
            return true;
        }
        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
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




    /*
     * @access public
     * @usage this function is used
     * to pull out the remittance based
     * from the given remittance id
     */
    public function getRemittanceByRemittanceId()
    {
        if( !$user_id = $this->_getUserProfileId("partner_list_deposit","R") ){
            return false;
        }
        $remittanceid = $this->input->get('remittanceid');
        if($object = $this->_deposit_request_service->getRemittanceByRemittanceId($remittanceid)){
            $this->_respondWithSuccessCode($this->_deposit_request_service->getResponseCode(),array('result' => $object,'total' => count($object)));
            return true;
        }
        $this->_respondWithCode($this->_deposit_request_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
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
        if(count($object) > 0){
            return false;
        }
        return true;
    }





}