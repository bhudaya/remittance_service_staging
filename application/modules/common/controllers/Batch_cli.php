<?php

use Iapps\Common\Helper\RequestHeader;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\Common\Core\IappsDateTime;
use Iapps\RemittanceService\RemittanceRecord\RemittanceProcessPrelimCheckListener;
use Iapps\Common\Core\IpAddress;
use Iapps\RemittanceService\Common\MessageCode;
use Iapps\RemittanceService\RemittanceCompanyUser\ProcessProfileEditedListener;
use Iapps\RemittanceService\RemittanceCompanyUser\CreateRemittanceProfileListener;
use Iapps\RemittanceService\RemittanceCompanyUser\AutoVerifyProfileListener;
use Iapps\RemittanceService\RemittanceCompanyRecipient\RecheckRemcoRecipientStatusListener;

class Batch_cli extends Cli_Base_Controller{

    public function listenProcessPrelim()
    {
        if( !$systemUser = $this->_getUserProfileId() )
            return false;

        RequestHeader::set(ResponseHeader::FIELD_X_APP, $this->getArgument(0));
        RequestHeader::set(ResponseHeader::FIELD_X_AUTHORIZATION, $this->clientToken);
        AccountServiceFactory::reset();

        $long_running = $this->getArgument(1);
        if( $long_running == 'true' )
            $ttl = NULL;
        else
            $ttl = 10;

        $listener = new RemittanceProcessPrelimCheckListener(NULL, $ttl);
        $listener->setIpAddress(IpAddress::fromString($this->_getIpAddress()));
        $listener->setUpdatedBy($systemUser);
        $listener->listenEvent();

        $this->_respondWithSuccessCode(MessageCode::CODE_JOB_PROCESS_PASSED);
        return true;
    }

    public function listenProcessProfileEdited()
    {
        if( !$systemUser = $this->_getUserProfileId() )
            return false;

        RequestHeader::set(ResponseHeader::FIELD_X_APP, $this->getArgument(0));
        RequestHeader::set(ResponseHeader::FIELD_X_AUTHORIZATION, $this->clientToken);
        AccountServiceFactory::reset();

        $long_running = $this->getArgument(1);
        if( $long_running == 'true' )
            $ttl = NULL;
        else
            $ttl = 10;

        $listener = new ProcessProfileEditedListener(NULL, $ttl);
        $listener->setIpAddress(IpAddress::fromString($this->_getIpAddress()));
        $listener->setUpdatedBy($systemUser);
        $listener->listenEvent();

        $this->_respondWithSuccessCode(MessageCode::CODE_JOB_PROCESS_PASSED);
        return true;
    }

    public function listenCreateRemittanceProfile()
    {
        if( !$systemUser = $this->_getUserProfileId() )
            return false;

        RequestHeader::set(ResponseHeader::FIELD_X_APP, $this->getArgument(0));
        RequestHeader::set(ResponseHeader::FIELD_X_AUTHORIZATION, $this->clientToken);
        AccountServiceFactory::reset();

        $long_running = $this->getArgument(1);
        if( $long_running == 'true' )
            $ttl = NULL;
        else
            $ttl = 10;

        $listener = new CreateRemittanceProfileListener(NULL, $ttl);
        $listener->setIpAddress(IpAddress::fromString($this->_getIpAddress()));
        $listener->setUpdatedBy($systemUser);
        $listener->listenEvent();

        $this->_respondWithSuccessCode(MessageCode::CODE_JOB_PROCESS_PASSED);
        return true;
    }

    public function listenAutoVerifyProfile()
    {
        if( !$systemUser = $this->_getUserProfileId() )
            return false;

        RequestHeader::set(ResponseHeader::FIELD_X_APP, $this->getArgument(0));
        RequestHeader::set(ResponseHeader::FIELD_X_AUTHORIZATION, $this->clientToken);
        AccountServiceFactory::reset();

        $long_running = $this->getArgument(1);
        if( $long_running == 'true' )
            $ttl = NULL;
        else
            $ttl = 10;

        $listener = new AutoVerifyProfileListener(NULL, $ttl);
        $listener->setIpAddress(IpAddress::fromString($this->_getIpAddress()));
        $listener->setUpdatedBy($systemUser);
        $listener->listenEvent();

        $this->_respondWithSuccessCode(MessageCode::CODE_JOB_PROCESS_PASSED);
        return true;
    }
    
    public function listenRecheckRecipientStatus()
    {
        if( !$systemUser = $this->_getUserProfileId() )
            return false;

        RequestHeader::set(ResponseHeader::FIELD_X_APP, $this->getArgument(0));
        RequestHeader::set(ResponseHeader::FIELD_X_AUTHORIZATION, $this->clientToken);
        AccountServiceFactory::reset();

        $long_running = $this->getArgument(1);
        if( $long_running == 'true' )
            $ttl = NULL;
        else
            $ttl = 10;

        $listener = new RecheckRemcoRecipientStatusListener(NULL, $ttl);
        $listener->setIpAddress(IpAddress::fromString($this->_getIpAddress()));
        $listener->setUpdatedBy($systemUser);
        $listener->listenEvent();

        $this->_respondWithSuccessCode(MessageCode::CODE_JOB_PROCESS_PASSED);
        return true;
    }
}