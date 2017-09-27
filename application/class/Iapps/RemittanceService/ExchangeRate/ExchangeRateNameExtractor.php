<?php

namespace Iapps\RemittanceService\ExchangeRate;

use Iapps\Common\Core\IappsBaseEntityCollection;
use Iapps\Common\Helper\CreatorNameExtractor;

class ExchangeRateNameExtractor extends CreatorNameExtractor{

    protected function getIds(IappsBaseEntityCollection $collection)
    {
        $ids = parent::getIds($collection);

        foreach($collection AS $entity)
        {
            $ids[] = $entity->getApproveRejectBy();
        }

        return $ids;
    }

    protected function mapNames(IappsBaseEntityCollection $collection, IappsBaseEntityCollection $users)
    {
        $collection = parent::mapNames($collection, $users);

        if( $collection instanceof ExchangeRateCollection )
        {
            $collection->joinApprovalName($users);
        }

        return $collection;
    }
}