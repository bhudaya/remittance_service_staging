<?php

namespace Iapps\RemittanceService\RemittanceTransaction;

use Iapps\Common\Core\IappsBaseDataMapper;

interface ITransactionProfitCostDataMapper extends IappsBaseDataMapper{

    public function findByParam(TransactionProfitCost $profitCost, $limit, $page);
    public function insert(TransactionProfitCost $profitCost);
    public function update(TransactionProfitCost $profitCost);
}