<?php

use Iapps\RemittanceService\Attribute\AttributeValueRepository;
use Iapps\RemittanceService\Attribute\AttributeValueService;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\Common\Microservice\AccountService\FunctionCode;

class Attribute_value extends Base_Controller{

    protected $_attribute_value_service;

    function __construct()
    {
        parent::__construct();

        $this->load->model('attribute/Attribute_value_model');
        $repo = new AttributeValueRepository($this->Attribute_value_model);
        $this->_attribute_value_service = new AttributeValueService($repo, $this->_getIpAddress());
    }

    public function getValues()
    {
        if( !$profileId = $this->_getUserProfileId() )
            return false;

        if( !$this->is_required($this->input->get(), array('attribute')) )
            return false;

        $attr_code = $this->input->get('attribute');
        if( $result = $this->_attribute_value_service->getByAttributeCode($attr_code) )
        {
            $this->_respondWithSuccessCode($this->_attribute_value_service->getResponseCode(), array('result' => $result,'total' => count($result['list'])));
            return true;
        }

        $this->_respondWithCode($this->_attribute_value_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getAll()
    {
        if( !$profileId = $this->_getUserProfileId() )
            return false;

        if( $result = $this->_attribute_value_service->getAll() )
        {
            $this->_respondWithSuccessCode($this->_attribute_value_service->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_attribute_value_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
}