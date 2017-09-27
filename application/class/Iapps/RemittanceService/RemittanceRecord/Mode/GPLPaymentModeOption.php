<?php

namespace Iapps\RemittanceService\RemittanceRecord\Mode;

use Iapps\Common\Microservice\AccountService\AccountService;
use Iapps\DeliveryService\RecipientAddress\RecipientAddressCollection;
use Iapps\RemittanceService\Attribute\RecipientAttributeCollection;
use Iapps\RemittanceService\Common\AddressNameExtractor;
use Iapps\RemittanceService\Common\MessageCode;
use Iapps\RemittanceService\Recipient\Recipient;
use Iapps\RemittanceService\Attribute\RecipientAttributeServiceFactory;
use Iapps\RemittanceService\Attribute\AttributeCode;
use Iapps\RemittanceService\Recipient\RecipientServiceFactory;
use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\RemittanceService\RemittanceCompany\RemittanceCompanyServiceFactory;
use Iapps\RemittanceService\RemittanceRecord\RemittanceCalculationDirection;
use Iapps\RemittanceService\RemittanceRecord\RemittancePaymentModeOptionInterface;
use Iapps\RemittanceService\RemittanceRecord\RemittanceRecordServiceFactory;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransaction;
use Iapps\RemittanceService\RemittanceRecord\RemittanceRecord;
use Iapps\RemittanceService\RemittanceTransaction\ItemType;
use Iapps\RemittanceService\RemittanceCompanyUser\RemittanceCompanyUserServiceFactory;
use Iapps\Common\Microservice\PaymentService\PaymentServiceFactory;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransactionServiceFactory;
use Iapps\Common\Microservice\AccountService\User;
use Iapps\Common\Core\IpAddress;
use Iapps\RemittanceService\Common\Logger;
use Iapps\Common\Microservice\CountryService\CountryServiceFactory;

class GPLPaymentModeOption implements RemittancePaymentModeOptionInterface{

    protected $_accountService;

    protected $transaction;
    protected $sender_id;
    protected $recipient_id;

    protected $updatedBy;
    protected $ipAddress;

    const REMITTANCE_COMPANY_CODE = 'GPL';
    const SALARY_INCOME_SOURCE = 'Salary';

    function __construct(RemittanceTransaction $transaction, $sender_id, $recipient_id, $ipAddress='127.0.0.1', $updated_by=null)
    {
        $this->transaction = $transaction;
        $this->sender_id = $sender_id;
        $this->recipient_id = $recipient_id;
        if($ipAddress instanceof IpAddress) {
            $this->setIpAddress($ipAddress);
        }else{
            $this->setIpAddress(IpAddress::fromString($ipAddress));
        }
        $this->setUpdatedBy($updated_by);
    }

    public function setUpdatedBy($updatedBy)
    {
        $this->updatedBy = $updatedBy;
        return $this;
    }

    public function getUpdatedBy()
    {
        return $this->updatedBy;
    }

    public function setIpAddress(IpAddress $ip)
    {
        $this->ipAddress = $ip;
        return $this;
    }

