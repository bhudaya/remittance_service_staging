<?php

namespace Iapps\RemittanceService\Common;

use Iapps\Common\Helper\InputValidator;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\Common\Microservice\AccountService\AgentAccountServiceFactory;

class UserAuthorizationChecker{

    public static function check($user_id)
    {
        $_ci = get_instance();

        $v = InputValidator::make($_ci->input->request_headers(), array(ResponseHeader::FIELD_X_USER_AUTHORIZATION));

        if( $v->fails() )
        {
            return false;
        }

        $userToken = $_ci->input->get_request_header(ResponseHeader::FIELD_X_USER_AUTHORIZATION);
        $agentAccountService = AgentAccountServiceFactory::build();
        if( $agentAccountService->checkUserAuthorization($user_id, $userToken) )
            return true;

        return false;
    }
}