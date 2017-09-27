<?php

class Recipient extends Base_Controller{
    function __construct() {
        parent::__construct();
        
        $this->_service_audit_log->setTableName('iafb_remittance.recipient');
    }
}