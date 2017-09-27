<?php

namespace Iapps\RemittanceService\RemittanceServiceConfig;

use Iapps\RemittanceService\RemittanceServiceConfig\RemittanceServiceConfig;

class RemittanceServiceConfigValidator {

    protected $config;
    protected $isFailed = true;

    public static function make(RemittanceServiceConfig $config)
    {
        $v = new RemittanceServiceConfigValidator();
        $v->config = $config;
        $v->validate();

        return $v;
    }

    public function fails()
    {
        return $this->isFailed;
    }

    public function setRemittanceServiceConfig(RemittanceServiceConfig $config)
    {
        $this->config = $config;
        return true;
    }

    public function getRemittanceServiceConfig()
    {
        return $this->config;
    }

    public function validate()
    {
        $this->isFailed = true;
        if( $this->_validateCode($this->getRemittanceServiceConfig()->getFromCountryCurrencyCode()) AND
            $this->_validateCode($this->getRemittanceServiceConfig()->getToCountryCurrencyCode()) )
        {
            $this->isFailed = false;
            return true;
        }
        return false;
    }

    protected function _validateCode($code)
    {//make sure it six character code
        return (strlen($code) == 6);// AND ctype_upper($code));
    }

    protected function _validateNumeric($number)
    {//make sure its digit
        return (is_numeric($number));
    }

    protected function _validatePercentage($number)
    {//make sure its digit and below or equal 100
        return (is_numeric($number) && $number <= 100);
    }
}