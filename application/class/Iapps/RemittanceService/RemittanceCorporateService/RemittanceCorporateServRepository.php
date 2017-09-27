<?php
/**
 * Created by PhpStorm.
 * User: zhoulin
 * Date: 24/06/16
 * Time: 下午5:33
 */

namespace Iapps\RemittanceService\RemittanceCorporateService;

use Iapps\Common\Core\Exception\DataMapperException;
use Iapps\Common\Core\IappsBaseDataMapper;
use Iapps\Common\Core\IappsBaseRepository;
use Iapps\Common\CorporateService\CorporateService;
use Iapps\Common\CorporateService\CorporateServiceRepository;
use Iapps\RemittanceService\Common\CacheKey;

class RemittanceCorporateServRepository extends CorporateServiceRepository
{
    protected $defaultCacheKey = CacheKey::REMITTANCE_CORPORATE_SERVICE_ID;

    public function findByIds($corporate_service_ids)
    {
        return $this->getDataMapper()->findByIds($corporate_service_ids);
    }

    public function findByServiceProviderIds(array $service_provider_ids)
    {
        return $this->getDataMapper()->findByServiceProviderIds($service_provider_ids);
    }
    
    public function getCorporateServiceByServiceProId($service_provider_id)
    {
        return $this->getDataMapper()->getCorporateServiceByServiceProId($service_provider_id);
    }

    /* what is this?
    public function findByFromAndToCountryCurrencyCode($from_country_currency_code, $to_country_currency_code)
    {
        return $this->getDataMapper()->findByFromAndToCountryCurrencyCode($from_country_currency_code, $to_country_currency_code);
    }*/

    public function update(CorporateService $CorporateService)
    {
        $this->_removeCache($CorporateService);
        return parent::update($CorporateService);
    }

    public function insert(CorporateService $CorporateService)
    {
        $this->_removeCache($CorporateService);
        return parent::insert($CorporateService);
    }

    public function delete(CorporateService $CorporateService)
    {
        $this->_removeCache($CorporateService);
        return parent::delete($CorporateService);
    }

    protected function _removeCache(CorporateService $remittanceCorporateService)
    {
        $cacheKeys = array(
            CacheKey::REMITTANCE_CORPORATE_SERVICE_ID . $remittanceCorporateService->getId()
        );

        foreach($cacheKeys AS $cacheKey )
            $this->deleteElastiCache($cacheKey);
    }
}