<?php

namespace Iapps\RemittanceService\RemittanceConfig;

use Iapps\Common\Core\IappsBaseEntityCollection;
use Iapps\Common\Microservice\AccountService\User;
use Iapps\RemittanceService\RemittanceCompany\RemittanceCompany;
use Iapps\RemittanceService\RemittanceCompany\RemittanceCompanyCollection;
use Iapps\RemittanceService\RemittanceCorporateService\RemittanceCorporateServiceCollection;
use Iapps\RemittanceService\RemittanceServiceConfig\RemittanceServiceConfigCollection;
use Iapps\Common\Core\IappsDateTime;

class RemittanceConfigCollection extends IappsBaseEntityCollection{
    
    public function joinInCorporateService(RemittanceCorporateServiceCollection $corporateServCollection)
    {
        foreach( $this AS $remConfig )
        {
            if( $corporateServ = $corporateServCollection->getById($remConfig->getCashinCorporateServiceId()) )
                $remConfig->setInCorporateService($corporateServ);
        }        

        return $this;
    }

    public function joinOutCorporateService(RemittanceCorporateServiceCollection $corporateServCollection)
    {
        foreach( $this AS $remConfig )
        {
            if( $corporateServ = $corporateServCollection->getById($remConfig->getCashOutCorporateServiceId()) )
                $remConfig->setOutCorporateService($corporateServ);
        }        

        return $this;
    }

    public function joinRemittanceService(RemittanceServiceConfigCollection $remittanceServCollection)
    {
        foreach( $this AS $remConfig )
        {
            if( $remittanceServ = $remittanceServCollection->getById($remConfig->getRemittanceServiceId()) )
                $remConfig->setRemittanceService($remittanceServ);      
        }                

        return $this;
    }

    public function joinFromCountryPartner($users)
    {
        foreach( $this AS $remConfig )
        {
            if( $user = $users->getById($remConfig->getFromCountryPartnerId()) )
            {
                $remConfig->setFromCountryPartnerName($user->getName());
                $remConfig->getInCorporateService()->setServiceProviderName($user->getName());
            }
        }              

        return $this;
    }

    public function joinToCountryPartner($users)
    {
        foreach( $this AS $remConfig )
        {
            if( $user = $users->getById($remConfig->getToCountryPartnerId()) )
            {
                $remConfig->setToCountryPartnerName($user->getName());
                $remConfig->getOutCorporateService()->setServiceProviderName($user->getName());
            }
        }

        return $this;

    }
    
    public function joinApproveRejectBy($users)
    {
        foreach( $this AS $remConfig )
        {
            if( $user = $users->getById($remConfig->getApproveRejectBy()) )
                $remConfig->setApproveRejectByName($user->getName());
        }        
        
        return $this;
    }
    
    public function joinPricingLastUpdated($pricing_data)
    {
        foreach ($pricing_data as $key => $value) {
            if( ($remConfig = $this->getById($key)) && !empty($value) )
            {
                $value = IappsDateTime::fromString($value);
                $remConfig->setLastPricingApproveAt($value);
            }
        }
        
        return $this;
    }
    
    public function getLowestRateByRemittanceService()
    {
        $temp = array();    //to store the remittance config id with lower rate, key remittance service id
        foreach($this AS $remittanceConfig)
        {
            $key = $remittanceConfig->getRemittanceService()->getId();
            if( array_key_exists($key, $temp) )
            {
                if( $remittanceConfig->getDisplayRate() > $temp[$key]['rate'] )
                {
                    $temp[$key]['rate'] = $remittanceConfig->getDisplayRate();
                    $temp[$key]['id'] = $remittanceConfig->getId();
                }
            }
            else
            {
                $temp[$key]['rate'] = $remittanceConfig->getDisplayRate();
                $temp[$key]['id'] = $remittanceConfig->getId();
            }
        }

        $collection = new RemittanceConfigCollection();
        foreach($temp AS $value)
        {
            $collection->addData($this->getById($value['id']) );
        }

        return $collection;
    }

