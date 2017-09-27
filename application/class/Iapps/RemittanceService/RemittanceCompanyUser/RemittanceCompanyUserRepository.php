<?php

namespace Iapps\RemittanceService\RemittanceCompanyUser;

use Iapps\Common\Core\IappsBaseRepository;
use Iapps\RemittanceService\Common\CacheKey;

class RemittanceCompanyUserRepository extends IappsBaseRepository{

    public function findByCustomerID($customerID)
    {
        $cacheKey = CacheKey::REMITTANCE_COMPANY_USER_CUSTOMERID . $customerID;
        
        if( !$result = $this->getElasticCache($cacheKey) )
        {
            $filter = new RemittanceCompanyUser();
            $filter->setCustomerID($customerID);            
            if( $result = $this->findByFilter($filter) )
            {
                $result = $result->getResult()->current();
                $this->setElasticCache($cacheKey, $result);
            }
        }
        
        return $result;
    }
    
    public function findByFilter(RemittanceCompanyUser $filter)
    {
        return $this->getDataMapper()->findByFilter($filter);
    }

    public function findByFilters(RemittanceCompanyUserCollection $filters)
    {
        return $this->getDataMapper()->findByFilters($filters);
    }

    public function insert(RemittanceCompanyUser $remittanceCompanyUser)
    {
        $this->_removeCache($remittanceCompanyUser);
        return $this->getDataMapper()->insert($remittanceCompanyUser);
    }

    public function update(RemittanceCompanyUser $remittanceCompanyUser, $checkNull = true)
    {
        $this->_removeCache($remittanceCompanyUser);
        return $this->getDataMapper()->update($remittanceCompanyUser, $checkNull);
    }
    
    protected function _removeCache(RemittanceCompanyUser $remittanceCompanyUser)
    {
        $cacheKeys = array(
            CacheKey::REMITTANCE_COMPANY_USER_CUSTOMERID . $remittanceCompanyUser->getCustomerID()
        );
        
        foreach($cacheKeys AS $key)
        {
            $this->deleteElastiCache($key);
        }
    }
}