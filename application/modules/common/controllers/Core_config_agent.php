<?php

use Iapps\RemittanceService\Common\CoreConfigDataServiceFactory;
use Iapps\Common\Core\IpAddress;
use Iapps\Common\Helper\ResponseHeader;

class Core_config_agent extends Agent_Base_Controller{
    
    function __construct() {
        parent::__construct();

        $this->_serv = CoreConfigDataServiceFactory::build();
        $this->_serv->setIpAddress(IpAddress::fromString($this->_getIpAddress()));
    }
    
    public function getRecipientSetting()
    {
        //TODO use correct function
        if( !$user_id = $this->_getUserProfileId() )
            return false;
                               
        if( $result = $this->_serv->getRecipientSetting() ) 
        {            
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }    
}

