<?php

namespace Iapps\RemittanceService\Recipient;

use Iapps\Common\Core\IappsBaseEntityCollection;
use Iapps\Common\Microservice\AccountService\User;
use Iapps\RemittanceService\Attribute\RecipientAttribute;
use Iapps\RemittanceService\Attribute\RecipientAttributeCollection;
use Iapps\RemittanceService\RecipientCollectionInfo\RecipientCollectionInfo;
use Iapps\RemittanceService\RecipientCollectionInfo\RecipientCollectionInfoCollection;

class RecipientCollection extends IappsBaseEntityCollection{

    public function getRecipientUserProfileIds()
    {
        $ids = array();
        foreach ($this as $recipient) {
            if( $recipient->getRecipientUserProfileId() != NULL)
            {
                $ids[] = $recipient->getRecipientUserProfileId();
            }
        }

        return $ids;
    }

    public function sortByLastSentAndCreatedAt()
    {
        $data = $this->toArray();

        if( $sortedArray = usort($data, array($this, "_sortLastSentCreatedAt") ))
        {
            $sortedCollection = new RecipientCollection();
            foreach($data AS $recipient)
            {
                $sortedCollection->addData($recipient);
            }

            return $sortedCollection;
        }

        return $this;
    }

    // Define the custom sort function
    private function _sortLastSentCreatedAt($a,$b) {
        if( $a instanceof Recipient AND
            $b instanceof Recipient )
        {
            $a_timestamp = null;
            $b_timestamp = null;

            if( $b->getLastSentAt()->isNull() )
                $b_timestamp = $b->getCreatedAt();
            else
                $b_timestamp = $b->getLastSentAt();

            if( $a->getLastSentAt()->isNull() )
                $a_timestamp = $a->getCreatedAt();
            else
                $a_timestamp = $a->getLastSentAt();

            return $a_timestamp->getUnix() < $b_timestamp->getUnix();
        }

        //remain same order if
        return false;
    }

    public function joinRecipientAttribute(RecipientAttributeCollection $recipientAttributeCollection)
    {        
        foreach($recipientAttributeCollection AS $attribute)
        {
            if( $attribute instanceof RecipientAttribute )
            {
                if( $recipient = $this->getById($attribute->getRecipientId()) )
                    $recipient->getAttributes()->addData($attribute);                    
            }
        }

        return $this;
    }

    public function joinCollectionInfo(RecipientCollectionInfoCollection $recipientCollectionInfoCollection)
    {        
        foreach($recipientCollectionInfoCollection AS $collectionInfo)
        {
            if( $collectionInfo instanceof RecipientCollectionInfo )
            {
                if( $recipient = $this->getById($collectionInfo->getRecipientId()) )
                    $recipient->getCollectionInfos()->addData($collectionInfo);
            }
        }        

        return $this;
    }
    
    public function joinUser(IappsBaseEntityCollection $userCollection)
    {
        foreach( $this AS $recipient)
        {
            if( $recipient instanceof Recipient )
            {
                if( $user = $userCollection->getById($recipient->getUser()->getId()) )
                    $recipient->setUser($user);             
            }
        }

        return $this;
    }
}