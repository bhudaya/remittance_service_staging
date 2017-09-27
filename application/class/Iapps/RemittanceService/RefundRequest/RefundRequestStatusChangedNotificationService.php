<?php

namespace  Iapps\RemittanceService\RefundRequest;

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
use Iapps\RemittanceService\Common\CoreConfigDataServiceFactory;
use Iapps\RemittanceService\Common\CoreConfigType;
use Iapps\RemittanceService\Common\Logger;

class RefundRequestStatusChangedNotificationService extends EventConsumer{

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
            if( isset($data->refund_request_id) )
            {
                $status = isset($data->status) ? $data->status : NULL;
                $this->_notifyUser($data->refund_request_id, $status);
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


    protected function _notifyUser($refund_request_id, $status = NULL)
    {
        //get refund request detail
        $refundRequestServ = RefundRequestServiceFactory::build();

        try{
            //wait one second for the data to be commited to DB
            sleep(2);

            $refundRequestServ->setAccountService($this->_getAccountService());
            if( $refund_request = $refundRequestServ->getRefundRequestDetail($refund_request_id) )
            {
                if( $refund_request instanceof RefundRequest )
                {
                    //get status
                    if( $status == NULL )
                        $status = $refund_request->getStatus()->getCode();

                    switch($status)
                    {
                        case RefundRequestStatus::REFUNDED:
                            return $this->_notifyRefunded($refund_request);
                            break;
                        case RefundRequestStatus::AUTO_REFUNDED:
                            return $this->_notifyRefunded($refund_request);
                            break;
                        case RefundRequestStatus::REJECTED:
                            break;
                        case RefundRequestStatus::CANCELLED:
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
        $this->listen(RefundRequestEventType::REFUND_REQUEST_STATUS_CHANGED, NULL, 'remittance.queue.refundRequestChanged');
    }

    protected function _notifyRefunded(RefundRequest $refundRequest)
    {

        if( $this->_getAccountService()->checkAccessByUserProfileId($refundRequest->getUserProfileId(), FunctionCode::APP_PUBLIC_FUNCTIONS) )
        {
            if($user = $this->_getAccountService()->getUser(null, $refundRequest->getUserProfileId())) {

                $coreServ = CoreConfigDataServiceFactory::build();
                $content = $coreServ->getConfig(CoreConfigType::REFUND_REQUEST_REFUNDED_MESSAGE);

                $total_amount = CurrencyFormatter::format(abs($refundRequest->getAmount()), $refundRequest->getCountryCurrencyCode());
                $content = str_replace("[REFUNDED_AMOUNT]", $total_amount, $content);

                if ($info = $this->_getPaymentService()->getPaymentModeInfo($refundRequest->getPaymentCode())) {
                    $payment_mode = $info->getName();
                } else
                    $payment_mode = $refundRequest->getPaymentCode();

                $content = str_replace("[REFUND_MODE]", $payment_mode, $content);

                Logger::debug('Sending Notification for refund request: ' . $refundRequest->getId());
                Logger::debug($content);
                $this->_sendChat($user, $content);

                $mobileNumberObj = $user->getMobileNumberObj();
                if ($user->getMobileNumberObj() != NULL) {
                    $content = $coreServ->getConfig(CoreConfigType::REFUND_REQUEST_REFUNDED_SMS);
                    $content = str_replace("[REFUNDED_AMOUNT]", $total_amount, $content);
                    $content = str_replace("[REFUND_MODE]", $payment_mode, $content);

                    Logger::debug('Sending Sender SMS for refund request: ' . $refundRequest->getId());

                    Logger::debug($content);
                    $this->_sendSMS($mobileNumberObj, $content);
                }
            }

        }


        //do nothing
        return true;
    }

}