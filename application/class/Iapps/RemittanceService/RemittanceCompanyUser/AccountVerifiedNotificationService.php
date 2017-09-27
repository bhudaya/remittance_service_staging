<?php

namespace  Iapps\RemittanceService\RemittanceCompanyUser;

use Iapps\RemittanceService\Common\CoreConfigType;
use Iapps\RemittanceService\Common\NotificationMessageType;
use Iapps\Common\ChatService\ChatServiceProducer;
use Iapps\Common\ChatService\NotificationChannel;
use Iapps\Common\ChatService\NotificationTag;
use Iapps\Common\CommunicationService\CommunicationServiceProducer;
use Iapps\Common\Helper\MessageBroker\BroadcastEventConsumer;
use Iapps\Common\Microservice\AccountService\AccountService;
use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\Common\Microservice\AccountService\FunctionCode;
use Iapps\Common\Microservice\AccountService\MobileNumberObj;
use Iapps\Common\Microservice\AccountService\User;
use Iapps\RemittanceService\Common\CoreConfigDataServiceFactory;
use Iapps\Common\Helper\MessageBroker\EventConsumer;
use Iapps\RemittanceService\Common\AddressNameExtractor;

class AccountVerifiedNotificationService extends EventConsumer{

    protected $header = array();
    protected $_accountServ;

    protected function _getAccountService()
    {
        if( !$this->_accountServ )
            $this->_accountServ = AccountServiceFactory::build();

        return $this->_accountServ;
    }

    public function doTask($msg)
    {
        $data = json_decode($msg->body);

        try
        {
            if( isset($data->user_profile_id) && isset($data->service_provider_id))
            {
                //$status = isset($data->status) ? $data->status : NULL;
                $this->_notifyUser($data->user_profile_id, $data->service_provider_id);
            }


            return true;
        }
        catch(\Exception $e)
        {
            return false;
        }
    }

    protected function _notifyUser($user_profile_id, $service_provider_id)
    {//get user

        if( $user = $this->_getAccountService()->getUser(NULL, $user_profile_id, false) ) {

            if( $main_agent_user = $this->_getAccountService()->getUser(NULL, $service_provider_id, false) ) {
                $this->_sendEmail($user, $main_agent_user);
            }
            if ($this->_getAccountService()->checkAccessByUserProfileId($user_profile_id, FunctionCode::APP_PUBLIC_FUNCTIONS)) {//for APP user - send chat
                //send notification as well
                $this->_sendChat($user, true);
            } else if ($this->_getAccountService()->checkAccessByUserProfileId($user_profile_id, FunctionCode::PUBLIC_FUNCTIONS)) {//for non APP user - send SMS
                $this->_sendSMS($user);
            }
        }
        return false;
    }

    protected function _getEmailSubject()
    {
        $coreconfig = CoreConfigDataServiceFactory::build();
        return $coreconfig->getConfig(CoreConfigType::ACCOUNT_VERIFIED_EMAIL_SUBJECT);
    }

    protected function _sendEmail(User $user, User $mainAgentUser)
    {
        if( $email = $this->_checkEmailToSend($user) )
        {
            if( $subject = $this->_getEmailSubject() AND
                $body = $this->_getEmailBody($user, $mainAgentUser) )
            {
                $communication = new CommunicationServiceProducer();
                return $communication->sendEmail(getenv("ICS_PROJECT_ID"), $subject, $body, $body, array($email));
            }
        }

        return false;
    }

    protected function _sendSMS(User $user)
    {
        if( $mobile = $user->getMobileNumberObj() )
        {
            $content = $this->_getMessage();
            $mobile_country_code = '+'.$mobile->getDialingCode();
            $basic_mobile_number = $mobile->getMobileNumber();
            $communication = new CommunicationServiceProducer();
            return $communication->sendSMS(getenv("ICS_PROJECT_ID"), $content, $mobile_country_code, $basic_mobile_number, "", "");
        }

        return false;
    }

    protected function _sendChat(User $user, $content, $forUserApp = true)
    {
        if( $projectID = $this->_getProjectID($forUserApp) )
        {
            $from = '';  // From will be null if  it is System
            $to = $user->getId();
            $channel = NotificationChannel::N2U;
            $type = NotificationTag::MESSAGE;
            $id = '';
            $option = '';
            $chat_obj = new ChatServiceProducer();

            $stat = $chat_obj->sendNotification($projectID, $content, $from, $to, $channel, $type, $id, $option);
            return $stat;
        }

        return false;
    }

    protected function _getProjectID($forUserApp)
    {
        if( $forUserApp )
            return getenv("ICS_USER_PROJECT_ID");
        else
            return getenv("ICS_AGENT_PROJECT_ID");
    }

    protected function _getEmailBody(User $user, User $mainAgentUser)
    {
        $coreconfig = CoreConfigDataServiceFactory::build();
        $body = $coreconfig->getConfig(CoreConfigType::ACCOUNT_VERIFIED_EMAIL_BODY);

        $body = str_replace("[NAME]", $user->getName(), $body);

        $companyName = $mainAgentUser->getName();
        $mainAgentUser = AddressNameExtractor::extract($mainAgentUser);
        $address = $this->_getAddress($mainAgentUser);

        $body = str_replace("[COMPANYNAME]", $companyName, $body);
        $body = str_replace("[COMPANYADDRESS]", $address, $body);

        return $body;
    }

    protected function _getMessage()
    {
        $coreconfig = CoreConfigDataServiceFactory::build();
        return $coreconfig->getConfig(CoreConfigType::ACCOUNT_VERIFIED_MESSAGE);
    }

    protected function _getAddress(User $user){
        $address = $user->getHostAddress()->address;
        if(!empty($user->getHostAddress()->postal_code)){
            $address .= "," . $user->getHostAddress()->postal_code;
        }
        if(!empty($user->getHostAddress()->city_name)){
            $address .= "," . $user->getHostAddress()->city_name;
        }
        if(strcasecmp($user->getHostAddress()->city_name, $user->getHostAddress()->province_name) != 0){
            if(!empty($user->getHostAddress()->province_name))
                $address .= "," . $user->getHostAddress()->province_name;
        }
        if(strcasecmp($user->getHostAddress()->country_name, $user->getHostAddress()->province_name) != 0){
            if(!empty($user->getHostAddress()->country_name))
                $address .= "," . $user->getHostAddress()->country_name;
        }

        return $address;
    }

    protected function _checkEmailToSend(User $user)
    {
        if( !$user->getEmailVerifiedAt()->isNull() )
            return $user->getEmail();

        return false;
    }

    public function listenEvent()
    {
        $this->listen(RemittanceCompanyUserEventType::REMCO_PROFILE_STATUS_CHANGED, RemittanceCompanyUserStatus::VERIFIED, 'remittance.queue.notifyAccountVerified');
    }

}