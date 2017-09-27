<?php

use Iapps\RemittanceService\Common\CoreConfigDataServiceFactory;
use Iapps\Common\Helper\InputValidator;
use Iapps\Common\Core\IpAddress;
use Iapps\RemittanceService\Common\FunctionCode;
use Iapps\Common\Microservice\AccountService\AccessType;
use Iapps\RemittanceService\Common\CoreConfigType;
use Iapps\Common\Helper\ResponseHeader;

class Core_config_admin extends Admin_Base_Controller{
    
    function __construct() {
        parent::__construct();

        $this->_serv = CoreConfigDataServiceFactory::build();
        $this->_serv->setIpAddress(IpAddress::fromString($this->_getIpAddress()));
    }
    
    public function getRecipientSetting()
    {
        //TODO use correct function
        if( !$admin_id = $this->_getUserProfileId(FunctionCode::ADMIN_GET_RECIPIENT_SETTING, AccessType::READ) )
            return false;
                               
        if( $result = $this->_serv->getRecipientSetting() ) 
        {            
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    
    public function editRecipientSetting()
    {
        //TODO use correct function
        if( !$admin_id = $this->_getUserProfileId(FunctionCode::ADMIN_EDIT_RECIPIENT_SETTING, AccessType::WRITE) )
            return false;

        $this->_serv->setUpdatedBy($admin_id);
        if (!$this->is_required($this->input->post(), array('max_recipient', 'max_collection_info'), false))
            return false;
        
        $max_recipient = (int) $this->input->post('max_recipient');
        $max_collection_info = (int) $this->input->post('max_collection_info');
        
        
        if( $max_recipient < 0 or $max_recipient > 999 )
        {
            $this->_response(InputValidator::constructInvalidParamResponse('Invalid max recipient given'));
            return false;
        }
            
        if( $max_collection_info < 0 or $max_collection_info > 999 )
        {
            $this->_response(InputValidator::constructInvalidParamResponse('Invalid max collection info given'));
            return false;
        }
                
        if ($this->_serv->updateRecipientSetting($max_recipient, $max_collection_info)) 
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode());
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
}

