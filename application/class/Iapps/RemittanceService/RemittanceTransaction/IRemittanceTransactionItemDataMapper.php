<?php

namespace Iapps\RemittanceService\RemittanceTransaction;

use Iapps\Common\Transaction\ITransactionItemDataMapper;
use Iapps\Common\Transaction\TransactionItem;


interface IRemittanceTransactionItemDataMapper extends ITransactionItemDataMapper{

    public function update(TransactionItem $item);

}