<?php

namespace Iapps\RemittanceService\RemittanceRecord;

use Iapps\Common\Microservice\AccountService\User;
use Iapps\Common\CommunicationService\CommunicationServiceProducer;
use Iapps\Common\Core\IappsBaseEntity;
use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\CorporateService\CorporateService;
use Iapps\Common\Helper\GuidGenerator;
use Iapps\Common\Microservice\PaymentService\CountryCurrency;
use Iapps\Common\SystemCode\SystemCode;
use Iapps\Common\Transaction\TransactionStatus;
use Iapps\RemittanceService\Common\SystemCodeServiceFactory;
use Iapps\RemittanceService\Recipient\Recipient;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfig;
use Iapps\RemittanceService\RemittanceTransaction\ItemType;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransaction;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransactionItem;
use Iapps\RemittanceService\ValueObject\EncryptedFieldFactory;
use Iapps\RemittanceService\Attribute\AttributeCode;
use Iapps\RemittanceService\Attribute\RemittanceAttributeCollection;

class RemittanceRecord extends IappsBaseEntity{

    protected $in_transaction;
    protected $out_transaction;
    protected $remittanceID;
    protected $sender;
    protected $recipient;   
    protected $remittance_configuration;
    protected $in_exchange_rate_id;
    protected $out_exchange_rate_id;
    protected $display_rate;
    protected $from_amount;
    protected $to_amount;
    protected $status;
    protected $paid_at;
    protected $payment_request_id;
    protected $collected_at;
    protected $collection_request_id;
    protected $collection_info;
    protected $approval_required;
    protected $approval_status;
    protected $approved_rejected_at;
    protected $approved_rejected_by;    
    protected $approve_reject_remark;

    protected $is_face_to_face_trans;
    protected $is_face_to_face_recipient;
    protected $is_home_collection;
    protected $lat;
    protected $lon;
	protected $is_nff;

    protected $scheduled_by_user;
    protected $scheduled_at;

    protected $in_country_currency_code;
    protected $out_country_currency_code;
    protected $from_currency_code;
    protected $to_currency_code;
    protected $from_country_code;
    protected $to_country_code;
    protected $in_transaction_type;
    protected $out_transaction_type;
    protected $from_country_name;
    protected $to_country_name;
    protected $sender_user_name;
    protected $recipient_user_name;
    protected $recipient_user_profile_id;
    protected $source_of_funds;
    protected $bank_info;
    protected $collection_mode;
    protected $payment_mode;
    protected $fees_charged;
    protected $discount;


    protected $user_as_recipient = FALSE;
    protected $transaction;
    protected $transactionID;
    protected $in_transactionID;
    protected $out_transactionID;
    protected $remittance_status;
    protected $in_transaction_status;
    protected $out_transaction_status;
    protected $in_transaction_expired_at;
    protected $reason;
    protected $pin_number;
    protected $reference_number;
    protected $partner_system;
    protected $remittance_purpose;

    protected $country_partner;
    protected $country_partner_display_rate;
    protected $country_partner_profit;
    protected $country_partner_status;
    protected $in_service_provider_id;
    protected $out_service_provider_id;
    protected $prelim_check_status;
    
    protected $attributes;
            
    function __construct()
    {
        parent::__construct();

        $this->status = new SystemCode();
        $this->paid_at = new IappsDateTime();
        $this->approved_rejected_at = new IappsDateTime();
        $this->approved_rejected_by = new User();
        $this->collected_at = new IappsDateTime();
        $this->recipient = new Recipient();
        $this->sender = new User();
        $this->in_transaction = new RemittanceTransaction();
        $this->out_transaction = new RemittanceTransaction();
        $this->transaction = new RemittanceTransaction();
        $this->remittance_configuration = new RemittanceConfig();
        $this->in_transaction_expired_at = new IappsDateTime();

        $this->scheduled_by_user = new User();
        $this->scheduled_at = new IappsDateTime();

        $this->collection_info = EncryptedFieldFactory::build();
        $this->attributes = new RemittanceAttributeCollection();
    }

    /**
     * @return mixed
     */
    public function getRemittancePurpose()
    {
        return $this->remittance_purpose;
    }

    /**
     * @param mixed $remittance_purpose
     */
    public function setRemittancePurpose($remittance_purpose)
    {
        $this->remittance_purpose = $remittance_purpose;
    }

