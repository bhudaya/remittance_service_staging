<?php

namespace Iapps\RemittanceService\RemittanceTransaction;

use Iapps\Common\Transaction\TransactionItemCollection;

class RemittanceTransactionItemCollection extends TransactionItemCollection{

    public function groupItemsBySpreadAndFee()
    {
        $collection = new RemittanceTransactionItemCollection();

        if( $main_item = $this->_getMainItem() )
        {
            $collection->addData($main_item);
        }

        if( $serviceFee = $this->_getServiceFeeItems() ) {
            $collection->addData($serviceFee);
        }

        if( $paymentFee = $this->_getPaymentFeeItems() ) {
            $collection->addData($paymentFee);
        }

        $this->_combineOtherItems($collection);

        return $collection;
    }
    
    public function getMainItem()
    {
        return $this->_getMainItem();
    }
    
    public function getServiceFeeItem()
    {
        return $this->_getServiceFeeItems();
    }
    
    public function getPaymentFeeItem()
    {
        return $this->_getPaymentFeeItems();
    }

    protected function _getMainItem()
    {
        foreach($this AS $item)
        {
            if( $item instanceof RemittanceTransactionItem )
            {
                if( $item->isMainItem() )
                {
                    return $item;
                }
            }
        }

        return false;
    }

    protected function _getServiceFeeItems()
    {
        $fee = NULL;
        foreach($this AS $item)
        {
            if( $item instanceof RemittanceTransactionItem )
            {
                if( $item->isServiceFee() )
                {
                    if( $fee == NULL )
                    {
                        $fee = $item;
                        $fee->setName('Service Fee');
                        $fee->setDescription('Service Fee');
                    }
                    else
                    {
                        $fee->combineItem($item);
                    }
                }
            }
        }

        return $fee;
    }

    protected function _getPaymentFeeItems()
    {
        $fee = NULL;
        foreach($this AS $item)
        {
            if( $item instanceof RemittanceTransactionItem )
            {
                if( $item->isPaymentFee() )
                {
                    if( $fee == NULL )
                    {
                        $fee = $item;
                        $fee->setName('Payment Fee');
                        $fee->setDescription('Payment Fee');
                    }
                    else
                    {
                        $fee->combineItem($item);
                    }
                }
            }
        }

        return $fee;
    }

    protected function _combineOtherItems(RemittanceTransactionItemCollection $itemCol)
    {
        foreach($this AS $item)
        {
            if( $item instanceof RemittanceTransactionItem )
            {
                if( !$item->isMainItem() AND
                    !$item->isServiceFee() AND
                    !$item->isPaymentFee() )
                    $itemCol->addData($item);

            }
            else
                $itemCol->addData($item);
        }

        return $itemCol;
    }
}