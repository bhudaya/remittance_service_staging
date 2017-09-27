<?php

namespace Iapps\RemittanceService\RemittanceCompanyRecipient;

use Iapps\Common\Core\IappsBaseEntityCollection;
use Iapps\Common\Helper\CreatorNameExtractor;

class RemcoUserNameExtractor extends CreatorNameExtractor
{
    public static function extractByEntity(RemittanceCompanyRecipient $entity)
    {
        $collection = new RemittanceCompanyRecipientCollection();
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
            if( $entity instanceof RemittanceCompanyRecipient )
            {
                if( $entity->getFaceToFaceVerifiedBy() )
                    $ids[] = $entity->getFaceToFaceVerifiedBy();
            }
        }

        return $ids;
    }

    protected function mapNames(IappsBaseEntityCollection $collection, IappsBaseEntityCollection $users)
    {
        if( $collection instanceof RemittanceCompanyRecipientCollection )
        {
            $collection->joinVerifiedByName($users);
        }

        return $collection;
    }
}