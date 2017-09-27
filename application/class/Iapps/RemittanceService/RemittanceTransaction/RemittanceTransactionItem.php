<?php

namespace Iapps\RemittanceService\RemittanceTransaction;

use Iapps\Common\Core\IappsBaseEntity;
use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\CorporateService\CorporateService;
use Iapps\Common\CorporateService\CorporateServiceFee;
use Iapps\Common\CorporateService\CorporateServicePaymentModeFee;
use Iapps\Common\Transaction\TransactionItem;

class RemittanceTransactionItem extends TransactionItem {

    protected $itemInfo;

    public function setItemInfo(IappsBaseEntity $item)
    {
        $this->itemInfo = $item;
        return $this;
    }

    public function getItemInfo()
    {
        return $this->itemInfo;
    }

    public function isMainItem()
    {
        return ($this->getItemType()->getCode() == ItemType::CORPORATE_SERVICE);
    }

    public function isServiceFee()
    {
        return ($this->getItemType()->getCode() == ItemType::CORPORATE_SERVICE_FEE);
    }

    public function isPaymentFee()
    {
        return ($this->getItemType()->getCode() == ItemType::PAYMENT_FEE);
    }

    public function combineItem(RemittanceTransactionItem $item)
    {
        $this->setUnitPrice($this->getUnitPrice() + $item->getUnitPrice() );
    }
}