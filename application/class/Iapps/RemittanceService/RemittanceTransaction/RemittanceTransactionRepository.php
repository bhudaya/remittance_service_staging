<?php

namespace Iapps\RemittanceService\RemittanceTransaction;

use Iapps\Common\Core\IappsBaseRepository;
use Iapps\Common\Transaction\TransactionRepositoryV2;

class RemittanceTransactionRepository extends TransactionRepositoryV2{

    //for manual transaction scope only, need to ensure no nested transactions inside the manual scope
    public function beginDBTransaction()
    {
        return $this->getDataMapper()->TransBegin();
    }

    public function commitDBTransaction()
    {
        return $this->getDataMapper()->TransCommit();
    }

    public function statusDBTransaction()
    {
        return $this->getDataMapper()->TransStatus();
    }

    public function getTransactionByRecipientId($recipientId)
    {
        return $this->getDataMapper()->getTransactionByRecipientId($recipientId);
    }

    public function update(RemittanceTransaction $remittanceTransaction)
    {
        $this->_removeCache($remittanceTransaction);
        return $this->getDataMapper()->update($remittanceTransaction);
    }
}