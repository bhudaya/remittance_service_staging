<?php

namespace Iapps\RemittanceService\RemittanceCorporateService;

use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfigCollection;

class PartnerNameExtractor{

    public static function extract(RemittanceCorporateServiceCollection $collection)
    {
        $corporateIds = array();
        foreach( $collection AS $corpServ )
        {
            $corporateIds[] = $corpServ->getServiceProviderId();
        }

        $accServ = AccountServiceFactory::build();
        if( $corporates = $accServ->getUsers($corporateIds) )
        {
            $collection->joinPartnerName($corporates);
        }

        return $collection;
    }

    public static function extractFromRemittanceConfigCollection(RemittanceConfigCollection $collection)
    {
        $corpCollection = new RemittanceCorporateServiceCollection();
        foreach($collection AS $reConfig)
        {
            foreach( $reConfig->getCorporateServiceCollection() as $corp )
                $corpCollection->addData($corp);
        }

        self::extract($corpCollection);

        return $collection;
    }
}