    /**
     * @return mixed
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * @param mixed $reason
     */
    public function setReason($reason)
    {
        $this->reason = $reason;
    }

    /**
     * @return mixed
     */
    public function getRemittanceStatus()
    {
        return $this->remittance_status;
    }

    /**
     * @param mixed $remittance_status
     */
    public function setRemittanceStatus($remittance_status)
    {
        $this->remittance_status = $remittance_status;
    }

    /**
     * @return mixed
     */
    public function getInTransactionStatus()
    {
        return $this->in_transaction_status;
    }

    /**
     * @param mixed $in_transaction_status
     */
    public function setInTransactionStatus($in_transaction_status)
    {
        $this->in_transaction_status = $in_transaction_status;
    }

    /**
     * @return mixed
     */
    public function getOutTransactionStatus()
    {
        return $this->out_transaction_status;
    }


    /**
     * @param mixed $out_transaction_status
     */
    public function setOutTransactionStatus($out_transaction_status)
    {
        $this->out_transaction_status = $out_transaction_status;
    }

    public function setTransaction(RemittanceTransaction $transaction)
    {
        $this->transaction = $transaction;
        return $this;
    }

    public function getTransaction()
    {
        return $this->transaction;
    }

    public function setInTransaction(RemittanceTransaction $transaction)
    {
        $this->in_transaction = $transaction;
        return $this;
    }

    public function getInTransaction()
    {
        return $this->in_transaction;
    }

    public function setOutTransaction(RemittanceTransaction $transaction)
    {
        $this->out_transaction = $transaction;
        return $this;
    }

    public function getOutTransaction()
    {
        return $this->out_transaction;
    }

    /**
     * @return mixed
     */
    public function getFeesCharged()
    {
        return $this->fees_charged;
    }

    /**
     * @param mixed $fees_charged
     */
    public function setFeesCharged($fees_charged)
    {
        $this->fees_charged = $fees_charged;
    }


    public function getDiscount()
    {
        return $this->discount;
    }

    public function setDiscount($discount)
    {
        $this->discount = $discount;
    }


    /**
     * @return mixed
     */
    public function getCollectionMode()
    {
        return $this->collection_mode;
    }

    /**
     * @param mixed $collection_mode
     */
    public function setCollectionMode($collection_mode)
    {
        $this->collection_mode = $collection_mode;
    }

    public function getPaymentMode()
    {
        return $this->payment_mode;
    }

    public function setPaymentMode($payment_mode)
    {
        $this->payment_mode = $payment_mode;
    }

    /**
     * @return mixed
     */
    public function getSourceOfFunds()
    {
        return $this->source_of_funds;
    }

    /**
     * @param mixed $source_of_funds
     */
    public function setSourceOfFunds($source_of_funds)
    {
        $this->source_of_funds = $source_of_funds;
    }

    /**
     * @return mixed
     */
    public function getBankInfo()
    {
        return $this->bank_info;
    }

    /**
     * @param mixed $bank_info
     */
    public function setBankInfo($bank_info)
    {
        $this->bank_info = $bank_info;
    }

    /**
     * @return mixed
     */
    public function getRecipientUserProfileId()
    {
        return $this->recipient_user_profile_id;
    }

    /**
     * @param mixed $recipient_user_profile_id
     */
    public function setRecipientUserProfileId($recipient_user_profile_id)
    {
        $this->recipient_user_profile_id = $recipient_user_profile_id;
    }

    /**
     * @return mixed
     */
    public function getSenderUserName()
    {
        return $this->sender_user_name;
    }

    /**
     * @param mixed $sender_user_name
     */
    public function setSenderUserName($sender_user_name)
    {
        $this->sender_user_name = $sender_user_name;
    }

    /**
     * @return mixed
     */
    public function getRecipientUserName()
    {
        return $this->recipient_user_name;
    }

    /**
     * @param mixed $recipient_user_name
     */
    public function setRecipientUserName($recipient_user_name)
    {
        $this->recipient_user_name = $recipient_user_name;
    }

    /**
     * @return mixed
     */
    public function getRelationshipToSender()
    {
        return $this->relationship_to_sender;
    }
    /**
     * @param mixed $relationship_to_sender
     */
    public function setRelationshipToSender($relationship_to_sender)
    {
        $this->relationship_to_sender = $relationship_to_sender;
    }

