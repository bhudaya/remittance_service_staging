<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Iapps\RemittanceService\RemittancePaymentMode;

use Iapps\Common\Core\IappsBaseEntityCollection;

/**
 * Description of PaymentModeCostGroupCollection
 *
 * @author lichao
 */
class PaymentModeCostGroupCollection extends IappsBaseEntityCollection {
    //put your code here

    public function joinPaymentModeCostItems(PaymentModeCostCollection $costCollection)
    {
        foreach( $this AS $group )
        {
            foreach($costCollection AS $cost)
            {
                if( $cost->getPaymentModeGroupId() == $group->getId() )
                {
                    $group->addPaymentModeCostItems($cost);
                }
            }
        }

        return $this;
    }
}
