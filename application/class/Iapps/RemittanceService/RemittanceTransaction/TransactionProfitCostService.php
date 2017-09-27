<?php

namespace Iapps\RemittanceService\RemittanceTransaction;

use Iapps\Common\Core\IappsBaseService;
use Iapps\RemittanceService\Common\MessageCode;

class TransactionProfitCostService extends IappsBaseService{

    public function addProfitCost(TransactionProfitCost $profitCost)
    {
        $profitCost->setCreatedBy($this->getUpdatedBy());

        if( $this->getRepository()->insert($profitCost) )
        {
            return $profitCost;
        }

        $this->setResponseCode(MessageCode::CODE_FAILED_TO_ADD_PROFIT_COST);
        return false;
    }
}