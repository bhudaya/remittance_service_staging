<?php

namespace Iapps\RemittanceService\RemittanceCompanyRecipient;

use Iapps\Common\Core\IappsBaseEntityCollection;
use Iapps\Common\Microservice\AccountService\User;
use Iapps\RemittanceService\RemittanceCompany\RemittanceCompany;
use Iapps\RemittanceService\RemittanceCompany\RemittanceCompanyCollection;
use Iapps\RemittanceService\Recipient\Recipient;
use Iapps\RemittanceService\Recipient\RecipientCollection;

class RemittanceCompanyRecipientCollection extends IappsBaseEntityCollection{

    public function joinRemittanceCompany(RemittanceCompanyCollection $remittanceCompanyCollection)
    {
        foreach( $this AS $remittanceRecipient)
        {
            if( $remittanceRecipient instanceof RemittanceCompanyRecipient )
            {
                if( $company = $remittanceCompanyCollection->getById($remittanceRecipient->getRemittanceCompany()->getId()) )
                    $remittanceRecipient->setRemittanceCompany($company);
            }
        }

        return $this;
    }

    public function joinRecipient(IappsBaseEntityCollection $recipientCollection)
    {
        foreach( $this AS $remittanceRecipient)
        {
            if( $remittanceRecipient instanceof RemittanceCompanyRecipient )
            {
                if( $recipient = $recipientCollection->getById($remittanceRecipient->getRecipient()->getId()) )
                    $remittanceRecipient->setRecipient($recipient);
            }
        }

        return $this;
    }

    public function joinVerifiedByName(IappsBaseEntityCollection $recipientCollection)
    {
        foreach( $this AS $entity)
        {
            if( $entity instanceof RemittanceCompanyRecipient )
            {
                if( $entity->getFaceToFaceVerifiedBy() == NULL )
                    continue;

                if( $user = $recipientCollection->getById($entity->getFaceToFaceVerifiedBy()) )
                {
                    if( $user instanceof User )
                    {
                        $entity->setFaceToFaceVerifiedByName($user->getName());
                    }
                }
            }
        }

        return $this;
    }

    public function setRemittanceCompany(RemittanceCompany $company)
    {
        foreach($this AS $remittanceRecipient)
        {
            if( $remittanceRecipient instanceof RemittanceCompanyRecipient )
            {
                $remittanceRecipient->setRemittanceCompany($company);
            }
        }

        return $this;
    }

    public function getRecipientIds()
    {
        $ids = array();
        foreach($this AS $remittanceRecipient)
        {
            if( $remittanceRecipient instanceof RemittanceCompanyRecipient )
            {
                if( $remittanceRecipient->getRecipient()->getId() )
                    $ids[] = $remittanceRecipient->getRecipient()->getId();
            }
        }

        return $ids;
    }
	
	public function getbyStatus($status)
	{
		$result = new RemittanceCompanyRecipientCollection();
		foreach($this AS $remittanceRecipient)
        {
            if( $remittanceRecipient instanceof RemittanceCompanyRecipient )
            {
                if( $remittanceRecipient->getRecipientStatus()->getCode() == $status )
                    $result->addData($remittanceRecipient);
            }
        }
		
		return $result;
	}
    
    public function getByRecipient(Recipient $recipient)
    {
        foreach( $this AS $remcoRecipient )
        {
            if( $remcoRecipient->belongsToRecipient($recipient) )
                return $remcoRecipient;
        }
        
        return false;
    }
    
    public function getRecipientList()
    {
        $recipientCol = new RecipientCollection();
        
        foreach($this AS $remcoRecipient)
        {
            $recipientCol->addData($remcoRecipient->getRecipient() );
        }
        
        return $recipientCol;
    }
}