<?php

namespace Iapps\RemittanceService\RemittanceProfitSharing;

use Iapps\Common\Core\IappsBaseRepository;
use Iapps\RemittanceService\Common\CacheKey;

class RemittanceCorpServProfitSharingRepository extends IappsBaseRepository
{   
    public function insert(RemittanceCorpServProfitSharing $corp_serv_profit_sharing)
    {
        $this->_removeCache($corp_serv_profit_sharing);
        return $this->dataMapper->insert($corp_serv_profit_sharing);
    }

    public function checkHasOtherPendingProfitSharing($corporate_service_id)
    {
        return $this->dataMapper->checkHasOtherPendingProfitSharing($corporate_service_id);
    }

    public function findAllByStatus($collection, $limit, $page, $is_active = NULL, $status = NULL, $corporate_service_id = NULL)
    {
        if( $is_active == NULL  AND $status == NULL )
        {
            $cacheKey = CacheKey::REMITTANCE_PROFIT_SHARING_CORPORATE_ID . $corporate_service_id;
            if( $result = $this->getElasticCache($cacheKey) )
            {
                return $result;
            }
            else
            {
                $result = $this->getDataMapper()->findAllByStatus($collection, $limit, $page, $is_active, $status, $corporate_service_id);
                $this->setElasticCache($cacheKey, $result);
                return $result;
            }
        }

        return $this->dataMapper->findAllByStatus($collection, $limit, $page, $is_active, $status, $corporate_service_id);
    }

    public function findByParam(RemittanceCorpServProfitSharing $corp_serv_profit_sharing, $limit = NULL, $page = NULL)
    {
        return $this->dataMapper->findByParam($corp_serv_profit_sharing, $limit, $page);
    }

    public function update(RemittanceCorpServProfitSharing $corp_serv_profit_sharing)
    {
        $this->_removeCache($corp_serv_profit_sharing);
        return $this->dataMapper->update($corp_serv_profit_sharing);
    }
    
    public function findAllList($limit, $page, array $corporateServiceIds = NULL, $isActive = NULL, array $status = NULL)
    {
        return $this->getDataMapper()->findAllList($limit, $page, $corporateServiceIds, $isActive, $status);
    }

    protected function _removeCache(RemittanceCorpServProfitSharing $remittanceCorpServProfitSharing)
    {
        $cacheKeys = array(
            $cacheKey = CacheKey::REMITTANCE_PROFIT_SHARING_CORPORATE_ID . $remittanceCorpServProfitSharing->getId()
        );

        foreach($cacheKeys AS $cacheKey)
        {
            $this->deleteElastiCache($cacheKey);
        }
    }
}