    public function getApprovedExchangeRateIds()
    {
        $ids = array();
        foreach($this AS $reConfig)
        {
            if( $reConfig instanceof RemittanceConfig )
            {
                if( $reConfig->getInCorporateService()->getExchangeRateId() )
                    $ids[] =$reConfig->getInCorporateService()->getExchangeRateId();

                if( $reConfig->getOutCorporateService()->getExchangeRateId() )
                    $ids[] = $reConfig->getOutCorporateService()->getExchangeRateId();
            }
        }

        return $ids;
    }

    public function toListingFormat()
    {
        $formattedArray = array();
        foreach($this AS $reConfig)
        {
            if( $reConfig instanceof RemittanceConfig )
            {
                $temp = $reConfig->getSelectedField(array('id', 'channel_id', 'created_at', 'updated_at', 'created_by_name', 'remittance_service', 'status', 'display_rate', 'from_country_currency_code', 'to_country_currency_code'));
                $temp['from_partner'] = $reConfig->getInCorporateService()->getServiceProviderName();
                $temp['to_partner'] = $reConfig->getOutCorporateService()->getServiceProviderName();
                $temp['rate_last_update'] = $reConfig->getLastRateUpdatedAt();
                $temp['formatted_display_rate'] = $reConfig->getFormattedExchangeRate();

                $formattedArray[] = $temp;
            }
        }

        return $formattedArray;
    }

    public function getChannel($forInternational)
    {
        $collection = new RemittanceConfigCollection();
        foreach($this AS $reConfig)
        {
            if( $reConfig instanceof RemittanceConfig )
            {
                if( $forInternational )
                {
                    if( $reConfig->getRemittanceService()->isInternational() )
                        $collection->addData($reConfig);
                }
                else
                {
                    if( $reConfig->getRemittanceService()->isDomestic() )
                        $collection->addData($reConfig);
                }
            }
        }

        return $collection;
    }

    public function getFromCountryPartner($partner_id)
    {
        $ids = array();
        foreach($this AS $reConfig)
        {
            if( $reConfig instanceof RemittanceConfig )
            {

                if( $reConfig->getFromCountryPartnerId() == $partner_id){
                    $ids[] = $reConfig->getId();
                }
            }
        }

        return $ids;
    }

    public function getToCountryPartner($partner_id){
        $ids = array();
        foreach($this AS $reConfig)
        {
            if( $reConfig instanceof RemittanceConfig )
            {

                if( $reConfig->getToCountryPartnerId() == $partner_id){
                    $ids[] = $reConfig->getId();
                }
            }
        }

        return $ids;
    }

    public function getByMainAgent(User $mainAgent)
    {
        $collection = new RemittanceConfigCollection();
        foreach($this AS $reConfig)
        {
            if( $reConfig instanceof RemittanceConfig )
            {
                if( $reConfig->serviceProviderBelongsTo($mainAgent->getId()) )
                {
                    $collection->addData($reConfig);
                }
            }
        }

        return $collection;
    }

    public function getInServiceProviderIds()
    {
        $ids = array();
        foreach( $this AS $reConfig)
        {
            if( $reConfig instanceof RemittanceConfig )
            {
                if( !is_null($reConfig->getInCorporateService()->getServiceProviderId()) )
                    $ids[] = $reConfig->getInCorporateService()->getServiceProviderId();
            }
        }

        return $ids;
    }

    public function joinRemittanceCompany(RemittanceCompanyCollection $remittanceCompanyCollection)
    {
        $remittanceCompanyCollection->indexField("service_provider_id");        
        foreach($this AS $remConfig)
        {
            if( $remco = $remittanceCompanyCollection->getFromIndex("service_provider_id", $remConfig->getInCorporateService()->getServiceProviderId()) )
                $remConfig->setRemittanceCompany($remco);            
        }

        return $this;
    }
}