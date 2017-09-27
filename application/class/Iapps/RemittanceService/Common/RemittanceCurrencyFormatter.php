<?php

namespace Iapps\RemittanceService\Common;

use Iapps\Common\Helper\CurrencyFormatter;
use Iapps\Common\Microservice\PaymentService\PaymentServiceFactory;

class RemittanceCurrencyFormatter extends CurrencyFormatter{

    public static function formatCode($amount, $country_currency_code)
    {
        $symbol = $country_currency_code;
        $payServ = PaymentServiceFactory::build();
        if( $currencyInfo = $payServ->getCountryCurrencyInfo($country_currency_code) )
            $symbol = $currencyInfo->getCurrencyInfo()->getCode();

        return $symbol . " " . number_format($amount, 2);
    }
}