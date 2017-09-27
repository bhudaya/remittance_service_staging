<?php

use Iapps\RemittanceService\RemittanceCompany\RemittanceCompanyServiceFactory;
use Iapps\Common\Core\IpAddress;
use Iapps\RemittanceService\Common\FunctionCode;
use Iapps\Common\Microservice\AccountService\AccessType;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\RemittanceService\RemittanceCompany\RemittanceCompany;
use Iapps\Common\Helper\InputValidator;

class Remittance_company_admin extends Admin_Base_Controller{

    function __construct()
    {
        parent::__construct();

        $this->_serv = RemittanceCompanyServiceFactory::build();
        $this->_serv->setIpAddress(IpAddress::fromString($this->_getIpAddress()));
    }

    public function listCompany()
    {
        if( !$admin_id = $this->_getUserProfileId(FunctionCode::ADMIN_LIST_COMPANY, AccessType::READ) )
            return false;

        $page = $this->_getPage();
        $limit = $this->_getLimit();

        $this->_serv->setUpdatedBy($admin_id);
        if( $result = $this->_serv->getList($page, $limit) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getCompany()
    {
        if( !$admin_id = $this->_getUserProfileId(FunctionCode::ADMIN_GET_COMPANY, AccessType::READ) )
            return false;

        $this->_serv->setUpdatedBy($admin_id);

        //service_provider id
        if( !$this->is_required($this->input->get(), array('service_provider_id')))
            return false;

        $service_provider_id = $this->input->get('service_provider_id');

        if( $result = $this->_serv->getByServiceProviderId($service_provider_id, true) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function editCompany()
    {
        if( !$admin_id = $this->_getUserProfileId(FunctionCode::ADMIN_EDIT_COMPANY, AccessType::WRITE) )
            return false;

        if( !$this->is_required($this->input->post(), array('service_provider_id')) )
            return false;

        $service_provider_id = $this->input->post('service_provider_id');
        $required_face_to_face_verification = null;
        if( $this->input->post('required_face_to_face_verification') == 'false' )
            $required_face_to_face_verification = false;
        elseif( $this->input->post('required_face_to_face_verification') == 'true' )
            $required_face_to_face_verification = true;

        $required_acuity_check = null;
        if( $this->input->post('required_acuity_check') == 'false' )
            $required_acuity_check = false;
        elseif( $this->input->post('required_acuity_check') == 'true' )
            $required_acuity_check = true;

        $required_dow_jones_check = null;
        if( $this->input->post('required_dow_jones_check') == 'false' )
            $required_dow_jones_check = false;
        elseif( $this->input->post('required_dow_jones_check') == 'true' )
            $required_dow_jones_check = true;

        $required_ofac_un_mas_check = null;
        if( $this->input->post('required_ofac_un_mas_check') == 'false' )
            $required_ofac_un_mas_check = false;
        elseif( $this->input->post('required_ofac_un_mas_check') == 'true' )
            $required_ofac_un_mas_check = true;
		
		$required_face_to_face_trans = null;
        if( $this->input->post('required_face_to_face_trans') == 'false' )
            $required_face_to_face_trans = false;
        elseif( $this->input->post('required_face_to_face_trans') == 'true' )
            $required_face_to_face_trans = true;
		
		$required_face_to_face_recipient = null;
        if( $this->input->post('required_face_to_face_recipient') == 'false' )
            $required_face_to_face_recipient = false;
        elseif( $this->input->post('required_face_to_face_recipient') == 'true' )
            $required_face_to_face_recipient = true;
		
		$required_manual_approval_nff = null;
        if( $this->input->post('required_manual_approval_nff') == 'false' )
            $required_manual_approval_nff = false;
        elseif( $this->input->post('required_manual_approval_nff') == 'true' )
            $required_manual_approval_nff = true;

        $match_criteria = $this->input->post('match_criteria') ? $this->input->post('match_criteria') : 'precise';

        $receipt_footer = $this->input->post('receipt_footer') ? stripslashes($this->input->post('receipt_footer')) : NULL;
        $uen = $this->input->post('uen') ? $this->input->post('uen') : NULL;
        $mas_license_no = $this->input->post('mas_license_no') ? $this->input->post('mas_license_no') : NULL;
        $interval_of_dow_jones = $this->input->post('interval_of_dow_jones') ? $this->input->post('interval_of_dow_jones') : "0";

        if( is_null($required_acuity_check) AND is_null($required_face_to_face_verification) AND
            is_null($receipt_footer) AND is_null($uen) AND is_null($mas_license_no) AND is_null($required_dow_jones_check) AND is_null($required_ofac_un_mas_check)
			AND is_null($required_face_to_face_trans) AND is_null($required_face_to_face_recipient) AND is_null($required_manual_approval_nff) )
        {//error
            $this->_response(InputValidator::constructInvalidParamResponse('Must have at least one field given'));
            return false;
        }

        $this->_serv->setUpdatedBy($admin_id);

        $entity = new RemittanceCompany();
        $entity->setServiceProviderId($service_provider_id);
        $entity->setRequiredFaceToFaceVerification($required_face_to_face_verification);
        $entity->setRequiredAcuityCheck($required_acuity_check);
		$entity->setRequiredFaceToFaceTrans($required_face_to_face_trans);
		$entity->setRequiredFaceToFaceRecipient($required_face_to_face_recipient);
		$entity->setRequiredManualApprovalNFF($required_manual_approval_nff);
        $entity->setReceiptFooter($receipt_footer);
        $entity->setUen($uen);
        $entity->setMasLicenseNo($mas_license_no);
        $entity->setRequiredDowJonesCheck($required_dow_jones_check);
        $entity->setRequiredOFACUNMASCheck($required_ofac_un_mas_check);
        $entity->setMatchCriteria($match_criteria);
        $entity->setIntervalOfDowJones($interval_of_dow_jones);

        if( $result = $this->_serv->editByServiceProviderId($entity) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode());
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
}