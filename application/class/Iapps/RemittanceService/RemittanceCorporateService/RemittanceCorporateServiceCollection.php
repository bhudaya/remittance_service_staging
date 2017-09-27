<?php

namespace Iapps\RemittanceService\RemittanceCorporateService;

use Iapps\Common\Core\IappsBaseEntityCollection;
use Iapps\Common\CorporateService\CorporateServiceCollection;
use Iapps\Common\Microservice\AccountService\User;
use Iapps\RemittanceService\ExchangeRate\ExchangeRateCollection;
use Iapps\RemittanceService\RemittanceServiceConfig\RemittanceServiceConfigCollection;

class RemittanceCorporateServiceCollection extends CorporateServiceCollection{

    public function joinPartnerName(IappsBaseEntityCollection $corpCollection )
    {
        foreach( $this AS $entity)
        {
            if( $user = $corpCollection->getById($entity->getServiceProviderId()) )
            {
                if( $user instanceof User )
                {
                    $entity->setServiceProviderName($user->getName());
                }
            }
        }

        return $this;
    }

    public function joinRemittanceService(RemittanceServiceConfigCollection $reCollection )
    {
        foreach( $this AS $entity)
        {
            if( $reServ = $reCollection->getById($entity->getConversionRemittanceService()->getId()))
            {
                $entity->setConversionRemittanceService($reServ);
            }
        }

        return $this;
    }

    public function joinExchangeRate(ExchangeRateCollection $exCollection)
    {
        foreach( $this AS $entity)
        {
            if( $id = $entity->getExchangeRateId() )
            {
                if( $rate = $exCollection->getById($id) )
                {
                    $entity->setExchangeRateObj($rate);
                }
            }
        }

        return $this;
    }
}