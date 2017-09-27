<?php

use Iapps\Common\Core\IpAddress;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\RemittanceService\Reports\RegulatoryReport\RegulatoryReportService;
use Iapps\RemittanceService\Reports\RegulatoryReport\RegulatoryReportRepository;
use Iapps\RemittanceService\Common\FunctionCode;
use Iapps\Common\Microservice\AccountService\AccessType;
use Iapps\RemittanceService\Reports\NFFReport\NFFReportService;
use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\Helper\InputValidator;

class Regulatory_report_partner extends Partner_Base_Controller{

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
        {
            $this->_response(InputValidator::constructInvalidParamResponse('End date must be greater than start date'));
            return false;
        }
            

        $time1 = new DateTime($start_time);
        $time2 = new DateTime($end_time);
        $interval = $time1->diff($time2);
        if ($interval->format('%m') > 3) {
            $this->_response(InputValidator::constructInvalidParamResponse('Date Range must be within 3 months'));
            return false;
        }

        return true;
    }

    public function getReport()
    {   

        if( !$admin_id = $this->_getUserProfileId(FunctionCode::PARTNER_GENERATE_REGULATORY_REPORT, AccessType::READ) )
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
    
    public function getNFFReport()
    {//start time , end time are datetime in UTC
        if( !$admin_id = $this->_getUserProfileId(FunctionCode::PARTNER_GENERATE_REGULATORY_REPORT, AccessType::READ) )
            return false;
        
        if( !$this->is_required($this->input->get(), array('start_time','end_time')))
            return false;
        
        if( !$mainAgent = $this->_getMainAgent() )
            return false;
        
        $service_provider_id = $mainAgent->getId();
        $statuses = $this->input->get('status') ? explode('|', $this->input->get('status')) : array();
        $customerID = $this->input->get('customerID') ? $this->input->get('customerID') : NULL;
        $start_time = $this->input->get('start_time');
        $end_time = $this->input->get('end_time');        

        if (!$this->_checkDate($start_time, $end_time))
            return false;
        
        $start_time = IappsDateTime::fromString($start_time);
        $end_time = IappsDateTime::fromString($end_time);
        
        //get NFF report
        $reportServ = new NFFReportService();
        $reportServ->setOption('start_time', $start_time);
        $reportServ->setOption('end_time', $end_time);
        $reportServ->setOption('service_provider_id', $service_provider_id);
        if( $customerID )
            $reportServ->setOption('customerID', $customerID);
        if( count($statuses) > 0 )
            $reportServ->setOption('statuses', $statuses);
            
        $fileName = 'NFF_REPORT_' . IappsDateTime::now()->getString() . ".csv";
        if ($content = $reportServ->generateCSVBase64($fileName)) {
            //$filePath = $this->export_report_pdf($result,$report_type);
            $result['file_name'] = $fileName;
            $result['content'] = $content;
            $this->_respondWithSuccessCode($reportServ->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($reportServ->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
}