<?php

use Iapps\Common\Core\IpAddress;
use Iapps\RemittanceService\DepositTracker\DeductDepositListener;
use Iapps\RemittanceService\Common\MessageCode;
use Iapps\Common\Helper\RequestHeader;
use Iapps\Common\Helper\ResponseHeader;


class Remittance_deduction_batch extends System_Base_Controller
{

    function __construct()
    {
        parent::__construct();
    }

    public function listenRemittanceDeduction()
    {
        if (!$systemUser = $this->_getUserProfileId()){
            return false;
        }

        $listener = new DeductDepositListener();
        $listener->listenEvent();
        @$this->_respondWithSuccessCode(MessageCode::CODE_APPROVE_DEDUCTION_SUCCESS);
        return true;
    }





}