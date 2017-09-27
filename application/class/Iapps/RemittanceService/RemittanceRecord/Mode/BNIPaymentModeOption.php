<?php

namespace Iapps\RemittanceService\RemittanceRecord\Mode;

use Iapps\RemittanceService\Recipient\Recipient;
use Iapps\RemittanceService\Attribute\RecipientAttributeServiceFactory;
use Iapps\RemittanceService\Attribute\AttributeCode;
use Iapps\RemittanceService\Recipient\RecipientServiceFactory;
use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\RemittanceService\RemittanceRecord\RemittancePaymentModeOptionInterface;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransaction;
use Iapps\RemittanceService\Common\MessageCode;



class BNIPaymentModeOption implements RemittancePaymentModeOptionInterface{

    protected $_accountService;

    protected $transaction;
    protected $sender_id;
    protected $recipient_id;


    function __construct(RemittanceTransaction $transaction, $sender_id, $recipient_id)
    {
        $this->transaction = $transaction;
        $this->sender_id = $sender_id;
        $this->recipient_id = $recipient_id;
    }

    public function setAccountService(AccountService $accountService)
    {
        $this->_accountService = $accountService;
    }

    public function getAccountService()
    {
        if( !$this->_accountService )
        {
            $this->_accountService = AccountServiceFactory::build();
        }

        return $this->_accountService;
    }

    public function getOption(array $paymentInfo)
    {
        if(! $this->transaction->isCashOut()){
            return $paymentInfo;
        }

        $paymentInfo['option']['landed_currency'] = explode("-", $paymentInfo['country_currency_code'])[1];

        if( $user = $this->_getUser($this->sender_id) )
        {
            $address =  $user->getHostAddress()->address;
            if(!empty($user->getHostAddress()->postal_code)){
                $address .= "," . $user->getHostAddress()->postal_code;
            }
            if(!empty($user->getHostAddress()->province)){
                $address .= "," . $user->getHostAddress()->province;
            }
            if(!empty($user->getHostAddress()->city)){
                $address .= "," . $user->getHostAddress()->city;
            }
            if(!empty($user->getHostAddress()->country)){
                $address .= "," . $user->getHostAddress()->country;
            }
            $paymentInfo['option']['sender_address'] = $address;
            $paymentInfo['option']['sender_phone'] = $user->getMobileNumber();
            $paymentInfo['option']['sender_fullname'] = $user->getFullName();

        }

        if( $recipient = $this->_getRecipient($this->recipient_id) )
        {
            if($recipient instanceof Recipient){

                if($recipient->isSlideUser()){
                    $paymentInfo['option']['receiver_mobile_phone'] = $recipient->getRecipientUser()->getMobileNumber();
                    $paymentInfo['option']['receiver_fullname'] = $recipient->getRecipientUser()->getFullName();
                }else{
                    $paymentInfo['option']['receiver_mobile_phone'] = $recipient->getRecipientDialingCode()->getValue().$recipient->getRecipientMobileNumber()->getValue();
                    $paymentInfo['option']['receiver_fullname'] = $recipient->getRecipientAlias();

                }
                $attribute       = RecipientAttributeServiceFactory::build();

                $address = '';
                if($recipient_attributes = $attribute->getAllRecipientAttribute($recipient->getId())){
                    foreach($recipient_attributes as $attr){
                        if($attr->getAttribute()->getCode() == AttributeCode::RESIDENTIAL_ADDRESS ){
                            $address = $attr->getValue();
                        }
                    }
                }

                $paymentInfo['option']['receiver_address'] = $address;
                /*$paymentInfo['option']['receiver_gender'] = 'F';
                $paymentInfo['option']['receiver_birth_date'] = '2014-02-27';*/
             }
        }
        return $paymentInfo;
    }

    public function saveResponse()
    {
        return true;
    }

    public function getFormattedResponseMessage($response)
    {
        return $response;
    }

    protected function _getRecipient($recipient_id)
    {
        $recipient_serv = RecipientServiceFactory::build();
        if( !$recipient = $recipient_serv->getRecipientDetail($recipient_id,false) )
        {
            $this->setResponseCode(MessageCode::CODE_INVALID_RECIPIENT);
            return false;
        }

        if( $user_id = $recipient->getRecipientUserProfileId() )
        {
            $accServ = $this->getAccountService();
            if ($user = $accServ->getUser(NULL, $user_id))
            {
                $recipient->setRecipientUser($user);
            }
        }

        return $recipient;
    }

    protected function _getUser($user_id)
    {
        $accServ = $this->getAccountService();
        if( $user = $accServ->getUserProfile($user_id) )
        {
            return $user;
        }

        return false;
    }

    public function setResponseCode($responseCode)
    {
        $this->responseCode = $responseCode;
        return true;
    }

    public function getResponseCode()
    {
        return $this->responseCode;
    }
}