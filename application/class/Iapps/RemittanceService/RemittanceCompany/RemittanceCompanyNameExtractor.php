<?php

namespace Iapps\RemittanceService\RemittanceCompany;

use Iapps\Common\Core\IappsBaseEntityCollection;
use Iapps\Common\Helper\CreatorNameExtractor;

class RemittanceCompanyNameExtractor extends CreatorNameExtractor{

    protected function getIds(IappsBaseEntityCollection $collection)
    {
        $ids = parent::getIds($collection);

        if( $collection instanceof RemittanceCompanyCollection )
        {
            foreach($collection AS $entity)
            {
                $ids[] = $entity->getServiceProviderId();
            }
        }

        return $ids;
    }

    protected function mapNames(IappsBaseEntityCollection $collection, IappsBaseEntityCollection $users)
    {
        parent::mapNames($collection, $users);

        if( $collection instanceof RemittanceCompanyCollection )
            $collection->joinCompanyInfo($users);

        return $collection;
    }
}