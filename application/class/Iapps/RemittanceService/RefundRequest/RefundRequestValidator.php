<?php

namespace Iapps\RemittanceService\RefundRequest;

use Iapps\Common\Validator\IappsValidator;
use Iapps\RemittanceService\Common\SystemCodeServiceFactory;

class RefundRequestValidator extends IappsValidator{

    protected $recipient;

    public static function make(RefundRequest $refundRequest)
    {
        $v = new RefundRequestValidator();
        $v->refund_request = $refundRequest;
        $v->validate();

        return $v;
    }

    public function validate()
    {
        $this->isFailed = true;

        /*
        if( $this->_validateFields() AND
            $this->_validateIsActive() )*/
        $this->isFailed = false;
    }

    /*

    protected function _validateFields()
    {
        return ($this->recipient->getUserProfileId() != NULL AND
                $this->recipient->getRecipientAlias() != NULL AND
                $this->recipient->getRecipientDialingCode()->getValue() != NULL AND
                $this->recipient->getRecipientMobileNumber()->getValue() != NULL);
    }

    protected function _validateIsActive()
    {
        return ($this->recipient->getIsActive() == 0 OR
                $this->recipient->getIsActive() == 1 );
    }*/
}