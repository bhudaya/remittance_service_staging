<?php

namespace Iapps\RemittanceService\Recipient;

use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\Common\Validator\IappsValidator;
use Iapps\Common\Validator\RegexValidator;
use Iapps\RemittanceService\Attribute\AttributeCode;
use Iapps\RemittanceService\Common\SystemCodeServiceFactory;

class RecipientValidator extends IappsValidator{

    protected $recipient;

    public static function make(Recipient $recipient)
    {
        $v = new static();

        $v->recipient = $recipient;
        $v->validate();

        return $v;
    }

    public static function validateRecipientType($code)
    {
        $systemcode = SystemCodeServiceFactory::build();
        return $systemcode->validateSystemCode($code, new RecipientType());
    }

    public function validate()
    {
        $this->isFailed = true;

        if( $this->_validateSameUser() AND
            $this->_validateFields() AND
            $this->_validateByType() AND
            $this->_validateIsActive() )
            $this->isFailed = false;
    }

    protected function _validateSameUser()
    {
        return ($this->recipient->getUserProfileId() != $this->recipient->getRecipientUserProfileId() );
    }

    /*
     * No need to ensure recipient_user_profile_id as non-registered user is allowed
     */
    protected function _validateFields()
    {
        if( $this->recipient instanceof Recipient ) {
            return ($this->recipient->getUserProfileId() != NULL AND
                    $this->recipient->getRecipientAlias() != NULL);
        }

        return false;
    }

    protected function _validateByType()
    {
        if( $this->recipient instanceof Recipient )
        {
            if( self::validateRecipientType($this->recipient->getRecipientType()) )
            {
                switch($this->recipient->getRecipientType() )
                {

                    case RecipientType::NON_MEMBER:
                        //must have full name
                        if( !$this->recipient->getAttributes()->hasAttribute(AttributeCode::FULL_NAME))
                            return false;

                        //must have mobile number
                        if( !(  $this->recipient->getRecipientDialingCode()->getValue() != NULL AND
                                $this->recipient->getRecipientMobileNumber()->getValue() != NULL AND
                                $this->_isDigit($this->recipient->getRecipientDialingCode()->getValue()) AND
                                $this->_isDigit($this->recipient->getRecipientMobileNumber()->getValue()) )
                        )
                            return false;
                        break;

                    case RecipientType::KYC:
                    case RecipientType::NON_KYC:

                        //must have user profile id
                        if( $this->recipient->getRecipientUserProfileId() == NULL )
                            return false;

                        //must be valid id
                        $accountServ = AccountServiceFactory::build();
                        if( !$recipientUser = $accountServ->getUser(NULL, $this->recipient->getRecipientUserProfileId()) )
                            return false;

                        break;
                    default:
                        return false;
                }

                return true;
            }
        }

        return false;
    }

    protected function _isDigit($value)
    {
        $v = RegexValidator::make($value, "/^\d+$/");
        return !$v->fails();
    }

    protected function _validateIsActive()
    {
        return ($this->recipient->getIsActive() == 0 OR
                $this->recipient->getIsActive() == 1 );
    }
}