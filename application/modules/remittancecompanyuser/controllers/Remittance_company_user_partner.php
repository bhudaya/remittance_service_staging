<?php

use Iapps\RemittanceService\RemittanceCompanyUser\PartnerRemittanceCompanyUserService;
use Iapps\Common\Core\IpAddress;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\RemittanceService\Common\FunctionCode;
use Iapps\Common\Microservice\AccountService\AccessType;
use Iapps\Common\Helper\InputValidator;
use Iapps\Common\Microservice\AccountService\User;
use Iapps\Common\Microservice\AccountService\MobileNumberObj;

class Remittance_company_user_partner extends Partner_Base_Controller{

    function __construct()
    {
        parent::__construct();

        $this->_serv = new PartnerRemittanceCompanyUserService();
        $this->_serv->setIpAddress(IpAddress::fromString($this->_getIpAddress()));
    }

    public function get()
    {
        // access control
        if( !$adminId = $this->_getUserProfileId(FunctionCode::PARTNER_GET_USER, AccessType::READ) )
            return false;

        if (!$this->is_required($this->input->get(), array('user_profile_id')))
            return false;

        $user_profile_id = $this->input->get('user_profile_id');

        $this->_serv->setUpdatedBy($adminId);
        if( $result = $this->_serv->getUserProfile($user_profile_id) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getList()
    {
        // access control
        if( !$adminId = $this->_getUserProfileId(FunctionCode::PARTNER_GET_USER, AccessType::READ) )
            return false;

        $page = $this->_getPage();
        $limit = $this->_getLimit();

        $filter = new User();
        if($this->input->get('full_name'))
            $filter->setFullName($this->input->get('full_name'));
        if($this->input->get('email'))
            $filter->setEmail($this->input->get('email'));
        if($this->input->get('mobile_number')){
            $user_mobile_number = new MobileNumberObj();
            $user_mobile_number->setMobileNumber($this->input->get('mobile_number'));
            $filter->setMobileNumberObj($user_mobile_number);
        }
        if($this->input->get('country_code'))
            $filter->setHostCountryCode($this->input->get('country_code'));
        if($this->input->get('host_identity_card'))
            $filter->setHostIdentityCard($this->input->get('host_identity_card'));
        if($this->input->get('accountID'))
            $filter->setAccountID($this->input->get('accountID'));
        if($this->input->get('account_status'))
            $filter->getUserStatus()->setCode($this->input->get('account_status'));

        $this->_serv->setUpdatedBy($adminId);
        if( $result = $this->_serv->getUserList($page, $limit, $filter) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result->getResult(), 'total' => $result->getTotal()));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function verifyUser()
    {
        $this->_returnObsoleteFunction();
        return false;

        /*
        if( !$adminId = $this->_getUserProfileId(FunctionCode::PARTNER_VERFIY_USER, AccessType::WRITE) )
            return false;

        if (!$this->is_required($this->input->post(), array('user_profile_id', 'status')))
            return false;

        $user_profile_id = $this->input->post('user_profile_id');
        $status = $this->input->post('status'); //pass or fail
        $remark = $this->input->post('reason') ? $this->input->post('reason') : NULL; //pass or fail

        if( !in_array($status, array('pass', 'fail')) )
        {
            $this->_response(InputValidator::constructInvalidParamResponse('Invalid status'));
            return false;
        }

        $this->_serv->setUpdatedBy($adminId);
        if( $this->_serv->verifyUserProfile($user_profile_id, $status, $remark) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode());
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
        */
    }
}