<?php

namespace Iapps\RemittanceService\Recipient;

class LocalRecipientValidator extends RecipientValidator{

    protected function _validateFields()
    {
        if( parent::_validateFields() )
        {
            if( $this->recipient instanceof Recipient)
            {
                //must have mobile number
                return (  $this->validateRecipientType($this->recipient->getRecipientType()) AND
                          $this->recipient->getRecipientDialingCode()->getValue() != NULL AND
                          $this->recipient->getRecipientMobileNumber()->getValue() != NULL AND
                          $this->_isDigit($this->recipient->getRecipientDialingCode()->getValue()) AND
                          $this->_isDigit($this->recipient->getRecipientMobileNumber()->getValue()) );
            }
        }

        return false;
    }

    protected function _validateByType()
    {//regardless of type
        return true;
    }
}