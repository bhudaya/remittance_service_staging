<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Iapps\RemittanceService\RemittancePaymentMode;

use Iapps\Common\Core\IappsBaseEntityCollection;

/**
 * Description of PaymentModeCostCollection
 *
 * @author lichao
 */
class PaymentModeCostCollection extends IappsBaseEntityCollection {
    //put your code here

    public function joinServiceProvider($users)
    {
        foreach($this AS $entity)
        {
            foreach($users AS $user)
            {
                if( $entity->getServiceProviderId() == $user->getId() )
                {
                    $entity->setServiceProviderName($user->getName());
                    break;
                }
            }
        }

        return $this;
    }
    
    public function joinRole($roles)
    {
        foreach($this AS $entity)
        {
            foreach($roles AS $role)
            {
                if( $entity->getRoleId() == $role->getId() )
                {
                    $entity->setRoleName($role->getName());
                    break;
                }
            }
        }

        return $this;
    }
    
}
