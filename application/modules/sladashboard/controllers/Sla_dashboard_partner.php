<?php

use Iapps\RemittanceService\Common\FunctionCode;
use Iapps\Common\Microservice\AccountService\AccessType;
use Iapps\Common\Core\IpAddress;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\RemittanceService\SlaDashboard\SlaDashboardTransactionRepository;
use Iapps\RemittanceService\SlaDashboard\SlaDashboardTransactionService;

class Sla_dashboard_partner extends Partner_Base_Controller{

    protected $_service;    
    
    function __construct()
    {
        parent::__construct();

        $this->load->model('sladashboard/Sla_transaction_model');
        $repo = new SlaDashboardTransactionRepository($this->Sla_transaction_model);
        $this->_service = new SlaDashboardTransactionService($repo);
        $this->_service->setIpAddress(IpAddress::fromString($this->_getIpAddress())); 
    }
    public function getSLARemittanceTransactionStatus()
    {
        if( !$login_user_id = $this->_getUserProfileId(FunctionCode::VIEW_REMITTANCE_DASHBOARD, AccessType::READ) )
            return false;

        if( !$this->is_required($this->input->post(), array('sla_remittance_cut_off_time',
                                                            'sla_commerce_time_from',
                                                            'sla_commerce_time_to',
                                                            'sla_remittance_on_time',
                                                            'sla_remittance_warning_time',
                                                            'date')) )
        {
            return false;
        }

        $this->_service->setUpdatedBy($login_user_id);

        $sla_commerce_time_from = $this->input->post('sla_commerce_time_from');
        $sla_commerce_time_to = $this->input->post('sla_commerce_time_to');
        $sla_remittance_cut_off_time = $this->input->post('sla_remittance_cut_off_time');
        $sla_remittance_on_time = $this->input->post('sla_remittance_on_time');
        $sla_remittance_warning_time = $this->input->post('sla_remittance_warning_time');
        $date = $this->input->post('date');

        if( !$service_provider_id = $this->_service->getServiceProviderId())
        {
            $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
            return false;
        }

        if($object = $this->_service->getSLARemittanceTransactionStatus($service_provider_id,$sla_commerce_time_from,
                                                                    $sla_commerce_time_to,
                                                                    $sla_remittance_cut_off_time,
                                                                    $sla_remittance_on_time,
                                                                    $sla_remittance_warning_time,
                                                                    $date,
                                                                    false) )
        {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(),array('result' => $object));
            return true;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

}