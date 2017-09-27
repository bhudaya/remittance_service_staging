<?php

use Iapps\Common\Core\IpAddress;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\RemittanceService\UserRiskLevel\UserRiskLevelRepository;
use Iapps\RemittanceService\UserRiskLevel\UserRiskLevelService;
use Iapps\RemittanceService\UserRiskLevel\UserRiskLevel;
use Iapps\Common\Microservice\AccountService\AccessType;
use Iapps\RemittanceService\Common\FunctionCode;

class User_risk_level extends Partner_Base_Controller{

    protected $_service;

    function __construct()
    {
        parent::__construct();

        $this->load->model('remittanceofficer/User_risk_level_service_model');
        $repo = new UserRiskLevelRepository($this->User_risk_level_service_model);
        $this->_service = new UserRiskLevelService($repo);
        $this->_service->setIpAddress(IpAddress::fromString($this->_getIpAddress()));
    }

    public function getAllUserRiskLevel()
    {   
        $limit = $this->_getLimit();
        $page  = $this->_getPage();
        
        if( !$user_id = $this->_getUserProfileId(FunctionCode::PARTNER_LIST_USER_RISK_LEVEL, AccessType::READ) )
            return false;

        if( $result = $this->_service->getAllUserRiskLevel($limit, $page) )
        {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;

    }
    public function getUserRiskLevelByUserProfiledId()
    {

        if( !$user_id = $this->_getUserProfileId(FunctionCode::PARTNER_LIST_USER_RISK_LEVEL, AccessType::READ) )
            return false;

        if( !$this->is_required($this->input->get(), array('user_profile_id')))
        {
            return false;
        }

        $user_profile_id = $this->input->get("user_profile_id");

        if($result = $this->_service->getUserRiskLevelByUserProfiledId($user_profile_id))
        {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(),array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getUserProfileAndRiskLevelByUserProfiledId()
    {

        if( !$user_id = $this->_getUserProfileId(FunctionCode::PARTNER_LIST_USER_RISK_LEVEL, AccessType::READ) )
            return false;

        if( !$this->is_required($this->input->get(), array('user_profile_id')))
        {
            return false;
        }

        $user_profile_id = $this->input->get("user_profile_id");

        if($result = $this->_service->getUserProfileAndRiskLevelByUserProfileId($user_profile_id))
        {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(),array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function updateUserRiskLevel(){

        if( !$admin_id = $this->_getUserProfileId(FunctionCode::PARTNER_UPDATE_USER_RISK_LEVEL, AccessType::WRITE) )
            return false;

        if( !$this->is_required($this->input->post(), array('user_profile_id',
                                                            'level_changed_reason')))
        {
            return false;
        }

        $object = new UserRiskLevel();

        $object->setUserProfileId($this->input->post('user_profile_id'));
        $object->setUnActiveRiskLevel($this->input->post('unactive_risk_level'));

        if ($this->input->post('active_risk_level')) {
            $object->setActiveRiskLevel($this->input->post('active_risk_level'));
        }
        $object->setLevelChangedReason($this->input->post('level_changed_reason'));

        if($result = $this->_service->updateUserRiskLevel($object,$admin_id))
        {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(),array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function updateUserRiskLevelStatus()
    {
        if( !$this->is_required($this->input->post(), array('id',
                                                            'approval_status')))
        {
            return false;
        }

        $object = new UserRiskLevel();

        if( !$admin_id = $this->_getUserProfileId(FunctionCode::PARTNER_UPDATE_USER_RISK_LEVEL_STATUS, AccessType::WRITE) )
            return false;

        $object->setId($this->input->post('id'));
        $object->setApprovalStatus($this->input->post('approval_status'));
        $object->setUpdatedBy($admin_id);

        if($result = $this->_service->updateUserRiskLevelStatus($object))
        {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(),array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
}