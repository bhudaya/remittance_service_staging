<?php

namespace Iapps\RemittanceService\ExchangeRate;

use Iapps\AccountService\Account\UserCollection;
use Iapps\Common\Core\IappsBaseEntityCollection;
use Iapps\Common\CorporateService\CorporateService;
use Iapps\Common\Microservice\AccountService\PartnerAccountServiceFactory;
use Iapps\Common\Microservice\AccountService\User;
use Iapps\Common\SystemCode\SystemCode;
use Iapps\RemittanceService\Common\ChannelType;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfig;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfigCollection;

class ExchangeRateCollection extends IappsBaseEntityCollection{

    public function getByCorporateService(CorporateService $corporateService)
    {
        foreach($this AS $exchangeRate)
        {
            if( $exchangeRate->getCorporateServiceId() == $corporateService->getId() )
            {
                return $exchangeRate;
            }
        }

        return false;
    }

    public function getByChannel($channel_code)
    {
        foreach($this AS $exchangeRate)
        {
            if( $exchangeRate->getChannel()->getCode() == $channel_code )
            {
                return $exchangeRate;
            }
        }

        return false;
    }

    public function getRefExchangeRateIds()
    {
        $ids = array();
        foreach($this AS $exchangeRate)
        {
            if($exchangeRate->getRefExchangeRate()->getId())
                $ids[] = $exchangeRate->getRefExchangeRate()->getId();
        }

        return $ids;
    }

    public function joinRefExchangeRate(ExchangeRateCollection $col)
    {
        foreach($this AS $exchangeRate)
        {
            if( $refId = $exchangeRate->getRefExchangeRate()->getId() )
            {
                foreach($col AS $refExchangeRate)
                {
                    if( $refExchangeRate->getId() == $refId )
                        $exchangeRate->setRefExchangeRate($refExchangeRate);
                }
            }
        }

        return $this;
    }

    public function joinRemittanceConfig(RemittanceConfigCollection $configs)
    {
        $configList = new RemittanceConfigCollection();

        foreach($configs AS $config)
        {
            if( $config instanceof RemittanceConfig )
            {
                $found = false;
                if( $inRate = $this->getByCorporateService($config->getInCorporateService()) )
                {
                    $found = true;
                    $config->getInCorporateService()->setExchangeRateObj($inRate);
                    $config->getInCorporateService()->setExchangeRateId($inRate->getId());
                    $config->getInCorporateService()->setExchangeRate($inRate->getExchangeRate());
                    $config->getInCorporateService()->setMargin($inRate->getMargin());
                }

                if( $outRate = $this->getByCorporateService($config->getOutCorporateService()) )
                {
                    $found = true;
                    $config->getOutCorporateService()->setExchangeRateObj($outRate);
                    $config->getOutCorporateService()->setExchangeRateId($outRate->getId());
                    $config->getOutCorporateService()->setExchangeRate($outRate->getExchangeRate());
                    $config->getOutCorporateService()->setMargin($outRate->getMargin());
                }

                if( $found )
                {
                    $configList->addData($config);
                }
            }
        }

        return $configList;
    }

    public function toListingFormat(RemittanceConfig $remittanceConfig)
    {
        $formattedArray = array();
        foreach($this AS $exchangeRate)
        {
            if( $exchangeRate instanceof ExchangeRate )
            {
                $temp = $exchangeRate->getSelectedField(array('id', 'created_at', 'created_by_name', 'channel', 'status'));

                if( $exchangeRate->getRefExchangeRate()->getId() != NULL )
                    $ref_rate = $exchangeRate->getRefExchangeRate();
                else
                    $ref_rate = $exchangeRate;

                if( $corp = $remittanceConfig->rateBelongsTo($exchangeRate) )
                {
                    $temp['service_provider_name'] = $corp->getServiceProviderName();
                    $temp['rate_type'] = $corp->getRateType();
                }

                if( $corp = $remittanceConfig->rateBelongsTo($ref_rate) )
                {
                    $temp['from'] = $corp->getConversionRemittanceService()->getFromCountryCurrencyCode();
                    $temp['to'] = $corp->getConversionRemittanceService()->getToCountryCurrencyCode();
                }

                $temp['updated_by_corporate'] = $exchangeRate->getUpdatedByCorporate();
                $temp['prices'] = $exchangeRate->getPrices();

                $formattedArray[] = $temp;
            }
        }

        return $formattedArray;
    }

    public function joinApprovalName(IappsBaseEntityCollection $userCollection)
    {
        foreach( $this AS $entity)
        {
            if( $entity instanceof ExchangeRate )
            {
                if( $user = $userCollection->getById($entity->getApproveRejectBy()) )
                {
                    if( $user instanceof User )
                    {
                        $entity->setApproveRejectByName($user->getName());
                    }
                }
            }
        }

        return $this;
    }

    public function getTrendData()
    {
        $tempData = array();
        foreach($this AS $rate)
        {
            if( $rate instanceof ExchangeRate )
            {
                $temp['datetime'] = $rate->getApproveRejectAt()->getString();
                $temp['rate'] = $rate->getApproveRate();

                $tempData[$rate->getApproveRejectAt()->getUnix()] = $temp;
            }
        }

        ksort($tempData);

        $data = array();
        foreach($tempData AS $rate)
        {
            $data[] = $rate;
        }

        return $data;
    }

    public function extractUpdatedByCorporate()
    {
        foreach( $this as $rate)
        {
            if( $rate instanceof ExchangeRate )
            {
                if( $rate->getChannel()->getCode() == ChannelType::CODE_ADMIN_PANEL )
                    $rate->setUpdatedByCorporate('IApps');
                elseif( $rate->getChannel()->getCode() == ChannelType::CODE_PARTNER_PANEL )
                {
                    $accServ = PartnerAccountServiceFactory::build();
                    if( $upline = $accServ->getUplineStructure($rate->getCreatedBy()) )
                    {
                        $rate->setUpdatedByCorporate($upline->first_upline->getUser()->getName());
                    }
                }
            }
        }

        return $this;
    }
}