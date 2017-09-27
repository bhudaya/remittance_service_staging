<?php

use Iapps\Common\Core\IpAddress;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\Common\Microservice\AccountService\AccessType;
use Iapps\RemittanceService\Common\FunctionCode;
use Iapps\RemittanceService\Reports\AgungCustomerReport\AgungCustomerReportService;
use Iapps\RemittanceService\Reports\AgungCustomerReport\AgungCustomerReportRepository;

class Agung_customer_report extends Base_Controller{

    protected $_service;
    function __construct()
    {
        parent::__construct();

        $this->_service = new AgungCustomerReportService();
        $this->_service->setIpAddress(IpAddress::fromString($this->_getIpAddress()));  
    }

    protected function _checkDate($date_from, $date_to)
    {
        if ($date_from > $date_to)
            return false;

        return true;
    }

    public function getAgungCustomerReport()
    {   

        if( !$admin_id = $this->_getUserProfileId(FunctionCode::VIEW_TEKTAYA_REPORT, AccessType::READ) )
            return false;

        // $admin_id = '2ad948a1-8848-40cd-bce5-9c5a73f639a5';
        if( !$this->is_required($this->input->post(), array('date_from','date_to','report_lang')))
        {
            return false;
        }

        $date_from = $this->input->post('date_from');
        $date_to = $this->input->post('date_to');
        $report_lang = $this->input->post('report_lang');

        if (!$this->_checkDate($date_from, $date_to))
            return false;

        if ($result = $this->_service->getAgungCustomerReport($admin_id,$date_from,$date_to,$report_lang)) {

            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $result,'total' => count($result)));
            return true;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

}