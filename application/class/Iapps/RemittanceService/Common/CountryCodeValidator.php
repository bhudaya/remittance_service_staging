<?php

namespace Iapps\RemittanceService\Common;

use Iapps\Common\Validator\IappsValidator;
use Iapps\Common\Microservice\CountryService\CountryService;

class CountryCodeValidator extends IappsValidator{

    protected $country_code;
    protected $countryInfo;

    public static function make($code)
    {
        $v = new CountryCodeValidator();
        $v->country_code = $code;
        $v->validate();

        return $v;
    }

    public function getCountryInfo()
    {
        return $this->countryInfo;
    }

    public function validate()
    {
        $this->isFailed = true;
        $country_serv = new CountryService();
        if( $this->countryInfo = $country_serv->getCountryInfo($this->country_code) )
        {
            $this->isFailed = false;
        }
    }
}