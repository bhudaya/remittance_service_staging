<?php

namespace Iapps\RemittanceService\RemittanceTransaction;

use Iapps\Common\Transaction\Transaction;
use Iapps\RemittanceService\Common\TransactionType;

class RemittanceTransaction extends Transaction{

    protected $recipient_id;
    protected $confirm_collection_mode;
    protected $profit_cost_items;

    function __construct()
    {
        parent::__construct();

        $this->items = new RemittanceTransactionItemCollection();
        $this->profit_cost_items = new TransactionProfitCostCollection();
    }

    public function setRecipientId($recipientId)
    {
        $this->recipient_id = $recipientId;
        return $this;
    }

    public function getRecipientId()
    {
        return $this->recipient_id;
    }

    public function setConfirmCollectionMode($collectionMode)
    {
        $this->confirm_collection_mode = $collectionMode;
        return $this;
    }

    public function getConfirmCollectionMode()
    {
        return $this->confirm_collection_mode;
    }

    public function setProfitCostItems(TransactionProfitCostCollection $items)
    {
        $this->profit_cost_items = $items;
        return $this;
    }

    public function addProfitCostItem(TransactionProfitCost $profitCost)
    {
        $profitCost->setTransactionId($this->getId());
        $this->getProfitCostItems()->addData($profitCost);
        return $this;
    }

    public function getProfitCostItems()
    {
        return $this->profit_cost_items;
    }

    public function jsonSerialize()
    {
        $json = parent::jsonSerialize();

        $json['confirm_collection_mode'] = $this->getConfirmCollectionMode();

        return $json;
    }

    public function getCombinedTransactionArray(array $fields = NULL)
    {
        $transactionArray = $this->getSelectedField($fields);
        $combinedItems = $this->getItems()->groupItemsBySpreadAndFee();

        if( array_key_exists('items', $fields) )
            $transactionArray['items'] = $combinedItems->getSelectedField($fields['items']);
        else
            $transactionArray['items'] = $combinedItems->jsonSerialize();

        return $transactionArray;
    }

    public function isCashIn()
    {
        return ($this->getTransactionType()->getCode() == TransactionType::CODE_CASH_IN OR
                $this->getTransactionType()->getCode() == TransactionType::CODE_LOCAL_CASH_IN);
    }

    public function isCashOut()
    {
        return ($this->getTransactionType()->getCode() == TransactionType::CODE_CASH_OUT OR
            $this->getTransactionType()->getCode() == TransactionType::CODE_LOCAL_CASH_OUT);
    }
}