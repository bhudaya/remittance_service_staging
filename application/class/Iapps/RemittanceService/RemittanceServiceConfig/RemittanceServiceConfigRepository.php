<?php

namespace Iapps\RemittanceService\RemittanceServiceConfig;

use Iapps\Common\Core\IappsBaseRepository;
use Iapps\RemittanceService\Common\CacheKey;

class RemittanceServiceConfigRepository extends IappsBaseRepository{

    protected $defaultCacheKey = CacheKey::REMITTANCE_SERVICE_ID;

    public function findAll()
    {
        $cacheKey = CacheKey::REMITTANCE_SERVICE_LIST;

        if( $result = $this->getElasticCache($cacheKey) )
            return $result;

        if( $result = $this->getDataMapper()->findAll() )
        {
            $this->setElasticCache($cacheKey, $result);
        }

        return $result;
    }

    public function findByFromAndToCountryCurrencyCode($from_country_currency_code, $to_country_currency_code)
    {
        $cacheKey = CacheKey::REMITTANCE_SERVICE_FROM_TO . $from_country_currency_code . $to_country_currency_code;

        if( $result = $this->getElasticCache($cacheKey) )
            return $result;

        if( $result = $this->getDataMapper()->findByFromAndToCountryCurrencyCode($from_country_currency_code, $to_country_currency_code) )
        {
            $this->setElasticCache($cacheKey, $result);
        }

        return $result;
    }

    public function findByFromCountryCurrencyList($from_country_currency_list)
    {
        return $this->getDataMapper()->findByFromCountryCurrencyList($from_country_currency_list);
    }

    public function add(RemittanceServiceConfig $config)
    {
        $this->_removeCache($config);
        return $this->getDataMapper()->insert($config);
    }

    public function update(RemittanceServiceConfig $config)
    {
        $this->_removeCache($config);
        return $this->getDataMapper()->update($config);
    }

    public function updateRates(RemittanceServiceConfig $config)
    {
        $this->_removeCache($config);
        return $this->getDataMapper()->updateRates($config);
    }

    public function findByIds(array $corporate_service_ids)
    {
        return $this->getDataMapper()->findByIds($corporate_service_ids);
    }

    protected function _removeCache(RemittanceServiceConfig $config)
    {
        $cacheKeys = array(
            CacheKey::REMITTANCE_CONFIG_ID . $config->getId(),
            CacheKey::REMITTANCE_SERVICE_LIST,
            CacheKey::REMITTANCE_SERVICE_FROM_TO . $config->getFromCountryCurrencyCode() . $config->getToCountryCurrencyCode()
        );

        foreach($cacheKeys AS $cacheKey )
            $this->deleteElastiCache($cacheKey);
    }
}