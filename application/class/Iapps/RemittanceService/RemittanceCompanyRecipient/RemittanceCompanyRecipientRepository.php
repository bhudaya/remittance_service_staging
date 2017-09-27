<?php

namespace Iapps\RemittanceService\RemittanceCompanyRecipient;

use Iapps\Common\Core\IappsBaseRepository;
use Iapps\RemittanceService\Common\CacheKey;

class RemittanceCompanyRecipientRepository extends IappsBaseRepository{

    public function findByRecipientId($recipient_id)
    {
        $cacheKey = CacheKey::REMITTANCE_COMPANY_RECIPIENT_RECIPIENT_ID . $recipient_id;
        
        if( !$result = $this->getElasticCache($cacheKey) )
        {
            $filter = new RemittanceCompanyRecipient();
            $filter->getRecipient()->setId($recipient_id);
            if( $result = $this->findByFilter($filter) )
                $this->setElasticCache ($cacheKey, $result);
        }
        
        return $result;
    }
    
    public function findByRecipientIdAndCompanyId($recipient_id, $company_id)
    {
        $cacheKey = CacheKey::REMITTANCE_COMPANY_RECIPIENT_RECIPIENT_ID_COMPANY_ID . $recipient_id . $company_id;
        
        if( !$result = $this->getElasticCache($cacheKey) )
        {
            $filter = new RemittanceCompanyRecipient();
            $filter->getRecipient()->setId($recipient_id);
            $filter->getRemittanceCompany()->setId($company_id);
            if( $result = $this->findByFilter($filter) )
                $this->setElasticCache ($cacheKey, $result);
        }
        
        return $result;
    }
    
    public function findByFilter(RemittanceCompanyRecipient $filter)
    {
        return $this->getDataMapper()->findByFilter($filter);
    }

    public function findByFilters(RemittanceCompanyRecipientCollection $filters)
    {
        return $this->getDataMapper()->findByFilters($filters);
    }

    public function insert(RemittanceCompanyRecipient $remittanceCompanyRecipient)
    {
        $this->_removeCache($remittanceCompanyRecipient);
        return $this->getDataMapper()->insert($remittanceCompanyRecipient);
    }

    public function update(RemittanceCompanyRecipient $remittanceCompanyRecipient, $checkNull = true)
    {
        $this->_removeCache($remittanceCompanyRecipient);
        return $this->getDataMapper()->update($remittanceCompanyRecipient, $checkNull);
    }
    
    protected function _removeCache(RemittanceCompanyRecipient $recipient)
    {
        $cacheKeys = array(
            CacheKey::REMITTANCE_COMPANY_RECIPIENT_RECIPIENT_ID . $recipient->getRecipient()->getId(),
            CacheKey::REMITTANCE_COMPANY_RECIPIENT_RECIPIENT_ID_COMPANY_ID . $recipient->getRecipient()->getId() . $recipient->getRemittanceCompany()->getId()
        );
        
        foreach($cacheKeys AS $cacheKey)
        {
            $this->deleteElastiCache($cacheKey);
        }
    }
}