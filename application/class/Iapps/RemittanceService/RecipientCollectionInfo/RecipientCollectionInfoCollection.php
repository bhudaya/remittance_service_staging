<?php

namespace Iapps\RemittanceService\RecipientCollectionInfo;

use Iapps\Common\Core\IappsBaseEntityCollection;

class RecipientCollectionInfoCollection extends IappsBaseEntityCollection{

    public function hasPaymentCode($payment_code)
    {
        foreach($this AS $collectionInfo)
        {
            if( $collectionInfo->getPaymentCode() == $payment_code )
                return $collectionInfo;
        }

        return NULL;
    }

    public function exists(RecipientCollectionInfo $recipientCollectionInfo)
    {
        foreach($this AS $refRecipientCollectionInfo)
        {
            if( $refRecipientCollectionInfo instanceof RecipientCollectionInfo )
            {
                if( $refRecipientCollectionInfo->equals($recipientCollectionInfo) )
                    return true;
            }
        }

        return false;
    }

    public function getByRequiredAttribute(array $requiredAttribute)
    {
        $collection = new RecipientCollectionInfoCollection();
        foreach($this AS $recipientCollectionInfo)
        {
            if( $recipientCollectionInfo instanceof RecipientCollectionInfo )
            {
                if( $this->_isRequiredAttributeMatched($recipientCollectionInfo, $requiredAttribute) )
                    $collection->addData($recipientCollectionInfo);
            }
        }

        return $collection;
    }

    protected function _isRequiredAttributeMatched(RecipientCollectionInfo $recipientCollectionInfo, array $requiredAttribute)
    {
        if( count($requiredAttribute) <= 0 )
            return true;

        foreach($requiredAttribute AS $attributeObj)
        {
            if( !$option = $recipientCollectionInfo->getAttribute($attributeObj->attribute ) )
                return false;

            if( $attributeObj->selection_only == 1)
            {
                //check value is one of the option
                $codes = array();
                foreach( $attributeObj->value AS $value )
                    $codes[] = $value->code;

                if( !in_array($option, $codes) )
                    return false;
            }
        }

        return true;
    }

    /*
     * This is a customised function to cater for wrong attribute account_no to map to back_account
     */
    public function mapAccountNo()
    {
        foreach($this AS $recipientCollectionInfo)
        {
            if( $recipientCollectionInfo instanceof RecipientCollectionInfo )
            {
                $recipientCollectionInfo->mapAccountNo();
            }
        }

        return $this;
    }
}