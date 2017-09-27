<?php

use Iapps\Common\DepositTracker\DepositTrackerRequestRepository;
use Iapps\Common\DepositTracker\DepositTrackerRequestService;
use Iapps\Common\DepositTracker\DepositTrackerRequestListener;
use Iapps\Common\DepositTracker\DepositTracker;
use Iapps\Common\Core\IpAddress;
use Iapps\Common\Helper\ResponseHeader;

class Deposit_tracker_request extends Admin_Base_Controller
{
    protected $_service;

    function __construct()
    {
//        parent::__construct();
//
//        $this->load->model('common/Deposit_tracker_request_model');
//        $repo = new DepositTrackerRequestRepository($this->Deposit_tracker_request_model);
//        $this->_service = new DepositTrackerRequestService($repo);
//
//        $this->_service->setIpAddress(IpAddress::fromString($this->_getIpAddress()));
    }

//    public function getDepositTrackerRequestList()
//    {
//
//        $page = $this->_getPage();
//        $limit = $this->_getLimit();
//
//        if( $object = $this->_service->getDepositTrackerRequestList( $limit, $page) )
//        {
//            $this->_respondWithSuccessCode($this->_service->getResponseCode(),array('result' => $object->result->toArray(), 'total' => $object->total));
//            return true;
//        }
//
//        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
//        return false;
//    }

//    public function getDepositTrackerRequestByServiceProviderandCountryCurrency()
//    {
//
//        $service_provider_id   = $this->input->get("service_provider_id");
//        $country_currency_code = $this->input->get("country_currency_code");
//
//        if( $info = $this->_service->findByServiceProviderAndCountryCurrencyCode($service_provider_id, $country_currency_code) )
//        {
//            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $info));
//            return false;
//        }
//
//        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
//        return false;
//    }


//    public function getDepositTrackerRequestInfo()
//    {
//
//        if( !$this->is_required($this->input->get(), array('id')) )
//        {
//            return false;
//        }
//
//        $id = $this->input->get("id");
//
//        if( $info = $this->_service->getDepositTrackerRequestInfo($id) )
//        {
//            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $info));
//            return false;
//        }
//
//        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
//        return false;
//    }

//    public function addDepositTrackerRequest($config)
//    {
//
//        if( !$this->is_required($this->input->post(), array('service_provider_id', 'country_currency_code', 'min_threshold', 'balance')) )
//        {
//            return false;
//        }
//
//        $service_provider_id   = $this->input->post("service_provider_id");
//        $country_currency_code = $this->input->post("country_currency_code");
//        $min_threshold         = $this->input->post("min_threshold");
//        $balance               = $this->input->post("balance");
//
//        $config = new DepositTracker();
//        $config->setServiceProviderId($service_provider_id);
//        $config->setCountryCurrencyCode($country_currency_code);
//        $config->setMinThreshold($min_threshold);
//        $config->setBalance($balance);
//
//
//        if( $config = $this->_service->addDepositTracker($config) )
//        {
//            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $config));
//            return true;
//        }
//
//        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
//        return false;
//    }

//    public function editDepositTrackerRequest()
//    {
//
//        if( !$this->is_required($this->input->post(), array('service_provider_id', 'country_currency_code', 'min_threshold', 'balance')) )
//        {
//            return false;
//        }
//
//        $service_provider_id   = $this->input->post("service_provider_id");
//        $country_currency_code = $this->input->post("country_currency_code");
//        $min_threshold         = $this->input->post("min_threshold");
//        $balance               = $this->input->post("balance");
//
//        $config = new DepositTracker();
//        $config->setServiceProviderId($service_provider_id);
//        $config->setCountryCurrencyCode($country_currency_code);
//        $config->setMinThreshold($min_threshold);
//        $config->setBalance($balance);
//
//
//        if( $config = $this->_service->editDepositTracker($config) )
//        {
//            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $config));
//            return true;
//        }
//
//        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
//        return false;
//    }

    

}
