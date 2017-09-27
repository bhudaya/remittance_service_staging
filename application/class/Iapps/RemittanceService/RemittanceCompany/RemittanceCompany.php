<?php

namespace Iapps\RemittanceService\RemittanceCompany;

use Iapps\Common\Core\IappsBaseEntity;
use Iapps\Common\Microservice\AccountService\User;

class RemittanceCompany extends IappsBaseEntity{

    protected $service_provider_id;
    protected $company_code;
    protected $receipt_footer;
    protected $uen;
    protected $mas_license_no;
    protected $receipt_format;
    protected $required_face_to_face_verification;
    protected $required_acuity_check;
    protected $required_dow_jones_check;
    protected $required_ofac_un_mas_check;
    protected $match_criteria;
    protected $interval_of_dow_jones;
	protected $required_face_to_face_trans;
	protected $required_face_to_face_recipient;
	protected $required_manual_approval_nff;

    protected $company_info; //user info

    function __construct()
    {
        parent::__construct();

        $this->company_info = new User();
    }

    public function setServiceProviderId($service_provider_id)
    {
        $this->service_provider_id = $service_provider_id;
        return $this;
    }

    public function getServiceProviderId()
    {
        return $this->service_provider_id;
    }

    public function setCompanyCode($company_code)
    {
        $this->company_code = $company_code;
        return $this;
    }

    public function getCompanyCode()
    {
        return $this->company_code;
    }

    public function setReceiptFooter($receipt_footer)
    {
        $this->receipt_footer = $receipt_footer;
        return $this;
    }

    public function getReceiptFooter()
    {
        return $this->receipt_footer;
    }

    public function setReceiptFormat($receipt_format)
    {
        $this->receipt_format = $receipt_format;
        return $this;
    }

    public function getReceiptFormat()
    {
        return $this->receipt_format;
    }

    public function setUen($uen)
    {
        $this->uen = $uen;
        return $this;
    }

    public function getUen()
    {
        return $this->uen;
    }

    public function setMasLicenseNo($mas_license_no)
    {
        $this->mas_license_no = $mas_license_no;
        return $this;
    }

    public function getMasLicenseNo()
    {
        return $this->mas_license_no;
    }

    public function setCompanyInfo(User $user)
    {
        $this->company_info = $user;
        return $this;
    }

    public function getCompanyInfo()
    {
        return $this->company_info;
    }

    public function setRequiredFaceToFaceVerification($required_face_to_face_verification)
    {
        $this->required_face_to_face_verification = $required_face_to_face_verification;
        return $this;
    }

    public function getRequiredFaceToFaceVerification()
    {
        return $this->required_face_to_face_verification;
    }

    public function setRequiredAcuityCheck($required_acuity_check)
    {
        $this->required_acuity_check = $required_acuity_check;
        return $this;
    }

    public function getRequiredAcuityCheck()
    {
        return $this->required_acuity_check;
    }
	
	public function setRequiredFaceToFaceTrans($required_face_to_face_trans)
    {
        $this->required_face_to_face_trans = $required_face_to_face_trans;
        return $this;
    }

    public function getRequiredFaceToFaceTrans()
    {
        return $this->required_face_to_face_trans;
    }
	
	public function setRequiredFaceToFaceRecipient($required_face_to_face_recipient)
    {
        $this->required_face_to_face_recipient = $required_face_to_face_recipient;
        return $this;
    }

    public function getRequiredFaceToFaceRecipient()
    {
        return $this->required_face_to_face_recipient;
    }
	
	public function setRequiredManualApprovalNFF($required_manual_approval_nff)
    {
        $this->required_manual_approval_nff = $required_manual_approval_nff;
        return $this;
    }

    public function getRequiredManualApprovalNFF()
    {
        return $this->required_manual_approval_nff;
    }

    public function setRequiredDowJonesCheck($required_dow_jones_check)
    {
        $this->required_dow_jones_check = $required_dow_jones_check;
        return $this;
    }

    public function getRequiredDowJonesCheck()
    {
        return $this->required_dow_jones_check;
    }

    public function setRequiredOFACUNMASCheck($required_ofac_un_mas_check)
    {
        $this->required_ofac_un_mas_check = $required_ofac_un_mas_check;
        return $this;
    }

    public function getRequiredOFACUNMASCheck()
    {
        return $this->required_ofac_un_mas_check;
    }

    public function setMatchCriteria($match_criteria)
    {
        $this->match_criteria = $match_criteria;
        return $this;
    }

    public function getMatchCriteria()
    {
        return $this->match_criteria;
    }

    public function setIntervalOfDowJones($interval_of_dow_jones)
    {
        $this->interval_of_dow_jones = $interval_of_dow_jones;
        return $this;
    }

    public function getIntervalOfDowJones()
    {
        return $this->interval_of_dow_jones;
    }

    public function jsonSerialize()
    {
        $json = parent::jsonSerialize();

        $json['service_provider_id'] = $this->getServiceProviderId();
        $json['company_code'] = $this->getCompanyCode();
        $json['receipt_footer'] = $this->getReceiptFooter();
        $json['receipt_format'] = $this->getReceiptFormat();
        $json['uen'] = $this->getUen();
        $json['mas_license_no'] = $this->getMasLicenseNo();
        $json['required_face_to_face_verification'] = $this->getRequiredFaceToFaceVerification();
        $json['required_acuity_check'] = $this->getRequiredAcuityCheck();
        $json['required_dow_jones_check'] = $this->getRequiredDowJonesCheck();
        $json['required_ofac_un_mas_check'] = $this->getRequiredOFACUNMASCheck();
        $json['match_criteria'] = $this->getMatchCriteria();
        $json['interval_of_dow_jones'] = $this->getIntervalOfDowJones();
		$json['required_face_to_face_trans'] = $this->getRequiredFaceToFaceTrans();
		$json['required_face_to_face_recipient'] = $this->getRequiredFaceToFaceRecipient();
		$json['required_manual_approval_nff'] = $this->getRequiredManualApprovalNFF();

        return $json;
    }
}