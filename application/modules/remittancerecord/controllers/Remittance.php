<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
use \Iapps\RemittanceService\RemittanceRecord\RemittanceRecordRepository;
use \Iapps\RemittanceService\RemittanceRecord\RemittanceRecordService;
use Iapps\Common\Core\IappsDateTime;
use \Iapps\Common\Helper\ResponseHeader;
use Iapps\RemittanceService\Common\FunctionCode;
use Iapps\Common\Microservice\AccountService\AccessType;

/**
 * Description of Remittance
 *
 * @author lichao
 */
class Remittance extends Base_Controller{
    //put your code here
    
    protected $_remittance_record_service;
    
    function __construct() {
        parent::__construct();

        $this->load->model('remittancerecord/Remittance_model');
        $repo = new RemittanceRecordRepository($this->Remittance_model);
        $this->_remittance_record_service = new RemittanceRecordService($repo);

        $this->_service_audit_log->setTableName('iafb_remittance.remittance');
    }

    public function getRemittanceTransactionList()
    {
        /*if( !$this->is_required($this->input->get(), array('limit','page')) )
        {
            return false;
        }*/
        $limit = $this->input->get("limit") ? $this->input->get('limit') : 10;
        $page = $this->input->get("page") ? $this->input->get('page') : 1;

        $start_time = $this->input->get('start_time') ? $this->input->get('start_time') : null;
        $end_time = $this->input->get('end_time') ? $this->input->get('end_time') : null;
        if(!empty($this->input->get('prelim_check'))){
            $prelim_check = intval($this->input->get('prelim_check'));
        }else{
            if ($this->input->get('prelim_check') === '') {
                $prelim_check = null;
            } else {
                $prelim_check = intval($this->input->get('prelim_check'));
            }
        }
        $status = $this->input->get('status')?$this->input->get('status'):null;
        if(!empty($start_time))
        {
            $start_time = IappsDateTime::fromString($start_time)->getUnix();
        }
        if(!empty($end_time))
        {
            $end_time = IappsDateTime::fromString($end_time)->getUnix();
        }


        if( $result = $this->_remittance_record_service->getListRemittanceTransaction($limit, $page, $start_time, $end_time, $prelim_check, $status) )
        {
//            $this->_respondWithSuccessCode($this->_remittance_record_service->getResponseCode(), array('result'=>$result->result));
            $this->_respondWithSuccessCode($this->_remittance_record_service->getResponseCode(), array('result' => $result['data'],'total' => $result['total']));
            return true;
        }

        $this->_respondWithCode($this->_remittance_record_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getRemittanceTransactionDetail()
    {
        if( !$this->is_required($this->input->get(),array('id')) ){
            return false;
        }
        $id = $this->input->get('id');
        if( $result = $this -> _remittance_record_service->getRemittanceTransactionDetail($id) )
        {
            $this->_respondWithSuccessCode($this->_remittance_record_service->getResponseCode(),array('result' => $result));
            return true;
        }
        $this->_respondWithCode($this->_remittance_record_service->getResponseCode(),ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

}
