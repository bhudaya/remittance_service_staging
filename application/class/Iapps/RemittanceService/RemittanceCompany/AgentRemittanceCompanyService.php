<?php

namespace Iapps\RemittanceService\RemittanceCompany;

use Iapps\Common\Microservice\AccountService\AccountServiceFactory;

class AgentRemittanceCompanyService extends RemittanceCompanyService{
    
    public function getServiceProviderId()
    {
        $acc_serv = AccountServiceFactory::build();
        if( $structure = $acc_serv->getAgentUplineStructure() )
        {
            if( $upline = $structure->first_upline )
            {
                if( $upline->getRoles()->hasRole(array('main_agent')) )
                {
                    return $upline->getUser()->getId();
                }
            }

            if( $upline = $structure->second_upline )
            {
                if( $upline->getRoles()->hasRole(array('main_agent')) )
                {
                    return $upline->getUser()->getId();
                }
            }
        }

        return false;
    }
}