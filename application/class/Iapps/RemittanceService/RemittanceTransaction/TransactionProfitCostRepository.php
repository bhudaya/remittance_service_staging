<?php

namespace Iapps\RemittanceService\RemittanceTransaction;

use Iapps\Common\Core\IappsBaseRepository;

class TransactionProfitCostRepository extends IappsBaseRepository{

    public function findByParam(TransactionProfitCost $profitCost, $limit, $page)
    {
        return $this->getDataMapper()->findByParam($profitCost, $limit, $page);
    }

    public function insert(TransactionProfitCost $profitCost)
    {
        return $this->getDataMapper()->insert($profitCost);
    }

    public function update(TransactionProfitCost $profitCost)
    {
        return $this->getDataMapper()->update($profitCost);
    }
}