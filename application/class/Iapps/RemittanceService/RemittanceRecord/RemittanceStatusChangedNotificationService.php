<?php

namespace  Iapps\RemittanceService\RemittanceRecord;

use Iapps\Common\ChatService\ChatServiceProducer;
use Iapps\Common\ChatService\NotificationChannel;
use Iapps\Common\ChatService\NotificationTag;
use Iapps\Common\CommunicationService\CommunicationServiceProducer;
use Iapps\Common\Helper\CurrencyFormatter;
use Iapps\Common\Helper\MessageBroker\BroadcastEventConsumer;
use Iapps\Common\Helper\MessageBroker\EventConsumer;
use Iapps\Common\Microservice\AccountService\AccountService;
use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\Common\Microservice\AccountService\FunctionCode;
use Iapps\Common\Microservice\AccountService\MobileNumberObj;
use Iapps\Common\Microservice\AccountService\User;
use Iapps\Common\Microservice\PaymentService\PaymentService;
use Iapps\Common\Microservice\PaymentService\PaymentServiceFactory;
use Iapps\RemittanceService\Attribute\RemittanceAttribute;
use Iapps\RemittanceService\Attribute\RemittanceAttributeCollection;
use Iapps\RemittanceService\Common\CoreConfigDataServiceFactory;
use Iapps\RemittanceService\Common\CoreConfigType;
use Iapps\RemittanceService\Common\TransactionType;
use Iapps\RemittanceService\RefundRequest\RefundRequest;
use Iapps\RemittanceService\RefundRequest\RefundRequestServiceFactory;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransaction;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransactionEventType;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransactionServiceFactory;
use Iapps\RemittanceService\Common\Logger;
use Iapps\RemittanceService\Attribute\RemittanceAttributeServiceFactory;
use Iapps\RemittanceService\Attribute\AttributeCode;

class RemittanceStatusChangedNotificationService extends EventConsumer{

    protected $header = array();
    protected $_accountServ;
    protected $_paymentServ;

    const CASH_PAYMENT_CODE_GROUP = 'cash';
    const EWALLET_PAYMENT_CODE_GROUP = 'ewallet';

    protected function _getAccountService()
    {
        if( !$this->_accountServ )
            $this->_accountServ = AccountServiceFactory::build();

        return $this->_accountServ;
    }

    protected function _getPaymentService()
    {
        if( !$this->_paymentServ )
            $this->_paymentServ = PaymentServiceFactory::build();

        return $this->_paymentServ;
    }

    public function setHeader(array $header)
    {
        $this->header = $header;
        return $this;
    }

    public function getHeader()
    {
        return $this->header;
    }

    public function doTask($msg)
    {
        $data = json_decode($msg->body);

        try
        {
            if( isset($data->remittance_id) )
            {
                $status = isset($data->status) ? $data->status : NULL;
                $this->_notifyUser($data->remittance_id, $status);
            }


            return true;
        }
        catch(\Exception $e)
        {
            return false;
        }
    }

