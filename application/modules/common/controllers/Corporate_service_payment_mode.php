<?php defined('BASEPATH') OR exit('No direct script access allowed');

use Iapps\Common\CorporateService\CorporateServicePaymentModeRepository;
use Iapps\RemittanceService\Common\CorporateServicePaymentModeExtendedService;
use Iapps\Common\CorporateService\CorporateServicePaymentModeFeeRepository;
use Iapps\Common\CorporateService\CorporateServicePaymentModeFeeService;
use Iapps\Common\CorporateService\CorporateServiceRepository;
use Iapps\Common\CorporateService\CorporateServService;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\Common\Helper\InputValidator;
use Iapps\Common\Core\IappsDateTime;
use Iapps\RemittanceService\Common\RemittanceCorporateServicePaymentModeRepository;

class Corporate_service_payment_mode extends Base_Controller {

    protected $_service;
    protected $_table_name = "iafb_remittance.corporate_service_payment_mode";

    function __construct()
    {
        parent::__construct();

        $this->load->model('common/corporate_service_payment_mode_model');
        $repo = new RemittanceCorporateServicePaymentModeRepository($this->corporate_service_payment_mode_model);
        $this->_service = new CorporateServicePaymentModeExtendedService($repo, $this->_table_name);
    }

    public function getCorporateServicePaymentModeByCorporateServiceId()
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

        if( $object = $this->_service->getSupportedPaymentMode($corporate_service_id) )
        {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $object->result->toArray(), 'total' => $object->total));
            return false;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getCorporateServicePaymentModeInfo()
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

        if( $paymentModeInfo = $this->_service->getPaymentModeInfo($id) )
        {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $paymentModeInfo));
            return false;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }


    public function addCorporateServicePaymentMode()
    {
        if( !$user_id = $this->_get_user_id() )
        {
            return false;
        }

        if( !$this->is_required($this->input->post(), array('corporate_service_id', 'payment_mode_code', 'direction', 'is_default')) )
        {
            return false;
        }

        $corporate_service_id = $this->input->post("corporate_service_id");
        $payment_mode_code = $this->input->post("payment_mode_code");
        $direction = $this->input->post("direction");
        $is_default = $this->input->post("is_default");
        $role_id = $this->input->post("role_id") ? $this->input->post("role_id") : null;

        $payment_mode = new \Iapps\Common\CorporateService\CorporateServicePaymentMode();
        $payment_mode->setCorporateServiceId($corporate_service_id);
        $payment_mode->setPaymentCode($payment_mode_code);
        $payment_mode->setDirection($direction);
        $payment_mode->setIsDefault($this->convertStringToBooleanInt($is_default));
        $payment_mode->setRoleId($role_id);

        $this->_service->setUpdatedBy($user_id);

        if( $payment_mode = $this->_service->addPaymentMode($payment_mode) )
        {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $payment_mode));
            return true;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function editCorporateServicePaymentMode()
    {
        if( !$user_id = $this->_get_user_id() )
        {
            return false;
        }

        if( !$this->is_required($this->input->post(), array('id', 'corporate_service_id', 'payment_mode_code', 'direction', 'is_default')) )
        {
            return false;
        }

        $id = $this->input->post("id");
        $corporate_service_id = $this->input->post("corporate_service_id");
        $payment_mode_code = $this->input->post("payment_mode_code");
        $direction = $this->input->post("direction");
        $is_default = $this->input->post("is_default");
        $role_id = $this->input->post("role_id");

        $payment_mode = new \Iapps\Common\CorporateService\CorporateServicePaymentMode();
        $payment_mode->setId($id);
        $payment_mode->setCorporateServiceId($corporate_service_id);
        $payment_mode->setPaymentCode($payment_mode_code);
        $payment_mode->setDirection($direction);
        $payment_mode->setIsDefault($this->convertStringToBooleanInt($is_default));
        $payment_mode->setRoleId($role_id);

        $this->_service->setUpdatedBy($user_id);

        if( $payment_mode = $this->_service->editPaymentMode($payment_mode) )
        {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $payment_mode));
            return true;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function removeCorporateServicePaymentMode()
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

        $payment_mode = new \Iapps\Common\CorporateService\CorporateServicePaymentMode();
        $payment_mode->setId($id);

        $this->_service->setUpdatedBy($user_id);

        if( $this->_service->removePaymentMode($payment_mode) )
        {
            $this->_respondWithSuccessCode($this->_service->getResponseCode());
            return true;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getCorporateServicePaymentModeWithFeeByCorporateServiceId()
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

        if( $result = $this->_service->getCorpServicePaymentModeWithFeeByCorpServId($corporate_service_id) )
        {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }



}