    protected $relationship_to_sender;

    /**
     * @return mixed
     */
    public function getFromCountryName()
    {
        return $this->from_country_name;
    }

    /**
     * @param mixed $from_country_name
     */
    public function setFromCountryName($from_country_name)
    {
        $this->from_country_name = $from_country_name;
    }

    /**
     * @return mixed
     */
    public function getToCountryName()
    {
        return $this->to_country_name;
    }

    /**
     * @param mixed $to_country_name
     */
    public function setToCountryName($to_country_name)
    {
        $this->to_country_name = $to_country_name;
    }

    /**
     * @return mixed
     */
    public function getInTransactionType()
    {
        return $this->in_transaction_type;
    }

    /**
     * @param mixed $in_transaction_type
     */
    public function setInTransactionType($in_transaction_type)
    {
        $this->in_transaction_type = $in_transaction_type;
    }

    /**
     * @return mixed
     */
    public function getOutTransactionType()
    {
        return $this->out_transaction_type;
    }

    /**
     * @param mixed $out_transaction_type
     */
    public function setOutTransactionType($out_transaction_type)
    {
        $this->out_transaction_type = $out_transaction_type;
    }

    /**
     * @return mixed
     */
    public function getFromCurrencyCode()
    {
        return $this->from_currency_code;
    }

    /**
     * @param mixed $from_currency_code
     */
    public function setFromCurrencyCode($from_currency_code)
    {
        $this->from_currency_code = $from_currency_code;
    }

    /**
     * @return mixed
     */
    public function getToCurrencyCode()
    {
        return $this->to_currency_code;
    }

    /**
     * @param mixed $to_currency_code
     */
    public function setToCurrencyCode($to_currency_code)
    {
        $this->to_currency_code = $to_currency_code;
    }

    /**
     * @return mixed
     */
    public function getFromCountryCode()
    {
        return $this->from_country_code;
    }

    /**
     * @param mixed $from_country_code
     */
    public function setFromCountryCode($from_country_code)
    {
        $this->from_country_code = $from_country_code;
    }

    /**
     * @return mixed
     */
    public function getToCountryCode()
    {
        return $this->to_country_code;
    }

    /**
     * @param mixed $to_country_code
     */
    public function setToCountryCode($to_country_code)
    {
        $this->to_country_code = $to_country_code;
    }


    /**
     * @return mixed
     */
    public function getInCountryCurrencyCode()
    {
        return $this->in_country_currency_code;
    }

    /**
     * @param mixed $in_country_currency_code
     */
    public function setInCountryCurrencyCode($in_country_currency_code)
    {
        $this->in_country_currency_code = $in_country_currency_code;
    }

    /**
     * @return mixed
     */
    public function getOutCountryCurrencyCode()
    {
        return $this->out_country_currency_code;
    }

    /**
     * @param mixed $out_country_currency_code
     */
    public function setOutCountryCurrencyCode($out_country_currency_code)
    {
        $this->out_country_currency_code = $out_country_currency_code;
    }

    public function getTransactionIdGUID()
    {
        return $this->transaction->getId();
    }

    public function setTransactionIdGUID($transaction_id)
    {
        $this->transaction->setId($transaction_id);
        return $this;
    }

    public function setInTransactionId($in_transaction_id)
    {
        $this->in_transaction->setId($in_transaction_id);
        return $this;
    }

    public function getInTransactionId()
    {
        return $this->in_transaction->getId();
    }

    public function setOutTransactionId($out_transaction_id)
    {
        $this->out_transaction->setId($out_transaction_id);
        return $this;
    }

    public function getOutTransactionId()
    {
        return $this->out_transaction->getId();
    }

    public function setRemittanceID($remittanceID)
    {
        $this->remittanceID = $remittanceID;
        return $this;
    }

    public function getRemittanceID()
    {
        return $this->remittanceID;
    }

    public function setSender(User $sender)
    {
        $this->sender = $sender;
        return $this;
    }

    /**
     * 
     * @return User
     */
    public function getSender()
    {
        return $this->sender;
    }

    public function setSenderUserProfileId($sender_user_profile_id)
    {
        $this->sender->setId($sender_user_profile_id);
        return $this;
    }

    public function getSenderUserProfileId()
    {
        return $this->sender->getId();
    }

