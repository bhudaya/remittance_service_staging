<?php

namespace Iapps\RemittanceService\RemittanceRecord;

use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\CorporateService\CorporateServiceCollection;
use Iapps\Common\Helper\RequestHeader;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\Common\Microservice\CountryService\CountryServiceFactory;
use Iapps\Common\Microservice\DeliveryService\Delivery;
use Iapps\Common\Microservice\DeliveryService\DeliveryServiceFactory;
use Iapps\Common\Microservice\PaymentService\CountryCurrency;
use Iapps\Common\Microservice\PaymentService\PaymentServiceFactory;
use Iapps\Common\Microservice\PromoCode\PromoCodeClientFactory;
use Iapps\Common\Microservice\PromoCode\PromoTransactionType;
use Iapps\Common\Transaction\TransactionItemService;
use Iapps\RemittanceService\Attribute\AttributeValue;
use Iapps\RemittanceService\Attribute\AttributeValueServiceFactory;
use Iapps\RemittanceService\Attribute\RecipientAttributeServiceFactory;
use Iapps\RemittanceService\Attribute\AttributeCode;
use Iapps\RemittanceService\Attribute\RemittanceAttributeServiceFactory;
use Iapps\RemittanceService\Common\ChannelTypeValidator;
use Iapps\RemittanceService\Common\CoreConfigDataServiceFactory;
use Iapps\RemittanceService\Common\CoreConfigType;
use Iapps\RemittanceService\Common\IncrementIDServiceFactory;
use Iapps\Common\AuditLog\AuditLogAction;
use Iapps\Common\CommunicationService\CommunicationServiceProducer;
use Iapps\Common\Core\IappsBaseService;
use Iapps\Common\CorporateService\CorporateService;
use Iapps\Common\Helper\GuidGenerator;
use Iapps\Common\Microservice\AccountService\AccountService;
use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\Common\Microservice\AccountService\User;
use Iapps\Common\Microservice\PaymentService\PaymentService;
use Iapps\Common\Transaction\Transaction;
use Iapps\Common\Transaction\TransactionItemCollection;
use Iapps\Common\Transaction\TransactionStatus;
use Iapps\RemittanceService\Common\MessageCode;
use Iapps\Common\Core\IappsBaseRepository;
use Iapps\RemittanceService\Common\IncrementIDAttribute;
use Iapps\RemittanceService\ExchangeRate\ExchangeRateServiceFactory;
use Iapps\RemittanceService\RecipientCollectionInfo\RecipientCollectionInfoServiceFactory;
use Iapps\RemittanceService\Common\SystemCodeServiceFactory;
use Iapps\RemittanceService\Common\TransactionType;
use Iapps\RemittanceService\Recipient\Recipient;
use Iapps\RemittanceService\Recipient\RecipientServiceFactory;
use Iapps\RemittanceService\RemittanceCompany\RemittanceCompanyServiceFactory;
use Iapps\RemittanceService\RemittanceCompanyRecipient\RemittanceCompanyRecipient;
use Iapps\RemittanceService\RemittanceCompanyRecipient\RemittanceCompanyRecipientServiceFactory;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfig;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfigCollection;
use Iapps\RemittanceService\RemittanceProfitSharing\RemittanceProfitSharingPartyServiceFactory;
use Iapps\RemittanceService\RemittanceTransaction\ItemType;
use Iapps\RemittanceService\RemittanceTransaction\ProfitCostType;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransaction;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransactionItemServiceFactory;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransactionServiceFactory;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfigServiceFactory;
use Iapps\RemittanceService\RefundRequest\RefundRequestServiceFactory;
use Iapps\RemittanceService\RemittanceTransaction\TransactionProfitCost;
use Iapps\RemittanceService\RemittanceTransaction\TransactionProfitCostRepository;
use Iapps\RemittanceService\RemittanceTransaction\TransactionProfitCostServiceFactory;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransactionEventBroadcastWithKeyProducer;
use Iapps\RemittanceService\Common\Logger;
use Iapps\RemittanceService\Recipient\RecipientType;
use Iapps\RemittanceService\Common\NameMatcher;
use Iapps\RemittanceService\Common\CacheKey;
use Iapps\Common\Core\IappsBaseEntityCollection;

class RemittanceRecordService extends IappsBaseService{

    protected $paymentInterface;
    protected $channel;
    protected $calculation_direction = 'to';
    protected $send_amount = NULL;
    protected $isNFF = true;

    private $pass = 1;
    private $fail = 2;   

    protected $_accountService;

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

    public function setChannelCode($code)
    {
        if( $channel = ChannelTypeValidator::validate($code) )
        {
            $this->channel = $channel;
            return $this;
        }

        return false;
    }

    public function getChannel()
    {
        if( !$this->channel )
        {
            $this->setResponseCode(MessageCode::CODE_INVALID_CHANNEL);
            return false;
        }

        return $this->channel;
    }

    public function setCalcDirection($calcDir)
    {
        $this->calculation_direction = $calcDir;

        return $this;
    }

    public function getCalcDirection()
    {
        return $this->calculation_direction;
    }

    public function setSendAmount($sendAmount)
    {
        $this->send_amount = $sendAmount;

        return $this;
    }

    public function getSendAmount()
    {
        return $this->send_amount;
    }

    function __construct(IappsBaseRepository $rp, $ipAddress='127.0.0.1', $updatedBy=NULL, RemittancePaymentInterface $interface = NULL)
    {
        parent::__construct($rp, $ipAddress, $updatedBy);
        if( $interface == NULL )
            $this->paymentInterface = new UserRemittancePayment();
        else
            $this->paymentInterface = $interface;
    }

    public function getListRemittanceTransaction($limit, $page, $start_time = null, $end_time = null, $prelim_check = null, $status = null, $for_international = null)
    {
        $recipient_arr = NULL;
        $record = new  RemittanceRecord();

        $channelFilter = array();
        if($for_international !=null || $for_international === false) {
            $remit_config_serv = RemittanceConfigServiceFactory::build();
            if( $info = $remit_config_serv->getAllRemittanceConfig(MAX_VALUE, 1) )
                $remittanceConfigColl = $info->result;

            if ($remittanceConfigColl) {
                $remittanceConfigColl = $remittanceConfigColl->getChannel($for_international);
                $channelFilter = $remittanceConfigColl->getIds();
            }
        }

        //if( $listObj = $this->getRepository()->findRemittanceTransactionList($limit, $page, $start_time, $end_time ,$prelim_check, $status) )
        if( $listObj = $this->getRepository()->findByParam($record, $limit, $page, $recipient_arr, $channelFilter, $start_time, $end_time, $prelim_check, $status) )
        {
            $results = array();
            $results['data'] = $listObj->result;
            $results['total'] = $listObj->total;
            $paymentServ = PaymentServiceFactory::build();
            $transactionServ = RemittanceTransactionServiceFactory::build();

            $allCountryCurrency = $paymentServ->getAllCountryCurrency();
            if(!empty($allCountryCurrency))
            {
                foreach ($results['data'] as $res)
                {
                    $inTransactionInfo = $transactionServ->findById($res->getInTransactionId());
                    $outTransactionInfo = $transactionServ->findById($res->getOutTransactionId());

                    if(!empty($inTransactionInfo) && !empty($outTransactionInfo))
                    {
                        $res->setInTransactionIDString($inTransactionInfo->getTransactionID());
                        $res->setOutTransactionIDString($outTransactionInfo->getTransactionID());

                        $res->setInCountryCurrencyCode($inTransactionInfo->getCountryCurrencyCode());
                        $res->setOutCountryCurrencyCode($outTransactionInfo->getCountryCurrencyCode());
                        foreach ($allCountryCurrency as $country)
                        {
                            if ($country->getCode() == $res->getInCountryCurrencyCode())
                            {
                                $res->setFromCountryCode($country->getCountryCode());
                                $res->setFromCurrencyCode($country->getCurrencyCode());
                            }
                            if ($country->getCode() == $res->getOutCountryCurrencyCode())
                            {
                                $res->setToCountryCode($country->getCountryCode());
                                $res->setToCurrencyCode($country->getCurrencyCode());
                            }
                        }
                    }
                    $res->setApprovalRequired('N/A');
                    if( empty($res->getApprovalStatus()) )
                    {
                        $res->setApprovalStatus('N/A');
                    }
                }
            }
            $this->setResponseCode(MessageCode::CODE_LIST_REMITTANCE_TRANSACTION_SUCCESS);
            return $results;
        }
        $this->setResponseCode(MessageCode::CODE_LIST_REMITTANCE_TRANSACTION_FAILED);
        return false;
    }

