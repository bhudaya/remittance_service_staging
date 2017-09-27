<?php

namespace Iapps\RemittanceService\RemittanceTransaction;

use Iapps\Common\Core\IappsBaseService;
use Iapps\Common\Microservice\AccountService\AccountService;
use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\Common\Microservice\AccountService\User;
use Iapps\Common\Microservice\DeliveryService\Delivery;
use Iapps\Common\Microservice\DeliveryService\DeliveryItem;
use Iapps\Common\Microservice\DeliveryService\DeliveryItemCollection;
use Iapps\Common\Microservice\DeliveryService\DeliveryServiceFactory;
use Iapps\Common\Microservice\DeliveryService\RecipientAddress;
use Iapps\Common\Microservice\PaymentService\Payment;
use Iapps\Common\Microservice\PromoCode\PromoCodeClientFactory;
use Iapps\Common\Transaction\Transaction;
use Iapps\Common\Transaction\TransactionStatus;
use Iapps\RemittanceService\Attribute\AttributeCode;
use Iapps\RemittanceService\Common\AddressNameExtractor;
use Iapps\RemittanceService\Common\Logger;
use Iapps\RemittanceService\Common\MessageCode;
use Iapps\RemittanceService\RemittanceCompanyUser\RemittanceCompanyUserServiceFactory;
use Iapps\RemittanceService\RemittanceCompanyRecipient\RemittanceCompanyRecipientServiceFactory;

use Iapps\Common\Helper\GuidGenerator;
use Iapps\Common\AuditLog\AuditLogAction;
use Iapps\Common\Core\IappsBaseEntity;
use Iapps\Common\Core\IappsDateTime;
use Iapps\RemittanceService\Attribute\RemittanceAttributeServiceFactory;
use Iapps\Common\Microservice\CountryService\CountryServiceFactory;
use Iapps\RemittanceService\Common\SystemCodeServiceFactory;
use Iapps\Common\SystemCode\SystemCode;
use Iapps\Common\SystemCode\SystemCodeService;
use Iapps\Common\Microservice\PaymentService\PaymentServiceFactory;
use Iapps\Common\Transaction\TransactionService;
use Iapps\Common\Core\IappsBaseRepository;
use Iapps\Common\Transaction\TransactionItemService;
use Iapps\Common\Transaction\TransactionItem;
use Iapps\RemittanceService\RemittanceRecord\RemittanceRecord;
use Iapps\RemittanceService\RemittanceRecord\RemittanceRecordServiceFactory;
use Iapps\RemittanceService\Common\TransactionType;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfigServiceFactory;
use Iapps\RemittanceService\ExchangeRate\ExchangeRateServiceFactory;
use Iapps\RemittanceService\Recipient\RecipientServiceFactory;
use Iapps\Common\Microservice\UserCreditService\PrelimCheckServiceFactory;
use Iapps\RemittanceService\RemittanceCompanyUser\PartnerRemittanceCompanyUserService;
use Iapps\RemittanceService\RemittanceRecord\VoidRemittancePaymentInterface;
use Iapps\RemittanceService\RemittanceRecord\UserDataSnapshotService;

class RemittanceTransactionService extends TransactionService
{
    protected $_bill_payment_serv;
    protected $_bill_request_serv;

    protected $_accountService;
    protected $_delivery_client;

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

    public function getItemService()
    {
        return $this->_item_serv;
    }

    public function setDeliveryClient($delivery_client)
    {
        $this->_delivery_client = $delivery_client;
        return $this;
    }

    public function getDeliveryClient()
    {
        return $this->_delivery_client;
    }

    function __construct(RemittanceTransactionRepository $trans_repo,
                        RemittanceTransactionItemService $trans_item_serv,
                         SystemCodeService $syscode_serv)
    {
        parent::__construct($trans_repo, $trans_item_serv, $syscode_serv);
    }

    public function findByIdWithItems($id)
    {
        if( $trx = $this->findById($id) )
        {
            if( $info = $this->_item_serv->findByTransactionId($trx->getId()) )
            {
                if( isset($info->result) )
                    $trx->setItems($info->result);
            }

            return $trx;
        }

        return false;
    }
    
    public function findByIdArr(array $ids)
    {
        if( $trxs = parent::findByIdArr($ids) )
        {
            if( $info = $this->_item_serv->findByTransactionIds($ids) )
            {
                foreach($info->getResult() AS $item)
                {
                    if( $trx = $trxs->getResult()->getById($item->getTransactionId()) )
                    {
                        $trx->getItems()->addData($item);
                    }
                }
            }

            return $trxs;
        }

        return false;
    }


    public function saveTransactionWithoutDBTransaction(Transaction $transaction)
    {
        $transaction->setCreatedBy($this->getUpdatedBy());

        //get status id if needed
        if( !$this->_extractStatus($transaction) )
            return false;

        if( $this->getRepository()->insert($transaction) )
        {
            foreach($transaction->getItems() AS $item)
            {
                if( !$this->_item_serv->insertItem($item) )
                {
                    return false;
                }
            }

            return $transaction;
        }

        return false;
    }

