<?php

namespace Iapps\RemittanceService\Recipient;


use Iapps\Common\Core\IappsBaseService;
use Iapps\Common\Core\IappsBasicBaseService;
use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\Common\Microservice\AccountService\User;
use Iapps\Common\Microservice\PaymentService\SystemPaymentService;
use Iapps\Common\Microservice\PaymentService\SystemPaymentServiceFactory;
use Iapps\Common\Transaction\TransactionStatus;
use Iapps\RemittanceService\Attribute\AttributeCode;
use Iapps\RemittanceService\Common\NameMatcher;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransaction;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransactionEventProducer;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransactionServiceFactory;

class RecipientConversionService extends IappsBasicBaseService{

    public function process($user_id)
    {
        //get user
        $accServ = AccountServiceFactory::build();
        if( $user = $accServ->getUser(null, $user_id) )
        {
            //get recipient with user's mobile number
            $recipientServ = RecipientServiceFactory::build();
            $trxServ = RemittanceTransactionServiceFactory::build();
            $paymentServ = SystemPaymentServiceFactory::build();
            $recipientServ->setUpdatedBy($this->getUpdatedBy());
            $recipientServ->setIpAddress($this->getIpAddress());
            $trxServ->setUpdatedBy($this->getUpdatedBy());
            $trxServ->setIpAddress($this->getIpAddress());

            if( $info = $recipientServ->findByMobileNumber($user->getMobileNumberObj()) )
            {
                $recipients = $info->result;

                foreach($recipients AS $recipient)
                {
                    if( $recipient = $this->_isMatched($recipient, $user) )
                    {
                        $v = $this->_validate($recipient);

                        if( !$v->fails() )
                        {
                            //if found, tag recipient to the user_id
                            $updatedRecipient = new Recipient();
                            $updatedRecipient->setId($recipient->getId());
                            $updatedRecipient->setRecipientUserProfileId($user_id);
                            if( $recipientServ->updateRecipient($updatedRecipient) )
                            {
                                //get recipient's transaction
                                if( $trxInfo = $trxServ->getTransactionByRecipientId($recipient->getId()) )
                                {
                                    $trxs = $trxInfo->result;

                                    foreach($trxs AS $trx)
                                    {
                                        if($trx instanceof RemittanceTransaction AND !$trx->getUserProfileId())
                                        {
                                            $oriTrx = clone($trx);

                                            //tag the recipient's transaction with the user_id
                                            $trx->setUserProfileId($user_id);

                                            //update
                                            if( $trxServ->update($trx, $oriTrx) )
                                            {
                                                //call payment service for completed trx
                                                if( $trx->getStatus()->getCode() == TransactionStatus::COMPLETED )
                                                {
                                                    $paymentServ->convertUser(getenv('MODULE_CODE'), $trx->getTransactionID(), $user_id);
                                                }

                                                //publish transaction converted
                                                RemittanceTransactionEventProducer::publishTransactionUserConverted($trx->getId());
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            return true;
        }

        return false;
    }

    protected function _isMatched(Recipient $recipient, User $user)
    {
        //if already assigned, returned false;
        if( $recipient->getRecipientUserProfileId() )
            return false;

        $recipientService = RecipientServiceFactory::build();
        if( $recipient = $recipientService->getRecipientDetail($recipient->getId(), false) )
        {
            return $recipient->isMatched($user);
        }

        return false;
    }

    protected function _validate(Recipient $recipient)
    {
        if( $recipient->getIsInternational() )
            return RecipientValidator::make($recipient);
        else
            return LocalRecipientValidator::make($recipient);
    }
}