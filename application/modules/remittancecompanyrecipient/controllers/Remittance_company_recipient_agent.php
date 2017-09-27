<?php

use Iapps\RemittanceService\RemittanceCompanyRecipient\AgentRemittanceCompanyRecipientServiceFactory;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\Common\Core\IpAddress;
use Iapps\RemittanceService\RemittanceCompanyRecipient\RemittanceCompanyRecipientStatus;
use Iapps\Common\Helper\InputValidator;

class Remittance_company_recipient_agent extends Agent_Base_Controller{

    function __construct()
    {
        parent::__construct();

        //load service
        $this->_serv = AgentRemittanceCompanyRecipientServiceFactory::build();
        $this->_serv->setIpAddress(IpAddress::fromString($this->_getIpAddress()));
    }

    public function add()
    {
        if( !$agent_id = $this->_getUserProfileId() )
            return false;

        if( !$this->is_required($this->input->post(), array('user_profile_id', 'recipient_id') ) )
            return false;
		
		$user_profile_id = $this->input->post('user_profile_id');
		$recipient_id = $this->input->post('recipient_id');
		
		$this->_serv->setUpdatedBy($agent_id);
		if( $this->_serv->addRecipient($recipient_id, $user_profile_id) )
		{
			$this->_respondWithSuccessCode($this->_serv->getResponseCode());
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
		return false;
    }
    
	public function getList()
	{
		if( !$agent_id = $this->_getUserProfileId() )
            return false;

        if( !$this->is_required($this->input->get(), array('user_profile_id', 'status') ) )
            return false;
		
		$user_profile_id = $this->input->get('user_profile_id');
        $status = $this->input->get('status');
        
        //validate status
        if( !RemittanceCompanyRecipientStatus::exists($status))
        {
            $this->_response(InputValidator::constructInvalidParamResponse('Invalid Status'));
            return false;
        }            
		
		$this->_serv->setUpdatedBy($agent_id);
		if( $result = $this->_serv->getList($user_profile_id, $status) )
		{
			$this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result->result, 'total' => $result->total));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
		return false;
	}
	
    public function verify()
    {
    	if( !$agent_id = $this->_getUserProfileId() )
            return false;

        if( !$this->is_required($this->input->post(), array('user_profile_id', 'recipient_id') ) )
            return false;
		
		$user_profile_id = $this->input->post('user_profile_id');
		$recipient_id = $this->input->post('recipient_id');
		
		$this->_serv->setUpdatedBy($agent_id);
		if( $this->_serv->verifyFaceToFaceRecipient($recipient_id, $user_profile_id) )
		{
			$this->_respondWithSuccessCode($this->_serv->getResponseCode());
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
		return false;
    }
	
}