    public function getRemittanceTransactionDetail($id)
    {
        if( $details = $this->getRepository()->findById($id) )
        {
            $module_code = getenv('MODULE_CODE');
            $paymentServ = PaymentServiceFactory::build();
            $transactionServ = RemittanceTransactionServiceFactory::build();
            $countryServ = CountryServiceFactory::build();
            $sysServ = SystemCodeServiceFactory::build();
            $recServ = RecipientServiceFactory::build();
            $recipientCollServ = RecipientCollectionInfoServiceFactory::build();

            $allCountryCurrency = $paymentServ->getAllCountryCurrency();
            $details->setRemittanceStatus($details->getStatus()->getDisplayName());
            if(!empty($allCountryCurrency))
            {
                $inTransactionInfo = $transactionServ->findById($details->getInTransactionId());
                $outTransactionInfo = $transactionServ->findById($details->getOutTransactionId());
                $item = RemittanceTransactionItemServiceFactory::build();
                $fee = $item->findByTransactionId($details->getInTransactionId());
                if(!empty($fee))
                {
                    $total_fee = 0;
                    $total_discount = 0;

                    foreach($fee->result as $f)
                    {
                        if($f->getItemType()->getCode() == ItemType::CORPORATE_SERVICE_FEE || $f->getItemType()->getCode() == ItemType::PAYMENT_FEE) {
                            $total_fee += $f->getNetAmount();
                        } else if ($f->getItemType()->getCode() == ItemType::DISCOUNT) {
                            $total_discount += $f->getNetAmount();
                        }
                    }

                    $details->setFeesCharged($total_fee);
                    $details->setDiscount($total_discount);

                }
                if (!empty($inTransactionInfo) && !empty($outTransactionInfo))
                {
                    $in_status = $sysServ->getById($inTransactionInfo->getStatus()->getId());
                    if(!empty($in_status))
                    {
                        $details->setInTransactionStatus($in_status->getDisplayName());
                    }
                    $out_status = $sysServ->getById($outTransactionInfo->getStatus()->getId());
                    if(!empty($out_status))
                    {
                        $details->setOutTransactionStatus($out_status->getDisplayName());
                    }

                    $details->setInTransactionIDString($inTransactionInfo->getTransactionID());
                    $details->setOutTransactionIDString($outTransactionInfo->getTransactionID());

                    $details->setInCountryCurrencyCode($inTransactionInfo->getCountryCurrencyCode());
                    $details->setOutCountryCurrencyCode($outTransactionInfo->getCountryCurrencyCode());
                    foreach ($allCountryCurrency as $country)
                    {
                        if ($country->getCode() == $details->getInCountryCurrencyCode())
                        {
                            $details->setFromCountryCode($country->getCountryCode());
                            $details->setFromCurrencyCode($country->getCurrencyCode());
                        }
                        if ($country->getCode() == $details->getOutCountryCurrencyCode())
                        {
                            $details->setToCountryCode($country->getCountryCode());
                            $details->setToCurrencyCode($country->getCurrencyCode());
                        }
                    }
                    $inTransactionType = $inTransactionInfo->getTransactionType()->getCode();
                    $outTransactionType = $outTransactionInfo->getTransactionType()->getCode();

                    if(!empty($inTransactionType))
                    {
                        $details->setInTransactionType($inTransactionType);
                        $sysInName = $sysServ->getByCode($details->getInTransactionType(),TransactionType::getSystemGroupCode());
                        if(!empty($sysInName) )
                        {
                            $details->setIntransactionType($sysInName->getDisplayName());
                        }

                    }
                    if(!empty($outTransactionType))
                    {
                        $details->setOutTransactionType($outTransactionType);
                        $sysOutName = $sysServ->getByCode($details->getOutTransactionType(),TransactionType::getSystemGroupCode());
                        if(!empty($sysInName) )
                        {
                            $details->setOuttransactionType($sysOutName->getDisplayName());
                        }
                    }

                    $details->setPaymentMode($inTransactionInfo->getConfirmPaymentCode());

                    if(!empty($details->getCollectionInfo()))
                    {
                        $paymentCode = json_decode($details->getCollectionInfo())->payment_code;
                        $collection_mode = $paymentServ->getPaymentInfo($paymentCode);
                        if(!empty($collection_mode))
                        {
                            $details->setCollectionMode($collection_mode->getName());
                        }
                    }
                }
                $fromCountryName = $countryServ->getCountryInfo($details->getFromCountryCode());
                if (!empty($fromCountryName))
                {
                    $details->setFromCountryName($fromCountryName->getName());
                }
                $toCountryName = $countryServ->getCountryInfo($details->getToCountryCode());
                if (!empty($toCountryName))
                {
                    $details->setToCountryName($toCountryName->getName());
                }

            }

            $remittanceAttributeServ = RemittanceAttributeServiceFactory::build();
            $remittanceAttributes = $remittanceAttributeServ->getAllRemittanceAttribute($details->getId());
            if( !empty($remittanceAttributes) )
            {
                foreach($remittanceAttributes as $attr)
                {
                    if($attr->getAttribute()->getCode() == AttributeCode::REJECT_REASON || $attr->getAttribute()->getCode()==AttributeCode::APPROVE_REASON ){
                        $details->setReason( $attr->getValue() );
                    }
                    if($attr->getAttribute()->getCode() == AttributeCode::PIN_NUMBER ){
                        $details->setPinNumber( $attr->getValue() );
                    }
                }
            }

            $attribute       = RecipientAttributeServiceFactory::build();
            $remittance_purpose = $attribute->getRecipientAttributeByCode($details->getRecipient()->getId(),AttributeCode::PURPOSE_OF_REMITTANCE);
            if(!empty($remittance_purpose))
            {
                foreach( $remittance_purpose->result as $purp){
                    $details->setRemittancePurpose( $purp->getValue() );
                }
            }

            $ship = $attribute -> getRecipientAttributeByCode($details->getRecipient()->getId(),AttributeCode::RELATIONSHIP_TO_SENDER);
            if(!empty($ship))
            {
                foreach( $ship->result as $r )
                {
                    $details->setRelationshipToSender($r->getValue());
                }
            }

            $userServ = AccountServiceFactory::build();
            if( $recipient = $recServ->getRecipientDetail($details->getRecipient()->getId(), false) )
            {
                if( $recipient instanceof Recipient )
                {
                    $details->setRecipientUserProfileId($recipient->getRecipientUser()->getId());
                    if( $fullName = $recipient->getAttributes()->hasAttribute(AttributeCode::FULL_NAME) )
                        $details->setRecipientUserName($fullName);
                }
            }

            $sender_user = $userServ->getUserProfile($details->getSenderUserProfileId());
            if(!empty($sender_user)){
                foreach ( $sender_user->getAttributes() as $s ) {
                    if($s->getAttribute()->getCode() == AttributeCode::SOURCE_OF_INCOME)
                    {
                        $details->setSourceOfFunds( $s->getValue() );
                    }
                }
            }

            $users = $userServ->getUsers(array($details->getSenderUserProfileId(), $recipient->getRecipientUser()->getId()));
            if (!empty($users))
            {
                foreach ($users as $u) {
                    if(!empty($details->getSenderUserProfileId())){
                        if ($u->getId() == $details->getSenderUserProfileId()) {
                            $details->setSenderUserName($u->getFullName());
                        }
                    }else{
                        return false;
                    }
                }
            }

            $details->setApprovalRequired('N/A');
            if( empty($details->getApprovalStatus()) )
            {
                $details->setApprovalStatus('N/A');
            }

            $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_TRANSACTION_SUCCESS);
            return $details;
        }
        $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_TRANSACTION_FAILED);
        return false;
    }

    public function getFinanceListRemittanceTransaction($limit, $page, $start_time = null, $end_time = null, $status = null, $reference_no = null, $bank_account_no = null, $collection_type = null, $transaction_no = null, $user_profile_id = null)
    {
        $recipient_arr = NULL;
        $record = new  RemittanceRecord();


        $inConfigIds = array();
        $outConfigIds = array();
        $partner_id =  null;

        if($user_profile_id !=null){
            $remit_config_serv = RemittanceConfigServiceFactory::build();
            if( $info = $remit_config_serv->getAllRemittanceConfig(MAX_VALUE, 1) )
                $remittanceConfigColl = $info->result;

            if ($remittanceConfigColl) {
                $accServ = $this->getAccountService();
                if( $upline = $accServ->getUplineStructure($user_profile_id) ) {
                    if($partner_id = $upline->first_upline->getUser()->getId()){
                        $inConfigIds = $remittanceConfigColl->getFromCountryPartner($partner_id);
                        $outConfigIds =  $remittanceConfigColl->getToCountryPartner($partner_id);
                    }
                }
            }
        }


        $channelFilter = array_unique(array_merge($inConfigIds, $outConfigIds));

        if(empty($channelFilter)){
            return false;
        }

        $timezone_format = NULL;
        if ($login_user_info = $accServ->getUser(NULL,$user_profile_id)) {

            if (isset($login_user_info->getHostAddress()->country)) {

                $countryCode = $login_user_info->getHostAddress()->country;
                $country_serv = CountryServiceFactory::build();

                if($countryInfo = $country_serv->getCountryInfo($countryCode) )
                {
                    $timezone_format = $countryInfo->getTimezoneFormat();
                }

            }
        }

        if ($status != NULL) {

            $record->getStatus()->setCode($status);

            if( !$this->_extractStatusId($record) ) {
                $this->setResponseCode(MessageCode::CODE_LIST_REMITTANCE_TRANSACTION_FAILED);
                return false;
            }
        }

        $statuses = array(TransactionStatus::EXPIRED, TransactionStatus::CANCELLED, TransactionStatus::CONFIRMED, TransactionStatus::COMPLETED);

        $newCollection = new RemittanceRecordCollection();

        $paymentServ = PaymentServiceFactory::build();
        $transactionServ = RemittanceTransactionServiceFactory::build();

        //if( $listObj = $this->getRepository()->findRemittanceTransactionList($limit, $page, $start_time, $end_time ,$prelim_check, $status) )
        if( $results = $this->getRepository()->findByParam($record, null, null, $recipient_arr, $channelFilter, $start_time, $end_time, null, null) )
        {

            $exchangeRateServ = ExchangeRateServiceFactory::build();
            $transactionProfitCostServ = TransactionProfitCostServiceFactory::build();

            $allCountryCurrency = $paymentServ->getAllCountryCurrency();

            if(!empty($allCountryCurrency))
            {
                foreach ($results->result as $res)
                {

                    if ($timezone_format) {

                        $res->getCreatedAt()->setTimeZoneFormat($timezone_format);
                        $createdAt = $res->getCreatedAt()->getLocalDateTimeStr('Y-m-d H:i:s');
                        $res->getCreatedAt()->setDateTimeString($createdAt);
                    }

                    if ($reference_no != NULL || $collection_type!= NULL) {
                        
                        $temp_paymentRequestInfo = $paymentServ->getPaymentBySearchFilter(getenv('MODULE_CODE'), NULL, $reference_no, $collection_type, 999, 1);

                        $transactionID_arr = array();

                        foreach ($temp_paymentRequestInfo->result as $paymentColEach)
                        {
                            $transactionID_arr[] = $paymentColEach->getTransactionID();
                        }

                        $trxInfo = $transactionServ->getRepository()->findByTransactionIDArr($transactionID_arr);

                        $transactionId_arr_out = array();
                        $transactionId_arr_in = array();

                        foreach ($trxInfo->result as $trxColEach)
                        {   
                            if ($trxColEach->getTransactionType()->getCode() == TransactionType::CODE_LOCAL_CASH_OUT || $trxColEach->getTransactionType()->getCode() == TransactionType::CODE_CASH_OUT) {
                                $transactionId_arr_out[] = $trxColEach->getId();
                            }else if ($trxColEach->getTransactionType()->getCode() == TransactionType::CODE_LOCAL_CASH_IN || $trxColEach->getTransactionType()->getCode() == TransactionType::CODE_CASH_IN) {
                                $transactionId_arr_in[] = $trxColEach->getId();
                            }
                        }

                        if (!in_array($res->getOutTransactionId(), $transactionId_arr_out)) {
                            continue;
                        }
                    }

                    $inTransactionInfo = $transactionServ->findById($res->getInTransactionId());
                    $outTransactionInfo = $transactionServ->findById($res->getOutTransactionId());

                    if(!empty($inTransactionInfo) && !empty($outTransactionInfo))
                    {
                        $res->setInTransactionIDString($inTransactionInfo->getTransactionID());
                        $res->setOutTransactionIDString($outTransactionInfo->getTransactionID());

                        $res->setInCountryCurrencyCode($inTransactionInfo->getCountryCurrencyCode());
                        $res->setOutCountryCurrencyCode($outTransactionInfo->getCountryCurrencyCode());

                        $res->setInTransactionStatus($inTransactionInfo->getStatus()->getCode());
                        $res->setOutTransactionStatus($outTransactionInfo->getStatus()->getCode());

                        $trxProfitCost = new TransactionProfitCost();
                        $trxProfitCost->setBeneficiaryPartyId($partner_id);
                        $trxProfitCost->setType(ProfitCostType::PROFIT);
                        $trxProfitCost->setTransactionId($res->getInTransactionID());

                        if ( $paymentRequestInfo = $paymentServ->getPaymentBySearchFilter(getenv('MODULE_CODE'),$outTransactionInfo->getTransactionID(), NULL, NULL, 1,1) ) {
                            
                            foreach ($paymentRequestInfo->result as $tempColEach)
                            {

                                $res->setReferenceNumber($tempColEach->getReferenceId());
                                if ($tempColEach->getPaymentCode() == 'BT1') {
                                    
                                    if (stristr($res->getOutTransactionIDString(),'TR002')) {
                                        
                                        $final_trxIDStr = str_replace('TR002', '', $res->getOutTransactionIDString());
                                        $res->setOutTransactionIDString($final_trxIDStr);
                                    }
                                }
                            }

                        }


                        if(in_array($res->getRemittanceConfigurationId(), $outConfigIds)) {
                            if(!in_array($res->getOutTransactionStatus(), $statuses)){
                                continue;
                            }
                            // if($status){
                            //     if($res->getOutTransactionStatus()!=$status){
                            //         continue;
                            //     }
                            // }
                            $profits = $transactionProfitCostServ->getRepository()->findByParam($trxProfitCost, 10, 1);
                            $exchangeRate = $exchangeRateServ->getBuyingRateByExchangeRateId($res->getOutExchangeRateId());


                            $res->setCountryPartnerProfit($profits);
                            $res->setCountryPartner(TransactionType::CODE_CASH_OUT);
                            $res->setCountryPartnerStatus($res->getOutTransactionStatus());
                            $res->setCountryPartnerDisplayRate($exchangeRate);

                        }else if(in_array($res->getRemittanceConfigurationId(), $inConfigIds)) {
                            if(!in_array($res->getInTransactionStatus(), $statuses)){
                                continue;
                            }
                            // if($status){
                            //     if($res->getInTransactionStatus()!=$status){
                            //         continue;
                            //     }
                            // }

                            $profits = $transactionProfitCostServ->getRepository()->findByParam($trxProfitCost, 10, 1);
                            $exchangeRate = $exchangeRateServ->getBuyingRateByExchangeRateId($res->getInExchangeRateId());

                            $res->setCountryPartnerProfit($profits);
                            $res->setCountryPartner(TransactionType::CODE_CASH_IN);
                            $res->setCountryPartnerStatus($res->getInTransactionStatus());
                            $res->setCountryPartnerDisplayRate($exchangeRate);
                        }

                        foreach ($allCountryCurrency as $country)
                        {
                            if ($country->getCode() == $res->getInCountryCurrencyCode())
                            {
                                $res->setFromCountryCode($country->getCountryCode());
                                $res->setFromCurrencyCode($country->getCurrencyCode());
                            }
                            if ($country->getCode() == $res->getOutCountryCurrencyCode())
                            {
                                $res->setToCountryCode($country->getCountryCode());
                                $res->setToCurrencyCode($country->getCurrencyCode());
                            }
                        }
                    }
                    $res->setApprovalRequired('N/A');
                    if( empty($res->getApprovalStatus()) )
                    {
                        $res->setApprovalStatus('N/A');
                    }

                    $res->setCountryPartnerStatus($res->getStatus()->getDisplayName());
                    
                    if ($transaction_no != NULL) {
                        
                        if ($res->getCountryPartner() == TransactionType::CODE_CASH_OUT) {
                        
                            if (!stristr($res->getOutTransactionIDString(),$transaction_no)) {
                                continue;
                            }

                        }else if ($res->getCountryPartner() == TransactionType::CODE_CASH_IN) {

                            if (!stristr($res->getInTransactionIDString(),$transaction_no)) {
                                continue;
                            }
                        }
                    }

                    if ($bank_account_no != NULL) {
                        
                        if ($res->getCollectionInfo() != false) {
                            
                            if (isset(json_decode($res->getCollectionInfo())->option)) {

                                if ($bankInfo = json_decode($res->getCollectionInfo())->option ) {

                                    if (isset($bankInfo->bank_account)) {

                                        if (stristr($bankInfo->bank_account,$bank_account_no)) {
                                            $newCollection->addData($res);
                                        }
                                    }
                                }
                            }
                        }
                    }else{
                        $newCollection->addData($res);
                    }

                }
            }

            $paginatedResult = $newCollection->pagination($limit, $page);

            $results = new \stdClass();
            $results->data = $paginatedResult->getResult();
            $results->total = $paginatedResult->getTotal();

            $this->setResponseCode(MessageCode::CODE_LIST_REMITTANCE_TRANSACTION_SUCCESS);
            return $results;
        }
        $this->setResponseCode(MessageCode::CODE_LIST_REMITTANCE_TRANSACTION_FAILED);
        return false;
    }

    protected function _getCalculator()
    {
        return new RemittanceFeeCalculator();
    }

    public function request($user_profile_id, $recipient_id, $remittance_config_id,
                            array $payment_info,
                            array $collection_info,
                            $remark = null,
                            $self_service = true,
                            $is_home_collection = false)
    {
        //find sender
        if( !$sender = $this->_getUser($user_profile_id) )
            return false;

        //find recipient
        if( !$recipient = $this->_getRecipient($recipient_id) )
            return false;

        $receiving_amount = $collection_info['amount'];
        if( $receiving_amount <= 0 )
        {
            $this->setResponseCode(MessageCode::CODE_INVALID_PAYMENT_AMOUNT);
            return false;
        }

        $promo_id = NULL;
        $promo = null;
        if( isset($payment_info['user_promo_reward_id']) )
        {
            $promo_id = $payment_info['user_promo_reward_id'];
            $promoServ = PromoCodeClientFactory::build(2);
            if( !$promo = $promoServ->check($user_profile_id, $promo_id) )
            {
                $this->setResponseCode(MessageCode::CODE_GET_PROMO_FAILED);
                return false;
            }

            if( !$promo->isType(PromoTransactionType::REMITTANCE) )
            {
                $this->setResponseCode(MessageCode::CODE_GET_PROMO_FAILED);
                return false;
            }
        }

        //calculate
        if( $this->getCalcDirection() == RemittanceCalculationDirection::DIR_FROM)
            $calc_amount = $this->getSendAmount();
        else
            $calc_amount = $receiving_amount;

        if( $calculator = $this->_getCalculator()->calculate($remittance_config_id, $calc_amount, $payment_info['payment_code'], $collection_info['payment_code'], $self_service, $promo, $this->getCalcDirection()) )
        {
            $remittance = $calculator->generateRemittanceRecord();
            $remittance->setSenderUserProfileId($user_profile_id);
            $remittance->setRecipient($recipient);
            $remittance->setSender($sender);
            $remittance->setIsFaceToFaceTrans($calculator->getRemittanceConfig()->getRequireFaceToFaceTrans());
            $remittance->setIsFaceToFaceRecipient($calculator->getRemittanceConfig()->getRequireFaceToFaceRecipient());
            $remittance->setIsHomeCollection((int)$is_home_collection);
            $remittance->setIsNFF($this->isNFF); //this will be determined again when complete
            $remittance->setSender($sender);

            $remittance->setCreatedBy($this->getUpdatedBy());            
            $remittance->getStatus()->setCode(RemittanceStatus::PENDING_PAYMENT);

            //user compulsory check
            $userChecker = UserCompulsoryRemittanceCheckerFactory::build($calculator->getRemittanceConfig())->checkRequestEligible($user_profile_id, $calculator->getRemittanceConfig());
            if( $userChecker->fails() )
            {
                $this->setResponseCode($userChecker->getResponseCode());
                if( !$this->getResponseCode() )  //just to make sure
                    $this->setResponseCode(MessageCode::CODE_USER_IS_NOT_QUALIFIED);
                return false;
            }
                       
            //recipient compulsory check
            $recipientChecker = $this->_getRecipientChecker($calculator->getRemittanceConfig());            
            $recipientChecker->checkRequestEligible($user_profile_id, $recipient_id, $calculator->getRemittanceConfig());
            if( $recipientChecker->fails() )
            {
                $this->setResponseCode($recipientChecker->getResponseCode());
                if( !$this->getResponseCode() )  //just to make sure
                    $this->setResponseCode(MessageCode::CODE_RECIPIENT_IS_NOT_QUALIFIED);
                return false;
            }
            
            if (!$remittanceID = RemittanceIDGenerator::generate($calculator->getRemittanceConfig()))
            {
                $this->setResponseMessage(MessageCode::CODE_REMCO_NOT_FOUND);
                return false;
            }

            $remittance->setRemittanceID($remittanceID);

            //prelim checker will do in a decoupled process, to avoid long waiting time
            $remittance->setApprovalRequired(2); //has not determined
            /*
            if (!PrelimCheckerFactory::build($calculator->getRemittanceConfig())->check($user_profile_id, $payment_info['country_currency_code'], $payment_info['amount']))
            {
                $remittance->setApprovalRequired(1);
                $remittance->setApprovalStatus(RemittanceApprovalStatus::PENDING);
            }
            else
                $remittance->setApprovalRequired(0);
            */
            $collection_info['calc_dir'] =  $this->getCalcDirection();
            $collection_info['payment_method'] =  $payment_info['payment_code'];
            $this->getRepository()->startDBTransaction();

            if($this->_generateTransaction($remittance, $calculator, $remark)) {

                //save collection info
                $remittance->setCollectionInfo(json_encode($collection_info));
                if ($this->_saveRemittance($remittance))
                {
                    if( $promo_id != NULL ) {
                        $promoServ = PromoCodeClientFactory::build(2);
                        if (!$promo = $promoServ->reservePromoCode($promo_id)) {
                            $this->getRepository()->rollbackDBTransaction();
                            $this->setResponseCode(MessageCode::CODE_GET_PROMO_FAILED);
                            return false;
                        }
                    }

                    $ori_remittance = clone($remittance);

                    $commit_trans = false;
                    if($this->_isRequestForPayment($calculator)) {

                        //request payment
                        if ($payment_info['amount'] > 0) {
                            if ($request_id = $this->_requestPayment($remittance->getInTransaction(), $payment_info, false, $user_profile_id, $recipient_id)) {
                                $remittance->setPayMentRequestId($request_id);
                            } else {
                                if( $promo_id )
                                    $this->_cancelReservedPromoCode($promo_id);
                                $this->getRepository()->rollbackDBTransaction();
                                return false;
                            }
                        }

                        //check if collection payment mode is requestable
                        if ($collectionChecker = CollectionChecker::check($recipient, $collection_info['payment_code'])) {
                            if ($remittance->getOutTransaction()->getItems()->validatePaymentAmount(-1 * $receiving_amount)) {
                                if ($collectionChecker->isRequestable()) {
                                    //request collection
                                    $collection_info['amount'] = -1 * $collection_info['amount'];
                                    //to use system payment due to recipient user will not be the sender.
                                    if ($request_id = $this->_requestPayment($remittance->getOutTransaction(), $collection_info, true, $user_profile_id, $recipient_id)) {
                                        $remittance->setCollectionRequestId($request_id);
                                    } else {
                                        if ($remittance->getPayMentRequestId())
                                            $this->_cancelPayment($remittance->getInTransaction(), $remittance->getPayMentRequestId());
                                        if( $promo_id )
                                            $this->_cancelReservedPromoCode($promo_id);
                                        $this->getRepository()->rollbackDBTransaction();
                                        return false;
                                    }
                                }

                                if ($this->_updateRemittance($remittance, $ori_remittance)) {
                                    $commit_trans = TRUE;
                                }
                            } else
                                $this->setResponseCode(MessageCode::CODE_INVALID_PAYMENT_AMOUNT);
                        }
                    }
                    else {
                        $commit_trans = TRUE;
                    }

                    if($commit_trans) {

                        $this->getRepository()->completeDBTransaction();

                        //publish remittance initiated
                        RemittanceEventProducer::publishStatusChanged($remittance->getId(), RemittanceStatus::INITIATE);

                        $this->setResponseCode(MessageCode::CODE_REMITTANCE_REQUEST_SUCCESS);
                        return array(
                            'remittance' => $remittance->getSelectedField(array('id', 'remittanceID', 'display_rate', 'from_amount', 'to_amount', 'status')),
                            'transaction' => $remittance->getInTransaction()->getCombinedTransactionArray(array('id', 'transactionID', 'created_at', 'status', 'country_currency_code', 'total_amount', 'remark',
                                'items' => array('id', 'name', 'description', 'quantity', 'unit_price', 'net_amount')
                            ))
                        );
                    }

                    if ($remittance->getPayMentRequestId())
                        $this->_cancelPayment($remittance->getInTransaction(), $remittance->getPayMentRequestId());

                    if( $promo_id != NULL )
                    {
                        $this->_cancelReservedPromoCode($promo_id);
                    }
                }
            }
        }
        else
            $this->setResponseCode(MessageCode::CODE_FAILED_TO_COMPUTE_RATES);

        if( !$this->getResponseCode() )
            $this->setResponseCode(MessageCode::CODE_FAILED_TO_REQUEST);
        return false;
    }
    
    protected function _getRecipientChecker(RemittanceConfig $remittanceConfig)
    {
        $checker = RecipientCompulsoryRemittanceCheckerFactory::build($remittanceConfig);
        $checker->setUpdatedBy($this->getUpdatedBy());
        $checker->setIpAddress($this->getIpAddress());
        return $checker;
    }

    protected function _cancelReservedPromoCode($promo_id)
    {
        $promoServ = PromoCodeClientFactory::build(2);
        $promo = $promoServ->cancelReservedPromoCode($promo_id);
    }

    public function complete($user_profile_id, $remittance_id, array $paymentInfo = NULL )
    {
        //retrieve remittance request
        if( $remittance = $this->retrieveRemittance($remittance_id) )
        {
            if( $remittance instanceof RemittanceRecord )
            {
                if( $remittance->getSenderUserProfileId() == $user_profile_id )
                {
                    //get remittance config
                    $remittanceConfigServ = RemittanceConfigServiceFactory::build();
                    if( !$config = $remittanceConfigServ->getRemittanceConfigById($remittance->getRemittanceConfigurationId()) )
                    {
                        $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_CONFIG_FAILED);
                        return false;
                    }

                    //user compulsory check
                    $userChecker = UserCompulsoryRemittanceCheckerFactory::build($config)->check($user_profile_id, $config);
                    if( $userChecker->fails() )
                    {
                        $this->setResponseCode($userChecker->getResponseCode());
                        if( !$this->getResponseCode() )  //just to make sure
                            $this->setResponseCode(MessageCode::CODE_USER_IS_NOT_QUALIFIED);
                        return false;
                    }
                    
                    //recipient compulsory check
                    $recipientChecker = $this->_getRecipientChecker($config);
                    $recipientChecker->check($user_profile_id, $remittance->getRecipient()->getId(), $config);
                    if( $recipientChecker->fails() )
                    {
                        $this->setResponseCode($recipientChecker->getResponseCode());
                        if( !$this->getResponseCode() )  //just to make sure
                            $this->setResponseCode(MessageCode::CODE_RECIPIENT_IS_NOT_QUALIFIED);
                        return false;
                    }

                    $ori_remittance = clone($remittance);

                    if( $this->_completeAction($remittance) ) {

                        //request payment if required
                        if ($remittance->getPayMentRequestId() == NULL) {
                            if (!$paymentInfo) {
                                $this->setResponseCode(MessageCode::CODE_INVALID_PAYMENT_AMOUNT);
                                return false;
                            }

                            if ($remittance->getInTransaction()->getConfirmPaymentCode() !== $paymentInfo['payment_code']) {
                                $this->setResponseCode(MessageCode::CODE_INVALID_PAYMENT_AMOUNT);
                                return false;
                            }

                            if ($request_id = $this->_requestPayment($remittance->getInTransaction(), $paymentInfo, false, $remittance->getSenderUserProfileId(), $remittance->getRecipient()->getId())) {
                                $remittance->setPayMentRequestId($request_id);
                            } else
                                return false;
                        }

                        $this->getRepository()->startDBTransaction();

                        //set expiry for out transaction - only for local transfer
                        if( !$remittance->isInternational() )
                        {
                            if(!$this->_updateTransactionExpiry($remittance->getOutTransaction())) {
                                $this->getRepository()->rollbackDBTransaction();
                                return false;
                            }
                        }

                        //check if approval required, if yes, cashout remain initiated
                        //otherwise, proceed cashout
                        $collection_info = NULL;
                        if ($remittance->getApprovalRequired() == 1 OR $remittance->getApprovalRequired() == 2)
                            $remittance->getStatus()->setCode(RemittanceStatus::PROCESSING);
                        else if ($remittance->getCollectionRequestId() != NULL) {
                            if (!$cashout_payment = $this->_cashoutRemittance($remittance, true)) {
                                $this->getRepository()->rollbackDBTransaction();
                                return false;
                            }

                            $collection_info = $cashout_payment->getSelectedField(array('id', 'status', 'payment_code', 'amount', 'additional_info'));
                        } else {// pending collection transaction
                            if (!$this->_pendingTransaction($remittance->getOutTransaction())) {
                                $this->getRepository()->rollbackDBTransaction();
                                return false;
                            }

                            $remittance->getStatus()->setCode(RemittanceStatus::DELIVERING);
                        }
                        
                        $snapShotServ = new UserDataSnapshotService($this->getIpAddress()->getString(), $this->getUpdatedBy());
                        $snapShotServ->buildSnapshot($remittance);

                        if ($this->_completeTransaction($remittance->getInTransaction())) {
                            //assume this will not failed?
                            if ($cashin_payment = $this->_completePayment($remittance->getInTransaction(), $remittance->getPayMentRequestId())) {

                                $remittance->setPaidAt(IappsDateTime::now());
                                $remittance->setIsNFF($this->isNFF);

                                //update remittance
                                if ($this->_updateRemittance($remittance, $ori_remittance)) {
                                    //update recipient last sent
                                    $recipientService = RecipientServiceFactory::build();
                                    $recipient = (new Recipient())->setId($remittance->getRecipient()->getId())
                                        ->setLastSentAt($remittance->getPaidAt());
                                    $recipientService->updateRecipient($recipient);

                                    $this->getRepository()->completeDBTransaction();
                                    RemittanceEventProducer::publishStatusChanged($remittance->getId(), $remittance->getStatus()->getCode());

                                    $this->setResponseCode(MessageCode::CODE_PURCHASE_REMITTANCE_SUCCESS);
                                    return array(
                                        'remittance' => $remittance->getSelectedField(array('id', 'remittanceID', 'display_rate', 'from_amount', 'to_amount', 'status')),
                                        'transaction' => $remittance->getInTransaction()->getCombinedTransactionArray(array('id', 'transactionID', 'created_at', 'status', 'country_currency_code', 'total_amount', 'remark',
                                            'items' => array('id', 'name', 'description', 'quantity', 'unit_price', 'net_amount')
                                        )),
                                        'payment' => $cashin_payment->getSelectedField(array('id', 'status', 'payment_code', 'amount', 'additional_info')),
                                        'collection' => $collection_info
                                    );
                                }
                            }
                        }
                    }
                }
            }
        }

        if( !$this->getResponseCode() )
            $this->setResponseCode(MessageCode::CODE_PURCHASE_REMITTANCE_FAIL);
        return false;
    }

    public function cancel($user_profile_id, $remittance_id)
    {
        //retrieve remittance request
        if( $remittance = $this->retrieveRemittance($remittance_id) )
        {
            if ($remittance instanceof RemittanceRecord)
            {
                if( $remittance->getSenderUserProfileId() != $user_profile_id )
                {
                    $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_TRANSACTION_NOT_FOUND);
                    return false;
                }

                //only pending payment can be cancelled
                if( $remittance->getStatus()->getCode() == RemittanceStatus::PENDING_PAYMENT )
                {
                    $ori_remittance = clone($remittance);

                    //cancel payment if requested
                    if( $remittance->getPayMentRequestId() )
                        $this->_cancelPayment($remittance->getInTransaction(), $remittance->getPayMentRequestId());

                    if( $remittance->getCollectionRequestId() )
                        $this->_cancelPayment($remittance->getOutTransaction(), $remittance->getCollectionRequestId(), true);

                    $remittance->getStatus()->setCode(RemittanceStatus::CANCELLED);

                    if( $this->_cancelTransaction($remittance->getInTransaction()) AND
                        $this->_cancelTransaction($remittance->getOutTransaction()) )
                    {
                        //update remittance
                        if( $this->_updateRemittance($remittance, $ori_remittance) )
                        {
                            foreach($remittance->getInTransaction()->getItems() AS $item)
                            {
                                if( $item->getItemType()->getCode() == ItemType::DISCOUNT )
                                {
                                    if($remittance->getInTransaction()->getTransactionType()->getCode() != TransactionType::CODE_REFUND) {
                                        $promoServ = PromoCodeClientFactory::build(2);
                                        if (!$promoServ->cancelReservedPromoCode($item->getItemId())) {
                                            Logger::debug('Failed to cancel reserved promo code' . $remittance->getInTransaction()->getId());
                                        }
                                    }
                                }
                            }

                            //publish remittance cancelled queue if remittance is home collection
                            if((bool)$remittance->getIsHomeCollection()) {
                                RemittanceTransactionEventBroadcastWithKeyProducer::publishTransactionStatusChanged($remittance->getInTransaction()->getTransactionID(), $remittance->getInTransaction()->getStatus()->getCode());
                            }

                            $this->setResponseCode(MessageCode::CODE_REMITTANCE_CANCELLED_SUCCESS);
                            return true;
                        }
                    }
                }
            }
        }

        if( !$this->getResponseCode() )
            $this->setResponseCode(MessageCode::CODE_REMITTANCE_CANCELLED_FAILED);
        return false;
    }

    public function completeCashOut($remittance_id )
    {
        //retrieve remittance request
        if( $remittance = $this->retrieveRemittance($remittance_id) )
        {
            if( $remittance instanceof RemittanceRecord ) {

                //check remittance status is delivering
                if ($remittance->getStatus()->getCode() == RemittanceStatus::DELIVERING) {

                    $ori_remittance = clone($remittance);
                    $this->getRepository()->startDBTransaction();

                    if ($remittance->getCollectionInfo() != NULL && $remittance->getCollectionRequestId() == NULL) {
                        $collection_info = json_decode($remittance->getCollectionInfo(), true);

                        if ($collection_info['amount'] <= 0)
                            return false;
                        else {
                            //request cash out
                            //PartnerRemittancePayment
                            $collection_info['amount'] = -1 * $collection_info['amount'];
                            if ($request_id = $this->_requestPayment($remittance->getOutTransaction(), $collection_info)) {
                                $remittance->setCollectionRequestId($request_id);

                                //complete cash out
                                if (!$cashout_payment = $this->_cashoutRemittance($remittance)) {
                                    $this->getRepository()->rollbackDBTransaction();
                                    return false;
                                }

                                $collection_info = $cashout_payment->getSelectedField(array('id', 'status', 'payment_code', 'amount', 'additional_info'));

                                //update remittance
                                if ($this->_updateRemittance($remittance, $ori_remittance)) {
                                    $this->getRepository()->completeDBTransaction();
                                    RemittanceEventProducer::publishStatusChanged($remittance->getId(), $remittance->getStatus()->getCode());

                                    $this->setResponseCode(MessageCode::CODE_COMPLETE_CASHOUT_REMITTANCE_SUCCESS);
                                    return array(
                                        'remittance' => $remittance->getSelectedField(array('id', 'remittanceID', 'display_rate', 'from_amount', 'to_amount', 'status')),
                                        'transaction' => $remittance->getInTransaction()->getCombinedTransactionArray(array('id', 'transactionID', 'created_at', 'status', 'country_currency_code', 'total_amount', 'remark',
                                            'items' => array('id', 'name', 'description', 'quantity', 'unit_price', 'net_amount')
                                        )),
                                        //'payment' => $cashin_payment->getSelectedField(array('id', 'status', 'payment_code', 'amount', 'additional_info')),
                                        'collection' => $collection_info
                                    );
                                }
                            }
                        }
                    }
                }
            }
        }

        if( !$this->getResponseCode() )
            $this->setResponseCode(MessageCode::CODE_COMPLETE_CASHOUT_REMITTANCE_FAIL);
        return false;
    }

    public function deliver($remittance_id)
    {
        //retrieve remittance request
        if( $remittance = $this->retrieveRemittance($remittance_id) )
        {
            if ($remittance instanceof RemittanceRecord AND
                $remittance->getStatus()->getCode() == RemittanceStatus::DELIVERING)
            {
                $ori_remittance = clone($remittance);

                $this->getRepository()->startDBTransaction();
                if( $remittance->getCollectionRequestId() != NULL )
                {
                    if( !$cashout_payment = $this->_cashoutRemittance($remittance, true) )
                    {
                        $this->getRepository()->rollbackDBTransaction();
                        $this->_createPaymentFailRefundRequest($remittance, $ori_remittance, $this->getResponseMessage());

                        //reset collection request id
                        //$remittance->setCollectionRequestId(NULL);
                        //$this->getRepository()->updateRequestCollectionId($remittance);
                        Logger::debug("Remittance Deliver: Failed to collect: " . $remittance->getId());
                        return false;
                    }
                }
                else
                {
                    //if self service and no collection request, request and cashout remittance
                    if( $collectionChecker = CollectionChecker::check($remittance->getRecipient(), $remittance->getOutTransaction()->getConfirmPaymentCode()) )
                    {
                        if( $collectionChecker->isRequestable() )
                        {
                            //request collection
                            $collection_info = json_decode($remittance->getCollectionInfo(), true);
                            $collection_info['amount'] = -1 * $collection_info['amount'];
                            if( !$request_id = $this->_requestPayment($remittance->getOutTransaction(), $collection_info, true, $remittance->getSenderUserProfileId(), $remittance->getRecipient()->getId()))
                            {
                                $this->getRepository()->rollbackDBTransaction();
                                $this->_createPaymentFailRefundRequest($remittance, $ori_remittance, $this->getResponseMessage());
                                return false;
                            }
                            $remittance->setCollectionRequestId($request_id);
                            if( !$cashout_payment = $this->_cashoutRemittance($remittance, true) )
                            {
                                $this->getRepository()->rollbackDBTransaction();
                                $this->_createPaymentFailRefundRequest($remittance, $ori_remittance, $this->getResponseMessage());
                                return false;
                            }
                        }
                        else
                        {//nothing to process
                            $this->getRepository()->rollbackDBTransaction();
                            $this->setResponseCode(MessageCode::CODE_EDIT_REMITTANCE_TRANSACTION_SUCCESS);
                            return true;
                        }
                    }
                    else
                    {
                        $this->getRepository()->rollbackDBTransaction();
                        $this->setResponseCode(MessageCode::CODE_CORPORATE_PAYMENT_MODE_NOT_FOUND);
                        return false;
                    }
                }

                Logger::debug("Remittance Deliver: before update: " . $remittance->getId());
                if( $this->_updateRemittance($remittance, $ori_remittance) )
                {
                    $this->getRepository()->completeDBTransaction();

                    Logger::debug("Remittance Deliver: updated: " . $remittance->getId());
                    if($remittance->getStatus()->getCode()!= RemittanceStatus::DELIVERING) {
                        RemittanceEventProducer::publishStatusChanged($remittance->getId(), $remittance->getStatus()->getCode());
                    }
                    $this->setResponseCode(MessageCode::CODE_EDIT_REMITTANCE_TRANSACTION_SUCCESS);
                    return true;
                }
            }
        }

        $this->setResponseCode(MessageCode::CODE_REMITTANCE_RECORD_NOT_FOUND);
        return false;
    }

    public function approve($remittance_id, array $attributes, $remark)
    {
        //retrieve remittance request
        if( $remittance = $this->retrieveRemittance($remittance_id) )
        {
            if ($remittance instanceof RemittanceRecord)
            {
                if( $remittance->getStatus()->getCode() == RemittanceStatus::PROCESSING AND
                    $remittance->isApprovalRequired() )
                {
                    $ori_remittance = clone($remittance);

                    $remittance->setApprovalStatus(RemittanceApprovalStatus::APPROVED);
                    $remittance->setApproveRejectRemark($remark);
                    $remittance->setApprovedRejectedBy($this->getUpdatedBy());
                    $remittance->setApprovedRejectedAt(IappsDateTime::now());

                    $this->getRepository()->startDBTransaction();

                    //set remittance attribute
                    $attr_serv = RemittanceAttributeServiceFactory::build();
                    $attr_serv->setUpdatedBy($this->getUpdatedBy());
                    $attr_serv->setIpAddress($this->getIpAddress());
                    foreach($attributes as $info)
                    {
                        $arrtibuteV = new AttributeValue();
                        foreach ($info as $key => $value) {

                            if ($key == 'id') {
                                $arrtibuteV->setId($value);
                            }else{
                                $arrtibuteV->getAttribute()->setCode($key);
                                $arrtibuteV->setValue($value);
                            }
                        }
                        //update attributes
                        if( !$attr_serv->setRemittanceAttribute($remittance->getId(), $arrtibuteV) )
                        {
                            $this->getRepository()->rollbackDBTransaction();
                            $this->setResponseCode(MessageCode::CODE_EDIT_REMITTANCE_TRANSACTION_FAILED);
                            return false;
                        }
                    }

                    //decoupled delivery process to an event
                    if( !$this->_pendingTransaction($remittance->getOutTransaction()) )
                    {
                        $this->getRepository()->rollbackDBTransaction();
                        return false;
                    }

                    $remittance->getStatus()->setCode(RemittanceStatus::DELIVERING);

                    if( $this->_updateRemittance($remittance, $ori_remittance) )
                    {
                        $this->getRepository()->completeDBTransaction();
                        RemittanceEventProducer::publishStatusChanged($remittance->getId(), $remittance->getStatus()->getCode());

                        $this->setResponseCode(MessageCode::CODE_REMITTANCE_APPROVAL_SUCCESS);
                        return true;
                    }

                    $this->getRepository()->rollbackDBTransaction();
                }
            }
        }

        $this->setResponseCode(MessageCode::CODE_REMITTANCE_APPROVAL_FAILED);
        return false;
    }

    public function reject($remittance_id, array $attributes, $remark)
    {
        //retrieve remittance request
        if( $remittance = $this->retrieveRemittance($remittance_id) )
        {
            if ($remittance instanceof RemittanceRecord)
            {
                if( $remittance->getStatus()->getCode() == RemittanceStatus::PROCESSING AND
                    $remittance->isApprovalRequired() )
                {
                    $ori_remittance = clone($remittance);

                    $remittance->setApprovalStatus(RemittanceApprovalStatus::REJECTED);
                    $remittance->setApproveRejectRemark($remark);
                    $remittance->setApprovedRejectedBy($this->getUpdatedBy());
                    $remittance->setApprovedRejectedAt(IappsDateTime::now());

                    $this->getRepository()->startDBTransaction();

                    //set remittance attribute
                    $reject_reason_value = NULL;
                    $attr_serv = RemittanceAttributeServiceFactory::build();
                    $attr_serv->setUpdatedBy($this->getUpdatedBy());
                    $attr_serv->setIpAddress($this->getIpAddress());
                    foreach($attributes as $info)
                    {
                        $arrtibuteV = new AttributeValue();
                        foreach ($info as $key => $value) {

                            if ($key == 'id') {
                                $arrtibuteV->setId($value);
                            }else{
                                $arrtibuteV->getAttribute()->setCode($key);
                                $arrtibuteV->setValue($value);
                                $reject_reason_value = $value;
                            }
                        }
                        //update attributes
                        if( !$attr_serv->setRemittanceAttribute($remittance->getId(), $arrtibuteV) )
                        {
                            $this->getRepository()->rollbackDBTransaction();
                            $this->setResponseCode(MessageCode::CODE_EDIT_REMITTANCE_TRANSACTION_FAILED);
                            return false;
                        }
                    }

                    $remittance->getStatus()->setCode(RemittanceStatus::REJECTED);

                    if( $this->_updateRemittance($remittance, $ori_remittance) ) {

                        //initiate refund request for in transaction
                        $refund_request_serv = RefundRequestServiceFactory::build();
                        $refund_request_serv->setUpdatedBy($this->getUpdatedBy());

                        $refund_reason = NULL;
                        if( $refundReason = $refund_request_serv->getRefundReason('Rejected Transaction') )
                            $refund_reason = $refundReason;
                        else
                            $refund_reason = array("id"=>"9a780472-6450-11e6-82aa-06c98ccb64a5","value"=>"Rejected Transaction");

                        $refund_remarks = "Reject Reason: ". $reject_reason_value.". Remarks: " .$remark;

                        $approval_required = $this->_getApprovalRequiredForRefund($remittance);
                        if(!$refund_request_serv->initiateFullRefundRequest($remittance->getInTransaction()->getTransactionID(), $refund_reason, $refund_remarks, $approval_required)) {

                            $this->getRepository()->rollbackDBTransaction();
                            $this->setResponseCode($refund_request_serv->getResponseCode());
                            return false;

                        }

                        $this->getRepository()->completeDBTransaction();
                        RemittanceEventProducer::publishStatusChanged($remittance->getId(), $remittance->getStatus()->getCode());
                        $this->setResponseCode(MessageCode::CODE_REMITTANCE_REJECTION_SUCCESS);
                        return true;
                    }

                    $this->getRepository()->rollbackDBTransaction();

                }
            }
        }

        $this->setResponseCode(MessageCode::CODE_REMITTANCE_REJECTION_FAILED);
        return false;
    }

    public function expire(RemittanceRecord $remittance)
    {
        $ori = clone($remittance);
        $remittance->getStatus()->setCode(RemittanceStatus::EXPIRED);
        if( $this->_updateRemittance($remittance, $ori) ) {
            return $remittance;
        }

        return false;
    }

    public function prelimCheck($remittance_id)
    {
        if( $remittance = $this->retrieveRemittance($remittance_id) )
        {
            if( $remittance instanceof RemittanceRecord )
            {
                $oriRemittance = clone($remittance);

                if( $remittance->getStatus()->getCode() == RemittanceStatus::PROCESSING AND
                    $remittance->getApprovalRequired() == 2)
                {// call prelim check to determine if approval required

                    //get remittance config -> email list
                    $remittanceConfigServ = RemittanceConfigServiceFactory::build();
                    $config = $remittanceConfigServ->getRemittanceConfigById($remittance->getRemittanceConfigurationId());
                    if( $config instanceof RemittanceConfig )
                    {
                        $this->getRepository()->startDBTransaction();
                        if( PrelimCheckerFactory::build($config)->check($remittance->getId()) )
                        {//prelim pass
                            $needApproval = false; //no need by default
                            
                            //for NFF, remco setting will overwrite it
                            if( $remittance->getIsNFF() == 1 )
                            {//based on remco setting
                                if( $config->getRemittanceCompany()->getRequiredManualApprovalNFF() )
                                    $needApproval = true;
                                else
                                    $needApproval = false;                            
                            }
                        }
                        else //will always need approval if prelim failed
                            $needApproval = true;
                        
                        if($needApproval)
                        {
                            $remittance->setApprovalRequired(1);
                            $remittance->setApprovalStatus(RemittanceApprovalStatus::PENDING);
                        }
                        else
                        {
                            $remittance->setApprovalRequired(0);

                            if( !$this->_pendingTransaction($remittance->getOutTransaction()) )
                            {
                                $this->getRepository()->rollbackDBTransaction();
                                return false;
                            }

                            $remittance->getStatus()->setCode(RemittanceStatus::DELIVERING);
                        }


                        //update remittance
                        if( $this->_updateRemittance($remittance, $oriRemittance) )
                        {
                            $this->getRepository()->completeDBTransaction();

                            //publish approval required changed
                            RemittanceEventProducer::publishApprovalRequiredChanged($remittance->getId());
                            if( $remittance->getStatus()->getCode() == RemittanceStatus::DELIVERING )
                                RemittanceEventProducer::publishStatusChanged($remittance->getId(), $remittance->getStatus()->getCode());
                            return true;
                        }
                    }

                    return false;
                }
            }
        }

        return false;
    }

    protected function _generateTransaction(RemittanceRecord $remittance, RemittanceFeeCalculator $calculator, $remark)
    {
        if( $cashin_transaction = $this->_generateCashInTransaction($remittance, $calculator, $remark) AND
            $cashout_transaction = $this->_generateCashOutTransaction($remittance, $calculator, $remark) )
        {
            if( $this->getChannel() )
            {
                $cashin_transaction->setChannel($this->getChannel());
                $cashout_transaction->setChannel($this->getChannel());
            }

            if( $this->_saveTransaction($cashin_transaction) AND $this->_saveTransaction($cashout_transaction) )
                return true;
        }

        return false;
    }

    public function collect($remittance_id)
    {
        //retrieve remittance request
        if ($remittance = $this->retrieveRemittance($remittance_id)) {
            if ($remittance instanceof RemittanceRecord) {

                //check remittance status is delivering
                if ($remittance->getStatus()->getCode() == RemittanceStatus::DELIVERING) {
                    $ori_remittance = clone($remittance);
                    $this->getRepository()->startDBTransaction();

                    if ($remittance->getCollectionRequestId() != NULL) {
                        $remittance->getStatus()->setCode(RemittanceStatus::COLLECTED);

                        if ($this->_updateRemittance($remittance, $ori_remittance)) {
                            $this->getRepository()->completeDBTransaction();
                            RemittanceEventProducer::publishStatusChanged($remittance->getId(), $remittance->getStatus()->getCode());

                            $this->setResponseCode(MessageCode::CODE_REMITTANCE_APPROVAL_SUCCESS);
                            return true;
                        }

                        $this->getRepository()->rollbackDBTransaction();
                    }
                }
            }
        }

        if (!$this->getResponseCode())
            $this->setResponseCode(MessageCode::CODE_COMPLETE_CASHOUT_REMITTANCE_FAIL);

        return false;
    }

    public function fail($remittance_id, $response_message)
    {
        //retrieve remittance request
        if( $remittance = $this->retrieveRemittance($remittance_id) )
        {
            if( $remittance instanceof RemittanceRecord ) {

                //check remittance status is not collected
                if ($remittance->getStatus()->getCode() != RemittanceStatus::COLLECTED) {

                    $ori_remittance = clone($remittance);
                    $this->getRepository()->startDBTransaction();

                    $this->_createPaymentFailRefundRequest($remittance, $ori_remittance, $response_message);

                    $this->setResponseCode(MessageCode::CODE_REFUND_REQUEST_ATTRIBUTE_ADD_SUCCESS);
                    return true;
                }
            }
        }

        if( !$this->getResponseCode() )
            $this->setResponseCode(MessageCode::CODE_REFUND_REQUEST_ATTRIBUTE_ADD_FAILED);
        return false;
    }


    protected function _requestPayment(RemittanceTransaction $transaction, array $payment_info, $bySystem = false, $sender_id=null, $recipient_id=null)
    {
        if( $bySystem )
            $paymentInterface = new SystemRemittancePayment();
        else
            $paymentInterface = $this->paymentInterface;

        if( $transaction->getItems()->validatePaymentAmount($payment_info['amount']) )
        {
            //if( $transaction->isCashOut() AND $transaction->getConfirmPaymentCode() == 'EWA' )

            //{//if ewallet collection mode

            $paymentModeInterface = RemittancePaymentModeOptionFactory::build($transaction, $sender_id, $recipient_id, $this->getIpAddress(), $this->getUpdatedBy());
            $payment_info = $paymentModeInterface->getOption($payment_info);
            //$payment_info['option']['is_collection'] = '1';
            //}

            if( $request_id = $paymentInterface->paymentRequest($transaction, $payment_info) )
            {
                return $request_id;
            }

            $lastResponse = $paymentInterface->getLastResponse();
            if( isset($lastResponse['status_code']) )
                $this->setResponseCode($lastResponse['status_code']);
            else
                $this->setResponseCode(MessageCode::CODE_PAYMENT_REQUEST_FAILED);

            if( isset($lastResponse['message']) )
                $this->setResponseMessage($lastResponse['message']);
            return false;
        }
        else
        {//invalid payment amount
            $this->setResponseCode(MessageCode::CODE_INVALID_PAYMENT_AMOUNT);
            return false;
        }
    }

    protected function _completePayment(RemittanceTransaction $transaction, $request_id, $bySystem = false)
    {
        if( $bySystem )
            $paymentInterface = new SystemRemittancePayment();
        else
            $paymentInterface = $this->paymentInterface;

        if( $info = $paymentInterface->paymentComplete($transaction, $request_id) )
            return $info;

        $lastResponse = $paymentInterface->getLastResponse();
        if( isset($lastResponse['status_code']) )
            $this->setResponseCode($lastResponse['status_code']);
        else
            $this->setResponseCode(MessageCode::CODE_MAKE_PAYMENT_FAIL);

        if( isset($lastResponse['message']) )
            $this->setResponseMessage($lastResponse['message']);

        return false;
    }

    protected function _cancelPayment(RemittanceTransaction $transaction, $request_id, $bySystem = false)
    {
        if( $bySystem )
            $paymentInterface = new SystemRemittancePayment();
        else
            $paymentInterface = $this->paymentInterface;

        if( $info = $paymentInterface->paymentCancel($transaction, $request_id) )
            return $info;

        $lastResponse = $paymentInterface->getLastResponse();
        if( isset($lastResponse['status_code']) )
            $this->setResponseCode($lastResponse['status_code']);
        else
            $this->setResponseCode(MessageCode::CODE_MAKE_PAYMENT_FAIL);

        if( isset($lastResponse['message']) )
            $this->setResponseMessage($lastResponse['message']);

        return false;
    }

    protected function _saveTransaction(RemittanceTransaction $transaction)
    {
        $trx_serv = RemittanceTransactionServiceFactory::build();
        $trx_serv->setUpdatedBy($this->getUpdatedBy());
        $trx_serv->setIpAddress($this->getIpAddress());

        $transaction->setCreatedBy($this->getUpdatedBy());
        if( !$result = $trx_serv->saveTransaction($transaction) )
        {
            $this->setResponseCode(MessageCode::CODE_ADD_REMITTANCE_TRANSACTION_FAILED);
            return false;
        }

        return $result;
    }

    protected function _saveRemittance(RemittanceRecord $remittance)
    {
        if( !$this->_extractStatusId($remittance) )
            return false;

        if( $this->getRepository()->insert($remittance) )
        {
            $this->fireLogEvent('iafb_remittance.remittance', AuditLogAction::CREATE, $remittance->getId());
            return $remittance;
        }

        $this->setResponseCode(MessageCode::CODE_ADD_REMITTANCE_TRANSACTION_FAILED);
        return false;
    }

    protected function _saveAdditionalInformation(RemittanceRecord $remittance, array $additional_info)
    {
        $remittanceAttrServ = RemittanceAttributeServiceFactory::build();
        foreach($additional_info AS $info)
        {
            $value = new AttributeValue();
            if( isset($info['attribute_code']) )
                $value->getAttribute()->setCode($info['attribute_code']);
            else
            {
                $this->setResponseCode(MessageCode::CODE_INVALID_ADDITIONAL_INFO);
                return false;
            }

            if( isset($info['value_id']) )
                $value->setId($info['value_id']);

            if( isset($info['value']) )
                $value->setValue($info['value'] );
            else
            {
                $this->setResponseCode(MessageCode::CODE_INVALID_ADDITIONAL_INFO);
                return false;
            }

            if( !$remittanceAttrServ->setRemittanceAttribute($remittance->getId(), $value) )
            {
                $this->setResponseCode($remittanceAttrServ->getResponseCode());
                return false;
            }
        }

        return true;
    }

    protected function _extractStatusId(RemittanceRecord $remittance)
    {
        //extract status id
        $system_code_serv = SystemCodeServiceFactory::build();
        if( !$status = $system_code_serv->getByCode($remittance->getStatus()->getCode(), RemittanceStatus::getSystemGroupCode()))
        {
            $this->setResponseCode(MessageCode::CODE_ADD_REMITTANCE_TRANSACTION_FAILED);
            return false;
        }
        $remittance->setStatus($status);

        return $remittance;
    }

    protected function _generateCashInTransaction(RemittanceRecord $remittance, RemittanceFeeCalculator $calculator, $remark)
    {
        $inc_serv = IncrementIDServiceFactory::build();
        if( $TransactionID = $inc_serv->getIncrementID(IncrementIDAttribute::TRANSACTION_ID) )
            if($trx = $remittance->generateCashInTransaction($calculator, $TransactionID, $remark)) {

                if ($calculator->getRemittanceConfig()->getCashinExpiryPeriod() > 0) {
                    $trx->setExpiredDate(IappsDateTime::now()->addMinute($calculator->getRemittanceConfig()->getCashinExpiryPeriod()));
                }

                return $trx;
            }

        return false;
    }

    protected function _generateCashOutTransaction(RemittanceRecord $remittance, RemittanceFeeCalculator $calculator, $remark)
    {
        $inc_serv = IncrementIDServiceFactory::build();
        if ($TransactionID = $inc_serv->getIncrementID(IncrementIDAttribute::TRANSACTION_ID))
        {
            if($trx = $remittance->generateCashOutTransaction($calculator, $TransactionID, $remark) )
            {
                //move the set expiry period for cash out trans to be on complete
                //set expired
                /*
                if( !$remittance->isInternational() )
                {
                    $configServ = CoreConfigDataServiceFactory::build();
                    $expiryInMin = 14400; //default 10 days
                    if( $value = $configServ->getConfig(CoreConfigType::CASHOUT_EXPIRY_PERIOD) )
                        $expiryInMin = $value;

                    $trx->setExpiredDate(IappsDateTime::now()->addMinute($expiryInMin));
                }*/

                return $trx;
            }
        }


        return false;
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

    public function retrieveRemittance($remittance_id)
    {
        if( $remittance = $this->getRepository()->findById($remittance_id) )
        {
            if( $remittance instanceof RemittanceRecord )
            {
                if( $this->_retrieveRelatedRecord($remittance) )
                {
                    return $remittance;
                }
            }
        }

        $this->setResponseCode(MessageCode::CODE_REMITTANCE_RECORD_NOT_FOUND);
        return false;
    }

    protected function _retrieveRelatedRecord(RemittanceRecord $remittance)
    {
        $trxServ = RemittanceTransactionServiceFactory::build();
        if( $inTrx = $trxServ->findByIdWithItems($remittance->getInTransaction()->getId()) AND
            $outTrx = $trxServ->findByIdWithItems($remittance->getOutTransaction()->getId()) )
        {
            $remittance->setInTransaction($inTrx);
            $remittance->setOutTransaction($outTrx);

            $remittance->setInCountryCurrencyCode($inTrx->getCountryCurrencyCode());
            $remittance->setOutCountryCurrencyCode($outTrx->getCountryCurrencyCode());
            
            //find recipient
            if( !$recipient = $this->_getRecipient($remittance->getRecipient()->getId()) )
                return false;

            if( !$sender = $this->_getUser($remittance->getSenderUserProfileId()) )
                return false;

            $remittance->setRecipient($recipient);
            $remittance->setSender($sender);

            return $remittance;
        }

        return false;
    }

    protected function _cashoutRemittance(RemittanceRecord $remittance, $bySystem = false)
    {
        if( $this->_completeTransaction($remittance->getOutTransaction()) )
        {
            //complete payment
            if( $info = $this->_completePayment($remittance->getOutTransaction(), $remittance->getCollectionRequestId(), $bySystem) )
            {
                $paymentModeInterface = RemittancePaymentModeOptionFactory::build($remittance->getOutTransaction(), $remittance->getSenderUserProfileId(), $remittance->getRecipientUserProfileId(), $this->getIpAddress(), $this->getUpdatedBy());
                $paymentModeInterface->saveResponse();

                if($info->getId()!=null){
                    $remittance->setCollectedAt(IappsDateTime::now());
                    $remittance->getStatus()->setCode(RemittanceStatus::COLLECTED);

                    //bind remittance status changed to this event, no need to publish here
                    //RemittanceEventProducer::publishRemittanceCompleted($remittance->getId());
                }
                return $info;
            }
        }

        return false;
    }

    protected function _isRequestForPayment(RemittanceFeeCalculator $calculator)
    {
        if((bool)$calculator->getRemittanceConfig()->getRemittanceCompany()->getRequiredFaceToFaceTrans()) {
            return false;
        }

        return true;
    }

    protected function _completeAction(RemittanceRecord $remittanceRecord)
    {
        //set location coordinate (for user app is optional)
        $request_header = RequestHeader::get();
        $location = array_key_exists(ResponseHeader::FIELD_X_LOCATION, $request_header) ? $request_header[ResponseHeader::FIELD_X_LOCATION] : NULL;
        if($location != NULL) {
            $location_arr = explode( ',', $location );
            if(count($location_arr) == 2) {
                $remittanceRecord->setLat($location_arr[0]);
                $remittanceRecord->setLon($location_arr[1]);
            }
        }

        if((bool)$remittanceRecord->getIsFaceToFaceTrans()) {
            return false;

        } else if ((bool)$remittanceRecord->getIsFaceToFaceRecipient()) {

            $remConfigServ = RemittanceConfigServiceFactory::build();
            if ($remConfig = $remConfigServ->getRemittanceConfigById($remittanceRecord->getRemittanceConfigurationId())) {

                if ($remConfig instanceof RemittanceConfig) {
                    $rcServ = RemittanceCompanyServiceFactory::build();
                    if ($remCo = $rcServ->getByServiceProviderId($remConfig->getCashInCorporateService()->getServiceProviderId())) {

                        $remCoRecipientServ = RemittanceCompanyRecipientServiceFactory::build();
                        if ($remCoRecipient = $remCoRecipientServ->getByCompanyAndRecipient($remCo, $remittanceRecord->getRecipient()->getId())) {

                            if ($remCoRecipient instanceof RemittanceCompanyRecipient) {
                                $remCoRecipient->setRecipient($remittanceRecord->getRecipient());
                                if ($remCoRecipient->isFaceToFaceVerified()) {
                                    return true;
                                }
                            }

                        }
                    }
                }
            }

            return false;
        }

        return true;
    }

    protected function _completeTransaction(RemittanceTransaction $transaction)
    {
        $trx_serv = RemittanceTransactionServiceFactory::build();
        $trx_serv->setUpdatedBy($this->getUpdatedBy());
        $trx_serv->setIpAddress($this->getIpAddress());

        if( !$result = $trx_serv->completeTransaction($transaction) )
        {
            $this->setResponseCode(MessageCode::CODE_ADD_REMITTANCE_TRANSACTION_FAILED);
            return false;
        }

        return $result;
    }

    protected function _pendingTransaction(RemittanceTransaction $transaction)
    {
        $trx_serv = RemittanceTransactionServiceFactory::build();
        $trx_serv->setUpdatedBy($this->getUpdatedBy());
        $trx_serv->setIpAddress($this->getIpAddress());

        if( !$result = $trx_serv->confirmTransaction($transaction) )
        {
            $this->setResponseCode(MessageCode::CODE_ADD_REMITTANCE_TRANSACTION_FAILED);
            return false;
        }

        return $result;
    }

    protected function _updateTransactionExpiry(RemittanceTransaction $transaction, $expiry_period = 0)
    {
        if($expiry_period == 0) {
            $configServ = CoreConfigDataServiceFactory::build();
            $expiry_period = $configServ->getConfig(CoreConfigType::CASHOUT_EXPIRY_PERIOD);
        }

        if($expiry_period <= 0) {
            return true;
        }

        $oriTrans = clone($transaction);

        $trx_serv = RemittanceTransactionServiceFactory::build();
        $trx_serv->setUpdatedBy($this->getUpdatedBy());
        $trx_serv->setIpAddress($this->getIpAddress());
        $transaction->setExpiredDate(IappsDateTime::now()->addMinute($expiry_period));
        if( !$result = $trx_serv->update($transaction, $oriTrans) )
        {
            $this->setResponseCode(MessageCode::CODE_EDIT_REMITTANCE_TRANSACTION_FAILED);
            return false;
        }

        return $result;
    }

    protected function _cancelTransaction(RemittanceTransaction $transaction)
    {
        $trx_serv = RemittanceTransactionServiceFactory::build();
        $trx_serv->setUpdatedBy($this->getUpdatedBy());
        $trx_serv->setIpAddress($this->getIpAddress());

        if( !$result = $trx_serv->cancelTransaction($transaction) )
        {
            $this->setResponseCode(MessageCode::CODE_TRANSACTION_CANCELLED_FAILED);
            return false;
        }

        return $result;
    }

    protected function _updateRemittance(RemittanceRecord $remittance, RemittanceRecord $ori_record)
    {
        if( !$this->_extractStatusId($remittance) )
            return false;

        $remittance->setUpdatedBy($this->getUpdatedBy());
        if( $this->getRepository()->update($remittance) )
        {
            $this->fireLogEvent('iafb_remittance.remittance', AuditLogAction::UPDATE, $remittance->getId(), $ori_record);
            return $remittance;
        }

        $this->setResponseCode(MessageCode::CODE_ADD_REMITTANCE_TRANSACTION_FAILED);
        return false;
    }

    protected function _createPaymentFailRefundRequest(RemittanceRecord $remittance, RemittanceRecord $ori_remittance, $remark = NULL){
        $remittance->getStatus()->setCode(RemittanceStatus::FAILED);
        if( $this->_updateRemittance($remittance, $ori_remittance) ) {
            //initiate refund request for in transaction
            $refund_request_serv = RefundRequestServiceFactory::build();
            $refund_request_serv->setUpdatedBy($this->getUpdatedBy());
            $refund_reason = NULL;
            if( $refundReason = $refund_request_serv->getRefundReason('Failed Transaction') )
                $refund_reason = $refundReason;
            else
                $refund_reason = array("id"=>"f5bd3548-8c41-11e6-a872-a0620e1e6bc1","value"=>"Failed Transaction");

            if( $remark == NULL )
                $remark = $remittance->getOutTransactionIDString() . ' ' . $refund_reason['value'];
            $refund_remarks = $remark;

            $approval_required = $this->_getApprovalRequiredForRefund($remittance);
            if(!$refund_request_serv->initiateFullRefundRequest($remittance->getInTransaction()->getTransactionID(), $refund_reason, $refund_remarks, $approval_required)) {
                $this->getRepository()->rollbackDBTransaction();
            }
            $this->getRepository()->completeDBTransaction();
            RemittanceEventProducer::publishStatusChanged($remittance->getId(), $remittance->getStatus()->getCode());
        }
    }

    protected function _getApprovalRequiredForRefund(RemittanceRecord $remittance){
        $approval_required = true;
        if(!$remittance->isInternational()) {
            if ($remittance->getInTransaction()->getConfirmPaymentCode() == 'EWA') {
                $approval_required = false;
            }
        }
        return $approval_required;
    }

    public function getRemittanceByParam(RemittanceRecord $record, $limit = 100, $page = 1)
    {
        if ($collection = $this->getRepository()->findByParam($record, $limit, $page)) {
            $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_TRANSACTION_SUCCESS);
            return $collection;
        }

        $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_TRANSACTION_FAILED);
        return false;
    }

    public function getByTransactionId($transaction_id, $isCashin = true)
    {
        $filter = new RemittanceRecord();
        if( $isCashin )
            $filter->getInTransaction()->setId($transaction_id);
        else
            $filter->getOutTransaction()->setId($transaction_id);

        if ($info = $this->getRepository()->findByParam($filter, 1, 1)) {

            $remittance = $info->result->current();
            if( $remittance instanceof RemittanceRecord )
            {
                if( $this->_retrieveRelatedRecord($remittance) )
                {
                    return $remittance;
                }
            }
        }

        $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_TRANSACTION_FAILED);
        return false;
    }

    protected function _filterByServiceProvider(RemittanceConfigCollection $remConfigColl){
        return $remConfigColl;
    }

    public function getRemittanceByCreatorAndRecipientCache(RemittanceRecord $record, $limit = 100, $page = 1, $for_international = false)
    {
        //cache listing by 5 seconds
        $cacheKey = CacheKey::USER_REMITTANCE_LIST_USER_PROFILE_ID_INTERNATIONAL_LIMIT_PAGE . $record->getSender()->getId() . $for_international .$limit . $page;
        if( $result = $this->getElasticCache($cacheKey) )
        {
            $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_TRANSACTION_SUCCESS);
            return $result;
        }            
        
        $result = $this->getRemittanceByCreatorAndRecipient($record, $limit, $page, $for_international);
        if( $result )
            $this->setElasticCache($cacheKey, $result, 5);  //only for 5 seconds, no need to remove if changes made in between
        
        return $result;
    }
    
    public function getRemittanceByCreatorAndRecipient(RemittanceRecord $record, $limit = 100, $page = 1, $for_international = false)
    {
        $recipient_serv = RecipientServiceFactory::build();
        $recipient_arr = NULL;        
        if($recipientColl = $recipient_serv->getByRecipientUserProfileId($record->getRecipient()->getRecipientUserProfileId()))
        {
            foreach ($recipientColl->result as $recipientEach) {
                $recipient_arr[] = $recipientEach->getId();
            }
        }

        $remit_config_serv = RemittanceConfigServiceFactory::build();
        if( $info = $remit_config_serv->getAllRemittanceConfig(MAX_VALUE, 1) )
            $remittanceConfigColl = $info->result;

        $channelFilter = array();
        if( $remittanceConfigColl )
        {
            if(!$remittanceConfigColl = $this->_filterByServiceProvider($remittanceConfigColl))
            {
                $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_TRANSACTION_FAILED);
                return false;
            }

            $remittanceConfigColl = $remittanceConfigColl->getChannel($for_international);
            $channelFilter = $remittanceConfigColl->getIds();
        }

        if( $record->getStatus()->getCode() )
            $this->_extractStatusId($record);

        $newCollection = new RemittanceRecordCollection();
        if ($collection = $this->getRepository()->findByParam($record, $limit, $page, $recipient_arr, $channelFilter)) {

            $paymentServ = PaymentServiceFactory::build();
            $userServ = $this->getAccountService();
            $trans_serv = RemittanceTransactionServiceFactory::build();

            $allCountryCurrency = $paymentServ->getAllCountryCurrency();
            $allCountryCurrencyCollection = new IappsBaseEntityCollection();
            if ($allCountryCurrency) {
                foreach( $allCountryCurrency AS $country)
                {                    
                    $allCountryCurrencyCollection->indexField('code');
                    $allCountryCurrencyCollection->addData($country);
                }
            }
            
            $recipient_id_arr = array();
            $user_profile_id_arr = array(); //to store user profile id where user act as recipient
            $remit_config_id_arr = array();
            $transaction_id_arr = array();
            $home_collection_transaction_id_arr = array();
            $transactionID_for_payment_arr = array();
            $home_collection_transactionID_arr = array();

            foreach ($collection->result as $remittanceEach) {
                if ($remittanceEach->getSenderUserProfileId() == $record->getSenderUserProfileId()) {
                    $recipient_id_arr[] = $remittanceEach->getRecipient()->getId();
                }
                $user_profile_id_arr[] = $remittanceEach->getSenderUserProfileId();
                $remit_config_id_arr[] = $remittanceEach->getRemittanceConfigurationId();
                $transaction_id_arr[] = $remittanceEach->getInTransactionId();
                $transaction_id_arr[] = $remittanceEach->getOutTransactionId();
                if((bool)$remittanceEach->getIsHomeCollection()) {
                    $home_collection_transaction_id_arr[] = $remittanceEach->getInTransactionId();
                }
            }

            $recipientColl = $recipient_serv->getRecipientByIds($recipient_id_arr);

            $user_profile_name = NULL;
            $userColl = $userServ->getUsers(array_unique($user_profile_id_arr));
            if( $userEach = $userColl->getById($record->getSenderUserProfileId()) )
                $user_profile_name = $userEach->getName();            

            //$remittanceConfigColl = $remit_config_serv->getRemittanceConfigByIdArr($remit_config_id_arr);

            $transactionColl = $trans_serv->findByIdArr($transaction_id_arr);

            /*
            $paymentColl = NULL;
            if($transactionColl) {
                $transactionID_arr = array();
                foreach ($transactionColl->result as $transEach) {
                    $transactionID_arr[] = $transEach->getTransactionID();
                }
                $paymentColl = $paymentServ->getPaymentByTransactionIDs(getenv('MODULE_CODE'), $transactionID_arr);
            }*/

            $include_into_list = FALSE;
            foreach ($collection->result as $remittanceEach) {
                $include_into_list = FALSE;
                //set sender and recipient user name
                if ($remittanceEach->getSenderUserProfileId() == $record->getSenderUserProfileId()) {
                    $include_into_list = TRUE;
                    
                    if( $recipientEach = $recipientColl->result->getById($remittanceEach->getRecipient()->getId()) )
                        $remittanceEach->setRecipientUserName($recipientEach->getRecipientAlias());
                    
                    $remittanceEach->setSenderUserName($user_profile_name);
                } else {
                    if( $remittanceEach->getStatus()->getCode() == RemittanceStatus::DELIVERING ||
                        $remittanceEach->getStatus()->getCode() == RemittanceStatus::PENDING_COLLECTION ||
                        $remittanceEach->getStatus()->getCode() == RemittanceStatus::COLLECTED) {
                        $include_into_list = TRUE;
                        
                        if( $userEach = $userColl->getById($remittanceEach->getSenderUserProfileId()) )
                            $remittanceEach->setSenderUserName($userEach->getName());
                        
                        $remittanceEach->setRecipientUserName($user_profile_name);
                        $remittanceEach->setUserAsRecipient(TRUE);
                    }
                }

                if($include_into_list) {
                    //set in and out country currency code
                    if ($remittanceConfigColl) {
                        if( $remittanceConfigEach = $remittanceConfigColl->getById($remittanceEach->getRemittanceConfigurationId()) )
                        {
                            if ($remittanceConfigEach->getInCorporateService()) {
                                $remittanceEach->setInCountryCurrencyCode($remittanceConfigEach->getInCorporateService()->getCountryCurrencyCode());
                            }
                            if ($remittanceConfigEach->getOutCorporateService()) {
                                $remittanceEach->setOutCountryCurrencyCode($remittanceConfigEach->getOutCorporateService()->getCountryCurrencyCode());
                            }
                        }                        
                    }

                    //set in and out country code and currency code                        
                    if ($allCountryCurrencyCollection) {                                                
                        if( $country = $allCountryCurrencyCollection->getFromIndex('code', $remittanceEach->getInCountryCurrencyCode()) )
                        {
                            $remittanceEach->setFromCountryCode($country->getCountryCode());
                            $remittanceEach->setFromCurrencyCode($country->getCurrencyCode());
                        }
                        
                        if( $country = $allCountryCurrencyCollection->getFromIndex('code', $remittanceEach->getOutCountryCurrencyCode()) )
                        {
                            $remittanceEach->setToCountryCode($country->getCountryCode());
                            $remittanceEach->setToCurrencyCode($country->getCurrencyCode());
                        }
                    }

                    $in_transID_found = FALSE;
                    $out_transID_found = FALSE;
                    if ($transactionColl) {
                        if( $transEach = $transactionColl->result->getById($remittanceEach->getInTransactionId()) )
                        {
                            $remittanceEach->setInTransactionIDString($transEach->getTransactionID());
                            $remittanceEach->setPaymentMode($transEach->getConfirmPaymentCode());
                            $remittanceEach->setInTransactionExpiredAt($transEach->getExpiredDate());

                            if(in_array($transEach->getId(), $home_collection_transaction_id_arr)) {
                                $home_collection_transactionID_arr[] = $transEach->getTransactionID();
                            }
                        }
                        
                        if( $transEach = $transactionColl->result->getById($remittanceEach->getOutTransactionId()) )
                        {
                            $remittanceEach->setOutTransactionIDString($transEach->getTransactionID());
                        }
                        
                        if ($remittanceEach->getUserAsRecipient()) {
                            $remittanceEach->setTransactionID($remittanceEach->getOutTransactionIDString());
                            $remittanceEach->setTransactionIdGUID($remittanceEach->getOutTransactionId());
                        } else {
                            $remittanceEach->setTransactionID($remittanceEach->getInTransactionIDString());
                            $remittanceEach->setTransactionIdGUID($remittanceEach->getInTransactionId());
                        }                        
                    }

                    /*
                    if($paymentColl) {
                        $bank_description = array();
                        foreach ($paymentColl as $paymentEach) {
                            if($remittanceEach->getTransactionID() == $paymentEach->getTransactionID()) {
                                $remittanceEach->setCollectionMode($paymentEach->getPaymentCode());
                            }
                        }
                    }*/
                    if($remittanceEach->getCollectionInfo() != NULL)
                    {
                        $collection_info = json_decode($remittanceEach->getCollectionInfo(), true);
                        if(is_array($collection_info)) {
                            $remittanceEach->setCollectionMode($collection_info['payment_code']);
                        }
                    }

                    $newCollection->addData($remittanceEach);
                }
            }

            //to add home collection assigned to / scheduled by information
            if(count($home_collection_transactionID_arr) > 0) {
                $deliveryServ =  DeliveryServiceFactory::build();
                if($deliveryColl = $deliveryServ->getDeliveryListByTransactionIDList($home_collection_transactionID_arr)) {

                    foreach ($newCollection as $remittanceEach) {
                        if((bool)$remittanceEach->getIsHomeCollection()) {
                            foreach ($deliveryColl as $deliveryEach) {
                                if ($deliveryEach instanceof Delivery) {
                                    if ($remittanceEach->getInTransactionIDString() == $deliveryEach->getTransactionID()) {
                                        if($deliveryEach->getScheduledByUser()) {
                                            $remittanceEach->setScheduledByUser($deliveryEach->getScheduledByUser());
                                            $remittanceEach->setScheduledAt($deliveryEach->getScheduledAt());
                                        }
                                        break;
                                    }
                                }
                            }
                        }
                    }

                }
            }

            $collection->result = $newCollection;

            $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_TRANSACTION_SUCCESS);
            return $collection;
        }

        $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_TRANSACTION_FAILED);
        return false;
    }

    public function getRemittanceListByTransactionIDs(array $transaction_arr){
        if( $results = $this->getRepository()->findByTransactionIDArray($transaction_arr) )
        {
            $this->setResponseCode(MessageCode::CODE_LIST_REMITTANCE_TRANSACTION_SUCCESS);
            return $results;
        }
        $this->setResponseCode(MessageCode::CODE_LIST_REMITTANCE_TRANSACTION_FAILED);
        return false;
    }

    public function getRemittanceInfoByRemittanceId($remittance_id)
    {   
        if ($remittanceInfo = $this->retrieveRemittance($remittance_id)) {

            if ($remittanceInfo->getSender()->getFullName()) {
                $remittanceInfo->setSenderUserName($remittanceInfo->getSender()->getFullName());
            }else{
                $remittanceInfo->setSenderUserName($remittanceInfo->getSender()->getName());
            }

            if($fullName = $remittanceInfo->getRecipient()->getAttributes()->hasAttribute(AttributeCode::FULL_NAME)){
                
                $remittanceInfo->setRecipientUserName($fullName);
            }else{

                $remittanceInfo->setRecipientUserName($remittanceInfo->getRecipient()->getRecipientAlias());
            }

            $remit_config_serv = RemittanceConfigServiceFactory::build();
            if( $info = $remit_config_serv->getAllRemittanceConfig(MAX_VALUE, 1) )
                $remittanceConfigColl = $info->result;

            $paymentServ = PaymentServiceFactory::build();
            $allCountryCurrency = $paymentServ->getAllCountryCurrency();
        
            if ($remittanceConfigColl) {
                foreach ($remittanceConfigColl as $remittanceConfigEach) {
                    if ($remittanceInfo->getRemittanceConfigurationId() == $remittanceConfigEach->getId()) {
                        $remittanceInfo->setRemittanceConfiguration($remittanceConfigEach);
                        if ($remittanceConfigEach->getInCorporateService()) {
                            $remittanceInfo->setInCountryCurrencyCode($remittanceConfigEach->getInCorporateService()->getCountryCurrencyCode());
                            $remittanceInfo->setInServiceProviderId($remittanceConfigEach->getInCorporateService()->getServiceProviderId());
                        }
                        if ($remittanceConfigEach->getOutCorporateService()) {
                            $remittanceInfo->setOutCountryCurrencyCode($remittanceConfigEach->getOutCorporateService()->getCountryCurrencyCode());
                            $remittanceInfo->setOutServiceProviderId($remittanceConfigEach->getOutCorporateService()->getServiceProviderId());
                        }
                        break;
                    }
                }
            }

            //set in and out country code and currency code
            $in_country_currency_found = FALSE;
            $out_country_currency_found = FALSE;
            if ($allCountryCurrency) {
                foreach ($allCountryCurrency as $country) {
                    if ($country->getCode() == $remittanceInfo->getInCountryCurrencyCode() && !$in_country_currency_found) {
                        $remittanceInfo->setFromCountryCode($country->getCountryCode());
                        $remittanceInfo->setFromCurrencyCode($country->getCurrencyCode());
                        $in_country_currency_found = TRUE;
                    }
                    if ($country->getCode() == $remittanceInfo->getOutCountryCurrencyCode() && !$out_country_currency_found) {
                        $remittanceInfo->setToCountryCode($country->getCountryCode());
                        $remittanceInfo->setToCurrencyCode($country->getCurrencyCode());
                        $out_country_currency_found = TRUE;
                    }

                    if ($in_country_currency_found && $out_country_currency_found) {
                        break;
                    }
                }
            }

            $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_TRANSACTION_SUCCESS);
            return $remittanceInfo;
        }

        $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_TRANSACTION_FAILED);
        return false;
    }

    public function getSenderRemittanceInfo(RemittanceRecord $record,$date_from,$date_to)
    {

        // $temp_beginDate = date('Y-m-01 00:00:00');
        // $beginDate = IappsDateTime::fromString(date('Y-m-01 00:00:00'));
        // $endDate = IappsDateTime::fromString(date('Y-m-d 23:59:59', strtotime("$temp_beginDate +1 month -1 day")));

        $beginDate = IappsDateTime::fromString($date_from);
        $endDate = IappsDateTime::fromString($date_to);

        $remit_config_serv = RemittanceConfigServiceFactory::build();
        if( $info = $remit_config_serv->getAllRemittanceConfig(MAX_VALUE, 1) )
            $remittanceConfigColl = $info->result;


        $newCollection = new RemittanceRecordCollection();

        $status = array(RemittanceStatus::DELIVERING,
                        RemittanceStatus::PENDING_COLLECTION,
                        RemittanceStatus::COLLECTED,
                        RemittanceStatus::PROCESSING);

        $paymentServ = PaymentServiceFactory::build();
        $allCountryCurrency = $paymentServ->getAllCountryCurrency();

        if ($collection = $this->getRepository()->findSenderRemittanceInfo($record, $status, $beginDate->getUnix(), $endDate->getUnix())) {

            foreach ($collection->result as $each) {

               if ($remittanceInfo = $this->retrieveRemittance($each->getId())) {

                    if ($remittanceConfigColl) {
                        foreach ($remittanceConfigColl as $remittanceConfigEach) {
                            if ($remittanceInfo->getRemittanceConfigurationId() == $remittanceConfigEach->getId()) {
                                if ($remittanceConfigEach->getInCorporateService()) {
                                    $remittanceInfo->setInCountryCurrencyCode($remittanceConfigEach->getInCorporateService()->getCountryCurrencyCode());
                                    $remittanceInfo->setInServiceProviderId($remittanceConfigEach->getInCorporateService()->getServiceProviderId());
                                }
                                if ($remittanceConfigEach->getOutCorporateService()) {
                                    $remittanceInfo->setOutCountryCurrencyCode($remittanceConfigEach->getOutCorporateService()->getCountryCurrencyCode());
                                    $remittanceInfo->setOutServiceProviderId($remittanceConfigEach->getOutCorporateService()->getServiceProviderId());

                                }
                                break;
                            }
                        }
                    }

                    //set in and out country code and currency code
                    $in_country_currency_found = FALSE;
                    $out_country_currency_found = FALSE;
                    if ($allCountryCurrency) {
                        foreach ($allCountryCurrency as $country) {
                            if ($country->getCode() == $remittanceInfo->getInCountryCurrencyCode() && !$in_country_currency_found) {
                                $remittanceInfo->setFromCountryCode($country->getCountryCode());
                                $remittanceInfo->setFromCurrencyCode($country->getCurrencyCode());
                                $in_country_currency_found = TRUE;
                            }
                            if ($country->getCode() == $remittanceInfo->getOutCountryCurrencyCode() && !$out_country_currency_found) {
                                $remittanceInfo->setToCountryCode($country->getCountryCode());
                                $remittanceInfo->setToCurrencyCode($country->getCurrencyCode());
                                $out_country_currency_found = TRUE;
                            }

                            if ($in_country_currency_found && $out_country_currency_found) {
                                break;
                            }
                        }
                    }

                    $newCollection->addData($remittanceInfo);
               }
            }

            $collection->result = $newCollection;

            $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_TRANSACTION_SUCCESS);
            return $collection;
        }

        $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_TRANSACTION_FAILED);
        return false;
    }

    public function getByPrimaryKey(RemittanceRecord $record)
    {
        if( $remittance = $this->getRepository()->getByPrimaryKey($record) )
        {
            if( $remittance instanceof RemittanceRecord )
            {
                return $remittance;
            }
        }

        $this->setResponseCode(MessageCode::CODE_REMITTANCE_RECORD_NOT_FOUND);
        return false;
    }

    public function getRecipientRemittanceInfo(RemittanceRecord $record,$date_from,$date_to)
    {   

        $recipientService = RecipientServiceFactory::build(true, '2');

        if (!$recipient = $recipientService->getRecipientDetail($record->getRecipient()->getId(),false)) {
            return false;
        }
        
        $recipient_full_name = null;

        foreach ($recipient->getAttributes() as $att) {

            if ($att->getAttribute()->getCode() == AttributeCode::FULL_NAME) {
                $recipient_full_name = $att->getValue();
            }
        }

        $recipient_mobile_num_hash = $recipient->getRecipientMobileNumber()->getHashedValue();
        $recipient_mobile_dialing_code_hash = $recipient->getRecipientDialingCode()->getHashedValue();
        $recipient_id_arr = array();

        if ($recipient->getRecipientUserProfileId()) {

            $recipientEntity = new Recipient();
            $recipientEntity->setRecipientUserProfileId($recipient->getRecipientUserProfileId());

            if ($data = $recipientService->getRepository()->findByParam($recipientEntity,null,MAX_VALUE,1)) {
                
                foreach ($data->result as $eachData) {
                    $recipient_id_arr[] = $eachData->getId();
                }
            }
        }else{

            $attribute = RecipientAttributeServiceFactory::build();

            if ($data = $recipientService->getRecipientByHashedMobileNumber($recipient_mobile_dialing_code_hash,$recipient_mobile_num_hash))
            {
                foreach ($data as $eachData) {
                    foreach ($eachData->getAttributes() as $att) {
                        if ($att->getAttribute()->getCode() == AttributeCode::FULL_NAME) {

                            if ($att->getValue() == $recipient_full_name) {
                                $recipient_id_arr[] = $eachData->getId();
                            }
                        }
                    }
                }
            }
        }

        $beginDate = IappsDateTime::fromString($date_from);
        $endDate = IappsDateTime::fromString($date_to);
        // $temp_beginDate = date('Y-m-01 00:00:00');
        // $beginDate = IappsDateTime::fromString(date('Y-m-01 00:00:00'));
        // $endDate = IappsDateTime::fromString(date('Y-m-d 23:59:59', strtotime("$temp_beginDate +1 month -1 day")));

        $remit_config_serv = RemittanceConfigServiceFactory::build();
        if( $info = $remit_config_serv->getAllRemittanceConfig(MAX_VALUE, 1) )
            $remittanceConfigColl = $info->result;


        $newCollection = new RemittanceRecordCollection();

        $status = array(RemittanceStatus::DELIVERING,
                        RemittanceStatus::PENDING_COLLECTION,
                        RemittanceStatus::COLLECTED,
                        RemittanceStatus::PROCESSING);

        $paymentServ = PaymentServiceFactory::build();
        $allCountryCurrency = $paymentServ->getAllCountryCurrency();


        if ($collection = $this->getRepository()->findRecipintRemittanceInfo($status, $recipient_id_arr, $beginDate->getUnix(), $endDate->getUnix()))
        {

            foreach ($collection->result as $each) {

               if ($remittanceInfo = $this->retrieveRemittance($each->getId())) {

                    if ($remittanceConfigColl) {
                        foreach ($remittanceConfigColl as $remittanceConfigEach) {
                            if ($remittanceInfo->getRemittanceConfigurationId() == $remittanceConfigEach->getId()) {
                                if ($remittanceConfigEach->getInCorporateService()) {
                                    $remittanceInfo->setInCountryCurrencyCode($remittanceConfigEach->getInCorporateService()->getCountryCurrencyCode());
                                    $remittanceInfo->setInServiceProviderId($remittanceConfigEach->getInCorporateService()->getServiceProviderId());
                                }
                                if ($remittanceConfigEach->getOutCorporateService()) {
                                    $remittanceInfo->setOutCountryCurrencyCode($remittanceConfigEach->getOutCorporateService()->getCountryCurrencyCode());
                                    $remittanceInfo->setOutServiceProviderId($remittanceConfigEach->getOutCorporateService()->getServiceProviderId());
                                }
                                break;
                            }
                        }
                    }

                    //set in and out country code and currency code
                    $in_country_currency_found = FALSE;
                    $out_country_currency_found = FALSE;
                    if ($allCountryCurrency) {
                        foreach ($allCountryCurrency as $country) {
                            if ($country->getCode() == $remittanceInfo->getInCountryCurrencyCode() && !$in_country_currency_found) {
                                $remittanceInfo->setFromCountryCode($country->getCountryCode());
                                $remittanceInfo->setFromCurrencyCode($country->getCurrencyCode());
                                $in_country_currency_found = TRUE;
                            }
                            if ($country->getCode() == $remittanceInfo->getOutCountryCurrencyCode() && !$out_country_currency_found) {
                                $remittanceInfo->setToCountryCode($country->getCountryCode());
                                $remittanceInfo->setToCurrencyCode($country->getCurrencyCode());
                                $out_country_currency_found = TRUE;
                            }

                            if ($in_country_currency_found && $out_country_currency_found) {
                                break;
                            }
                        }
                    }

                    $newCollection->addData($remittanceInfo);
               }
            }

            $collection->result = $newCollection;

            $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_TRANSACTION_SUCCESS);
            return $collection;
        }

        $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_TRANSACTION_FAILED);
        return false;
    }
}