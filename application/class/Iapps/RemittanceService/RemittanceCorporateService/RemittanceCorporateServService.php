<?php

namespace Iapps\RemittanceService\RemittanceCorporateService;

use Iapps\Common\Core\IappsBaseService;
use Iapps\Common\AuditLog\AuditLogAction;
use Iapps\Common\Core\PaginatedResult;
use Iapps\Common\CorporateService\CorporateServService;
use Iapps\RemittanceService\Common\MessageCode;
use Iapps\RemittanceService\ExchangeRate\ExchangeRate;
use Iapps\RemittanceService\RemittanceServiceConfig\RemittanceServiceConfigServiceFactory;

class RemittanceCorporateServService extends CorporateServService{

    public function findByIds(array $corporate_service_ids){

        $corpServCollection = new RemittanceCorporateServiceCollection();
        foreach($corporate_service_ids AS $corporate_service_id)
        {//call one by one to use memcached
            if( $corporateServData = $this->getRepository()->findById($corporate_service_id) )
            {
                $corpServCollection->addData($corporateServData);
            }
        }

        if( count($corpServCollection) > 0 )
        {
            $data = new PaginatedResult();
            $data->setResult($corpServCollection);
            $data->setTotal(count($corpServCollection));

            $this->_extractRelatedRecord($data->getResult());
            return $data;
        }else{
            return false;
        }
    }

    public function getCorporateService($corporate_service_id){

        if( $data = parent::getCorporateService($corporate_service_id) )
        {
            if( $data instanceof RemittanceCorporateService )
            {
                $corpServCollection = new RemittanceCorporateServiceCollection();
                $corpServCollection->addData($data);
                $this->_extractRelatedRecord($corpServCollection);

                $this->setResponseCode(self::CODE_GET_CORPORATE_SERVICE_SUCCESS);
                return $corpServCollection->current();
            }
        }

        $this->setResponseCode(self::CODE_CORPORATE_SERVICE_NOT_FOUND);
        return false;
    }

    public function findByServiceProviderIds(array $serviceProviderIds)
    {
        if( $info = $this->getRepository()->findByServiceProviderIds($serviceProviderIds) )
        {
            return $info->result;
        }

        return false;
    }

    public function updateCorporateServiceApprovedRate($corporateServiceId, ExchangeRate $approvedRate)
    {
        if( $corpServ = $this->getCorporateService($corporateServiceId) )
        {
            if( $corpServ instanceof RemittanceCorporateService )
            {
                $ori = clone($corpServ);
                if( $corpServ->setApprovedRate($approvedRate) )
                {
                    $corpServ->setUpdatedBy($this->getUpdatedBy());

                    if( $this->getRepository()->update($corpServ) )
                    {
                        $this->fireLogEvent('iafb_remittance.corporate_service', AuditLogAction::UPDATE, $corpServ->getId(), $ori);
                        $this->setResponseCode(self::CODE_EDIT_CORPORATE_SERVICE_SUCCESS);
                        return $corpServ;
                    }
                }
            }
        }

        $this->setResponseCode(self::CODE_CORPORATE_SERVICE_NOT_FOUND);
        return false;
    }

    public function getCorporateServiceByServiceProId($service_provider_id)
    {
        if( $data = $this->getRepository()->getCorporateServiceByServiceProId($service_provider_id))
        {
            return $data;
        }else{
            return false;
        }
    }

    protected function _extractRelatedRecord(RemittanceCorporateServiceCollection $collection)
    {
        $ids = array();
        foreach($collection AS $corpServ)
        {
            $ids[] = $corpServ->getConversionRemittanceService()->getId();
        }

        if( count($ids) > 0 )
        {
            $reServ = RemittanceServiceConfigServiceFactory::build();
            if( $info = $reServ->findByIds($ids) )
            {
                $collection->joinRemittanceService($info->result);
            }
        }

        $collection->rewind();
        return $collection;
    }
}