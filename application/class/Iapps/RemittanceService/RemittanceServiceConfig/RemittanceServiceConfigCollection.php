<?php

namespace Iapps\RemittanceService\RemittanceServiceConfig;


use Iapps\Common\Core\IappsBaseEntityCollection;

class RemittanceServiceConfigCollection extends IappsBaseEntityCollection{

    public function filter(RemittanceServiceConfig $filter)
    {
        $filteredList = new RemittanceServiceConfigCollection();
        foreach( $this AS $service)
        {
            if( $service instanceof RemittanceServiceConfig )
            {
                if( $from = $filter->getFromCountryCurrencyCode() )
                {
                    if( $service->getFromCountryCurrencyCode() != $from )
                        continue;
                }

                if( $to = $filter->getToCountryCurrencyCode() )
                {
                    if( $service->getToCountryCurrencyCode() != $to )
                        continue;
                }

                $filteredList->addData($service);
            }
        }

        return $filteredList;
    }

    public function getByForInternational($forInternational = true)
    {
        $filteredList = new RemittanceServiceConfigCollection();
        foreach( $this AS $service)
        {
            if( $service instanceof RemittanceServiceConfig )
            {
                if( $forInternational )
                {
                    if( $service->isInternational() )
                    {
                        $filteredList->addData($service);
                        continue;
                    }
                }
                else
                {
                    if( $service->isDomestic() )
                    {
                        $filteredList->addData($service);
                        continue;
                    }
                }
            }
        }

        return $filteredList;
    }
}