<?php

namespace Iapps\RemittanceService\ExchangeRate;

use Iapps\RemittanceService\Common\ChannelType;
use Iapps\Common\CorporateService\CorporateService;
use Iapps\Common\Validator\IappsValidator;
use Iapps\RemittanceService\Common\MessageCode;

class ExchangeRateValidator extends IappsValidator{

    protected $exchangeRate;
    protected $corpServ;
    protected $isFailed = true;

    public static function make(ExchangeRate $exchangeRate, CorporateService $corpServ)
    {
        $v = new ExchangeRateValidator();
        $v->setExchangeRate($exchangeRate);
        $v->corpServ = $corpServ;
        $v->validate();

        return $v;
    }

    public function fails()
    {
        return $this->isFailed;
    }

    public function setExchangeRate(ExchangeRate $exchangeRate)
    {
        $this->exchangeRate = $exchangeRate;
        return $this;
    }

    public function getExchangeRate()
    {
        return $this->exchangeRate;
    }

    public function setCorporateService(CorporateService $corporateService)
    {
        $this->corpServ = $corporateService;
        return $this;
    }

    public function getCorporateService()
    {
        return $this->corpServ;
    }

    public function validate()
    {
        $this->isFailed = true;

        if( !$this->_validateChannel() )
            return false;

        //if corporate service if the conversion party, both exchange rate & margin to be filled up
        if( $this->getCorporateService()->getConversionRemittanceService()->getId() != NULL )
        {
            if( $this->_validateNumeric($this->getExchangeRate()->getExchangeRate() ) AND
                $this->_validateNumeric($this->getExchangeRate()->getMargin()) )
            {
                $this->isFailed = false;
                return true;
            }
        }
        else
        {//if corporate service if not conversion party, exchange rate = null & margin to be filled up
            if( $this->getExchangeRate()->getExchangeRate() == NULL  AND
                $this->_validateNumeric($this->getExchangeRate()->getMargin()) )
            {
                $this->isFailed = false;
                return true;
            }
        }

        return false;
    }

    protected function _validateChannel()
    {//only partner & admin can insert rates

        if( $this->getExchangeRate()->getChannel()->getCode() == ChannelType::CODE_ADMIN_PANEL OR
            $this->getExchangeRate()->getChannel()->getCode() == ChannelType::CODE_PARTNER_PANEL )
            return true;

        $this->setErrorCode(MessageCode::CODE_INVALID_CHANNEL);
        return false;
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