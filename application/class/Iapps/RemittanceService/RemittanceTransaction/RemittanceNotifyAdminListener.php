<?php

namespace Iapps\RemittanceService\RemittanceTransaction;

use Iapps\Common\CommunicationService\CommunicationServiceProducer;
use Iapps\Common\Helper\CurrencyFormatter;
use Iapps\Common\Helper\MessageBroker\EventConsumer;
use Iapps\Common\Helper\RequestHeader;
use Iapps\Common\Microservice\AccountService\AccountService;
use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\Common\Microservice\CountryService\CountryServiceFactory;
use Iapps\Common\Microservice\PaymentService\PaymentService;
use Iapps\Common\Microservice\PaymentService\PaymentServiceFactory;
use Iapps\Common\Microservice\RemittanceService\RemittanceTransaction;
use Iapps\RemittanceService\Common\RemittanceCurrencyFormatter;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfig;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfigServiceFactory;
use Iapps\RemittanceService\RemittanceRecord\RemittanceRecord;
use Iapps\RemittanceService\RemittanceRecord\RemittanceRecordServiceFactory;
use Iapps\RemittanceService\RemittanceRecord\RemittanceStatus;

class RemittanceNotifyAdminListener extends EventConsumer{

    protected function doTask($msg)
    {
        $this->setForceAcknowledgement(false);

        $data = json_decode($msg->body);

        try{
            if( isset($data->remittance_id) )
                return $this->_notifyAdmin($data->remittance_id);

            return true;
        } catch (\Exception $e){
            return false;
        }
    }

    protected function _notifyAdmin($remittance_id)
    {
        $remittanceServ = RemittanceRecordServiceFactory::build();
        $remittanceConfigServ = RemittanceConfigServiceFactory::build();
        if( $remittance = $remittanceServ->retrieveRemittance($remittance_id) )
        {
            if( $remittance instanceof RemittanceRecord )
            {
                if( $remittance->getStatus()->getCode() == RemittanceStatus::PROCESSING AND
                    $remittance->isApprovalRequired() )
                {
                    //get remittance config -> email list
                    $config = $remittanceConfigServ->getRemittanceConfigById($remittance->getRemittanceConfigurationId());
                    if( $config instanceof RemittanceConfig )
                    {
                        $emails = $config->getApprovingNotificationEmailsArray();

                        if( count($emails) <= 0 )
                        {// no one to notify
                            return true;
                        }

                        $title = 'SLIDE - New Remittance Pending Approval';

                        //construct email content
                        $content = "<p>New Remittance Transaction:</p>";
                        $content .= "<br/>";
                        $content .= "<p><b>Receipt No.</b> : " . $remittance->getRemittanceID() . "</p>";
                        $countryCode = $config->getInCorporateService()->getCountryCode();
                        $country_serv = CountryServiceFactory::build();
                        if($countryInfo = $country_serv->getCountryInfo($countryCode) )
                        {
                            $timezone_format =  $countryInfo->getTimezoneFormat();
                            $remittance->getPaidAt()->setTimeZoneFormat($timezone_format);
                            $paidAt = $remittance->getPaidAt()->getLocalDateTimeStr('d/m/Y h:i:sa');

                            $content .= "<p><b>Date / Time of transaction</b> : " . $paidAt . "</p>";
                            $content .= "<p><b>Send Amount</b> : " . RemittanceCurrencyFormatter::formatCode($remittance->getFromAmount(), $remittance->getInTransaction()->getCountryCurrencyCode()) . "</p>";
                            $content .= "<p><b>Receive Amount</b> : " . RemittanceCurrencyFormatter::formatCode($remittance->getToAmount(), $remittance->getOutTransaction()->getCountryCurrencyCode()) . "</p>";

                            $payment_mode_name = 'unknown';
                            if( $collectionMode = $remittance->getInTransaction()->getConfirmCollectionMode() )
                            {
                                $paymentService = PaymentServiceFactory::build();
                                if( $paymentMode = $paymentService->getPaymentModeInfo($collectionMode) )
                                    $payment_mode_name = $paymentMode->getName();
                            }
                            $content .= "<p><b>Collection Mode</b> : " . $payment_mode_name . "</p>";
                            $content .= "<p><b>Sender Name</b> : " . $remittance->getSender()->getFullName() . "</p>";
                            $content .= "<p>Please proceed to SLIDE Partner Panel to approve this remittance transaction within 60 minutes from time of transaction.</p>";

                            $commServ = new CommunicationServiceProducer();
                            return $commServ->sendEmail(getenv('ICS_PROJECT_ID'), $title, $content, $content, $emails);
                        }
                    }
                }
                else
                {//no need to do anything
                    return true;
                }
            }
        }

        return false;
    }

    public function listenEvent()
    {
        $this->listen(RemittanceTransactionEventType::REMITTANCE_STATUS_CHANGED, RemittanceStatus::PROCESSING, 'remittance.queue.notifyAdmin');
    }
}