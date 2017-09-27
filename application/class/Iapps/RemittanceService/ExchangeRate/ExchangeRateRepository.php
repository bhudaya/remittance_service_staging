<?php

namespace Iapps\RemittanceService\ExchangeRate;

use Iapps\Common\Core\IappsBaseRepository;
use Iapps\Common\Core\IappsDateTime;

class ExchangeRateRepository extends IappsBaseRepository{

    public function findByIds(array $ids)
    {
        return $this->getDataMapper()->findByIds($ids);
    }

    public function findAll($limit, $page)
    {
        return $this->getDataMapper()->findAll($limit, $page);
    }

    public function findByParam(ExchangeRate $exchangeRate, $limit = NULL, $page = NULL, IappsDateTime $fromCreatedDT = NULL, IappsDateTime $toCreatedDT = NULL )
    {
        return $this->getDataMapper()->findByParam($exchangeRate, $limit, $page, $fromCreatedDT, $toCreatedDT);
    }

    public function findByCorpServIdsAndStatuses(array $corpServIds, array $statuses, array $channels = array(), $limit = NULL, $page = NULL, IappsDateTime $fromApprovalDT = NULL, IappsDateTime $toApprovalDT = NULL)
    {
        return $this->getDataMapper()->findByCorpServIdsAndStatuses($corpServIds, $statuses, $channels, $limit, $page, $fromApprovalDT, $toApprovalDT);
    }

    public function insert(ExchangeRate $exchangeRate)
    {
        return $this->getDataMapper()->insert($exchangeRate);
    }

    public function update(ExchangeRate $exchangeRate)
    {
        return $this->getDataMapper()->update($exchangeRate);
    }
}