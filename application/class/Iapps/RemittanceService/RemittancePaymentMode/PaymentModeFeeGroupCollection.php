<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Iapps\RemittanceService\RemittancePaymentMode;

use Iapps\Common\Core\IappsBaseEntityCollection;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeFeeCollection;

/**
 * Description of PaymentModeFeeGroupCollection
 *
 * @author lichao
 */
class PaymentModeFeeGroupCollection extends IappsBaseEntityCollection {
    
    public function joinPaymentModeFeeItems(PaymentModeFeeCollection $paymentModeFeeCollection)
    {
        foreach($this AS $group)
        {
            foreach($paymentModeFeeCollection AS $entityPaymentModeFee)
            {
                if( $group->getId() == $entityPaymentModeFee->getCorporateServicePaymentModeFeeGroupId() )
                {
                    $group->addPaymentModeFeeItems($entityPaymentModeFee);
                }
            }
        }

        return $this;
    }
    
}