    protected function _sendSMS(MobileNumberObj $mobileNumberObj, $content)
    {
        $mobile_country_code = '+'.$mobileNumberObj->getDialingCode();
        $basic_mobile_number = $mobileNumberObj->getMobileNumber();
        $communication = new CommunicationServiceProducer();
        $stat = $communication->sendSMS(getenv("ICS_PROJECT_ID"), $content, $mobile_country_code, $basic_mobile_number, "", "");
        Logger::debug('result: ' . $stat);
        return $stat;
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
            Logger::debug('result: ' . $stat);
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


    protected function _notifyUser($remittance_id, $status = NULL)
    {
        //get transaction detail
        $remittanceServ = RemittanceRecordServiceFactory::build();

        try{
            //wait one second for the data to be commited to DB
            sleep(2);

            $remittanceServ->setAccountService($this->_getAccountService());
            if( $remittance = $remittanceServ->retrieveRemittance($remittance_id) )
            {
                if( $remittance instanceof RemittanceRecord )
                {
                    //get status
                    if( $status == NULL )
                        $status = $remittance->getStatus()->getCode();

                    switch($status)
                    {
                        case RemittanceStatus::PROCESSING:
                            return $this->_notifyProcessing($remittance);
                            break;
                        case RemittanceStatus::DELIVERING:
                            return $this->_notifyDelivering($remittance);
                            break;
                        case RemittanceStatus::COLLECTED:
                            return $this->_notifyCollected($remittance);
                            break;
                        case RemittanceStatus::REJECTED:
                            return $this->_notifyRejected($remittance);
                            break;
                        case RemittanceStatus::FAILED:
                            return $this->_notifyFailed($remittance);
                            break;
                    }
                }
            }

            return false;

        } catch (\Exception $e){
            return false;
        }
    }

    public function listenEvent()
    {
        $this->listen(RemittanceTransactionEventType::REMITTANCE_STATUS_CHANGED, NULL, 'remittance.queue.notifyRemittanceStatusChanged');
    }

    protected function _notifyProcessing(RemittanceRecord $remittance)
    {
        if( $remittance->getStatus()->getCode() == RemittanceStatus::PROCESSING AND
            $remittance->getApprovalRequired() == 2 AND
            $remittance->isInternational() )
        {
            if( $this->_getAccountService()->checkAccessByUserProfileId($remittance->getSender()->getId(), FunctionCode::APP_PUBLIC_FUNCTIONS) )
            {
                $coreServ = CoreConfigDataServiceFactory::build();
                $content = $coreServ->getConfig(CoreConfigType::SENDER_PROCESSING_MESSAGE);

                $total_amount = CurrencyFormatter::format($remittance->getInTransaction()->getItems()->getTotalAmount(), $remittance->getInTransaction()->getCountryCurrencyCode());
                $content = str_replace("[TOTAL_PAYABLE]", $total_amount, $content);

                if( $info = $this->_getPaymentService()->getPaymentModeInfo($remittance->getInTransaction()->getConfirmPaymentCode()) )
                {
                    $payment_mode = $info->getName();
                }
                else
                    $payment_mode = $remittance->getInTransaction()->getConfirmPaymentCode();


                $content = str_replace("[PAYMENT_MODE]", $payment_mode, $content);
                $to_amount  = CurrencyFormatter::format($remittance->getToAmount(), $remittance->getOutTransaction()->getCountryCurrencyCode());
                $content = str_replace("[TO_AMOUNT]", $to_amount, $content);
                $content = str_replace("[RECIPIENT_ALIAS]", $remittance->getRecipient()->getRecipientAlias(), $content);
                $content = str_replace("[TRANSACTION_ID]", $remittance->getInTransaction()->getTransactionID(), $content);
                $content = str_replace("[REMITTANCE_ID]", $remittance->getRemittanceID(), $content);

                Logger::debug('Sending Notification for remittance: ' . $remittance->getId());
                return $this->_sendChat($remittance->getSender(), $content);
            }

            return false;
        }

        //do nothing
        return true;
    }

    protected function _notifyCollected(RemittanceRecord $remittance)
    {
        //notify sender
        if( $this->_getAccountService()->checkAccessByUserProfileId($remittance->getSender()->getId(), FunctionCode::APP_PUBLIC_FUNCTIONS) )
        {
            $coreServ = CoreConfigDataServiceFactory::build();

            $to_amount  = CurrencyFormatter::format($remittance->getToAmount(), $remittance->getOutTransaction()->getCountryCurrencyCode());
            $collection_mode = $remittance->getInTransaction()->getConfirmCollectionMode();
            $payment_mode_group = '';
            if( $info = $this->_getPaymentService()->getPaymentModeInfo($remittance->getInTransaction()->getConfirmCollectionMode()) )
            {
                $collection_mode = $info->getName();
                $payment_mode_group = $info->getGroup();
            }
            $content = $coreServ->getConfig(CoreConfigType::SENDER_COLLECTED_MESSAGE);

            $content = str_replace("[RECIPIENT_ALIAS]", $remittance->getRecipient()->getRecipientAlias(), $content);

            $content = str_replace("[TO_AMOUNT]", $to_amount, $content);
            $content = str_replace("[COLLECTION_MODE]", $collection_mode, $content);

            if( !$remittance->getCollectedAt()->isNull() )
                $content = str_replace("[COLLECTION_TIME]", $remittance->getCollectedAt()->getFormat('d-M-y h:i A'), $content);
            else
                $content = str_replace("[COLLECTION_TIME]", 'unknown', $content);

            $content = str_replace("[TRANSACTION_ID]", $remittance->getOutTransaction()->getTransactionID(), $content);
            $content = str_replace("[REMITTANCE_ID]", $remittance->getRemittanceID(), $content);

            Logger::debug('Sending Sender Notification for remittance: ' . $remittance->getId());
            $this->_sendChat($remittance->getSender(), $content);
        }

        //notify receiver - only for slide user
        if( $recipientUserId = $remittance->getRecipient()->getRecipientUser()->getId() )
        {
            if( $this->_getAccountService()->checkAccessByUserProfileId($recipientUserId, FunctionCode::APP_PUBLIC_FUNCTIONS) )
            {//for APP user - send chat
                //construct chat message
                $content = $coreServ->getConfig(CoreConfigType::RECEIVER_COLLECTED_MESSAGE);
                if($payment_mode_group == self::EWALLET_PAYMENT_CODE_GROUP){
                    $content = $coreServ->getConfig(CoreConfigType::RECEIVER_COLLECTED_MESSAGE_EWALLET);
                }
                $content = str_replace("[SENDER_NAME]", $remittance->getSender()->getName(), $content);
                $content = str_replace("[TO_AMOUNT]", $to_amount, $content);

                if( !$remittance->getPaidAt()->isNull() )
                    $content = str_replace("[PAYMENT_TIME]", $remittance->getPaidAt()->getFormat('d-M-y h:i A'), $content);
                else
                    $content = str_replace("[PAYMENT_TIME]", 'unknown', $content);

                $content = str_replace("[COLLECTION_MODE]", $collection_mode, $content);
                $content = str_replace("[TRANSACTION_ID]", $remittance->getOutTransaction()->getTransactionID(), $content);
                $content = str_replace("[REMITTANCE_ID]", $remittance->getRemittanceID(), $content);

                Logger::debug('Sending Receiver Notification for remittance: ' . $remittance->getId());
                $this->_sendChat($remittance->getRecipient()->getRecipientUser(), $content);

                if( !$remittance->isInternational() )
                {
                    /*
                     * send SMS on top of push notification if local transfer
                     */
                    $mobileNumberObj = new MobileNumberObj();
                    $mobileNumberObj->setDialingCode($remittance->getRecipient()->getRecipientDialingCode()->getValue());
                    $mobileNumberObj->setMobileNumber($remittance->getRecipient()->getRecipientMobileNumber()->getValue());
                    Logger::debug('Sending Receiver SMS for local: ' . $remittance->getId());
                    $this->_sendSMS($mobileNumberObj, $content);
                }
            }
        }

        return true;
    }

    protected function _notifyDelivering(RemittanceRecord $remittance)
    {
        $coreServ = CoreConfigDataServiceFactory::build();

        $to_amount = CurrencyFormatter::format($remittance->getToAmount(), $remittance->getOutTransaction()->getCountryCurrencyCode());

        $collection_mode = $remittance->getInTransaction()->getConfirmCollectionMode();
        $payment_mode_group = '';
        $delivery_time = '-';
        if ($info = $this->_getPaymentService()->getPaymentModeInfo($remittance->getInTransaction()->getConfirmCollectionMode())){
            $collection_mode = $info->getName();
            $payment_mode_group = $info->getGroup();
            $delivery_time = $info->getDeliveryTime()->display_name;
        }

        $content = $coreServ->getConfig(CoreConfigType::SENDER_DELIVERING_MESSAGE);
        if($payment_mode_group == self::CASH_PAYMENT_CODE_GROUP){
            $content = $coreServ->getConfig(CoreConfigType::SENDER_DELIVERING_MESSAGE_CASH);
        }

        $remittanceAttributeServ = RemittanceAttributeServiceFactory::build();

        $pinNumber = '';
        $remittanceAttributes = $remittanceAttributeServ->getAllRemittanceAttribute($remittance->getId());
        if( !empty($remittanceAttributes) )
        {
            foreach($remittanceAttributes as $attr)
            {
                if($attr->getAttribute()->getCode() == AttributeCode::PIN_NUMBER ){
                    $pinNumber = $attr->getValue();
                    break;
                }
            }
        }

        $recipientFullName = $remittance->getRecipient()->getRecipientUser()->getFullName();

        if(!$recipientFullName) $recipientFullName = $remittance->getRecipient()->getRecipientAlias();
        $content = str_replace("[TO_AMOUNT]", $to_amount, $content);
        $content = str_replace("[RECIPIENT_ALIAS]", $remittance->getRecipient()->getRecipientAlias(), $content);
        $content = str_replace("[RECIPIENT_FULL_NAME]", $recipientFullName, $content);
        $content = str_replace("[SENDER_NAME]", $remittance->getSender()->getName(), $content);

        $content = str_replace("[TRANSACTION_ID]", $remittance->getInTransaction()->getTransactionID(), $content);
        $content = str_replace("[REMITTANCE_ID]", $remittance->getRemittanceID(), $content);
        $content = str_replace("[PIN_NUMBER]", $pinNumber, $content);
        $content = str_replace("[DELIVERY_TIME]", $delivery_time, $content);


        Logger::debug('Sending Sender Notification for remittance: ' . $remittance->getId());
        $this->_sendChat($remittance->getSender(), $content);

        if($remittance->isInternational()) {
            if ($payment_mode_group == self::CASH_PAYMENT_CODE_GROUP) {
                $mobileNumberObj = $remittance->getSender()->getMobileNumberObj();
                if ($remittance->getSender()->getMobileNumberObj() != NULL) {
                    $content = $coreServ->getConfig(CoreConfigType::SENDER_DELIVERING_SMS_CASH);
                    $content = str_replace("[TO_AMOUNT]", $to_amount, $content);
                    $content = str_replace("[RECIPIENT_FULL_NAME]", $recipientFullName, $content);
                    $content = str_replace("[SENDER_NAME]", $remittance->getSender()->getName(), $content);
                    $content = str_replace("[PIN_NUMBER]", $pinNumber, $content);
                    $content = str_replace("[DELIVERY_TIME]", $delivery_time, $content);

                    Logger::debug('Sending Sender SMS for remittance - cash: ' . $remittance->getId());
                    $this->_sendSMS($mobileNumberObj, $content);
                }
            } else {
                $mobileNumberObj = $remittance->getSender()->getMobileNumberObj();
                if ($remittance->getSender()->getMobileNumberObj() != NULL) {
                    $content = $coreServ->getConfig(CoreConfigType::SENDER_DELIVERING_SMS);
                    $content = str_replace("[TO_AMOUNT]", $to_amount, $content);
                    $content = str_replace("[RECIPIENT_FULL_NAME]", $recipientFullName, $content);
                    $content = str_replace("[SENDER_NAME]", $remittance->getSender()->getName(), $content);
                    $content = str_replace("[DELIVERY_TIME]", $delivery_time, $content);

                    Logger::debug('Sending Sender SMS for remittance - non-cash: ' . $remittance->getId());
                    $this->_sendSMS($mobileNumberObj, $content);
                }
            }
        }

        //if recipient is a app user
        $sentChat = false;
        if( $recipientUserId = $remittance->getRecipient()->getRecipientUser()->getId() )
        {
            if( $this->_getAccountService()->checkAccessByUserProfileId($recipientUserId, FunctionCode::APP_PUBLIC_FUNCTIONS) )
            {//for APP user - send chat
                //construct chat message
                $content = $coreServ->getConfig(CoreConfigType::RECEIVER_DELIVERING_MESSAGE);
                $content = str_replace("[SENDER_NAME]", $remittance->getSender()->getName(), $content);
                $content = str_replace("[TO_AMOUNT]", $to_amount, $content);

                if( !$remittance->getPaidAt()->isNull() )
                    $content = str_replace("[PAYMENT_TIME]", $remittance->getPaidAt()->getFormat('d-M-y h:i A'), $content);
                else
                    $content = str_replace("[PAYMENT_TIME]", 'unknown', $content);

                $content = str_replace("[COLLECTION_MODE]", $collection_mode, $content);
                $content = str_replace("[TRANSACTION_ID]", $remittance->getOutTransaction()->getTransactionID(), $content);
                $content = str_replace("[REMITTANCE_ID]", $remittance->getRemittanceID(), $content);

                Logger::debug('Sending Receiver Notification for remittance: ' . $remittance->getId());
                $this->_sendChat($remittance->getRecipient()->getRecipientUser(), $content);

                /* //disable send SMS to slide user for send local on delivering, will send on collected only.
                if( !$remittance->isInternational() )
                {
                    //send to SMS on top of push notification if local transfer

                    $mobileNumberObj = new MobileNumberObj();
                    $mobileNumberObj->setDialingCode($remittance->getRecipient()->getRecipientDialingCode()->getValue());
                    $mobileNumberObj->setMobileNumber($remittance->getRecipient()->getRecipientMobileNumber()->getValue());
                    Logger::debug('Sending Receiver SMS for local: ' . $remittance->getId());
                    $this->_sendSMS($mobileNumberObj, $content);
                }*/

                $sentChat = true;
            }
        }

        if( !$sentChat)
        {//sms
            $content = $coreServ->getConfig(CoreConfigType::RECEIVER_DELIVERING_SMS);
            if($payment_mode_group == self::EWALLET_PAYMENT_CODE_GROUP){
                $content = $coreServ->getConfig(CoreConfigType::RECEIVER_DELIVERING_SMS_EWALLET);
            }
            $content = str_replace("[SENDER_NAME]", $remittance->getSender()->getName(), $content);
            $content = str_replace("[TO_AMOUNT]", $to_amount, $content);
            if( !$remittance->getPaidAt()->isNull() )
                $content = str_replace("[PAYMENT_TIME]", $remittance->getPaidAt()->getFormat('d-M-y h:i A'), $content);
            else
                $content = str_replace("[PAYMENT_TIME]", 'unknown', $content);
            $content = str_replace("[TRANSACTION_ID]", $remittance->getOutTransaction()->getTransactionID(), $content);
            $content = str_replace("[REMITTANCE_ID]", $remittance->getRemittanceID(), $content);
            $content = str_replace("[EXPIRY_PERIOD]", $remittance->getCashOutExpiryPeriodInDay(), $content);

            $content = str_replace("[MOBILE_NO]", $remittance->getSender()->getMobileNumberObj()->getCombinedNumber(), $content);

            $mobileNumberObj = new MobileNumberObj();
            $mobileNumberObj->setDialingCode($remittance->getRecipient()->getRecipientDialingCode()->getValue());
            $mobileNumberObj->setMobileNumber($remittance->getRecipient()->getRecipientMobileNumber()->getValue());

            Logger::debug('Sending Receiver SMS for non slide user: ' . $remittance->getId());
            $this->_sendSMS($mobileNumberObj, $content);
        }

        return true;
    }

    protected function _notifyRejected(RemittanceRecord $remittance)
    {
        $coreServ = CoreConfigDataServiceFactory::build();
        if( $content = $coreServ->getConfig(CoreConfigType::SENDER_REJECTED_MESSAGE) )
        {
            if($remittance->isInternational()) {
                $to_amount = CurrencyFormatter::format($remittance->getToAmount(), $remittance->getOutTransaction()->getCountryCurrencyCode());
                $recipient_alias = $remittance->getRecipient()->getRecipientAlias();

                $reason = '';
                $attrServ = RemittanceAttributeServiceFactory::build();
                if( $attr = $attrServ->getAllRemittanceAttribute($remittance->getId()) )
                {
                    if( $attr instanceof RemittanceAttributeCollection )
                    {
                        if( $rejectReason = $attr->hasAttribute(AttributeCode::REJECT_REASON) )
                            $reason = "because of " . $rejectReason;
                    }
                }

                $content = str_replace("[TO_AMOUNT]", $to_amount, $content);
                $content = str_replace("[RECIPIENT_ALIAS]", $recipient_alias, $content);
                $content = str_replace("[REASON]", $reason, $content);
                $content = str_replace("[TRANSACTION_ID]", $remittance->getInTransaction()->getTransactionID(), $content);

                $this->_sendChat($remittance->getSender(), $content);
            }
        }

        return true;
    }

    protected function _notifyFailed(RemittanceRecord $remittance)
    {
        $coreServ = CoreConfigDataServiceFactory::build();
        if( $content = $coreServ->getConfig(CoreConfigType::SENDER_FAILED_MESSAGE) )
        {
            if($remittance->isInternational()) {
                $to_amount = CurrencyFormatter::format($remittance->getToAmount(), $remittance->getOutTransaction()->getCountryCurrencyCode());
                $recipient_alias = $remittance->getRecipient()->getRecipientAlias();

                $reason = "";
                $refundServ = RefundRequestServiceFactory::build();
                if( $refund = $refundServ->getByTransactionID($remittance->getInTransaction()->getTransactionID()) )
                {//todo: where else to get the reason?
                    if( $refund instanceof RefundRequest )
                        $reason = $refund->getRefundRemarks() ? "because of " . $refund->getRefundRemarks() : "";
                }

                $content = str_replace("[TO_AMOUNT]", $to_amount, $content);
                $content = str_replace("[RECIPIENT_ALIAS]", $recipient_alias, $content);
                $content = str_replace("[REASON]", $reason, $content);
                $content = str_replace("[TRANSACTION_ID]", $remittance->getInTransaction()->getTransactionID(), $content);

                $this->_sendChat($remittance->getSender(), $content);
            }
        }

        return true;
    }
}