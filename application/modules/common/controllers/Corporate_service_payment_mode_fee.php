<?php defined('BASEPATH') OR exit('No direct script access allowed');

use Iapps\Common\CorporateService\CorporateServicePaymentModeFeeRepository;
use Iapps\Common\CorporateService\CorporateServicePaymentModeFeeService;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\Common\Helper\InputValidator;
use Iapps\Common\Core\IappsDateTime;

class Corporate_service_payment_mode_fee extends Base_Controller {

    protected $_service;
    protected $_table_name = "iafb_remittance.corporate_service_payment_mode_fee";

    function __construct()
    {
        parent::__construct();

        $this->load->model('common/corporate_service_payment_mode_fee_model');
        $repo = new CorporateServicePaymentModeFeeRepository($this->corporate_service_payment_mode_fee_model);
        $this->_service = new CorporateServicePaymentModeFeeService($repo, $this->_table_name);
    }

    public function getCorporateServicePaymentModeFeeByCorporateServicePaymentModeId()
    {
        if( !$user_id = $this->_get_user_id() )
        {
            return false;
        }

        if( !$this->is_required($this->input->get(), array('corporate_service_payment_mode_id')) )
        {
            return false;
        }

        $corporate_service_payment_mode_id = $this->input->get("corporate_service_payment_mode_id");

        if( $object = $this->_service->getPaymentModeFeeByCorporateServicePaymentModeId($corporate_service_payment_mode_id) )
        {//todo Pagination
            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $object->result->toArray(), 'total' => $object->total));
            return false;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getCorporateServicePaymentModeFeeInfo()
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

        if( $paymentModeFeeInfo = $this->_service->getPaymentModeFee($id) )
        {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $paymentModeFeeInfo));
            return false;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function addCorporateServicePaymentModeFee()
    {
        if( !$user_id = $this->_get_user_id() )
        {
            return false;
        }

        if( !$this->is_required($this->input->post(), array('corporate_service_payment_mode_id', 'is_percentage', 'name', 'fee', 'converted_fee', 'converted_fee_country_currency_code', 'service_provider_id')) )
        {
            return false;
        }

        $corporate_service_payment_mode_id = $this->input->post("corporate_service_payment_mode_id");
        $is_percentage = $this->input->post("is_percentage");
        $name = $this->input->post("name");
        $fee = $this->input->post("fee");
        $converted_fee = $this->input->post("converted_fee");
        $converted_fee_country_currency_code = $this->input->post("converted_fee_country_currency_code");
        $service_provider_id = $this->input->post("service_provider_id");

        $payment_mode_fee = new \Iapps\Common\CorporateService\CorporateServicePaymentModeFee();
        $payment_mode_fee->setCorporateServicePaymentModeId($corporate_service_payment_mode_id);
        $payment_mode_fee->setIsPercentage($is_percentage);
        $payment_mode_fee->setName($name);
        $payment_mode_fee->setFee($fee);
        $payment_mode_fee->setConvertedFee($converted_fee);
        $payment_mode_fee->setConvertedFeeCountryCurrencyCode($converted_fee_country_currency_code);
        $payment_mode_fee->setServiceProviderId($service_provider_id);

        $this->_service->setUpdatedBy($user_id);

        if( $payment_mode_fee = $this->_service->addPaymentModeFee($payment_mode_fee) )
        {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $payment_mode_fee));
            return true;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function editCorporateServicePaymentModeFee()
    {
        if( !$user_id = $this->_get_user_id() )
        {
            return false;
        }

        if( !$this->is_required($this->input->post(), array('id','corporate_service_payment_mode_id', 'is_percentage', 'name', 'fee','converted_fee', 'converted_fee_country_currency_code', 'service_provider_id')) )
        {
            return false;
        }

        $id = $this->input->post("id");
        $corporate_service_payment_mode_id = $this->input->post("corporate_service_payment_mode_id");
        $is_percentage = $this->input->post("is_percentage");
        $name = $this->input->post("name");
        $fee = $this->input->post("fee");
        $converted_fee = $this->input->post("converted_fee");
        $converted_fee_country_currency_code = $this->input->post("converted_fee_country_currency_code");
        $service_provider_id = $this->input->post("service_provider_id");

        $payment_mode_fee = new \Iapps\Common\CorporateService\CorporateServicePaymentModeFee();
        $payment_mode_fee->setId($id);
        $payment_mode_fee->setCorporateServicePaymentModeId($corporate_service_payment_mode_id);
        $payment_mode_fee->setIsPercentage($is_percentage);
        $payment_mode_fee->setName($name);
        $payment_mode_fee->setFee($fee);
        $payment_mode_fee->setConvertedFee($converted_fee);
        $payment_mode_fee->setConvertedFeeCountryCurrencyCode($converted_fee_country_currency_code);
        $payment_mode_fee->setServiceProviderId($service_provider_id);

        $this->_service->setUpdatedBy($user_id);

        if( $payment_mode_fee = $this->_service->editPaymentModeFee($payment_mode_fee) )
        {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $payment_mode_fee));
            return true;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function removeCorporateServicePaymentModeFee()
    {
        if( !$user_id = $this->_get_user_id() )
        {
            return false;
        }

        if( !$this->is_required($this->input->post(), array('id')) )
        {
            return false;
        }

        $id = $this->input->post("id");

        $payment_mode_fee = new \Iapps\Common\CorporateService\CorporateServicePaymentModeFee();
        $payment_mode_fee->setId($id);

        $this->_service->setUpdatedBy($user_id);


        if( $this->_service->removePaymentModeFee($payment_mode_fee) )
        {
            $this->_respondWithSuccessCode($this->_service->getResponseCode());
            return true;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
}