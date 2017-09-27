<?php

namespace Iapps\RemittanceService\Recipient;

use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\Common\Microservice\AccountService\MobileNumberObj;
use Iapps\RemittanceService\Common\MessageCode;

class LocalRecipientService extends RecipientService{

    public function _setIsInternational(Recipient $recipient)
    {
        $recipient->setIsInternational(0);
        return $recipient;
    }

    protected function _checkRecipientExists(Recipient $recipient)
    {
        if( $recipient->getRecipientDialingCode()->getValue() AND
            $recipient->getRecipientMobileNumber()->getValue() )
        {
            if( $existRecipientsInfo = $this->getRepository()->findByMobileNumber($recipient->getUserProfileId(), $recipient->getRecipientDialingCode()->getValue(), $recipient->getRecipientMobileNumber()->getValue()) )
            {
                $existRecipients = $existRecipientsInfo->result;
                foreach($existRecipients AS $existRecipient )
                {
                    if( (bool) $existRecipient->getIsInternational() === false AND $existRecipient->getIsActive() == true)
                    {
                        $this->setResponseCode(MessageCode::CODE_RECIPIENT_EXISTS);
                        return false;
                    }
                }
            }
        }

        return true;
    }

    public function _assignRecipientUserProfileId(Recipient $recipient)
    {
        $dialing_code = $recipient->getRecipientDialingCode()->getValue();
        $mobile_number = $recipient->getRecipientMobileNumber()->getValue();

        if( $dialing_code AND $mobile_number )
        {
            $accServ = AccountServiceFactory::build();
            if( $recipientUser = $accServ->searchUser($dialing_code, $mobile_number) )
            {//if its slide user
                if( isset($recipientUser->getMobileNumber()->dialing_code) AND
                    isset($recipientUser->getMobileNumber()->mobile_number))
                {
                    $mobile = new MobileNumberObj();
                    $mobile->setDialingCode($recipientUser->getMobileNumber()->dialing_code);
                    $mobile->setMobileNumber($recipientUser->getMobileNumber()->mobile_number);
                    $recipientUser->setMobileNumberObj($mobile);
                }

                $recipient->isMatched($recipientUser);
            }
        }

        return $recipient;
    }

    protected function _getRecipientValidator()
    {
        return new LocalRecipientValidator();
    }
}
