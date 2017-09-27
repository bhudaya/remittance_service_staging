<?php

namespace Iapps\RemittanceService\Common;

use Iapps\Common\CorporateService\CorporateServicePaymentMode;
use Iapps\Common\CorporateService\CorporateServicePaymentModeRepository;

class RemittanceCorporateServicePaymentModeRepository extends CorporateServicePaymentModeRepository{

    public function findAllByCorporateServiceId($corporate_service_id)
    {
        $cacheKey = CacheKey::REMITTANCE_PAYMENT_MODE_CORPORATE_SERVICE_ID . $corporate_service_id;

        if( !$result = $this->getElasticCache($cacheKey) )
        {
            if( $result = parent::findAllByCorporateServiceId($corporate_service_id) )
            {
                $this->setElasticCache($cacheKey, $result);
            }
        }

        return $result;
    }

    public function update(CorporateServicePaymentMode $CorporateServicePaymentMode)
    {
        $this->_removeCache($CorporateServicePaymentMode);
        return parent::update($CorporateServicePaymentMode);
    }

    public function insert(CorporateServicePaymentMode $CorporateServicePaymentMode)
    {
        $this->_removeCache($CorporateServicePaymentMode);
        return parent::insert($CorporateServicePaymentMode);
    }

    public function delete(CorporateServicePaymentMode $CorporateServicePaymentMode)
    {
        $this->_removeCache($CorporateServicePaymentMode);
        return parent::insert($CorporateServicePaymentMode);
    }

    protected function _removeCache(CorporateServicePaymentMode $corporateServicePaymentMode)
    {
        $cacheKeys = array(
            CacheKey::REMITTANCE_PAYMENT_MODE_CORPORATE_SERVICE_ID . $corporateServicePaymentMode->getCorporateServiceId(),
            CacheKey::REMITTANCE_PAYMENT_MODE_CORPORATE_SERVICE_ID2 . $corporateServicePaymentMode->getCorporateServiceId()
        );

        foreach( $cacheKeys AS $cacheKey)
        {
            $this->_removeCache($cacheKey);
        }
    }
}