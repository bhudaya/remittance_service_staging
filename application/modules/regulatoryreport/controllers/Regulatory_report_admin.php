<?php

use Iapps\Common\Core\IpAddress;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\RemittanceService\Reports\RegulatoryReport\RegulatoryReportService;
use Iapps\RemittanceService\Reports\RegulatoryReport\RegulatoryReportRepository;
use Iapps\RemittanceService\Common\FunctionCode;
use Iapps\Common\Microservice\AccountService\AccessType;

class Regulatory_report_admin extends Admin_Base_Controller{

    protected $_report_serv;
    function __construct()
    {
        parent::__construct();
        $this->load->model('regulatoryreport/Regulatory_report_model');
        $repo = new RegulatoryReportRepository($this->Regulatory_report_model);
        $this->_report_serv = new RegulatoryReportService($repo);
    }

    protected function _checkDate($start_time, $end_time)
    {
        if ($start_time > $end_time)
            return false;

        $time1 = new DateTime($start_time);
        $time2 = new DateTime($end_time);
        $interval = $time1->diff($time2);
        if ($interval->format('%m') > 3) {
            return false;
        }

        return true;
    }

    public function getReport()
    {   

        if( !$admin_id = $this->_getUserProfileId(FunctionCode::ADMIN_GENERATE_REGULATORY_REPORT, AccessType::READ) )
            return false;

        if( !$this->is_required($this->input->post(), array('report_type','start_time','end_time')))
        {
            return false;
        }

        $start_time = $this->input->post('start_time');
        $end_time = $this->input->post('end_time');
        $report_type = $this->input->post('report_type');

        if (!$this->_checkDate($start_time, $end_time))
            return false;


        if ($report_type == 'funds_accepted_summary_report') {
            $result = $this->_report_serv->getFundsAcceptedSummaryReport($start_time,$end_time);

        }elseif ($report_type == 'funds_remitted_summary_report') {
            $result = $this->_report_serv->getFundsRemittedSummaryReport($start_time,$end_time);

        }elseif ($report_type == 'remittance_transaction_report') {
            $result = $this->_report_serv->getRemittanceTransactionReport($start_time,$end_time);
            
        }elseif ($report_type == 'statement_of_remittance_report') {
            $result = $this->_report_serv->getStatementOfRemittanceReport($start_time,$end_time);

        }

        if ($result) {
            $filePath = $this->export_report_pdf($result,$report_type);
            $this->_respondWithSuccessCode($this->_report_serv->getResponseCode(), array('result' => $filePath));
            return true;
        }

        $this->_respondWithCode($this->_report_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

}