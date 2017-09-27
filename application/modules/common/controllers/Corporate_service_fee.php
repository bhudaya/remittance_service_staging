<?php defined('BASEPATH') OR exit('No direct script access allowed');

use Iapps\Common\CorporateService\CorporateServiceFeeRepository;
use Iapps\RemittanceService\Common\CorporateServiceFeeExtendedService;
use Iapps\Common\CorporateService\CorporateServiceFee;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\Common\Helper\InputValidator;
use Iapps\Common\Core\IappsDateTime;

class Corporate_service_fee extends Base_Controller {

    protected $_service;
    protected $_table_name = "iafb_remittance.corporate_service_fee";

    function __construct()
    {
        parent::__construct();

        $this->load->model('common/Corporate_service_fee_model');
        $repo = new CorporateServiceFeeRepository($this->Corporate_service_fee_model);
        $this->_service = new CorporateServiceFeeExtendedService($repo, $this->_table_name);
    }

    public function getCorporateServiceFeeInfo()
    {
        if( !$user_id = $this->_get_user_id() )
        {
            return false;
        }

        if( !$this->is_required($this->input->get(), array('id')) )
        {
            return false;
        }

        $id = $this->input->get("id");

        if( $corpServFeeInfo = $this->_service->getFee($id) )
        {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $corpServFeeInfo));
            return false;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getCorporateServiceFeeByCorpServId()
    {
        if( !$user_id = $this->_get_user_id() )
        {
            return false;
        }

        if( !$this->is_required($this->input->get(), array('corporate_service_id')) )
        {
            return false;
        }

        $corporate_service_id = $this->input->get("corporate_service_id");

        if( $corpServFeeList = $this->_service->getCorpServiceFeeByCorpServId($corporate_service_id) )
        {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $corpServFeeList));
            return true;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }


    public function addCorpServiceFee()
    {
        if( !$user_id = $this->_get_user_id() )
        {
            return false;
        }

        if( !$this->is_required($this->input->post(), array('corporate_service_id',
            'service_provider_id',
            'fee_type_code',
            'transaction_fee',
            'name',
            'original_transaction_fee',
            'original_country_currency_code')))
        {
            return false;
        }

        $corporate_service_id = $this->input->post("corporate_service_id");
        $service_provider_id = $this->input->post("service_provider_id");
        $fee_type_code = $this->input->post("fee_type_code");
        $transaction_fee = $this->input->post("transaction_fee");
        $name = $this->input->post("name");
        $original_transaction_fee = $this->input->post("original_transaction_fee");
        $original_country_currency_code = $this->input->post("original_country_currency_code");

        $corpServFee = new \Iapps\Common\CorporateService\CorporateServiceFee();
        $corpServFee->setCorporateServiceId($corporate_service_id);
        $corpServFee->setServiceProviderId($service_provider_id);
        $corpServFee->setTransactionFee($transaction_fee);
        $corpServFee->setName($name);
        $corpServFee->setOriginalTransactionFee($original_transaction_fee);
        $corpServFee->setOriginalCountryCurrencyCode($original_country_currency_code);

        $this->_service->setUpdatedBy($user_id);
        if( $corpServFee = $this->_service->addCorpServiceFee($corpServFee, $fee_type_code) )
        {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $corpServFee));
            return true;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function editCorpServiceFee()
    {
        if( !$user_id = $this->_get_user_id() )
        {
            return false;
        }

        if( !$this->is_required($this->input->post(), array('id',
            'service_provider_id',
            'fee_type_code',
            'transaction_fee',
            'name',
            'original_transaction_fee',
            'original_country_currency_code')))
        {
            return false;
        }

        $id = $this->input->post("id");
        $service_provider_id = $this->input->post("service_provider_id");
        $fee_type_code = $this->input->post("fee_type_code");
        $transaction_fee = $this->input->post("transaction_fee");
        $name = $this->input->post("name");
        $original_transaction_fee = $this->input->post("original_transaction_fee");
        $original_country_currency_code = $this->input->post("original_country_currency_code");

        $corpServFee = new \Iapps\Common\CorporateService\CorporateServiceFee();
        $corpServFee->setId($id);
        $corpServFee->setServiceProviderId($service_provider_id);
        $corpServFee->setTransactionFee($transaction_fee);
        $corpServFee->setName($name);
        $corpServFee->setOriginalTransactionFee($original_transaction_fee);
        $corpServFee->setOriginalCountryCurrencyCode($original_country_currency_code);

        $this->_service->setUpdatedBy($user_id);
        if( $corpServFee = $this->_service->editCorpServiceFee($corpServFee, $fee_type_code) )
        {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $corpServFee));
            return true;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

}