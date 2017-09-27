<?php

namespace Iapps\RemittanceService\RemittanceCompanyUser;

use Iapps\Common\Core\IappsBaseEntityCollection;
use Iapps\Common\Microservice\AccountService\User;
use Iapps\RemittanceService\RemittanceCompany\RemittanceCompany;
use Iapps\RemittanceService\RemittanceCompany\RemittanceCompanyCollection;

class RemittanceCompanyUserCollection extends IappsBaseEntityCollection{

    public function joinRemittanceCompany(RemittanceCompanyCollection $remittanceCompanyCollection)
    {
        foreach( $this AS $remittanceUser)
        {
            if( $remittanceUser instanceof RemittanceCompanyUser )
            {
                if( $company = $remittanceCompanyCollection->getById($remittanceUser->getRemittanceCompany()->getId()) )
                    $remittanceUser->setRemittanceCompany($company);
            }
        }

        return $this;
    }

    public function joinUser(IappsBaseEntityCollection $userCollection)
    {
        foreach( $this AS $remittanceUser)
        {
            if( $remittanceUser instanceof RemittanceCompanyUser )
            {
                if( $user = $userCollection->getById($remittanceUser->getUser()->getId()) )
                    $remittanceUser->setUser($user);
            }
        }

        return $this;
    }

    public function joinCompletedByName(IappsBaseEntityCollection $userCollection)
    {
        foreach( $this AS $entity)
        {
            if( $entity instanceof RemittanceCompanyUser )
            {
                if( $entity->getCompletedBy() == NULL )
                    continue;

                if( $user = $userCollection->getById($entity->getCompletedBy()) )
                {
                    if( $user instanceof User )
                    {
                        $entity->setCompletedByName($user->getName());
                    }
                }
            }
        }

        return $this;
    }

    public function joinVerifiedByName(IappsBaseEntityCollection $userCollection)
    {
        foreach( $this AS $entity)
        {
            if( $entity instanceof RemittanceCompanyUser )
            {
                if( $entity->getVerifiedBy() == NULL )
                    continue;

                if( $user = $userCollection->getById($entity->getVerifiedBy()) )
                {
                    if( $user instanceof User )
                    {
                        $entity->setVerifiedByName($user->getName());
                    }
                }
            }
        }

        return $this;
    }

    public function joinRejectedByName(IappsBaseEntityCollection $userCollection)
    {
        foreach( $this AS $entity)
        {
            if( $entity instanceof RemittanceCompanyUser )
            {
                if( $entity->getRejectedBy() == NULL )
                    continue;

                if( $user = $userCollection->getById($entity->getRejectedBy()) )
                {
                    if( $user instanceof User )
                    {
                        $entity->setRejectedByName($user->getName());
                    }
                }
            }
        }

        return $this;
    }

    public function setRemittanceCompany(RemittanceCompany $company)
    {
        foreach($this AS $remittanceUser)
        {
            if( $remittanceUser instanceof RemittanceCompanyUser )
            {
                $remittanceUser->setRemittanceCompany($company);
            }
        }

        return $this;
    }

    public function getByUserProfileId($user_profile_id) 
    {
        foreach($this AS $remittanceUser)
        {
            if( $remittanceUser instanceof RemittanceCompanyUser )
            {
                if( $remittanceUser->getUser()->getId() == $user_profile_id )
                    return $remittanceUser;
            }
        }

        return false;        
    }
    public function getUserProfileIds()
    {
        $ids = array();
        foreach($this AS $remittanceUser)
        {
            if( $remittanceUser instanceof RemittanceCompanyUser )
            {
                if( $remittanceUser->getUser()->getId() )
                    $ids[] = $remittanceUser->getUser()->getId();
            }
        }

        return $ids;
    }
}