    public function setRecipient(Recipient $recipient)
    {
        $this->recipient = $recipient;
        return $this;
    }

    /**
     * 
     * @return Recipient
     */
    public function getRecipient()
    {
        return $this->recipient;
    }

    public function setRemittanceConfigurationId($remittance_configuration_id)
    {
        $this->getRemittanceConfiguration()->setId($remittance_configuration_id);
        return $this;
    }

    public function getRemittanceConfigurationId()
    {
        return $this->getRemittanceConfiguration()->getId();
    }

    public function setRemittanceConfiguration(RemittanceConfig $remittanceConfig)
    {
        $this->remittance_configuration = $remittanceConfig;
        return $this;
    }

    public function getRemittanceConfiguration()
    {
        return $this->remittance_configuration;
    }

    public function setInExchangeRateId($in_exchange_rate_id)
    {
        $this->in_exchange_rate_id = $in_exchange_rate_id;
        return $this;
    }

    public function getInExchangeRateId()
    {
        return $this->in_exchange_rate_id;
    }

    public function setOutExchangeRateId($out_exchange_rate_id)
    {
        $this->out_exchange_rate_id = $out_exchange_rate_id;
        return $this;
    }

    public function getOutExchangeRateId()
    {
        return $this->out_exchange_rate_id;
    }


    public function setDisplayRate($display_rate)
    {
        $this->display_rate = $display_rate;
        return $this;
    }

    public function getDisplayRate()
    {
        return $this->display_rate;
    }

    public function setFromAmount($from_amount)
    {
        $this->from_amount = $from_amount;
        return $this;
    }

    public function getFromAmount()
    {
        return $this->from_amount;
    }

    public function setToAmount($to_amount)
    {
        $this->to_amount = $to_amount;
        return $this;
    }

    public function getToAmount()
    {
        return $this->to_amount;
    }

