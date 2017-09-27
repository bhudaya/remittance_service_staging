<?php

namespace Iapps\RemittanceService\RemittanceTransaction;

use Iapps\Common\Validator\IappsValidator;

class PaymentInfoValidator extends IappsValidator{

    protected $data;

    public static function make(array $info)
    {
        $v = new PaymentInfoValidator();
        $v->data = $info;
        $v->validate();

        return $v;
    }

    public function validate()
    {
        $this->isFailed = true;

        if( isset($this->data['payment_code']) AND
            isset($this->data['amount']) )
        {
            if( $this->data['amount'] >= 0.0 )
                $this->isFailed = false;
        }

    }
}