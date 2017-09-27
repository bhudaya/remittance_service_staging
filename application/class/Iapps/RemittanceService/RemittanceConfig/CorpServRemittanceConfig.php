<?php

namespace Iapps\RemittanceService\RemittanceConfig;

use Iapps\Common\Core\IappsBaseEntity;

class CorpServRemittanceConfig extends RemittanceConfig{
    protected $service_provider_id;
    protected $cashin_corp_serv_name;
    protected $cashin_corp_serv_desc;
    protected $cashin_daily_limit;
    protected $cashout_corp_serv_name;
    protected $cashout_corp_serv_desc;
    protected $cashout_daily_limit;

    protected $from_country_currency_code;
    protected $to_country_currency_code;

    protected $conversion_remittance_service_id;






    public function setServiceProviderId($service_provider_id)
    {
        $this->service_provider_id = $service_provider_id;
        return true;
    }

    public function getServiceProviderId()
    {
        return $this->service_provider_id;
    }

    public function setCashInCorpServName($cashin_corp_serv_name)
    {
        $this->cashin_corp_serv_name = $cashin_corp_serv_name;
        return true;
    }

    public function getCashInCorpServName()
    {
        return $this->cashin_corp_serv_name;
    }

    public function setCashInCorpServDesc($cashin_corp_serv_desc)
    {
        $this->cashin_corp_serv_desc = $cashin_corp_serv_desc;
        return true;
    }

    public function getCashInCorpServDesc()
    {
        return $this->cashin_corp_serv_desc;
    }

    public function setCashInDailyLimit($cashin_daily_limit)
    {
        $this->cashin_daily_limit = $cashin_daily_limit;
        return true;
    }

    public function getCashInDailyLimit()
    {
        return $this->cashin_daily_limit;
    }

    public function setCashOutCorpServName($cashout_corp_serv_name)
    {
        $this->cashout_corp_serv_name = $cashout_corp_serv_name;
        return true;
    }

    public function getCashOutCorpServName()
    {
        return $this->cashout_corp_serv_name;
    }

    public function setCashOutCorpServDesc($cashout_corp_serv_desc)
    {
        $this->cashout_corp_serv_desc = $cashout_corp_serv_desc;
        return true;
    }

    public function getCashOutCorpServDesc()
    {
        return $this->cashout_corp_serv_desc;
    }

    public function setCashOutDailyLimit($cashout_daily_limit)
    {
        $this->cashout_daily_limit = $cashout_daily_limit;
        return true;
    }

    public function getCashOutDailyLimit()
    {
        return $this->cashout_daily_limit;
    }

    public function setFromCountryCurrencyCode($from_country_currency_code)
    {
        $this->from_country_currency_code = $from_country_currency_code;
        return true;
    }

    public function getFromCountryCurrencyCode()
    {
        return $this->from_country_currency_code;
    }

    public function setToCountryCurrencyCode($to_country_currency_code)
    {
        $this->to_country_currency_code = $to_country_currency_code;
        return true;
    }

    public function getToCountryCurrencyCode()
    {
        return $this->to_country_currency_code;
    }

    /**
     * @return mixed
     */
    public function getConversionRemittanceServiceId()
    {
        return $this->conversion_remittance_service_id;
    }

    /**
     * @param mixed $conversion_remittance_service_id
     */
    public function setConversionRemittanceServiceId($conversion_remittance_service_id)
    {
        $this->conversion_remittance_service_id = $conversion_remittance_service_id;
    }


    public function jsonSerialize()
    {
        $json = parent::jsonSerialize();

        $json['cashin_corp_serv_name']         = $this->getCashInCorpServName();
        $json['cashin_corp_serv_desc']         = $this->getCashInCorpServDesc();
        $json['cashin_daily_limit']            = $this->getCashInDailyLimit();
        $json['cashout_corp_serv_name']        = $this->getCashOutCorpServName();
        $json['cashout_corp_serv_desc']        = $this->getCashOutCorpServDesc();
        $json['cashout_daily_limit']           = $this->getCashOutDailyLimit();
        $json['service_provider_id']           = $this->getServiceProviderId();
        $json['from_country_currency_code']           = $this->getFromCountryCurrencyCode();
        $json['to_country_currency_code']           = $this->getToCountryCurrencyCode();
        $json['conversion_remittance_service_id']           = $this->getConversionRemittanceServiceId();

        return $json;
    }
}