    public function setStatus(SystemCode $code)
    {
        $this->status = $code;
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setPaidAt(IappsDateTime $paid_at)
    {
        $this->paid_at = $paid_at;
        return $this;
    }

    public function getPaidAt()
    {
        return $this->paid_at;
    }

    public function setPayMentRequestId($payment_request_id)
    {
        $this->payment_request_id = $payment_request_id;
        return $this;
    }

    public function getPayMentRequestId()
    {
        return $this->payment_request_id;
    }

    public function setCollectedAt(IappsDateTime $collected_at)
    {
        $this->collected_at = $collected_at;
        return $this;
    }

    public function getCollectedAt()
    {
        return $this->collected_at;
    }

    public function setCollectionRequestId($collection_request_id)
    {
        $this->collection_request_id = $collection_request_id;
        return $this;
    }

    public function getCollectionRequestId()
    {
        return $this->collection_request_id;
    }

    public function setCollectionInfo($collection_info)
    {
        $this->collection_info->setValue($collection_info);
        return $this;
    }

    public function getCollectionInfo($isValue = true)
    {
        if ($isValue)
            return $this->collection_info->getValue();
        else
            return $this->collection_info;
    }

    public function setApprovalRequired($approval_required)
    {
        $this->approval_required = $approval_required;
        return $this;
    }

    public function getApprovalRequired()
    {
        return $this->approval_required;
    }

    public function isApprovalRequired()
    {
        return $this->getApprovalRequired() == 1;
    }

    public function setApprovalStatus($approval_status)
    {
        $this->approval_status = $approval_status;
        return $this;
    }

    public function getApprovalStatus()
    {
        return $this->approval_status;
    }

    public function setApprovedRejectedAt(IappsDateTime $approved_rejected_at)
    {
        $this->approved_rejected_at = $approved_rejected_at;
        return $this;
    }

    public function getApprovedRejectedAt()
    {
        return $this->approved_rejected_at;
    }

    public function setApprovedRejectedBy($approved_rejected_by)
    {
        $this->getApprovedRejectedByUser()->setId($approved_rejected_by);
        return $this;
    }

    public function getApprovedRejectedBy()
    {
        return $this->approved_rejected_by->getId();
    }
    
    public function setApprovedRejectedByUser(User $approved_rejected_by)
    {
        $this->approved_rejected_by = $approved_rejected_by;
        return $this;
    }

    public function getApprovedRejectedByUser()
    {
        return $this->approved_rejected_by;
    }

    public function setApproveRejectRemark($approve_reject_remark)
    {
        $this->approve_reject_remark = $approve_reject_remark;
        return $this;
    }

    public function getApproveRejectRemark()
    {
        return $this->approve_reject_remark;
    }

    public function setIsFaceToFaceTrans($is_face_to_face_trans)
    {
        $this->is_face_to_face_trans = $is_face_to_face_trans;
        return $this;
    }

    public function getIsFaceToFaceTrans()
    {
        return $this->is_face_to_face_trans;
    }

    public function setIsFaceToFaceRecipient($is_face_to_face_recipient)
    {
        $this->is_face_to_face_recipient = $is_face_to_face_recipient;
        return $this;
    }

    public function getIsFaceToFaceRecipient()
    {
        return $this->is_face_to_face_recipient;
    }

    public function setIsHomeCollection($is_home_collection)
    {
        $this->is_home_collection = $is_home_collection;
        return $this;
    }

    public function getIsHomeCollection()
    {
        return $this->is_home_collection;
    }

    public function setLat($lat)
    {
        $this->lat = $lat;
        return $this;
    }

    public function getLat()
    {
        return $this->lat;
    }

    public function setLon($lon)
    {
        $this->lon = $lon;
        return $this;
    }

    public function getLon()
    {
        return $this->lon;
    }
	
	public function setIsNFF($is_nff)
    {
        $this->is_nff = $is_nff;
        return $this;
    }

    public function getIsNFF()
    {
        return $this->is_nff;
    }

    public function setScheduledByUser(User $scheduledByUser)
    {
        $this->scheduled_by_user = $scheduledByUser;
        return $this;
    }

    public function getScheduledByUser()
    {
        return $this->scheduled_by_user;
    }

    public function setScheduledAt(IappsDateTime $scheduledAt)
    {
        $this->scheduled_at = $scheduledAt;
        return $this;
    }

    public function getScheduledAt()
    {
        return $this->scheduled_at;
    }

    public function setUserAsRecipient($user_as_recipient)
    {
        $this->user_as_recipient = $user_as_recipient;
        return $this;
    }

    public function getUserAsRecipient()
    {
        return $this->user_as_recipient;
    }

    public function setTransactionID($transactionID)
    {
        $this->transactionID = $transactionID;
        return $this;
    }

    public function getTransactionID()
    {
        return $this->transactionID;
    }

    public function setInTransactionIDString($transactionID)
    {
        $this->in_transactionID = $transactionID;
        return $this;
    }

    public function getInTransactionIDString()
    {
        return $this->in_transactionID;
    }

    public function setOutTransactionIDString($transactionID)
    {
        $this->out_transactionID = $transactionID;
        return $this;
    }

    public function getOutTransactionIDString()
    {
        return $this->out_transactionID;
    }

    public function setInTransactionExpiredAt(IappsDateTime $in_transaction_expired_at)
    {
        $this->in_transaction_expired_at = $in_transaction_expired_at;
        return $this;
    }

    public function getInTransactionExpiredAt()
    {
        return $this->in_transaction_expired_at;
    }
    
    public function setAttributes(RemittanceAttributeCollection $attributes)
    {
        $this->attributes = $attributes;
        return $this;
    }

    /**
     * 
     * @return RemittanceAttributeCollection
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @return mixed
     */
    public function getPinNumber()
    {
        return $this->pin_number;
    }

    /**
     * @param mixed $pin_number
     */
    public function setPinNumber($pin_number)
    {
        $this->pin_number = $pin_number;
    }

    /**
     * @return mixed
     */
    public function getReferenceNumber()
    {
        return $this->reference_number;
    }

    /**
     * @param mixed $reference_number
     */
    public function setReferenceNumber($reference_number)
    {
        $this->reference_number = $reference_number;
    }

    /**
     * @return mixed
     */
    public function getPartnerSystem()
    {
        return $this->partner_system;
    }

    /**
     * @param mixed $partner_system
     */
    public function setPartnerSystem($partner_system)
    {
        $this->partner_system = $partner_system;
    }

    public function getCountryPartner()
    {
        return $this->country_partner;
    }
    public function setCountryPartner($country_partner)
    {
        $this->country_partner = $country_partner;
    }
    public function getCountryPartnerDisplayRate()
    {
        return $this->country_partner_display_rate;
    }
    public function setCountryPartnerDisplayRate($country_partner_display_rate)
    {
        $this->country_partner_display_rate = $country_partner_display_rate;
    }
    public function getCountryPartnerProfit()
    {
        return $this->country_partner_profit;
    }
    public function setCountryPartnerProfit($country_partner_profit)
    {
        $this->country_partner_profit = $country_partner_profit;
    }
    public function getCountryPartnerStatus()
    {
        return $this->country_partner_status;
    }
    public function setCountryPartnerStatus($country_partner_status)
    {
        $this->country_partner_status = $country_partner_status;
    }
    public function getInServiceProviderId()
    {
        return $this->in_service_provider_id;
    }
    public function setInServiceProviderId($in_service_provider_id)
    {
        $this->in_service_provider_id = $in_service_provider_id;
    }
    public function getOutServiceProviderId()
    {
        return $this->out_service_provider_id;
    }
    public function setOutServiceProviderId($out_service_provider_id)
    {
        $this->out_service_provider_id = $out_service_provider_id;
    }

    public function getPrelimCheckStatus()
    {
        return $this->prelim_check_status;
    }
    public function setPrelimCheckStatus($prelim_check_status)
    {
        $this->prelim_check_status = $prelim_check_status;
    }

    public function jsonSerialize()
    {
        $json = parent::jsonSerialize();

        $json['in_transaction_id'] = $this->getInTransactionId();
        $json['out_transaction_id'] = $this->getOutTransactionId();
        $json['remittanceID'] = $this->getRemittanceID();
        $json['sender_user_profile_id'] = $this->getSenderUserProfileId();
        $json['recipient_id'] = $this->getRecipient()->getId();
        $json['remittance_configuration_id'] = $this->getRemittanceConfigurationId();
        $json['in_exchange_rate_id'] = $this->getInExchangeRateId();
        $json['out_exchange_rate_id'] = $this->getOutExchangeRateId();
        $json['display_rate'] = $this->getDisplayRate();
        $json['from_amount'] = $this->getFromAmount();
        $json['to_amount'] = $this->getToAmount();
        $json['status'] = $this->getStatus()->getCode();
        $json['paid_at'] = $this->getPaidAt()->getString();
        $json['payment_request_id'] = $this->getPayMentRequestId();
        $json['collected_at'] = $this->getCollectedAt()->getString();
        $json['collection_request_id'] = $this->getCollectionRequestId();
        $json['approval_required'] = $this->getApprovalRequired();
        $json['approval_status'] = $this->getApprovalStatus();
        $json['approved_rejected_at'] = $this->getApprovedRejectedAt()->getString();
        $json['approved_rejected_by'] = $this->getApprovedRejectedBy();
        $json['approve_reject_remark'] = $this->getApproveRejectRemark();
        $json['pin_number'] = $this->getPinNumber();
        $json['reference_number'] = $this->getReferenceNumber();
        $json['partner_system'] = $this->getPartnerSystem();

        $json['in_country_currency_code'] = $this->getInCountryCurrencyCode();
        $json['out_country_currency_code'] = $this->getOutCountryCurrencyCode();
        $json['from_currency_code'] = $this->getFromCurrencyCode();
        $json['to_currency_code'] = $this->getToCurrencyCode();
        $json['from_country_code'] = $this->getFromCountryCode();
        $json['to_country_code'] = $this->getToCountryCode();
        $json['in_transaction_type'] = $this->getInTransactionType();
        $json['out_transaction_type'] = $this->getOutTransactionType();
        $json['from_country_name'] = $this->getFromCountryName();
        $json['to_country_name'] = $this->getToCountryName();
        $json['relationship_to_sender'] = $this->getRelationshipToSender();
        $json['sender_user_name'] = $this->getSenderUserName();
        $json['recipient_user_name'] = $this->getRecipientUserName();
        $json['recipient_user_profile_id'] = $this->getRecipientUserProfileId();
        $json['source_of_income'] = $this->getSourceOfFunds();
        $json['bank_info'] = $this->getBankInfo();
        $json['collection_mode'] = $this->getCollectionMode();
        $json['payment_mode'] = $this->getPaymentMode();
        $json['fees_charged'] = $this->getFeesCharged();
        $json['discount'] = $this->getDiscount();
        $json['final_fee'] = $this->getFeesCharged() - abs($this->getDiscount());

        $json['user_as_recipient'] = $this->getUserAsRecipient();
        $json['transactionID'] = $this->getTransactionID();
        $json['in_transactionID'] = $this->getInTransactionIDString();
        $json['out_transactionID'] = $this->getOutTransactionIDString();
        $json['in_transaction_expired_at'] = $this->getInTransactionExpiredAt()->getString();
        $json['remittance_status'] = $this->getRemittanceStatus();
        $json['in_transaction_status'] = $this->getInTransactionStatus();
        $json['out_transaction_status'] = $this->getOutTransactionStatus();
        $json['reason'] = $this->getReason();
        $json['transaction_id'] = $this->getTransactionIdGUID();
        $json['collection_info'] = $this->getCollectionInfo();
        $json['remittance_purpose'] = $this->getRemittancePurpose();

        $json['is_face_to_face_trans'] = (bool)$this->getIsFaceToFaceTrans();
        $json['is_face_to_face_recipient'] = (bool)$this->getIsFaceToFaceRecipient();
        $json['is_home_collection'] = (bool)$this->getIsHomeCollection();
        $json['lat'] = $this->getLat();
        $json['lon'] = $this->getLon();
        $json['is_nff'] = $this->getIsNFF();

        $json['scheduled_by'] = $this->getScheduledByUser()->getId();
        $json['scheduled_by_name'] = $this->getScheduledByUser()->getName();
        $json['scheduled_by_accountID'] = $this->getScheduledByUser()->getAccountID();
        $json['scheduled_at'] = $this->getScheduledAt()->getString();

        $json['country_partner'] = $this->getCountryPartner();
        $json['country_partner_display_rate'] = $this->getCountryPartnerDisplayRate();
        $json['country_partner_profit'] = $this->getCountryPartnerProfit();
        $json['country_partner_status'] = $this->getCountryPartnerStatus();
        $json['recipient_residing_country_code'] = $this->getRecipient()->getAttributes()->hasAttribute(AttributeCode::RESIDENTIAL_COUNTRY);
        $json['in_service_provider_id'] = $this->getInServiceProviderId();
        $json['out_service_provider_id'] = $this->getOutServiceProviderId();
        $json['prelim_check_status'] = $this->getPrelimCheckStatus();

        return $json;
    }

    public function generateCashInTransaction(RemittanceFeeCalculator $calculator, $transactionID, $remark)
    {
        $systemCodeServ = SystemCodeServiceFactory::build();
        $cashin_trx = new RemittanceTransaction();
        $cashin_trx->setId(GuidGenerator::generate());
        if( !$trxType = $systemCodeServ->getById($calculator->getRemittanceConfig()->getInCorporateService()->getTransactionTypeId()) )
            return false;
        $cashin_trx->setTransactionType($trxType);
        //$cashin_trx->getTransactionType()->setId($calculator->getRemittanceConfig()->getInCorporateService()->getTransactionTypeId());
        $cashin_trx->setTransactionID($transactionID);
        $cashin_trx->setUserProfileId($this->getSenderUserProfileId());
        $cashin_trx->getStatus()->setCode(TransactionStatus::CONFIRMED);
        $cashin_trx->setCountryCurrencyCode($calculator->getRemittanceConfig()->getRemittanceService()->getFromCountryCurrencyCode());
        $cashin_trx->setRemark($remark);
        $cashin_trx->setDescription('To: ' . $this->getRecipient()->getRecipientAlias());
        $cashin_trx->setConfirmPaymentCode($calculator->getPaymentMode());
        $cashin_trx->setConfirmCollectionMode($calculator->getCollectionMode());

        foreach($calculator->getTransactionItems() AS $item)
        {
            $cashin_trx->addItem($item);
        }

        foreach($calculator->getProfitCostItems() AS $item)
        {
            $cashin_trx->addProfitCostItem($item);
        }

        $this->setInTransaction($cashin_trx);
        return $cashin_trx;
    }

    public function generateCashOutTransaction(RemittanceFeeCalculator $calculator, $transactionID, $remark)
    {
        $systemCodeServ = SystemCodeServiceFactory::build();
        $cashout_trx = new RemittanceTransaction();
        $cashout_trx->setId(GuidGenerator::generate());
        if( !$trxType = $systemCodeServ->getById($calculator->getRemittanceConfig()->getOutCorporateService()->getTransactionTypeId()) )
            return false;
        $cashout_trx->setTransactionType($trxType);
        //$cashout_trx->getTransactionType()->setId($calculator->getRemittanceConfig()->getOutCorporateService()->getTransactionTypeId());
        $cashout_trx->setTransactionID($transactionID);
        $cashout_trx->setUserProfileId($this->getRecipient()->getRecipientUserProfileId());
        $cashout_trx->setRecipientId($this->getRecipient()->getId());
        $cashout_trx->getStatus()->setCode(TransactionStatus::INITIATED);
        $cashout_trx->setCountryCurrencyCode($calculator->getRemittanceConfig()->getRemittanceService()->getToCountryCurrencyCode());
        $cashout_trx->setDescription('From: ' . $this->getSender()->getName());
        $cashout_trx->setRemark($remark);
        $cashout_trx->setConfirmPaymentCode($calculator->getCollectionMode());

        $cashout_trxItem = new RemittanceTransactionItem();
        $cashout_trxItem->setId(GuidGenerator::generate());
        $cashout_trxItem->getItemType()->setCode(ItemType::CORPORATE_SERVICE);
        $cashout_trxItem->setItemId($calculator->getRemittanceConfig()->getOutCorporateService()->getId());
        $cashout_trxItem->setName($calculator->getRemittanceConfig()->getOutCorporateService()->getName());
        $cashout_trxItem->setDescription($calculator->getDescription()->toJson());
        $cashout_trxItem->setUnitPrice(-1*$this->getToAmount());

        $cashout_trx->addItem($cashout_trxItem);

        $this->setOutTransaction($cashout_trx);
        return $cashout_trx;
    }

    public function cashedIn()
    {
        $this->getStatus()->setCode(RemittanceStatus::CASHED_IN);
        $this->setPaidAt(IappsDateTime::now());
        return $this;
    }

    public function cashedOut()
    {
        $this->getStatus()->setCode(RemittanceStatus::CASHED_OUT);
        $this->setRedeemedAt(IappsDateTime::now());
        return $this;
    }

    public function notifySender(User $sender, RemittanceTransaction $cashout_trx, CountryCurrency $out_cc, $template)
    {
        if( $sender->getEmail() != NULL )
        {
            $template = str_replace("[NAME]", $sender->getName(), $template);
            $template = str_replace("[CURRENCY]", $out_cc->getCurrencyInfo()->getSymbol(), $template);
            $template = str_replace("[AMOUNT]", $this->getToAmount(), $template);
            $template = str_replace("[ALIAS]", $this->getRecipient()->getRecipientAlias(), $template);

            $com_serv = new CommunicationServiceProducer();
            $com_serv->sendEmail(1, 'You have purchased a Remittance Successfully', $template, $template, array($sender->getEmail()));
        }
    }

    public function notifyReceiver(User $sender, User $receiver, RemittanceTransaction $cashout_trx, CountryCurrency $out_cc, $template)
    {
        if( $receiver->getEmail() != NULL )
        {
            $template = str_replace("[NAME]", $receiver->getName(), $template);
            $template = str_replace("[SENDER]", $sender->getName(), $template);
            $template = str_replace("[CURRENCY]", $out_cc->getCurrencyInfo()->getSymbol(), $template);
            $template = str_replace("[AMOUNT]", $this->getToAmount(), $template);
            $template = str_replace("[REMARK]", $cashout_trx->getRemark(), $template);

            $com_serv = new CommunicationServiceProducer();
            $com_serv->sendEmail(1, 'You have purchased a Remittance Successfully', $template, $template, array($receiver->getEmail()));
        }
    }

    public function isInternational()
    {
        return ($this->getInTransaction()->getCountryCurrencyCode() != $this->getOutTransaction()->getCountryCurrencyCode());
    }

    public function getCashOutExpiryDate()
    {
        return $this->getOutTransaction()->getExpiredDate();
    }

    public function getCashOutExpiryPeriodInDay()
    {
        if( !$this->getCashOutExpiryDate()->isNull() )
        {
            $periodInSeconds = $this->getCashOutExpiryDate()->getUnix() - $this->getOutTransaction()->getCreatedAt()->getUnix();
            $periodInDays = round($periodInSeconds/86400);
            return $periodInDays;
        }

        return 0;
    }
}