<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Iapps\RemittanceService\RemittanceTransaction;

use Iapps\Common\AuditLog\AuditLogAction;
use Iapps\Common\Transaction\TransactionItem;
use Iapps\Common\Transaction\TransactionItemService;
use Iapps\RemittanceService\Common\MessageCode;
/**
 * Description of RemittanceTransactionItemService
 *
 * @author lichao
 */
class RemittanceTransactionItemService extends TransactionItemService
{
    //put your code here

    public function findByTransactionId($transactionId)
    {
        if ($collection = $this->getRepository()->findByTransactionId($transactionId)) {
            $this->setResponseCode(MessageCode::CODE_LIST_REMITTANCE_TRANSACTION_ITEM_SUCCESS);
            return $collection;
        }

        $this->setResponseCode(MessageCode::CODE_LIST_REMITTANCE_TRANSACTION_ITEM_FAILED);
        return false;
    }

    //override
    public function insertItem(TransactionItem $item)
    {
        if (!($item instanceof RemittanceTransactionItem))
            return false;

        if ($result = parent::insertItem($item)) {
            $this->fireLogEvent('iafb_remittance.transaction_item', AuditLogAction::CREATE, $item->getId());
            return $result;
        }

        return false;
    }

    public function update(TransactionItem $item, TransactionItem $oriItem)
    {
        $item->setUpdatedBy($this->getUpdatedBy());

        if($this->getRepository()->update($item)) {

            $this->fireLogEvent('iafb_remittance.transaction_item', AuditLogAction::UPDATE, $item->getId(), $oriItem);
            return true;

        }

        return false;
    }
}
