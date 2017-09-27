<?php

namespace Iapps\RemittanceService\Common;

use Iapps\Common\Validator\IappsValidator;
use Iapps\Common\Microservice\PaymentService\PaymentService;

class CurrencyCodeValidator extends IappsValidator{

    protected $country_currency_code;
    protected $countrycurrencyInfo;

    public static function make($code)
    {
        $v = new CurrencyCodeValidator();
        $v->country_currency_code = $code;
        $v->validate();

        return $v;
    }

    public function getCountryCurrencyInfo()
    {
        return $this->countrycurrencyInfo;
    }

    public function validate()
    {
        $this->isFailed = true;
        $pay_serv = new PaymentService();
        if( $this->countrycurrencyInfo = $pay_serv->getCountryCurrencyInfo($this->country_currency_code) )
        {
            $this->isFailed = false;
        }
    }
}