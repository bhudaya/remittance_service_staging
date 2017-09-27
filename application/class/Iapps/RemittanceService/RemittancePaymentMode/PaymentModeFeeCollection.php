<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Iapps\RemittanceService\RemittancePaymentMode;

use Iapps\Common\Core\IappsBaseEntityCollection;

/**
 * Description of PaymentModeFeeCollection
 *
 * @author lichao
 */
class PaymentModeFeeCollection extends IappsBaseEntityCollection {

    public static function fromArray(array $array )
    {
        $collection = new PaymentModeFeeCollection();
        foreach($array AS $entity)
        {
            if( $entity instanceof PaymentModeFee )
                $collection->addData($entity);
        }

        return $collection;
    }

    public function getApplicableFee($amount)
    {
        foreach( $this AS $feeItem )
        {
            if( $feeItem instanceof PaymentModeFee )
            {
                switch($feeItem->getMultitierType())
                {
                    case PaymentModeFeeMultitierType::CODE_FLAT:
                        return $feeItem;
                        break;
                    case PaymentModeFeeMultitierType::CODE_LESSTHAN:
                        if( $amount <= $feeItem->getReferenceValue1() )
                            return $feeItem;
                        break;
                    case PaymentModeFeeMultitierType::CODE_GREATERTHAN:
                        if( $amount > $feeItem->getReferenceValue1() )
                            return $feeItem;
                        break;
                    case PaymentModeFeeMultitierType::CODE_RANGE:
                        if( $amount > $feeItem->getReferenceValue1() AND
                            $amount <= $feeItem->getReferenceValue2() )
                            return $feeItem;
                        break;
                    default:
                        break;
                }
            }
        }

        return false;
    }
}
