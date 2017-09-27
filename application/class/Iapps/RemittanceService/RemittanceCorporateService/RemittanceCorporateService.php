<?php

namespace Iapps\RemittanceService\RemittanceCorporateService;

use Iapps\Common\CorporateService\CorporateService;
use Iapps\Common\SystemCode\SystemCode;
use Iapps\RemittanceService\ExchangeRate\ExchangeRate;
use Iapps\RemittanceService\RemittanceServiceConfig\RemittanceServiceConfig;

class RemittanceCorporateService extends CorporateService{

    protected $transaction_type;
    protected $conversion_remittance_service;
    protected $exchange_rate_id = NULL;
    protected $exchange_rate = NULL;
    protected $margin = NULL;
    protected $conversion_remittance_service_id;

    protected $service_provider_name;

    protected $rate_type;

    function __construct()
    {
        parent::__construct();

        $this->conversion_remittance_service = new RemittanceServiceConfig();
        $this->exchange_rate = new ExchangeRate();
        $this->transaction_type = new SystemCode();
    }

    public function setTransactionType(SystemCode $type)
    {
        $this->transaction_type = $type;
        return $this;
    }

    public function getTransactionType()
    {
        return $this->transaction_type;
    }

    public function setTransactionTypeId($id)
    {
        $this->transaction_type->setId($id);
        return $this;
    }

    public function getTransactionTypeId()
    {
        return $this->transaction_type->getId();
    }

    public function setConversionRemittanceService(RemittanceServiceConfig $reServ)
    {
        $this->conversion_remittance_service = $reServ;
        return $this;
    }

    public function getConversionRemittanceService()
    {
        return $this->conversion_remittance_service;
    }

    public function setExchangeRateObj(ExchangeRate $extRate)
    {
        $this->exchange_rate = $extRate;
        return $this;
    }

    public function getExchangeRateObj()
    {
        return $this->exchange_rate;
    }

    public function setExchangeRateId($id)
    {
        $this->exchange_rate->setId($id);
        return $this;
    }

    public function getExchangeRateId()
    {
        return $this->exchange_rate->getId();
    }

    public function setExchangeRate($rate)
    {
        $this->exchange_rate->setExchangeRate($rate);
        return $this;
    }

    public function getExchangeRate()
    {
        return $this->exchange_rate->getExchangeRate();
    }

    public function setMargin($margin)
    {
        $this->exchange_rate->setMargin($margin);
        return $this;
    }

    public function getMargin()
    {
        return $this->exchange_rate->getMargin();
    }


    public function getConversionRemittanceServiceId()
    {
        return $this->conversion_remittance_service_id;
    }


    public function setConversionRemittanceServiceId($conversion_remittance_service_id)
    {
        $this->conversion_remittance_service_id = $conversion_remittance_service_id;
    }


    public function jsonSerialize()
    {
        $json = parent::jsonSerialize();

        $json['conversion_remittance_service_id'] = $this->getConversionRemittanceService()->getId();
        $json['service_provider_name'] = $this->getServiceProviderName();
        $json['exchange_rate_id']   = $this->getExchangeRateId();
        $json['exchange_rate']  = $this->getExchangeRate();
        $json['margin']    = $this->getMargin();
        $json['rate_type'] = $this->getRateType();

        return $json;
    }

    public function setServiceProviderName($name)
    {
        $this->service_provider_name = $name;
        return $this;
    }

    public function getServiceProviderName()
    {
        return $this->service_provider_name;
    }

    public function setRateType($type)
    {
        $this->rate_type = $type;
        return $this;
    }

    public function getRateType()
    {
        return $this->rate_type;
    }

    public function setApprovedRate(ExchangeRate $approvedRate)
    {
        $this->setExchangeRateId($approvedRate->getId());
        $this->setMargin($approvedRate->getMargin());
        $this->setExchangeRate($approvedRate->getExchangeRate());
        return true;
    }
}