<?php

namespace Iapps\RemittanceService\RemittanceServiceConfig;

use Iapps\Common\Core\IappsBaseEntity;
use Iapps\Common\Core\IappsDateTime;

class RemittanceServiceConfig extends IappsBaseEntity{

    protected $from_country_currency_code;
    protected $to_country_currency_code;

    function __construct()
    {
        parent::__construct();
    }

    public function setFromCountryCurrencyCode($code)
    {
        $this->from_country_currency_code = $code;
        return $this;
    }

    public function getFromCountryCurrencyCode()
    {
        return $this->from_country_currency_code;
    }

    public function setToCountryCurrencyCode($code)
    {
        $this->to_country_currency_code = $code;
        return $this;
    }

    public function getToCountryCurrencyCode()
    {
        return $this->to_country_currency_code;
    }

    public function jsonSerialize()
    {
        $json = parent::jsonSerialize();

        $json['from_country_currency_code'] = $this->getFromCountryCurrencyCode();
        $json['to_country_currency_code'] = $this->getToCountryCurrencyCode();

        return $json;
    }

    public function isInternational()
    {
        return ($this->getFromCountryCurrencyCode() != $this->getToCountryCurrencyCode());
    }

    public function isDomestic()
    {
        return ($this->getFromCountryCurrencyCode() == $this->getToCountryCurrencyCode());
    }
}