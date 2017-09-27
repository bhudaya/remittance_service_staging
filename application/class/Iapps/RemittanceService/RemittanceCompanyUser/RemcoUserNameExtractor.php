<?php

namespace Iapps\RemittanceService\RemittanceCompanyUser;

use Iapps\Common\Core\IappsBaseEntityCollection;
use Iapps\Common\Helper\CreatorNameExtractor;

class RemcoUserNameExtractor extends CreatorNameExtractor
{
    public static function extractByEntity(RemittanceCompanyUser $entity)
    {
        $collection = new RemittanceCompanyUserCollection();
        $collection->addData($entity);
        $collection = static::extract($collection);
        $collection->rewind();
        return $collection->current();
    }

    protected function getIds(IappsBaseEntityCollection $collection)
    {
        $ids = array();
        foreach($collection AS $entity)
        {
            if( $entity instanceof RemittanceCompanyUser )
            {
                if( $entity->getRejectedBy() )
                    $ids[] = $entity->getRejectedBy();
                if( $entity->getVerifiedBy() )
                    $ids[] = $entity->getVerifiedBy();
                if( $entity->getCompletedBy() )
                    $ids[] = $entity->getCompletedBy();
            }
        }

        return $ids;
    }

    protected function mapNames(IappsBaseEntityCollection $collection, IappsBaseEntityCollection $users)
    {
        if( $collection instanceof RemittanceCompanyUserCollection )
        {
            $collection->joinCompletedByName($users);
            $collection->joinVerifiedByName($users);
            $collection->joinRejectedByName($users);
        }

        return $collection;
    }
}