    public function saveTransaction(Transaction $transaction)
    {
        if( !($transaction instanceof RemittanceTransaction) )
        {
            $this->setResponseCode(MessageCode::CODE_ADD_REMITTANCE_TRANSACTION_FAILED);
            return false;
        }

        //get item type if needed
        foreach($transaction->getItems() AS $item)
        {
            if( $item->getItemType()->getId() == NULL )
            {
                if( !$itemType = $this->_syscode_serv->getByCode($item->getItemType()->getCode(), ItemType::getSystemGroupCode()) )
                {
                    $this->setResponseCode(MessageCode::CODE_ADD_REMITTANCE_TRANSACTION_FAILED);
                    return false;
                }
                $item->setItemType($itemType);
            }
        }

        if( $this->saveTransactionWithoutDBTransaction($transaction) )
        {
            $this->fireLogEvent('iafb_remittance.transaction', AuditLogAction::CREATE, $transaction->getId());

            //insert profit cost
            foreach($transaction->getProfitCostItems() AS $item)
            {
                $profitCostServ = TransactionProfitCostServiceFactory::build();
                if( !$profitCostServ->addProfitCost($item) )
                {
                    $this->setResponseCode($profitCostServ->getResponseCode());
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    public function completeTransaction(Transaction $transaction)
    {
        $ori_transaction = clone($transaction);
        if( $result = parent::completeTransaction($transaction) )
        {
            foreach($transaction->getItems() AS $item)
            {
                if( $item->getItemType()->getCode() == ItemType::DISCOUNT )
                {
                    if($transaction->getTransactionType()->getCode() != TransactionType::CODE_REFUND) {
                        $promoServ = PromoCodeClientFactory::build(2);
                        if (!$promoServ->apply($item->getItemId())) {
                            Logger::debug('Failed to apply promo code' . $transaction->getId());
                            return false;
                        }
                    }
                }
            }

            RemittanceTransactionEventProducer::publishTransactionCreated($transaction->getId());
            $this->fireLogEvent('iafb_remittance.transaction', AuditLogAction::UPDATE, $transaction->getId(), $ori_transaction);

            return $result;
        }

        return false;
    }

    public function confirmTransaction(Transaction $transaction)
    {
        $ori_transaction = clone($transaction);
        if( $result = parent::confirmTransaction($transaction) )
        {
            $this->fireLogEvent('iafb_remittance.transaction', AuditLogAction::UPDATE, $transaction->getId(), $ori_transaction);

            return $result;
        }

        return false;
    }

    public function cancelTransaction(Transaction $transaction)
    {
        $ori_transaction = clone($transaction);
        if( $result = parent::cancelTransaction($transaction) )
        {
            $this->fireLogEvent('iafb_remittance.transaction', AuditLogAction::UPDATE, $transaction->getId(), $ori_transaction);

            return $result;
        }

        return false;
    }

    public function expireTransaction(Transaction $transaction)
    {
        if( $transaction->getStatus()->getCode() == TransactionStatus::CONFIRMED )
        {
            $transaction->getStatus()->setCode(TransactionStatus::EXPIRED);
            return $this->_updateStatus($transaction);
        }

        return false;
    }

    public function update(Transaction $transaction, Transaction $oriTransaction)
    {
        $transaction->setUpdatedBy($this->getUpdatedBy());

        if($this->getRepository()->update($transaction))
        {
            $this->fireLogEvent('iafb_remittance.transaction', AuditLogAction::UPDATE, $transaction->getId(), $oriTransaction);
            return true;
        }

        return false;
    }

    public function getTransactionHistoryListByDate(Transaction $transaction ,$limit, $page)
    {
        $objData = new \stdClass();

        if ($collection = parent::getTransactionHistoryListByDate($transaction, $limit, $page))
        {
            $result_array = array();
            foreach ($collection->result as $transEach) {
                //=  get_object_vars($transEach) ;
                $transEachArr = json_decode(json_encode($transEach), true);
                $config = new \Iapps\Common\Transaction\Transaction();
                $config->setTransactionID($transEachArr["transactionID"]);
                if( $object = $this->getTransactionDetail($config, $limit, $page ) )
                {
                    if (isset($object->remittance)) {
                        $transEachArr['sender'] = $object->remittance["sender"];
                        $transEachArr['recipient'] = $object->remittance["recipient"];
                    }
                }
                /*
                $objData->transaction = $transEach ;
                $objData->remittance = $remittanceInfo ;
                $result_array[] = $objData ;
                */
                $result_array[] = $transEachArr ;
            }
            $collection->result = $result_array ;
            $this->setResponseCode(self::CODE_GET_TRANSACTION_SUCCESS);
            return $collection;
        }
        $this->setResponseCode(self::CODE_GET_TRANSACTION_FAILED);
        return false;
    }    
    
    
    
    
    //todo refactor this function, too long and messy!!
    public function getTransactionDetail(Transaction $transaction ,$limit, $page)
    {
        if( $result = parent::getTransactionDetail($transaction, $limit, $page) )
        {
            $result->transaction->setItems($result->transaction_items);
            if( $result->transaction instanceof RemittanceTransaction )
            {
                $isCashIn = false;
                if( $result->transaction->isCashIn() )
                    $isCashIn = true;

                $remittanceRecordService = RemittanceRecordServiceFactory::build();

                $countryServ = CountryServiceFactory::build();
                $sysServ = SystemCodeServiceFactory::build();
                $prelimCheckServ = PrelimCheckServiceFactory::build();

                $remittanceRecordService->setAccountService($this->getAccountService());
                if( $remittance = $remittanceRecordService->getByTransactionId($result->transaction->getId(), $isCashIn) )
                {
                    $snapshotServ = new UserDataSnapshotService();
                    $snapshotServ->fromSnapShot($remittance);

                    $remittance->setApprovalRequired('N/A');
                    if( empty($remittance->getApprovalStatus()) )
                    {
                        $remittance->setApprovalStatus('N/A');
                    }
                    $remittanceAttributeServ = RemittanceAttributeServiceFactory::build();
                    $attributes = $remittanceAttributeServ->getAllRemittanceAttribute($remittance->getId());
                    if( !empty($attributes) )
                    {
                        foreach($attributes as $attr)
                        {
                            if($attr->getAttribute()->getCode() == AttributeCode::REJECT_REASON || $attr->getAttribute()->getCode()==AttributeCode::APPROVE_REASON ){
                                $remittance->setReason( $attr->getValue() );
                            }
                            if($attr->getAttribute()->getCode() == AttributeCode::PIN_NUMBER ){
                                $remittance->setPinNumber( $attr->getValue() );
                            }
                            if($attr->getAttribute()->getCode() == AttributeCode::REFERENCE_NUMBER ){
                                $remittance->setReferenceNumber( $attr->getValue() );
                            }
                            if($attr->getAttribute()->getCode() == AttributeCode::PARTNER_SYSTEM ){
                                $remittance->setPartnerSystem( $attr->getValue() );
                            }
                        }
                    }

                    $remittanceInfo = $remittance->getSelectedField(array('id', 'remittanceID', 'status', 'display_rate', 'from_amount', 'to_amount',
                        'is_face_to_face_trans', 'is_face_to_face_recipient', 'is_home_collection', 'lat', 'lon', 'created_at'));

                    $remittanceInfo['prelim_check_status'] = 'N/A';
                    $remittanceInfo['prelim_check_failed_reason'] = NULL;

                    if ($prelimCheckResult = $prelimCheckServ->getPrelimCheckResult($remittance->getId())) {

                        $remittanceInfo['prelim_check_status'] = $prelimCheckResult->getCheckResult();
                        $remittanceInfo['prelim_check_failed_reason'] = $prelimCheckResult->getFailedReason();
                    }

                    $remittanceInfo['in_transaction_id'] = $remittance->getInTransactionId();
                    $remittanceInfo['out_transaction_id'] = $remittance->getOutTransactionId();

                    $remittanceInfo['in_transactionID'] = $remittance->getInTransaction()->getTransactionID();
                    $remittanceInfo['out_transactionID'] = $remittance->getOutTransaction()->getTransactionID();

                    $in_status = $sysServ->getById($remittance->getInTransaction()->getStatus()->getId());
                    $out_status = $sysServ->getById($remittance->getOutTransaction()->getStatus()->getId());
                    $remittanceInfo['in_transaction_status'] = $in_status->getDisplayName();
                    $remittanceInfo['out_transaction_status'] = $out_status->getDisplayName();
                    $remittanceInfo['cashout_expiry_at'] = $remittance->getCashOutExpiryDate()->getString();
                    $remittanceInfo['cashout_expiry_period'] = $remittance->getCashOutExpiryPeriodInDay();

                    $remittanceInfo['fees_charged'] = 0;

                    foreach($result->transaction_items as $item){
                        if(in_array($item->getItemType()->getCode(),
                            array(ItemType::CORPORATE_SERVICE_FEE, ItemType::PAYMENT_FEE)))
                        {
                            $remittanceInfo['fees_charged'] += $item->getNetAmount();
                        }
                    }
                    $remittanceInfo['approval_required'] = $remittance->isApprovalRequired();
                    $remittanceInfo['approval_status'] =  $remittance->getApprovalStatus();
                    $remittanceInfo['approved_rejected_at'] = $remittance->getApprovedRejectedAt()->getString();
                    $remittanceInfo['approved_rejected_by'] = $remittance->getApprovedRejectedBy();
                    $remittanceInfo['approved_rejected_by_name'] = null;
                    if($remittance->getApprovedRejectedBy() != NULL) {
                        if($userApproverRejecter = $this->getAccountService()->getUser(null, $remittance->getApprovedRejectedBy())) {
                            $remittanceInfo['approved_rejected_by_name'] = $userApproverRejecter->getFullName();
                        }
                    }
                    $remittanceInfo['approve_reject_remark'] = $remittance->getApproveRejectRemark();
                    $remittanceInfo['reason'] = $remittance->getReason();
                    $remittanceInfo['pin_number'] = $remittance->getPinNumber();
                    $remittanceInfo['reference_number'] = $remittance->getReferenceNumber();
                    $remittanceInfo['partner_system'] = $remittance->getPartnerSystem();

                    $remittanceInfo['from_country_currency_code'] = $remittance->getInTransaction()->getCountryCurrencyCode();
                    $remittanceInfo['to_country_currency_code'] = $remittance->getOutTransaction()->getCountryCurrencyCode();
                    $remittanceInfo['is_international'] = $remittance->isInternational();

                    AddressNameExtractor::extract($remittance->getSender());
                    $remittanceInfo['sender'] = $remittance->getSender()->getSelectedField(array('id', 'name', 'full_name', 'host_country_code','mobile_no','accountID','host_identity_card','host_address'));
                    $remittanceInfo['sender']['id_type'] = $remittance->getSender()->getAttributes()->hasAttribute('id_type');
                    $remittanceInfo['sender']['nationality'] = $remittance->getSender()->getAttributes()->hasAttribute('nationality');

                    $remittanceInfo['sender']['country'] = NULL;
                    if( $senderCountry = $countryServ->getCountryInfo($remittanceInfo['sender']['host_country_code']) )
                        $remittanceInfo['sender']['country'] = $senderCountry->getName();

                    $remittanceInfo['sender'][AttributeCode::SOURCE_OF_INCOME] = $remittance->getSender()->getAttributes()->hasAttribute(AttributeCode::SOURCE_OF_INCOME);

                    $remittanceInfo['recipient'] = $remittance->getRecipient()->getSelectedField(array('id', 'user_profile_id', 'recipient_alias', 'recipient_dialing_code', 'recipient_mobile_number', 'host_address'));
                    $remittanceInfo['recipient'][AttributeCode::PURPOSE_OF_REMITTANCE] = $remittance->getRecipient()->getAttributes()->hasAttribute(AttributeCode::PURPOSE_OF_REMITTANCE);
                    $remittanceInfo['recipient'][AttributeCode::RELATIONSHIP_TO_SENDER] = $remittance->getRecipient()->getAttributes()->hasAttribute(AttributeCode::RELATIONSHIP_TO_SENDER);
                    $remittanceInfo['recipient'][AttributeCode::RESIDENTIAL_COUNTRY] = $remittance->getRecipient()->getAttributes()->hasAttribute(AttributeCode::RESIDENTIAL_COUNTRY);
                    $remittanceInfo['recipient'][AttributeCode::RESIDENTIAL_PROVINCE] = $remittance->getRecipient()->getAttributes()->hasAttribute(AttributeCode::RESIDENTIAL_PROVINCE);
                    $remittanceInfo['recipient'][AttributeCode::RESIDENTIAL_CITY] = $remittance->getRecipient()->getAttributes()->hasAttribute(AttributeCode::RESIDENTIAL_CITY);
                    $remittanceInfo['recipient'][AttributeCode::FULL_NAME] = $remittance->getRecipient()->getAttributes()->hasAttribute(AttributeCode::FULL_NAME);
                    $remittanceInfo['recipient'][AttributeCode::RESIDENTIAL_POST_CODE] = $remittance->getRecipient()->getAttributes()->hasAttribute(AttributeCode::RESIDENTIAL_POST_CODE);

                    if( $remittanceInfo['recipient'][AttributeCode::RESIDENTIAL_COUNTRY] )
                    {
                        $remittanceInfo['recipient']['country'] = NULL;
                        if( $recipientCountry = $countryServ->getCountryInfo($remittanceInfo['recipient'][AttributeCode::RESIDENTIAL_COUNTRY]) )
                            $remittanceInfo['recipient']['country'] = $recipientCountry->getName();
                    }

                    if( $remittanceInfo['recipient'][AttributeCode::RESIDENTIAL_PROVINCE] )
                    {
                        $remittanceInfo['recipient']['province'] = NULL;
                        if( $recipientProvince = $countryServ->getProvinceInfo($remittanceInfo['recipient'][AttributeCode::RESIDENTIAL_PROVINCE]) )
                            $remittanceInfo['recipient']['province'] = $recipientProvince->getName();
                    }

                    if( $remittanceInfo['recipient'][AttributeCode::RESIDENTIAL_CITY] )
                    {
                        $remittanceInfo['recipient']['city'] = NULL;
                        if( $recipientCity = $countryServ->getCityInfo($remittanceInfo['recipient'][AttributeCode::RESIDENTIAL_CITY]) )
                            $remittanceInfo['recipient']['city'] = $recipientCity->getName();
                    }

                    $remittanceInfo['recipient']['accountID'] = $remittance->getRecipient()->getRecipientUser()->getAccountID();
                    $remittanceInfo['recipient']['collectionInfo'] = NULL;


                    if($remittance->getCollectionInfo()) {
                        $collection_info = json_decode($remittance->getCollectionInfo(), true);
                        $recipientCollectionInfo = new \StdClass;
                        $recipientCollectionInfo->payment_mode = null;
                        $recipientCollectionInfo->payment_mode_name = null;
                        $recipientCollectionInfo->option = array();
                        $recipientCollectionInfo->payment_mode = $collection_info['payment_code'];

                        if( $recipientCollectionInfo->payment_mode )
                        {
                            $paymentService = PaymentServiceFactory::build();
                            if( $paymentMode = $paymentService->getPaymentModeInfo($recipientCollectionInfo->payment_mode) )
                            {
                                $recipientCollectionInfo->payment_mode_name = $paymentMode->getName();
                                $recipientCollectionInfo->need_approval = $paymentMode->getNeedApproval();
                            }
                        }

                        if( isset($collection_info['option']) && is_array($collection_info['option'])) {
                            $collection_info['option']['account_no'] = NULL;
                            if(array_key_exists('bank_account', $collection_info['option'])) {
                                $collection_info['option']['account_no'] = $collection_info['option']['bank_account'];
                            }
                            $recipientCollectionInfo->option = json_encode($collection_info['option']);
                        }

                        $remittanceInfo['recipient']['collectionInfo'] = $recipientCollectionInfo;
                    }
/*
                    if( $collection_mode = $remittance->getInTransaction()->getConfirmCollectionMode() )
                    {
                        $remittanceInfo['recipient']['collectionInfo'] = $remittance->getRecipient()->getCollectionInfos()->hasPaymentCode($collection_mode);
                    }
*/
                    $remittanceInfo['remittance_company'] = null;
                    if( $config = $this->_getRemittanceConfigInfo($remittance) )
                        $remittanceInfo['remittance_company'] = $config->getRemittanceCompany();


                    //if home collection, get info from delivery service
                    $remittanceInfo['home_collection'] = NULL;
                    if((bool)$remittance->getIsHomeCollection()) {
                        $delivery_service = DeliveryServiceFactory::build($this->getDeliveryClient());
                        if ($deliveryInfo = $delivery_service->getDeliveryListByTransactionID($remittance->getInTransaction()->getTransactionID())) {

                            $delivery = array();
                            $shipping_address = array();
                            
                            if (array_key_exists('delivery', $deliveryInfo)) {
                                $deliveryObj = $deliveryInfo['delivery'];
                                if ($deliveryObj instanceof Delivery) {
                                    $delivery['id'] = $deliveryObj->getId();
                                    $delivery['deliveryID'] = $deliveryObj->getDeliveryID();
                                    $delivery['status'] = $deliveryObj->getStatus()->getCode();
                                    $delivery['remarks'] = $deliveryObj->getRemarks();
                                    $delivery['delivery_transactionID'] = $deliveryObj->getDeliveryTransactionID();
                                    $delivery['scheduled_at'] = $deliveryObj->getScheduledAt()->getString();
                                    $delivery['scheduled_by'] = $deliveryObj->getScheduledBy();
                                    if($deliveryObj->getScheduledByUser() instanceof User) {
                                        $delivery['scheduled_by_name'] = $deliveryObj->getScheduledByUser()->getName();
                                        $delivery['scheduled_by_accountID'] = $deliveryObj->getScheduledByUser()->getAccountID();
                                    }
                                    $delivery['updated_at'] = $deliveryObj->getUpdatedAt()->getString();
                                }
                            }
                            if (array_key_exists('item', $deliveryInfo)) {
                                $deliveryItemCollObj = $deliveryInfo['item'];
                                if ($deliveryItemCollObj instanceof DeliveryItemCollection) {
                                    foreach ($deliveryItemCollObj as $deliveryIitem) {
                                        if ($deliveryIitem instanceof DeliveryItem) {
                                            $delivery['fee'] = $deliveryIitem->getFee();
                                            $delivery['delivery_mode_code'] = $deliveryIitem->getDeliveryModeCode();
                                            $delivery['delivery_mode_name'] = $deliveryIitem->getDeliveryModeName();
                                            $delivery['avg_delivery_time'] = $deliveryIitem->getAvgDeliveryTime();
                                            break;
                                        }
                                    }
                                }
                            }
                            if (array_key_exists('shipping_address', $deliveryInfo)) {
                                $shippingAddressObj = $deliveryInfo['shipping_address'];
                                if ($shippingAddressObj instanceof RecipientAddress) {
                                    $shipping_address['id'] = $shippingAddressObj->getId();
                                    $shipping_address['recipient_user_profile_id'] = $shippingAddressObj->getRecipientUserProfileId();
                                    $shipping_address['name'] = $shippingAddressObj->getName();
                                    $shipping_address['dialing_code'] = $shippingAddressObj->getDialingCode();
                                    $shipping_address['mobile_number'] = $shippingAddressObj->getMobileNumber();
                                    $shipping_address['combined_mobile_number'] = $shippingAddressObj->getDialingCode() . $shippingAddressObj->getMobileNumber();
                                    $shipping_address['country_code'] = $shippingAddressObj->getCountryCode();
                                    $shipping_address['province'] = $shippingAddressObj->getProvince();
                                    $shipping_address['city'] = $shippingAddressObj->getCity();
                                    $shipping_address['address'] = $shippingAddressObj->getAddress();
                                    $shipping_address['postal_code'] = $shippingAddressObj->getPostalCode();
                                }
                            }

                            $remittanceInfo['home_collection'] = $delivery;
                            $remittanceInfo['home_collection']['collection_address'] = $shipping_address;
                        }
                    }

                    /*
                    * Add agent name filed. = Payment->agent_id.
                    */

                    $remittanceInfo['agent_name'] = NULL;

                    if ($result->payment != NULL) {

                        if ($agentInfo = $this->getAccountService()->getUser(NULL,$result->payment[0]->agent_id)) {
                            
                            $remittanceInfo['agent_name'] = $agentInfo->getFullName();
                        }
                    }

                    /*
                    * Add main name filed. = remittance_company->service_provider_id.
                    */

                    $remittanceInfo['main_agent_name'] = NULL;
                    $remittanceInfo['remco_user'] = NULL;
                    $remittanceInfo['remco_recipient'] = NULL;
                    $remittanceInfo['customerID'] = NULL;

                    if ($remittanceInfo['remittance_company'] != NULL) {

                        if ($mainAgentInfo = $this->getAccountService()->getUser(NULL,$remittanceInfo['remittance_company']->getServiceProviderId())) {
                            
                            $remittanceInfo['main_agent_name'] = $mainAgentInfo->getFullName();
                        }
                        
                        //get services
                        $remcoUserServ = RemittanceCompanyUserServiceFactory::build();
                        $remcoRecipientServ = RemittanceCompanyRecipientServiceFactory::build();

                        //get remco
                        if($remcoUser = $remcoUserServ->getByCompanyAndUser($remittanceInfo['remittance_company'], $remittance->getSender()->getId()) )
                        {
                            $remittanceInfo['customerID'] = $remcoUser->getCustomerID();
                            $remittanceInfo['remco_user'] = $remcoUser->getSelectedField(array('id','customerID','user_status'));
                        }                            
                        
                        if($remcoRecipient = $remcoRecipientServ->getByCompanyAndRecipient($remittanceInfo['remittance_company'], $remittance->getRecipient()->getId()) )
                            $remittanceInfo['remco_recipient'] = $remcoRecipient->getSelectedField(array('id','recipient_status'));
                    }
                                       
                    $timezone_format = NULL;
                    if ($login_user_info = $this->getAccountService()->getUser(NULL,$this->getUpdatedBy())) {

                        if (isset($login_user_info->getHostAddress()->country)) {

                            $countryCode = $login_user_info->getHostAddress()->country;

                            if($countryInfo = $countryServ->getCountryInfo($countryCode) )
                            {
                                $timezone_format = $countryInfo->getTimezoneFormat();
                                $temp_created = IappsDateTime::fromString($remittanceInfo['created_at']);
                                $temp_created->setTimeZoneFormat($timezone_format);
                                $createdAt = $temp_created->getLocalDateTimeStr('Y-m-d H:i:s');
                                $remittanceInfo['created_at'] = $createdAt;
                            }
                        }
                    }

                    $result->remittance = $remittanceInfo;
                }
                return $result;
            }
        }

        $this->setResponseCode(self::CODE_GET_TRANSACTION_ITEM_HISTORY_FAILED);
        return false ;
    }

    public function getRelatedTransaction(Transaction $transaction)
    {
        $filter = new Transaction();
        $filter->setRefTransactionId($transaction->getId());
        if( $relatedTransactions = $this->getRepository()->findByParam($filter, MAX_VALUE, 1) )
        {//get items
            foreach( $relatedTransactions->result AS $relatedTransaction )
            {
                if($transactionItemColl = $this->_item_serv->findByTransactionId($relatedTransaction->getId()))
                    $relatedTransaction->setItems($transactionItemColl->result);

                if( $relatedTransaction->getConfirmPaymentCode() )
                {
                    $paymentService = PaymentServiceFactory::build();
                    if( $paymentMode = $paymentService->getPaymentModeInfo($relatedTransaction->getConfirmPaymentCode()) )
                        $relatedTransaction->setPaymentModeName($paymentMode->getName());
                }
            }

            $this->setResponseCode(self::CODE_GET_TRANSACTION_ITEM_HISTORY_SUCCESS);
            return $relatedTransactions;
        }

        $this->setResponseCode(self::CODE_GET_TRANSACTION_ITEM_HISTORY_FAILED);
        return false;
    }

    public function getFinanceTransactionDetail(Transaction $transaction ,$limit, $page, $user_profile_id)
    {

        $remit_config_serv = RemittanceConfigServiceFactory::build();
        $exchangeRateServ = ExchangeRateServiceFactory::build();
        if( $info = $remit_config_serv->getAllRemittanceConfig(MAX_VALUE, 1) )
            $remittanceConfigColl = $info->result;

        $inConfigIds = array();
        $outConfigIds = array();

        if ($remittanceConfigColl) {
            $accServ = $this->getAccountService();
            if( $upline = $accServ->getUplineStructure($user_profile_id) ) {
                if($partner_id = $upline->first_upline->getUser()->getId()){
                    $inConfigIds = $remittanceConfigColl->getFromCountryPartner($partner_id);
                    $outConfigIds =  $remittanceConfigColl->getToCountryPartner($partner_id);
                }
            }
        }


        $statuses = array(TransactionStatus::EXPIRED, TransactionStatus::CANCELLED, TransactionStatus::CONFIRMED, TransactionStatus::COMPLETED);

        if( $result = parent::getTransactionDetail($transaction, $limit, $page) )
        {
            $result->transaction->setItems($result->transaction_items);
            if( $result->transaction instanceof RemittanceTransaction )
            {
                $isCashIn = false;
                if( $result->transaction->isCashIn() )
                    $isCashIn = true;

                $remittanceRecordService = RemittanceRecordServiceFactory::build();

                $countryServ = CountryServiceFactory::build();
                $sysServ = SystemCodeServiceFactory::build();
                $transactionProfitCostServ = TransactionProfitCostServiceFactory::build();


                $accServ = $this->getAccountService();
                $timezone_format = NULL;
                if ($login_user_info = $accServ->getUser(NULL,$user_profile_id)) {

                    if (isset($login_user_info->getHostAddress()->country)) {

                        $countryCode = $login_user_info->getHostAddress()->country;
                        $country_serv = CountryServiceFactory::build();

                        if($countryInfo = $country_serv->getCountryInfo($countryCode) )
                        {
                            $timezone_format = $countryInfo->getTimezoneFormat();
                            $result->transaction->getCreatedAt()->setTimeZoneFormat($timezone_format);
                            $createdAt = $result->transaction->getCreatedAt()->getLocalDateTimeStr('Y-m-d H:i:s');
                            $result->transaction->getCreatedAt()->setDateTimeString($createdAt);
                        }
                    }
                }


                $remittanceRecordService->setAccountService($this->getAccountService());
                if( $remittance = $remittanceRecordService->getByTransactionId($result->transaction->getId(), $isCashIn) )
                {
                    $remittance->setApprovalRequired('N/A');
                    if( empty($remittance->getApprovalStatus()) )
                    {
                        $remittance->setApprovalStatus('N/A');
                    }
                    $remittanceAttributeServ = RemittanceAttributeServiceFactory::build();
                    $attributes = $remittanceAttributeServ->getAllRemittanceAttribute($remittance->getId());
                    if( !empty($attributes) )
                    {
                        foreach($attributes as $attr)
                        {
                            if($attr->getAttribute()->getCode() == AttributeCode::REJECT_REASON || $attr->getAttribute()->getCode()==AttributeCode::APPROVE_REASON ){
                                $remittance->setReason( $attr->getValue() );
                            }
                            if($attr->getAttribute()->getCode() == AttributeCode::PIN_NUMBER ){
                                $remittance->setPinNumber( $attr->getValue() );
                            }
                            if($attr->getAttribute()->getCode() == AttributeCode::REFERENCE_NUMBER ){
                                $remittance->setReferenceNumber( $attr->getValue() );
                            }
                            if($attr->getAttribute()->getCode() == AttributeCode::PARTNER_SYSTEM ){
                                $remittance->setPartnerSystem( $attr->getValue() );
                            }
                        }
                    }

                    $trxProfitCost = new TransactionProfitCost();
                    $trxProfitCost->setBeneficiaryPartyId($partner_id);
                    $trxProfitCost->setType(ProfitCostType::PROFIT);
                    $trxProfitCost->setTransactionId($remittance->getInTransactionID());

                    if(in_array($remittance->getRemittanceConfigurationId(), $outConfigIds)) {
                        if(!in_array($remittance->getOutTransaction()->getStatus()->getCode(), $statuses)){
                            return false;
                        }
                        $profits = $transactionProfitCostServ->getRepository()->findByParam($trxProfitCost, 10, 1);
                        $exchangeRate = $exchangeRateServ->getBuyingRateByExchangeRateId($remittance->getOutExchangeRateId());

                        $remittance->setCountryPartnerProfit($profits);
                        $remittance->setCountryPartner(TransactionType::CODE_CASH_OUT);
                        $remittance->setCountryPartnerStatus($remittance->getOutTransaction()->getStatus()->getCode());
                        $remittance->setCountryPartnerDisplayRate($exchangeRate);
                    }else if(in_array($remittance->getRemittanceConfigurationId(), $inConfigIds)) {
                        if(!in_array($remittance->getInTransaction()->getStatus()->getCode(), $statuses)){
                            return false;
                        }
                        $profits = $transactionProfitCostServ->getRepository()->findByParam($trxProfitCost, 10, 1);
                        $exchangeRate = $exchangeRateServ->getBuyingRateByExchangeRateId($remittance->getInExchangeRateId());

                        $remittance->setCountryPartnerProfit($profits);
                        $remittance->setCountryPartner(TransactionType::CODE_CASH_IN);
                        $remittance->setCountryPartnerStatus($remittance->getInTransaction()->getStatus()->getCode());
                        $remittance->setCountryPartnerDisplayRate($exchangeRate);
                    }else{
                        return false;
                    }

                    $remittanceInfo = $remittance->getSelectedField(array('id', 'remittanceID', 'status', 'display_rate', 'from_amount', 'to_amount', 'created_at'));

                    $remittanceInfo['in_transaction_id'] = $remittance->getInTransactionId();
                    $remittanceInfo['out_transaction_id'] = $remittance->getOutTransactionId();

                    $remittanceInfo['in_transactionID'] = $remittance->getInTransaction()->getTransactionID();
                    $remittanceInfo['out_transactionID'] = $remittance->getOutTransaction()->getTransactionID();

                    $remittanceInfo['cashout_expiry_at'] = $remittance->getCashOutExpiryDate()->getString();
                    $remittanceInfo['cashout_expiry_period'] = $remittance->getCashOutExpiryPeriodInDay();

                    foreach($result->transaction_items as $item){
                        if($item->getItemType()->getCode() == ItemType::CORPORATE_SERVICE_FEE)
                        {
                            $remittanceInfo['fees_charged'] = $item->getNetAmount();
                            break;
                        }
                    }

                    $remittanceInfo['approval_required'] = $remittance->isApprovalRequired();
                    $remittanceInfo['approval_status'] =  $remittance->getApprovalStatus();
                    $remittanceInfo['approved_rejected_at'] = $remittance->getApprovedRejectedAt()->getString();
                    $remittanceInfo['approved_rejected_by'] = $remittance->getApprovedRejectedBy();

                    $remittanceInfo['approved_rejected_by'] = $remittance->getApprovedRejectedBy();
                    $remittanceInfo['approved_rejected_by_name'] = null;
                    if($remittance->getApprovedRejectedBy() != NULL) {
                        if($userApproverRejecter = $this->getAccountService()->getUser(null, $remittance->getApprovedRejectedBy())) {
                            $remittanceInfo['approved_rejected_by_name'] = $userApproverRejecter->getFullName();
                        }
                    }

                    $remittanceInfo['approve_reject_remark'] = $remittance->getApproveRejectRemark();
                    $remittanceInfo['reason'] = $remittance->getReason();
                    $remittanceInfo['pin_number'] = $remittance->getPinNumber();
                    $remittanceInfo['reference_number'] = $remittance->getReferenceNumber();
                    $remittanceInfo['partner_system'] = $remittance->getPartnerSystem();

                    $remittanceInfo['from_country_currency_code'] = $remittance->getInTransaction()->getCountryCurrencyCode();
                    $remittanceInfo['to_country_currency_code'] = $remittance->getOutTransaction()->getCountryCurrencyCode();
                    $remittanceInfo['is_international'] = $remittance->isInternational();

                    $remittanceInfo['country_partner'] = $remittance->getCountryPartner();
                    $remittanceInfo['country_partner_display_rate'] = $remittance->getCountryPartnerDisplayRate();
                    $remittanceInfo['country_partner_profit'] = $remittance->getCountryPartnerProfit();
                    $remittanceInfo['country_partner_status'] = $remittance->getCountryPartnerStatus();

                    AddressNameExtractor::extract($remittance->getSender());

                    $remittanceInfo['sender'] = $remittance->getSender()->getSelectedField(array('id', 'full_name','host_country_code','mobile_no','accountID','host_identity_card','host_address'));
                    $remittanceInfo['sender']['id_type'] = $remittance->getSender()->getAttributes()->hasAttribute('id_type');
                    $remittanceInfo['sender']['nationality'] = $remittance->getSender()->getAttributes()->hasAttribute('nationality');
                    $senderCountry = $countryServ->getCountryInfo($remittanceInfo['sender']['host_country_code']);
                    $remittanceInfo['sender']['country'] = $senderCountry->getName();;
                    $remittanceInfo['sender'][AttributeCode::SOURCE_OF_INCOME] = $remittance->getSender()->getAttributes()->hasAttribute(AttributeCode::SOURCE_OF_INCOME);

                    $remittanceInfo['recipient'] = $remittance->getRecipient()->getSelectedField(array('id', 'user_profile_id', 'recipient_alias', 'recipient_dialing_code', 'recipient_mobile_number', 'host_address'));
                    $remittanceInfo['recipient'][AttributeCode::PURPOSE_OF_REMITTANCE] = $remittance->getRecipient()->getAttributes()->hasAttribute(AttributeCode::PURPOSE_OF_REMITTANCE);
                    $remittanceInfo['recipient'][AttributeCode::RELATIONSHIP_TO_SENDER] = $remittance->getRecipient()->getAttributes()->hasAttribute(AttributeCode::RELATIONSHIP_TO_SENDER);
                    $remittanceInfo['recipient'][AttributeCode::RESIDENTIAL_COUNTRY] = $remittance->getRecipient()->getAttributes()->hasAttribute(AttributeCode::RESIDENTIAL_COUNTRY);
                    $remittanceInfo['recipient'][AttributeCode::FULL_NAME] = $remittance->getRecipient()->getAttributes()->hasAttribute(AttributeCode::FULL_NAME);

                    if( isset($remittanceInfo['recipient'][AttributeCode::RESIDENTIAL_COUNTRY]) )
                    {
                        $remittanceInfo['recipient']['country'] = NULL;
                        if( $recipientCountry = $countryServ->getCountryInfo($remittanceInfo['recipient'][AttributeCode::RESIDENTIAL_COUNTRY]) )
                            $remittanceInfo['recipient']['country'] = $recipientCountry->getName();
                    }

                    $remittanceInfo['recipient']['accountID'] = $remittance->getRecipient()->getRecipientUser()->getAccountID();
                    $remittanceInfo['recipient']['collectionInfo'] = NULL;


                    if($remittance->getCollectionInfo()) {
                        $collection_info = json_decode($remittance->getCollectionInfo(), true);
                        $recipientCollectionInfo = new \StdClass;
                        $recipientCollectionInfo->payment_mode = null;
                        $recipientCollectionInfo->payment_mode_name = null;
                        $recipientCollectionInfo->option = array();
                        $recipientCollectionInfo->payment_mode = $collection_info['payment_code'];

                        if( $recipientCollectionInfo->payment_mode )
                        {

                            if ($recipientCollectionInfo->payment_mode == 'BT1') {
                                
                                if (stristr($remittanceInfo['out_transactionID'],'TR002')) {
                                    
                                    $final_trxIDStr = str_replace('TR002', '', $remittanceInfo['out_transactionID']);
                                    $remittanceInfo['out_transactionID'] = $final_trxIDStr;
                                }
                            }

                            $paymentService = PaymentServiceFactory::build();
                            if( $paymentMode = $paymentService->getPaymentModeInfo($recipientCollectionInfo->payment_mode) )
                            {
                                $recipientCollectionInfo->payment_mode_name = $paymentMode->getName();
                                $recipientCollectionInfo->need_approval = $paymentMode->getNeedApproval();
                            }

                        }

                        if( isset($collection_info['option']) && is_array($collection_info['option'])) {
                            $collection_info['option']['account_no'] = NULL;
                            if(array_key_exists('bank_account', $collection_info['option'])) {
                                $collection_info['option']['account_no'] = $collection_info['option']['bank_account'];
                            }
                            $recipientCollectionInfo->option = json_encode($collection_info['option']);
                        }

                        $remittanceInfo['recipient']['collectionInfo'] = $recipientCollectionInfo;
                    }
                    /*
                                        if( $collection_mode = $remittance->getInTransaction()->getConfirmCollectionMode() )
                                        {
                                            $remittanceInfo['recipient']['collectionInfo'] = $remittance->getRecipient()->getCollectionInfos()->hasPaymentCode($collection_mode);
                                        }
                    */

                    $result->remittance = $remittanceInfo;
                }
                return $result;
            }
        }

        $this->setResponseCode(self::CODE_GET_TRANSACTION_ITEM_HISTORY_FAILED);
        return false ;
    }

    public function _getRemittanceConfigInfo(RemittanceRecord $remittance)
    {
        if( $remittance_config_id = $remittance->getRemittanceConfigurationId() )
        {
            $remConfigServ = RemittanceConfigServiceFactory::build(2);
            if( $remConfig = $remConfigServ->getRemittanceConfigById($remittance_config_id) )
            {
                return $remConfig;
            }
        }

        return false;
    }

    /*public function getListRemittanceTransaction($limit, $page, $transaction_type_id = null, $status_id_str = null,
            $user_profile_id = null, $agent_id = null,
            $start_time = null, $end_time = null)
    {
        if( $collection = $this->getRepository()->findRemittanceTransactionList($limit, $page, $transaction_type_id, $status_id_str, $user_profile_id, $agent_id, $start_time, $end_time) )
        {
            $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_TRANSACTION_SUCCESS);
            return $collection;
        }
        $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_TRANSACTION_FAILED);
        return false;
    }*/



    public function getTransactionByRecipientId($recipientId)
    {
        if( $trxInfo = $this->getRepository()->getTransactionByRecipientId($recipientId) )
        {
            return $trxInfo;
        }
        return false;
    }

    public function void($transaction_id, VoidRemittancePaymentInterface $paymentInterface)
    {
        //find transaction
        if( $trx = $this->getRepository()->findById($transaction_id) )
        {
            switch($trx->getTransactionType()->getCode())
            {
                case TransactionType::CODE_REFUND:
                    return $this->_voidTransaction($trx, $paymentInterface);
                    break;
                default:
                    $this->setResponseCode(MessageCOde::CODE_TRANSACTION_TYPE_IS_NOT_SUPPORTED_FOR_VOID);
                    return false;
                    break;
            }
        }

        $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_TRANSACTION_FAILED);
        return false;
    }

    protected function _voidTransaction(RemittanceTransaction $transaction, VoidRemittancePaymentInterface   $paymentInterface)
    {
        if( $transaction->getStatus()->getCode() == TransactionStatus::COMPLETED )//only completed transaction can be voided
        {
            $transaction->getStatus()->setCode(TransactionStatus::VOID);

            $this->getRepository()->startDBTransaction();
            if( $this->_updateStatus($transaction) )
            {
                //void payment
                if( $paymentInterface->paymentVoid($transaction) )
                {
                    $this->getRepository()->completeDBTransaction();
                    $this->setResponseCode(MessageCode::CODE_TRANSACTION_VOIDED_SUCCESS);
                    return $transaction;
                }
            }

            $this->getRepository()->rollbackDBTransaction();
            $this->setResponseCode(MessageCode::CODE_TRANSACTION_VOIDED_FAILED);
            return false;
        }
    }

    /*
    public function approveTransaction($remittance_id, $attributes)
    {   
        if ($trxInfo = $this->getRepository()->findById($trx->getId())) {
            
            $this->getRepository()->startDBTransaction();

            if ($this->completeTransaction($trx)) {
                
               if ($remi_serv_info = $this->_getRemittanceInfoByTransactionId($trxInfo->getTransactionType()->getCode(), $trxInfo->getId())) {
                        
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
                        if( !$attr_serv->setRemittanceAttribute($remi_serv_info->getId(), $arrtibuteV) )
                        {
                            $this->getRepository()->rollbackDBTransaction();
                            $this->setResponseCode(MessageCode::CODE_EDIT_REMITTANCE_TRANSACTION_FAILED);
                            return false;
                        }
                    }

                    $remittanceSer = RemittanceRecordServiceFactory::build();

                    if (!$remittanceSer->approve($remi_serv_info->getId(),$trx->getRemark())) {
                        $this->getRepository()->rollbackDBTransaction();
                        $this->setResponseCode(MessageCode::CODE_EDIT_REMITTANCE_TRANSACTION_FAILED);
                        return false;
                    }

                    $this->fireLogEvent('iafb_remittance.transaction', AuditLogAction::UPDATE, $trxInfo->getId());
                    $this->getRepository()->completeDBTransaction();
                    $this->setResponseCode(MessageCode::CODE_EDIT_REMITTANCE_TRANSACTION_SUCCESS);
                    return true;
                }
                
            }

            $this->getRepository()->rollbackDBTransaction();
            $this->setResponseCode(MessageCode::CODE_EDIT_REMITTANCE_TRANSACTION_FAILED);
            return false;
            
        }

        $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_TRANSACTION_NOT_FOUND);
        return false;
    }
    */
    
    // public function rejectTransaction($trx, $attributes)
    // {   
    //     /*

    //     Select reject —> change RO status of transaction = Rejected  —> Reject trx (trx status = rejected)  —> Add transaction to refund list:

    //     */
    //     if ($trxInfo = $this->getRepository()->findById($trx->getId())) {
            
    //         $remittanceSer = RemittanceRecordServiceFactory::build();
            
    //         if (RemittanceTransactionValidator::_autoCancel($trx->getTransactionAmount())) {
                
    //             /*
    //             if refund ≤ $999  —> auto cancel & refund amount to Wallet  —> if successful, refund status = Completed. If not successful, refund status = failed  —> Go to refund list screen
    //             */
    //             $this->getRepository()->startDBTransaction();

    //             if ($this->cancelTransaction($trx)) {

    //                 // need refund amount to ewallet.
    //                 // continue....

    //                 if ($remi_serv_info = $this->_getRemittanceInfoByTransactionId($trxInfo->getTransactionType()->getCode(), $trxInfo->getId())) {

    //                     $attr_serv = RemittanceAttributeServiceFactory::build();
    //                     $attr_serv->setUpdatedBy($this->getUpdatedBy());
    //                     $attr_serv->setIpAddress($this->getIpAddress());
    //                     foreach($attributes as $info)
    //                     {
    //                         $arrtibuteV = new AttributeValue();
    //                         foreach ($info as $key => $value) {

    //                             if ($key == 'id') {
    //                                 $arrtibuteV->setId($value);
    //                             }else{
    //                                 $arrtibuteV->getAttribute()->setCode($key);
    //                                 $arrtibuteV->setValue($value);
    //                             }
    //                         }
    //                         //update attributes
    //                         if( !$attr_serv->setRemittanceAttribute($remi_serv_info->getId(), $arrtibuteV) )
    //                         {
    //                             $this->getRepository()->rollbackDBTransaction();
    //                             $this->setResponseCode(MessageCode::CODE_EDIT_REMITTANCE_TRANSACTION_FAILED);
    //                             return false;
    //                         }
    //                     }

                        

    //                     if (!$remittanceSer->reject($remi_serv_info->getId(),$trx->getRemark())) {
    //                         $this->getRepository()->rollbackDBTransaction();
    //                         $this->setResponseCode(MessageCode::CODE_EDIT_REMITTANCE_TRANSACTION_FAILED);
    //                         return false;
    //                     }

    //                     $this->fireLogEvent('iafb_remittance.transaction', AuditLogAction::UPDATE, $trxInfo->getId());
    //                     $this->getRepository()->completeDBTransaction();
    //                     $this->setResponseCode(MessageCode::CODE_EDIT_REMITTANCE_TRANSACTION_SUCCESS);
    //                     return true;
    //                 }
    //             }

    //             $this->getRepository()->rollbackDBTransaction();
    //             $this->setResponseCode(MessageCode::CODE_EDIT_REMITTANCE_TRANSACTION_FAILED);
    //             return false;

    //         }else{

    //             /*
    //             if refund >$999  —> refund status = pending  —> pop up alert in same screen as confirm reject screen: “Please arrange refund arrangements with <sender name>.”  —> Go to refund list screen 
    //             */
    //             $this->getRepository()->startDBTransaction();

    //             if ($this->cancelTransaction($trx)) {

    //                 // need go to refund amount list and  
    //                 if ($remi_serv_info = $this->_getRemittanceInfoByTransactionId($trxInfo->getTransactionType()->getCode(), $trxInfo->getId())) {

    //                     if (!$remittanceSer->reject($remi_serv_info->getId(),$trx->getRemark())) {
    //                         $this->getRepository()->rollbackDBTransaction();
    //                         $this->setResponseCode(MessageCode::CODE_EDIT_REMITTANCE_TRANSACTION_FAILED);
    //                         return false;
    //                     }

    //                     $this->fireLogEvent('iafb_remittance.transaction', AuditLogAction::UPDATE, $trxInfo->getId());
    //                     $this->getRepository()->completeDBTransaction();
    //                     $this->setResponseCode(MessageCode::CODE_EDIT_REMITTANCE_TRANSACTION_SUCCESS);
    //                     return true;
    //                 }

    //             }
    //             $this->getRepository()->rollbackDBTransaction();
    //             $this->setResponseCode(MessageCode::CODE_EDIT_REMITTANCE_TRANSACTION_FAILED);
    //             return false;

    //         }

    //     }

    //     $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_TRANSACTION_NOT_FOUND);
    //     return false;
    // }

    protected function _getRemittanceInfoByTransactionId($trxType, $trxID)
    {
        $remi_serv_info = NULL;
        $remi_serv = RemittanceRecordServiceFactory::build();

        if ($trxType == TransactionType::CODE_CASH_IN OR
            $trxType == TransactionType::CODE_LOCAL_CASH_IN) {
            if (!$remi_serv_info = $remi_serv->getRepository()->findByInTransactionId($trxID)) {

                $this->getRepository()->rollbackDBTransaction();
                $this->setResponseCode(MessageCode::CODE_REMITTANCE_SERVICE_NOT_EXIST);
                return false;
            }
        }else if ($trxType == TransactionType::CODE_CASH_OUT OR
                  $trxType == TransactionType::CODE_LOCAL_CASH_OUT) {
            if (!$remi_serv_info = $remi_serv->getRepository()->findByOutTransactionId($trxID)) {
                
                $this->getRepository()->rollbackDBTransaction();
                $this->setResponseCode(MessageCode::CODE_REMITTANCE_SERVICE_NOT_EXIST);
                return false;
            }
        }
        return $remi_serv_info;
    }

    public function getRemittanceInfoByOutTransactionId($transaction_id)
    {
        $remi_serv = RemittanceRecordServiceFactory::build();
        $recipient_serv = RecipientServiceFactory::build();

        if ($remi_serv_info = $remi_serv->getRepository()->findByOutTransactionId($transaction_id)) {
            
            if ($recipientInfo = $recipient_serv->getRecipient($remi_serv_info->getRecipient()->getId())) {
                
                $remi_serv_info->setRecipient($recipientInfo);
            }
            
            return $remi_serv_info;
        }

        return false;
    }
//    public function insertRemittanceTransaction(RemittanceTransaction $transaction, RemittanceTransactionItemCollection $transactionItem)
//    {
//        if( $transaction = $this->createdTransaction(
//                $transaction->getTransactionID(),
//                $transaction->getTransactionType(),
//                $transaction->get
//                $user_profile_id, $corp_serv) )
//        
//    }


}