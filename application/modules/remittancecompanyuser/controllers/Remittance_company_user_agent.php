<?php

use Iapps\RemittanceService\RemittanceCompanyUser\AgentRemittanceCompanyUserService;
use Iapps\Common\Core\IpAddress;
use Iapps\Common\Helper\ResponseHeader;

class Remittance_company_user_agent extends Agent_Base_Controller{

    function __construct()
    {
        parent::__construct();

        $this->_serv = new AgentRemittanceCompanyUserService();
        $this->_serv->setIpAddress(IpAddress::fromString($this->_getIpAddress()));
    }

    public function completeProfile()
    {
        if( !$agent_id = $this->_getUserProfileId() )
            return false;

        if (!$this->is_required($this->input->post(), array('user_profile_id')))
            return false;

        $user_profile_id = $this->input->post('user_profile_id');

        $this->_serv->setUpdatedBy($agent_id);

        //if( !$this->_checkUserAuthorization($user_profile_id) )
        //    return false;

        if( $this->_serv->completeUserProfile($user_profile_id) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode());
            return true;
        }
        elseif( $this->_serv->getUnauthorized() )
        {//unauthorized
            $this->_setInvalidUserAuthorizationResponse();
            return false;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getProfile()
    {
        if( !$agent_id = $this->_getUserProfileId() )
            return false;

        if (!$this->is_required($this->input->post(), array('user_profile_id')))
            return false;

        $user_profile_id = $this->input->post('user_profile_id');

        $this->_serv->setUpdatedBy($agent_id);
        if( $result = $this->_serv->getUserProfile($user_profile_id) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
}