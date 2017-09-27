<?php

namespace Iapps\RemittanceService\RemittanceTransaction;

use Iapps\Common\Core\IappsBaseRepository;
use Iapps\Common\Transaction\TransactionItem;
use Iapps\Common\Transaction\TransactionItemRepositoryV2;

class RemittanceTransactionItemRepository extends TransactionItemRepositoryV2{

    public function update(TransactionItem $item)
    {
        $this->_removeCache($item);
        return $this->getDataMapper()->update($item);
    }

}