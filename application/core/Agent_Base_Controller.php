<?php (defined('BASEPATH')) OR exit('No direct script access allowed');

use Iapps\Common\Microservice\AccountService\FunctionCode;
use Iapps\Common\Microservice\AccountService\AccessType;
use Iapps\Common\Microservice\AccountService\SessionType;
use Iapps\Common\Microservice\AccountService\AgentAccountServiceFactory;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\Common\Microservice\AccountService\AccountServiceFactory;

class Agent_Base_Controller extends Base_Controller
{
    function __construct()
    {
        parent::__construct();

        $this->_authoriseClient();
    }

    //override
    protected function _getUserProfileId($function = NULL, $access_type = NULL, $session_type = NULL)
    {
        if($function == NULL)
        {
            $function = FunctionCode::AGENT_FUNCTIONS;
        }
        if($access_type == NULL)
        {
            $access_type =  AccessType::WRITE;
        }
        if($session_type == NULL)
        {
            $session_type = SessionType::LOGIN;
        }

        return parent::_getUserProfileId($function, $access_type, $session_type);
    }

    protected function _checkUserAuthorization($userId)
    {
        if( !$this->is_required($this->input->request_headers(), array(ResponseHeader::FIELD_X_USER_AUTHORIZATION)))
            return false;

        $userToken = $this->input->get_request_header(ResponseHeader::FIELD_X_USER_AUTHORIZATION);

        $agentAccountService = AgentAccountServiceFactory::build();
        if( $agentAccountService->checkUserAuthorization($userId, $userToken) )
            return true;
        else
        {
            $this->_setInvalidUserAuthorizationResponse();
            return false;
        }
    }

    protected function _setInvalidUserAuthorizationResponse()
    {
        $this->response_message->getHeader()->setStatus(ResponseHeader::HEADER_FORBIDDEN);
        $this->response_message->setStatusCode(ResponseHeader::HEADER_FORBIDDEN);
        $this->response_message->setMessage('Invalid user token credentials.');
        $this->set_output();
    }

    protected function _checkLocation()
    {
        if( !$this->is_required($this->input->request_headers(), array(ResponseHeader::FIELD_X_LOCATION)))
            return false;

        return true;
    }
    
    protected function _getMainAgent()
    {
        $acc_serv = AccountServiceFactory::build();
        if( $structure = $acc_serv->getAgentUplineStructure() )
        {
            if( $upline = $structure->first_upline )
            {
                if( $upline->getRoles()->hasRole(array('main_agent')) )
                {
                    return $upline->getUser();
                }
            }

            if( $upline = $structure->second_upline )
            {
                if( $upline->getRoles()->hasRole(array('main_agent')) )
                {
                    return $upline->getUser();
                }
            }
        }

        return false;
    }
}