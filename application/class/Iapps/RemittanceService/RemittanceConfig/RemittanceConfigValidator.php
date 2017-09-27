<?php

namespace Iapps\RemittanceService\RemittanceConfig;

use Iapps\RemittanceService\RemittanceConfig\RemittanceConfig;

class RemittanceConfigValidator {

    protected $config;
    protected $isFailed = true;

    public static function make(RemittanceConfig $config)
    {
        $v = new RemittanceConfigValidator();
        $v->config = $config;
        $v->validate();

        return $v;
    }

    public function fails()
    {
        return $this->isFailed;
    }

    public function setRemittanceConfig(RemittanceConfig $config)
    {
        $this->config = $config;
        return true;
    }

    public function getRemittanceConfig()
    {
        return $this->config;
    }

    public function validate()
    {
        $this->isFailed = true;
        if( $this->_validateNumeric($this->getRemittanceConfig()->getMinLimit()) AND
            $this->_validateNumeric($this->getRemittanceConfig()->getMaxLimit())
        )
        {
            $this->isFailed = false;
            return true;
        }
        
        if( $this->getRemittanceConfig()->getMinLimit() >= $this->getRemittanceConfig()->getMaxLimit() )
        {
            $this->isFailed = false;
            return true;
        }
        return false;
    }




    protected function _validateNumeric($number)
    {//make sure its digit
        return (is_numeric($number));
    }

}