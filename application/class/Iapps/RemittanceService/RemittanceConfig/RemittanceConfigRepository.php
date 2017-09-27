<?php

namespace Iapps\RemittanceService\RemittanceConfig;

use Iapps\RemittanceService\Common\CacheKey;
use Iapps\Common\Core\IappsBaseRepository;


class RemittanceConfigRepository extends IappsBaseRepository{

    protected $defaultCacheKey = CacheKey::REMITTANCE_CONFIG_ID;

    public function findAll($limit, $page)
    {
        if( $limit == MAX_VALUE AND $page = 1 )
        {//try to find cached
            $cacheKey = CacheKey::REMITTANCE_CONFIG_ALL;
            if( !$result = $this->getElasticCache($cacheKey) )                
            {
                $result = $this->getDataMapper()->findAll($limit, $page);
                if( $result )
                    $this->setElasticCache($cacheKey, $result);
            }
            
            return $result;
        }
        else
            return $this->getDataMapper()->findAll($limit, $page);
    }

    public function findByIdArr(array $remittance_config_id_arr)
    {
        return $this->getDataMapper()->findByIdArr($remittance_config_id_arr);
    }

    public function findBySearchFilter(RemittanceConfig $remittanceConfig,$limit = NULL , $page = NULL)
    {
        return $this->getDataMapper()->findBySearchFilter($remittanceConfig,$limit, $page);
    }

    public function findByRemittanceServiceIds(array $remittanceServiceIds, RemittanceConfig $configFilter, $limit = NULL, $page = NULL )
    {
        return $this->getDataMapper()->findByRemittanceServiceIds($remittanceServiceIds, $configFilter, $limit, $page);
    }

    public function add(RemittanceConfig $config)
    {
        $this->_removeCache($config);
        return $this->getDataMapper()->insert($config);
    }

    public function edit(RemittanceConfig $config)
    {
        $this->_removeCache($config);
        return $this->getDataMapper()->update($config);
    }

    public function updateRemittanceConfigStatus(RemittanceConfig $config)
    {
        $this->_removeCache($config);
        return $this->getDataMapper()->updateStatus($config);
    }

    public function findByCorporateServiceIds(array $cashInCorporateServiceIds = NULL, array $cashOutCorporateServiceIds = NULL, RemittanceConfig $configFilter = NULL, $limit = NULL, $page = NULL )
    {
        return $this->getDataMapper()->findByCorporateServiceIds($cashInCorporateServiceIds, $cashOutCorporateServiceIds, $configFilter, $limit, $page);
    }
    
    public function findExistsRemittanceConfig($limit, $page, $remittanceConfigId = NULL, $cashInCountryCurrencyCode, $cashOutCountryCurrencyCode, $cashInCountryPartnerId, $cashOutCountryPartnerId, array $status = NULL)
    {
        return $this->getDataMapper()->findExists($limit, $page, $remittanceConfigId, $cashInCountryCurrencyCode, $cashOutCountryCurrencyCode, $cashInCountryPartnerId, $cashOutCountryPartnerId, $status);
    }

    protected function _removeCache(RemittanceConfig $config)
    {
        $cacheKeys = array(
            CacheKey::REMITTANCE_CONFIG_ID . $config->getId(),
            CacheKey::REMITTANCE_CONFIG_ALL
        );

        foreach($cacheKeys AS $cacheKey )
            $this->deleteElastiCache($cacheKey);
    }
}