    public function getIpAddress()
    {
        return $this->ipAddress;
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

        if( $user = $this->_getUser($this->sender_id) )
        {
            $arrAddress = $this->_getAddress($user);

            $paymentInfo['option']['sender_fullname'] = $user->getFullName();
            $paymentInfo['option']['sender_gender'] = $user->getGender();
            $paymentInfo['option']['sender_member_number'] = $user->getAccountID();

            /*if($remittanceUserComp = $this->_getRemittanceProfile($user)){
                $paymentInfo['option']['sender_member_number'] = $remittanceUserComp->getCustomerID();
            }*/
            $paymentInfo['option']['sender_member_number'] = $user->getAccountID();

            $paymentInfo['option']['sender_id_number'] = $user->getHostIdentity()->number;
            if( isset($user->getHostIdentity()->expiry_date) )
                $paymentInfo['option']['sender_id_expiry'] = $user->getHostIdentity()->expiry_date;
            $paymentInfo['option']['sender_birth_date'] = $user->getDOB()->getString();
            $paymentInfo['option']['sender_address'] = $arrAddress['address'];
            $paymentInfo['option']['sender_postal_code'] = $arrAddress['postal_code'];
            $paymentInfo['option']['sender_phone'] = '+'.$user->getMobileNumber();

            if( $value = $user->getAttributes()->hasAttribute('nationality') )
                $paymentInfo['option']['sender_nationality'] = $value;

            if( $value = $user->getAttributes()->hasAttribute('id_type') )
                $paymentInfo['option']['sender_id_type'] = $value;

            $paymentInfo['option']['sender_occupation'] = 'Others';

            if( $income_source = $user->getAttributes()->hasAttribute('income_source') )
                $paymentInfo['option']['sender_income_source'] = $income_source;


            if($income_source == self::SALARY_INCOME_SOURCE){
                if( $value = $user->getAttributes()->hasAttribute('occupation') )
                    $paymentInfo['option']['sender_occupation'] = $value;
            }else{
                if( $value = $user->getAttributes()->hasAttribute('nature_business') )
                    $paymentInfo['option']['sender_occupation'] = 'Others - ' . $value;
            }
        }

        if( $recipient = $this->_getRecipient($this->recipient_id) )
        {
            //,"receiver_address":"address 2",
            if($recipient instanceof Recipient){

                $paymentInfo['option']['receiver_mobile_phone'] = '+'.$recipient->getRecipientDialingCode()->getValue().$recipient->getRecipientMobileNumber()->getValue();

                $attribute = RecipientAttributeServiceFactory::build();
                if( $recipient_attributes = $attribute->getAllRecipientAttribute($recipient->getId())
                    AND $recipient_attributes instanceof RecipientAttributeCollection )
                {
                    if( $value = $recipient_attributes->hasAttribute(AttributeCode::RESIDENTIAL_COUNTRY) )
                        $paymentInfo['option']['receiver_country_code'] = $value;
                    if( $value = $recipient_attributes->hasAttribute(AttributeCode::FULL_NAME) )
                        $paymentInfo['option']['receiver_name'] = $value;
                    /*if( $value = $recipient_attributes->hasAttribute(AttributeCode::RESIDENTIAL_ADDRESS) )
                       $paymentInfo['option']['receiver_address'] = $value;
                   if( $value = $recipient_attributes->hasAttribute(AttributeCode::RESIDENTIAL_POST_CODE) )
                       $paymentInfo['option']['receiver_address'] .= "," . $value;
                   if( $value = $recipient_attributes->hasAttribute(AttributeCode::RESIDENTIAL_PROVINCE) )
                       $paymentInfo['option']['receiver_address'] .= "," . $value;
                   if( $value = $recipient_attributes->hasAttribute(AttributeCode::RESIDENTIAL_CITY) ){
                       $paymentInfo['option']['receiver_address'] .= "," . $value;
                       $paymentInfo['option']['bank_branch'] = $value;
                    }*/

                    $arrAddress = $this->_getAddressAttribute($recipient_attributes);
                    $paymentInfo['option']['receiver_address'] = $arrAddress['address'];
                    $paymentInfo['option']['bank_branch'] =  isset($arrAddress['city']) ? $arrAddress['city'] : '-';

                    if( $value = $recipient_attributes->hasAttribute(AttributeCode::RELATIONSHIP_TO_SENDER) )
                        $paymentInfo['option']['relationship'] = $value;
                    if( $value = $recipient_attributes->hasAttribute(AttributeCode::PURPOSE_OF_REMITTANCE) )
                        $paymentInfo['option']['purpose'] = $value;
                }

                if( !isset($paymentInfo['option']['receiver_name']) AND $recipient->isSlideUser() )
                {
                    //$paymentInfo['option']['receiver_name'] = $recipient->getRecipientUser()->getFullName();
                    $paymentInfo['option']['receiver_mobile_phone'] = '+'.$recipient->getRecipientUser()->getMobileNumber();
                    $paymentInfo['option']['receiver_country_code'] = $recipient->getRecipientUser()->getHostCountryCode();

                    $arrAddress = $this->_getAddress($recipient->getRecipientUser());

                    $paymentInfo['option']['receiver_address'] = $arrAddress['address'];
                    $paymentInfo['option']['bank_branch'] = isset($arrAddress['city']) ? $arrAddress['city'] : '-';
                }


                $paymentInfo['option']['receiver_name'] = $paymentInfo['option']['account_holder_name'];
            }
        }

        //Transaction Info
        $home_collection = "no";
        $rate = 0;
        $service_charge = 0;
        $remittanceServ = RemittanceRecordServiceFactory::build();

        if($remittanceInfo = $remittanceServ->getByTransactionId($this->transaction->getId(), false)){
            if($remittanceInfo instanceof RemittanceRecord){
                if($remittanceInfo->getIsHomeCollection()){
                    $home_collection = "yes";
                }
                $rate = $remittanceInfo->getDisplayRate();

                if($trxInfo = $remittanceServ->getRemittanceTransactionDetail($remittanceInfo->getId())){
                    $service_charge = $trxInfo->getFeesCharged() + $trxInfo->getDiscount();
                }
                $paymentInfo['option']['send_amount'] = $remittanceInfo->getFromAmount();
                $paymentInfo['option']['send_amount_currency'] = $remittanceInfo->getInTransaction()->getCountryCurrencyCode();
                $paymentInfo['option']['customer_ref_no'] = $remittanceInfo->getRemittanceID();
            }
        }
        $paymentInfo['option']['payment_method'] = $paymentInfo['payment_method'];

        $paymentInfo['option']['home_collection'] = $home_collection;
        $paymentInfo['option']['rate'] = $rate;
        $paymentInfo['option']['service_charge'] = !is_null($service_charge) ? $service_charge : 0;

        $paymentInfo['option']['conversion_direction'] = '*';
        if($paymentInfo['calc_dir'] == RemittanceCalculationDirection::DIR_TO){
            $paymentInfo['option']['conversion_direction'] = '/';
        }
        $paymentInfo['option']['round_decimal'] = 0;

        return $paymentInfo;
    }

