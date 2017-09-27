<?php

use Iapps\RemittanceService\RemittanceCompanyUser\RemittanceCompanyUserServiceFactory;
use Iapps\Common\Core\IpAddress;
use Iapps\Common\Helper\ResponseHeader;

class Remittance_company_user_user extends User_Base_Controller{

    function __construct()
    {
        parent::__construct();

        $this->_serv = RemittanceCompanyUserServiceFactory::build();
        $this->_serv->setIpAddress(IpAddress::fromString($this->_getIpAddress()));
    }

    public function getList()
    {
        if( !$user_id = $this->_getUserProfileId() )
            return false;

        $page = $this->_getPage();
        $limit = $this->_getLimit();

        $this->_serv->setUpdatedBy($user_id);
        if( $result = $this->_serv->listByUser($user_id, $page, $limit) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
}