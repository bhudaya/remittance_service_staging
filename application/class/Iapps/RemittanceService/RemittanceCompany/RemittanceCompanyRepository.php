<?php

namespace Iapps\RemittanceService\RemittanceCompany;

use Iapps\Common\Core\IappsBaseRepository;
use Iapps\RemittanceService\Common\CacheKey;

class RemittanceCompanyRepository extends IappsBaseRepository{
    
    protected $defaultCacheKey = CacheKey::REMITTANCE_COMPANY_ID;
    
    public function findAll()
    {
        $cacheKey = CacheKey::REMITTANCE_COMPANY_LIST;

        if( $result = $this->getElasticCache($cacheKey) )
            return $result;

        if( $result = $this->findByFilter(new RemittanceCompany()) )
        {
            $this->setElasticCache($cacheKey, $result);
        }

        return $result;
    }

    public function findByServiceProviderId($service_provider_id)
    {
        $cacheKey = CacheKey::REMITTANCE_COMPANY_SERVICE_PROVIDER_ID . $service_provider_id;

        if( $result = $this->getElasticCache($cacheKey) )
            return $result;


        $filter = (new RemittanceCompany())->setServiceProviderId($service_provider_id);
        if( $result = $this->findByFilter($filter) )
        {
            $this->setElasticCache($cacheKey, $result);
        }

        return $result;
    }

    public function findByCompanyCode($company_code)
    {
        $cacheKey = CacheKey::REMITTANCE_COMPANY_CODE . $company_code;

        if( $result = $this->getElasticCache($cacheKey) )
            return $result;


        $filter = (new RemittanceCompany())->setCompanyCode($company_code);
        if( $result = $this->findByFilter($filter) )
        {
            $this->setElasticCache($cacheKey, $result);
        }

        return $result;
    }

    public function findByFilter(RemittanceCompany $remittanceCompany)
    {
        return $this->getDataMapper()->findByFilter($remittanceCompany);
    }

    public function updateByServiceProviderId(RemittanceCompany $remittanceCompany)
    {
        $this->_removeCache($remittanceCompany);
        return $this->getDataMapper()->updateByServiceProviderId($remittanceCompany);
    }

    protected function _removeCache(RemittanceCompany $config)
    {
        $cacheKeys = array(
            CacheKey::REMITTANCE_COMPANY_ID . $config->getId(),
            CacheKey::REMITTANCE_COMPANY_LIST,
            CacheKey::REMITTANCE_COMPANY_SERVICE_PROVIDER_ID . $config->getServiceProviderId(),
            CacheKey::REMITTANCE_COMPANY_CODE . $config->getCompanyCode()
        );

        foreach($cacheKeys AS $cacheKey )
            $this->deleteElastiCache($cacheKey);
    }
}