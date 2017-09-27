<?php

use Iapps\RemittanceService\RemittanceCompany\RemittanceCompanyServiceFactory;
use Iapps\Common\Core\IpAddress;
use Iapps\Common\Helper\ResponseHeader;

class Remittance_company_agent extends Agent_Base_Controller{
    
    function __construct() {
        parent::__construct();

        $this->_serv = RemittanceCompanyServiceFactory::build('agent');
        $this->_serv->setIpAddress(IpAddress::fromString($this->_getIpAddress()));
    }
    
    public function getCompany()
    {
        if( !$agent_id = $this->_getUserProfileId() )
            return false;

        $this->_serv->setUpdatedBy($agent_id);

        if( !$service_provider_id = $this->_serv->getServiceProviderId() )
        {
            $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
            return false;
        }

        if( $result = $this->_serv->getByServiceProviderId($service_provider_id) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
}