    public function saveResponse()
    {
        $paymentServ = PaymentServiceFactory::build();

        $tranServ = RemittanceTransactionServiceFactory::build();

        if($this->transaction->getRefTransactionId()){
            return true;
        }
        if ($requestInfo = $paymentServ->getPaymentRequestByTransactionID($this->transaction->getTransactionID())) {
            if (property_exists($requestInfo, "result")) {
                if (count($requestInfo->result) > 0) {

                    $tranServ->setUpdatedBy($this->getUpdatedBy());
                    $tranServ->setIpAddress($this->getIpAddress());

                    $this->transaction->setUpdatedBy($this->getUpdatedBy());
                    Logger::debug('GPL Payment Request - ' .  $requestInfo->result[0]->id);
                    $response = $requestInfo->result[0]->response;
                    Logger::debug('GPL Response - ' . $response);
                    Logger::debug('GPL Reference - ' . json_encode($requestInfo->result[0]));
                    $response_arr = json_decode($response, true);

                    if(!isset($response_arr['transfer'])){
                        return false;
                    }
                    $gplResponse_arr = json_decode($response_arr['transfer'], true);

                    if(isset($gplResponse_arr['bill_no'])) {
                        $ori_transaction = clone($this->transaction);
                        $this->transaction->setRefTransactionId($gplResponse_arr['bill_no']);
                        if (!$result = $tranServ->update($this->transaction, $ori_transaction)) {
                            return false;
                        }

                    }
                    if(isset($gplResponse_arr['member_number'])) {
                        $remittanceCompanyUserServ = RemittanceCompanyUserServiceFactory::build();
                        $user = $this->_getUser($this->sender_id);
                        $remittanceComp = $this->_getRemittanceCompany();

                        if(!$result = $remittanceCompanyUserServ->updateProfile($user, $remittanceComp, $gplResponse_arr['member_number'])){
                            return false;
                        }

                    }
                    return true;
                }
            }
        }
        return false;
    }

