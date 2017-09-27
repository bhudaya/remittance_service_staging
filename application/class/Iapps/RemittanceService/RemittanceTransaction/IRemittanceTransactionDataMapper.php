<?php

namespace Iapps\RemittanceService\RemittanceTransaction;

use Iapps\Common\Core\IappsBaseDataMapper;
use \Iapps\Common\Transaction\ITransactionDataMapper;
use Iapps\Common\Transaction\Transaction;

interface IRemittanceTransactionDataMapper extends ITransactionDataMapper{

    public function TransBegin();
    public function TransCommit();
    public function TransStatus();

    public function getTransactionByRecipientId($recipientId);

    public function update(Transaction $transaction);
}