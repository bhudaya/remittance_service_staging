<?php

use Iapps\RemittanceService\Attribute\AttributeValueRepository;
use Iapps\RemittanceService\Attribute\AttributeValueService;
use Iapps\Common\Helper\ResponseHeader;

class Attribute_value_agent extends Agent_Base_Controller{

    protected $_attribute_value_service;

    function __construct()
    {
        parent::__construct();

        //must call CI load model once, workaround
        $this->load->model('attribute/Attribute_value_model');
        $repo = new AttributeValueRepository($this->Attribute_value_model);
        $this->_attribute_value_service = new AttributeValueService($repo, $this->_getIpAddress());
    }

    public function getValues()
    {
        if( !$agentId = $this->_getUserProfileId() )
            return false;

        $attr_code = $this->input->get('attribute') ? $this->input->get('attribute') : NULL;
        if( $attr_code )
        {
            if( $result = $this->_attribute_value_service->getByAttributeCode($attr_code) )
            {
                $this->_respondWithSuccessCode($this->_attribute_value_service->getResponseCode(), array('result' => $result));
                return true;
            }
        }
        else
        {
            if( $result = $this->_attribute_value_service->getAll() )
            {
                $this->_respondWithSuccessCode($this->_attribute_value_service->getResponseCode(), array('result' => $result));
                return true;
            }
        }


        $this->_respondWithCode($this->_attribute_value_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
}