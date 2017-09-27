<?php

namespace Iapps\RemittanceService\ExchangeRate;

use Iapps\Common\Core\IappsBaseDataMapper;
use Iapps\Common\Core\IappsDateTime;

interface IExchangeRateDataMapper extends IappsBaseDataMapper{

    public function findByIds(array $ids);
    public function findAll($limit, $page);
    public function insert(ExchangeRate $exchangeRate);
    public function findByParam(ExchangeRate $exchangeRate, $limit = NULL, $page = NULL, IappsDateTime $fromCreatedDT = NULL, IappsDateTime $toCreatedDT = NULL);
    public function findByCorpServIdsAndStatuses(array $corpServIds, array $statuses, array $channels = array(), $limit = NULL, $page = NULL, IappsDateTime $fromApprovalDT = NULL, IappsDateTime $toApprovalDT = NULL);
    public function update(ExchangeRate $exchangeRate);
}