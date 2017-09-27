<?php

namespace Iapps\RemittanceService\RemittanceCompany;

use Iapps\Common\Core\IappsBaseEntityCollection;

class RemittanceCompanyCollection extends IappsBaseEntityCollection{

    public function getServiceProviderIds()
    {
        $ids = array();

        foreach($this AS $remittanceCompany)
        {
            if( $remittanceCompany instanceof RemittanceCompany )
            {
                if( $remittanceCompany->getServiceProviderId() )
                {
                    $ids[] = $remittanceCompany->getServiceProviderId();
                }
            }
        }

        return array_unique($ids);
    }

    public function joinCompanyInfo(IappsBaseEntityCollection $users)
    {
        foreach($this AS $remittanceCompany)
        {
            if( $remittanceCompany instanceof RemittanceCompany )
            {
                if( $info = $users->getById($remittanceCompany->getServiceProviderId()) )
                    $remittanceCompany->setCompanyInfo($info);
            }
        }

        return $this;
    }
}