    public function getFormattedResponseMessage($response)
    {
        $response_arr = json_decode($response, true);
        $response_key = array('transfer', 'inquiry');

        foreach($response_key as $key){
            if(isset($response_arr[$key])) {
                $response_arr = json_decode($response_arr[$key], true);
                break;
            }
        }

        $response_arr['remarks'] = isset($response_arr['response_message']) ? $response_arr['response_message'] : '';

        return $response_arr;
    }

    protected function _getRecipient($recipient_id)
    {
        $recipient_serv = RecipientServiceFactory::build();

        if( !$recipient = $recipient_serv->getRecipientDetail($recipient_id,false) )
        {
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

    protected function _getRemittanceCompany(){
        $remittanceCompanyServ = RemittanceCompanyServiceFactory::build();
        if($remittanceComp = $remittanceCompanyServ->getByCompanyCode(self::REMITTANCE_COMPANY_CODE)){
            return $remittanceComp;
        }
        return false;
    }
    protected function _getRemittanceProfile(User $user){
        $remittanceCompanyUserServ = RemittanceCompanyUserServiceFactory::build();

        if($remittanceComp = $this->_getRemittanceCompany()){
            if($remittanceUserComp = $remittanceCompanyUserServ->getByCompanyAndUser($remittanceComp, $user->getId())){
                return $remittanceUserComp;
            }
        }

        return false;
    }

    protected function _getAddress(User $user){
        $arrAddress = array();
        $user = AddressNameExtractor::extract($user);
        $address = $user->getHostAddress()->address;
        if(!empty($user->getHostAddress()->postal_code)){
            $arrAddress['postal_code'] = $user->getHostAddress()->postal_code;
            //$address .= "," . $user->getHostAddress()->postal_code;
        }
        if(!empty($user->getHostAddress()->city_name)){
            $arrAddress['city'] = $user->getHostAddress()->city_name;
            //$address .= "," . $user->getHostAddress()->city_name;
        }
        /*
        if(strcasecmp($user->getHostAddress()->city_name, $user->getHostAddress()->province_name) != 0){
            if(!empty($user->getHostAddress()->province_name))
                $address .= "," . $user->getHostAddress()->province_name;
        }
        if(strcasecmp($user->getHostAddress()->country_name, $user->getHostAddress()->province_name) != 0){
            if(!empty($user->getHostAddress()->country_name))
                $address .= "," . $user->getHostAddress()->country_name;
        }*/
        $arrAddress['address'] = $address;
        return $arrAddress;
    }

    protected function _getAddressAttribute(RecipientAttributeCollection $recipient_attributes)
    {
        $arrAddress = array();
        $address = '';

        if ($value = $recipient_attributes->hasAttribute(AttributeCode::RESIDENTIAL_ADDRESS))
            $address = $value;

        if ($value = $recipient_attributes->hasAttribute(AttributeCode::RESIDENTIAL_POST_CODE)) {
            $arrAddress['postal_code'] = $value;
        }

        $countryService = CountryServiceFactory::build();

        $city_name = '';
        if ($value = $recipient_attributes->hasAttribute(AttributeCode::RESIDENTIAL_CITY)) {
            $code = $value;
            if ($city = $countryService->getCityInfo($code)) {
                $city_name = $city->getName();
            }
        }

        $arrAddress['city'] = $city_name;
        $arrAddress['address'] = $address;
        return $arrAddress;
    }

}