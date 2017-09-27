<?php

namespace Iapps\RemittanceService\RemittanceConfig;

use Iapps\Common\Core\IappsBaseRepository;
use Iapps\Common\CorporateService\CorporateService;

use Iapps\Common\Core\IappsBaseService;
use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\SystemCode\SystemCode;
use Iapps\Common\Helper\GuidGenerator;
use Iapps\Common\AuditLog\AuditLogAction;

use Iapps\Common\CorporateService\CorporateServicePaymentMode;
use Iapps\Common\Microservice\AccountService\UserType;
use Iapps\Common\Microservice\PaymentService\PaymentService;
use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\Common\Microservice\PaymentService\PaymentDirection;
use Iapps\RemittanceService\Common\CorporateServServiceFactory;
use Iapps\RemittanceService\Common\TransactionType;
use Iapps\RemittanceService\Common\CurrencyCodeValidator;
use Iapps\RemittanceService\Common\MessageCode;
use Iapps\RemittanceService\RemittanceCompany\RemittanceCompanyServiceFactory;
use Iapps\RemittanceService\RemittanceCorporateService\PartnerNameExtractor;
use Iapps\RemittanceService\RemittanceServiceConfig\RemittanceServiceConfig;
use Iapps\RemittanceService\RemittanceServiceConfig\RemittanceServiceConfigServiceFactory;
use Iapps\RemittanceService\Common\SystemCodeServiceFactory;
use Iapps\RemittanceService\Common\IncrementIDServiceFactory;
use Iapps\RemittanceService\Common\IncrementIDAttribute;
use Iapps\RemittanceService\RemittanceCorporateService\RemittanceCorporateService;
use Iapps\RemittanceService\RemittanceCorporateService\RemittanceCorporateServService;
use Iapps\RemittanceService\RemittanceCorporateService\RemittanceCorporateServFactory;
use Iapps\RemittanceService\Common\CorporateServicePaymentModeExtendedServiceFactory;
use Iapps\RemittanceService\RemittanceProfitSharing\RemittanceCorpServProfitSharingServiceFactory;
use Iapps\RemittanceService\ExchangeRate\ExchangeRateServiceFactory;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeServiceFactory;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeFeeServiceFactory;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeCostServiceFactory;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeFeeGroupServiceFactory;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeCostGroupServiceFactory;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeFeeGroupStatus;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeCostGroupStatus;
use Iapps\RemittanceService\ExchangeRate\ExchangeRateStatus;
use Iapps\RemittanceService\RemittanceProfitSharing\RemittanceCorpServProfitSharingStatus;


class RemittanceConfigService extends IappsBaseService {

    private $_paymentModeService;
    private static $_remittanceconfig_memory = NULL;

    private function _getServiceExchangeRate()
    {
        $service = ExchangeRateServiceFactory::build();
        $service->setUpdatedBy($this->getUpdatedBy());
        $service->setIpAddress($this->getIpAddress());
        return $service;
    }
    
    private function _getServiceProfitSharing()
    {
        $service = RemittanceCorpServProfitSharingServiceFactory::build();
        $service->setUpdatedBy($this->getUpdatedBy());
        $service->setIpAddress($this->getIpAddress());
        return $service;
    }
    
    private function _getServicePaymentMode()
    {
        if( $this->_paymentModeService )
            return $this->_paymentModeService;
        
        $this->_paymentModeService = PaymentModeServiceFactory::build();
        $this->_paymentModeService->setUpdatedBy($this->getUpdatedBy());
        $this->_paymentModeService->setIpAddress($this->getIpAddress());
        return $this->_paymentModeService;
    }
    
    private function _getServicePaymentModeFeeService() {
        $service = PaymentModeFeeServiceFactory::build();
        $service->setUpdatedBy($this->getUpdatedBy());
        $service->setIpAddress($this->getIpAddress());
        return $service;
    }
    
    private function _getServicePaymentModeCostService() {
        $service = PaymentModeCostServiceFactory::build();
        $service->setUpdatedBy($this->getUpdatedBy());
        $service->setIpAddress($this->getIpAddress());
        return $service;
    }
    
    private function _getServicePaymentModeFeeGroupService()
    {
        $service = PaymentModeFeeGroupServiceFactory::build();
        $service->setUpdatedBy($this->getUpdatedBy());
        $service->setIpAddress($this->getIpAddress());
        return $service;
    }
    
    private function _getServicePaymentModeCostGroupService()
    {
        $service = PaymentModeCostGroupServiceFactory::build();
        $service->setUpdatedBy($this->getUpdatedBy());
        $service->setIpAddress($this->getIpAddress());
        return $service;
    }
    
    public function getRemittanceConfigById($remittance_configuration_id) {
        if ($reConfig = $this->getRepository()->findById($remittance_configuration_id)) {
            $remittanceCorpServProfitSharingService = RemittanceCorpServProfitSharingServiceFactory::build(); //

            $cashin_profitSharingByCorpSerId = $remittanceCorpServProfitSharingService->getProfitSharingByCorpSerId($reConfig->getCashinCorporateServiceId());
            $cashout_profitSharingByCorpSerId = $remittanceCorpServProfitSharingService->getProfitSharingByCorpSerId($reConfig->getCashOutCorporateServiceId());

            $reConfig->setCashInProfitSharingByCorpSerId((bool) $cashin_profitSharingByCorpSerId);
            $reConfig->setCashOutProfitSharingByCorpSerId((bool) $cashout_profitSharingByCorpSerId);

            if ($reConfig instanceof RemittanceConfig) {

                $reConfigCol = new RemittanceConfigCollection();


                $reConfigCol->addData($reConfig);

                $this->_extractRelatedRecord($reConfigCol);

                //this is already done in extract related record
                //PartnerNameExtractor::extractFromRemittanceConfigCollection($reConfigCol);
//
                $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_CONFIG_SUCCESS);
                $reConfigCol->rewind();
                return $reConfigCol->current();
            }
        }

        $this->setResponseCode(MessageCode::CODE_REMITTANCE_CONFIG_NOT_FOUND);
        return false;
    }

    public function getRemittanceConfigBySearchFilter(RemittanceConfig $remittanceConfig, $limit = NULL, $page = NULL) {
        if ($collection = $this->getRepository()->findBySearchFilter($remittanceConfig, $limit, $page)) {
            $this->_extractRelatedRecord($collection->result);

            $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_CONFIG_SUCCESS);
            return $collection;
        }

        $this->setResponseCode(MessageCode::CODE_REMITTANCE_CONFIG_NOT_FOUND);
        return false;
    }

    public function getAllRemittanceConfig($limit, $page) {
        if ($collection = $this->getRepository()->findAll($limit, $page)) {
            $this->_extractRelatedRecord($collection->result);

            $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_CONFIG_SUCCESS);
            return $collection;
        }

        $this->setResponseCode(MessageCode::CODE_REMITTANCE_CONFIG_NOT_FOUND);
        return false;
    }

    public function getRemittanceConfigByIdArr($remittance_config_id_arr) {
        if ($collection = $this->getRepository()->findByIdArr($remittance_config_id_arr)) {
            $this->_extractRelatedRecord($collection->result);

            $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_CONFIG_SUCCESS);
            return $collection;
        }

        $this->setResponseCode(MessageCode::CODE_REMITTANCE_CONFIG_NOT_FOUND);
        return false;
    }

    public function addRemittanceConfig(RemittanceConfig $remittanceConfig) {

        $fromConversionRemittanceServiceId = NULL;
        $toConversionRemittanceServiceId = NULL;
        $corporateServServ = $this->_getCorporateService();
        $systemCodeService = SystemCodeServiceFactory::build();
        $countryService = new PaymentService();
        $cash_in_corp = new RemittanceCorporateService();
        $cash_out_corp = new RemittanceCorporateService();

        $v = RemittanceConfigValidator::make($remittanceConfig);
        if ($v->fails()) {
            $this->setResponseCode(MessageCode::CODE_ADD_REMITTANCE_CONFIG_FAILED);
            return false;
        }
        
        // check exists
        if($existsCollection = $this->getExistsRemittanceConfigList(1, 1, NULL,
                $remittanceConfig->getFromCountryCurrencyCode(),
                $remittanceConfig->getToCountryCurrencyCode(),
                $remittanceConfig->getFromCountryPartnerId(),
                $remittanceConfig->getToCountryPartnerId(),
                array(RemittanceConfigStatus::PENDING, RemittanceConfigStatus::APPROVED)))
        {
            $this->setResponseCode(MessageCode::CODE_REMITTANCE_CONFIG_ALREADY_EXISTS);
            return false;
        }

        if ($this->_currencyCodeValidate($remittanceConfig->getFromCountryCurrencyCode())) {
            return false;
        }

        if ($this->_currencyCodeValidate($remittanceConfig->getToCountryCurrencyCode())) {
            return false;
        }

        if (!$this->_userTypeValidate($remittanceConfig->getFromCountryPartnerID())) {
            return false;
        }

        if (!$this->_userTypeValidate($remittanceConfig->getToCountryPartnerID())) {
            return false;
        }

        if (!$remittanceServiceId = $this->_remittanceServiceValidate($remittanceConfig->getFromCountryCurrencyCode(), $remittanceConfig->getToCountryCurrencyCode())) {
            $this->setResponseCode(MessageCode::CODE_REMITTANCE_SERVICE_NOT_EXIST);
            return false;
        }

        if ($remittanceConfig->getIntermediaryCurrency() == 'NONE') {
            if ($remittanceConfig->getRatesSetter() == 'from_partner') {
                $fromConversionRemittanceServiceId = $remittanceServiceId;
                $toConversionRemittanceServiceId = NULL;
            }
            if ($remittanceConfig->getRatesSetter() == 'to_partner') {
                $toConversionRemittanceServiceId = $remittanceServiceId;
                $fromConversionRemittanceServiceId = NULL;
            }
        } else {
            if (!$inConversionRemittanceServiceId = $this->_remittanceServiceValidate($remittanceConfig->getFromCountryCurrencyCode(), $remittanceConfig->getIntermediaryCurrency())) {
                $this->setResponseCode(MessageCode::CODE_REMITTANCE_SERVICE_NOT_EXIST);
                return false;
            }
            $fromConversionRemittanceServiceId = $inConversionRemittanceServiceId;
            
            if (!$outConversionRemittanceServiceId = $this->_remittanceServiceValidate($remittanceConfig->getIntermediaryCurrency(), $remittanceConfig->getToCountryCurrencyCode())) {
                $this->setResponseCode(MessageCode::CODE_REMITTANCE_SERVICE_NOT_EXIST);
                return false;
            }
            $toConversionRemittanceServiceId = $outConversionRemittanceServiceId;
        }

        /**
         *  cash_in_corp
         */
        $countryServIn = $countryService->getCountryCurrencyInfo($remittanceConfig->getFromCountryCurrencyCode());
        $sysCodeServIn = $systemCodeService->getByCode(TransactionType::CODE_CASH_IN, TransactionType::getSystemGroupCode());

        $cash_in_corp->setCountryCode($countryServIn->getCountryCode());
        $cash_in_corp->setServiceProviderId($remittanceConfig->getFromCountryPartnerID());
        $cash_in_corp->setName($sysCodeServIn->getDisplayName());
        $cash_in_corp->setDescription($sysCodeServIn->getDescription());
        $cash_in_corp->setTransactionTypeId($sysCodeServIn->getId());
        $cash_in_corp->setCountryCurrencyCode($remittanceConfig->getFromCountryCurrencyCode());
        $cash_in_corp->setConversionRemittanceServiceId($fromConversionRemittanceServiceId);
        $cash_in_corp->setCreatedBy($this->getUpdatedBy());

        /**
         *  cash_out_corp
         */
        $countryServOut = $countryService->getCountryCurrencyInfo($remittanceConfig->getToCountryCurrencyCode());
        $sysCodeServOut = $systemCodeService->getByCode(TransactionType::CODE_CASH_OUT, TransactionType::getSystemGroupCode());

        $cash_out_corp->setCountryCode($countryServOut->getCountryCode());
        $cash_out_corp->setServiceProviderId($remittanceConfig->getToCountryPartnerID());
        $cash_out_corp->setName($sysCodeServOut->getDisplayName());
        $cash_out_corp->setDescription($sysCodeServOut->getDescription());
        $cash_out_corp->setTransactionTypeId($sysCodeServOut->getId());
        $cash_out_corp->setCountryCurrencyCode($remittanceConfig->getToCountryCurrencyCode());
        $cash_out_corp->setConversionRemittanceServiceId($toConversionRemittanceServiceId);
        $cash_out_corp->setCreatedBy($this->getUpdatedBy());


        if (!$cash_in_corp_result = $corporateServServ->addService($cash_in_corp)) {
            $this->setResponseCode(MessageCode::CODE_ADD_CORPORATE_SERVICE_FAILED);
            return false;
        }

        if (!$cash_out_corp_result = $corporateServServ->addService($cash_out_corp)) {
            $this->setResponseCode(MessageCode::CODE_ADD_CORPORATE_SERVICE_FAILED);
            return false;
        }

        $inc_serv = IncrementIDServiceFactory::build();

        $remittance_config = new RemittanceConfig();
        $remittance_config->setId(GuidGenerator::generate());
        //$remittance_config->setChannelID($inc_serv->getIncrementID(IncrementIDAttribute::REMITTANCE_CHANNEL_ID));
        $remittance_config->setRemittanceServiceId($remittanceServiceId);
        $remittance_config->setCashinCorporateServiceId($cash_in_corp_result->getId());
        $remittance_config->setCashoutCorporateServiceId($cash_out_corp_result->getId());
        $remittance_config->setMinLimit($remittanceConfig->getMinLimit());
        $remittance_config->setMaxLimit($remittanceConfig->getMaxLimit());
        $remittance_config->setIsDefault($remittanceConfig->getIsDefault());
        $remittance_config->setStepAmount($remittanceConfig->getStepAmount());
        $remittance_config->setRequireFaceToFaceTrans($remittanceConfig->getRequireFaceToFaceTrans());
        $remittance_config->setRequireFaceToFaceRecipient($remittanceConfig->getRequireFaceToFaceRecipient());
        $remittance_config->setHomeCollectionEnabled($remittanceConfig->getHomeCollectionEnabled());
        $remittance_config->setCashinExpiryPeriod($remittanceConfig->getCashinExpiryPeriod());
        $remittance_config->setCreatedAt($remittanceConfig->getCreatedAt());
        $remittance_config->setCreatedBy($remittanceConfig->getCreatedBy());
        $remittance_config->setIsDefault(0);
        $remittance_config->setIsActive(0); //the channel is inactive

        if ($this->getRepository()->add($remittance_config)) {
            $this->setResponseCode(MessageCode::CODE_ADD_REMITTANCE_CONFIG_SUCCESS);
            //dispatch event to auditLog
            $this->fireLogEvent('iafb_remittance.remittance_config', AuditLogAction::CREATE, $remittanceConfig->getId());
            return $remittanceConfig;
        }

        $this->setResponseCode(MessageCode::CODE_ADD_REMITTANCE_CONFIG_FAILED);
        return false;
    }

    public function editRemittanceConfigOption(RemittanceConfig $new)
    {
        if( $RemittanceConfig = $this->getRemittanceConfigById($new->getId()) )
        {
            if( $RemittanceConfig instanceof RemittanceConfig )
            {
                if( $RemittanceConfig->getStatus() ==  RemittanceConfigStatus::APPROVED OR
                    $RemittanceConfig->getStatus() ==  RemittanceConfigStatus::PENDING )
                {                    
                    if( $new->getHomeCollectionEnabled() !== NULL )
                        $RemittanceConfig->setHomeCollectionEnabled($new->getHomeCollectionEnabled());

                    if( $new->getCashinExpiryPeriod() === '0' OR $new->getCashinExpiryPeriod() === 0)
                        $RemittanceConfig->setCashinExpiryPeriod(NULL);
                    elseif( $new->getCashinExpiryPeriod() != NULL )
                        $RemittanceConfig->setCashinExpiryPeriod($new->getCashinExpiryPeriod());

                    return $this->updateRemittanceConfig($RemittanceConfig);
                }

                $this->setResponseCode(MessageCode::CODE_REMITTANCE_CONFIG_IS_REJECTED);
                return false;
            }
        }

        $this->setResponseCode(MessageCode::CODE_REMITTANCE_CONFIG_NOT_FOUND);
        return false;
    }

    public function updateRemittanceConfigStatus($remittanceConfigurationId, $status, $remarks)
    {
        $limit = 10;
        $page = 1;
        
        // 1. Get remittance_service by remittance_conguration_id
        $entityRemittanceConfig = $this->getRemittanceConfigById($remittanceConfigurationId);
        if($entityRemittanceConfig == false)
        {
            $this->setResponseCode(MessageCode::CODE_REMITTANCE_CONFIG_NOT_FOUND);
            return false;
        }
        
        $entityRemittanceConfigOld = clone $entityRemittanceConfig;

        $entityRemittanceConfig->setUpdatedBy($this->getUpdatedBy());
        
        $cashInCorporateServiceId = $entityRemittanceConfig->getCashInCorporateServiceId();
        $cashOutCorporateServiceId = $entityRemittanceConfig->getCashOutCorporateServiceId();
        
        /**
         * already doing in getRemittanceConfigById function
         */
        // 2. Get and combine in/out corporate_service  by remittance_configuration.in_corporate_service_id, remittance_configuration.out_corporate_service_id
        
        // 3. If status = approve, go to 4. Otherwise, go to 10.
        if($status == RemittanceConfigStatus::APPROVED)
        {
            $cashInCorporateService = $entityRemittanceConfig->getCashInCorporateService();
            $cashOutCorporateService = $entityRemittanceConfig->getCashOutCorporateService();
            
            if( $cashInCorporateService == false || $cashInCorporateService->getId() == NULL)
            {
                $this->setResponseCode(MessageCode::CODE_INVALID_IN_CONVERSION_REMITTANCE_SERVICE_ID);
                return false;
            }
            if( $cashOutCorporateService == false || $cashOutCorporateService->getId() == NULL)
            {
                $this->setResponseCode(MessageCode::CODE_INVALID_OUT_CONVERSION_REMITTANCE_SERVICE_ID);
                return false;
            }
            
            //4. Validate in corporate_service has a valid exchange rate (corporate_service table)
            $serviceExchangeRate = $this->_getServiceExchangeRate();
            $entityExchangeRate = $serviceExchangeRate->findExchangeRateById($cashInCorporateService->getExchangeRateId());
            if($entityExchangeRate == FALSE)
            {
                $this->setResponseCode(MessageCode::CODE_REMITTANCE_CONFIG_EXCHANGE_RATE_NOT_APPROVED_YET);
                return false;
            }
            
            // 5. Validate out corporate service has a valid profit sharing record (profit_sharing_group table, approved and is active)
            $serviceProfitSharing = $this->_getServiceProfitSharing();
            $success = $serviceProfitSharing->getProfitSharingByCorpSerId($cashOutCorporateService->getId());
            if($success == false)
            {
                $this->setResponseCode(MessageCode::CODE_REMITTANCE_CONFIG_PROFIT_SHARING_NOT_APPROVED_YET);
                return false;
            }
            
            // 6. Validate in corporate service has at least a payment mode
            // 6.1 get payment mode list by cash in corporate service id (need is_active = 1 ?)
            $servicePaymentMode = $this->_getServicePaymentMode();
            $listPaymentMode = $servicePaymentMode->getPaymentModeListByCorporateServiceId(100, 1, array($cashInCorporateServiceId), NULL, 1, PaymentDirection::IN);
            if($listPaymentMode == false)
            {
                $this->setResponseCode(MessageCode::CODE_REMITTANCE_CONFIG_PRICING_CONFIG_NOT_APPROVED_YET);
                return false;
            }
            
            $paymentModeIds = NULL;
            if($listPaymentMode != null && count($listPaymentMode->result) > 0)
            {
                foreach ($listPaymentMode->result as $value) {
                    if($paymentModeIds == NULL)
                        $paymentModeIds = array();
                    array_push($paymentModeIds, $value->getId());
                }
            }

            // 7. Validate out corporate service has at least a payment mode (collection mode)
            $listCollectionMode = $servicePaymentMode->getPaymentModeListByCorporateServiceId(100, 1, array($cashOutCorporateServiceId), NULL, 1, PaymentDirection::OUT);
            if($listCollectionMode == false)
            {
                $this->setResponseCode(MessageCode::CODE_REMITTANCE_CONFIG_PRICING_CONFIG_NOT_APPROVED_YET);
                return false;
            }
            
            $collectionModeIds = NULL;
            if($listCollectionMode != null && count($listCollectionMode->result) > 0)
            {
                foreach ($listCollectionMode->result as $value) {
                    if($collectionModeIds == NULL)
                        $collectionModeIds = array();
                    array_push($collectionModeIds, $value->getId());
                }
            }

            // 8. Validate collection mode(s) has a valid payment_mode_fee (corporate_service_fee, approved, is_active)
            $servicePaymentModeFee = $this->_getServicePaymentModeFeeService();
            $listPaymentModeFee = $servicePaymentModeFee->getListByCorporrateServicePaymentModeIds($limit, $page, $collectionModeIds, NULL, 1, PaymentModeFeeGroupStatus::CODE_APPROVED);
            if( $listPaymentModeFee == FALSE )
            {
                $this->setResponseCode(MessageCode::CODE_REMITTANCE_CONFIG_PAYMENT_MODE_FEE_NOT_FOUND);
                return false;
            }

            // 9. Validate collection mode(s) has a valid payment_mode_cost (approved, is_active)
            $servicePaymentModeCost = $this->_getServicePaymentModeCostService();
            $listPaymentModeCost = $servicePaymentModeFee->getListByCorporrateServicePaymentModeIds($limit, $page, $collectionModeIds, NULL, 1, PaymentModeCostGroupStatus::CODE_APPROVED);
            if( $listPaymentModeCost == FALSE )
            {
                $this->setResponseCode(MessageCode::CODE_REMITTANCE_CONFIG_PAYMENT_MODE_COST_NOT_FOUND);
                return false;
            }
            
            $entityRemittanceConfig->setStatus($status);
            $entityRemittanceConfig->setApproveRejectRemark($remarks);
            $entityRemittanceConfig->setApproveRejectBy($this->getUpdatedBy());
            $entityRemittanceConfig->setApproveRejectAt(IappsDateTime::now());
            $entityRemittanceConfig->setIsActive(1);
            
        }
        else if($status == RemittanceConfigStatus::REJECTED)
        {
            // reject all rate,profit sharing, pricing config
            
            // 1. get pending rate list by remittance_config_id ( change status to 'REJECTED') 
            $serviceRates = $this->_getServiceExchangeRate();
            $serviceRates->setChannelCode(\Iapps\RemittanceService\Common\ChannelType::CODE_ADMIN_PANEL);
            
            if( $pendingRatesList = $serviceRates->getPendingApprovalRate($remittanceConfigurationId) )
            {
                // foreach list, and update status to reject
                foreach ($pendingRatesList['pending_rates'] as $entityRatesArray) {
                    
                    if( $serviceRates->updateExchangeRateStatus($remittanceConfigurationId, $entityRatesArray['id'], ExchangeRateStatus::REJECTED, $remarks) )
                    {
                        
                    }
                }
            }
            
            // 2. get pending profit sharing list ( change status to 'REJECTED') 
            $serviceProfitSharing = $this->_getServiceProfitSharing();
            if( $listProfitSharing = $serviceProfitSharing->getAllProfitSharingList(100,1,array($cashInCorporateServiceId, $cashOutCorporateServiceId),NULL, array(RemittanceCorpServProfitSharingStatus::PENDING), false) )
            {
                foreach ($listProfitSharing->result as $entityProfitSharing) {

                    $entityProfitSharing->setUpdatedBy($this->getUpdatedBy());
                    $entityProfitSharing->setApproveRejectBy($this->getUpdatedBy());
                    $entityProfitSharing->setApproveRejectRemark($remarks);
                    $entityProfitSharing->setStatus(RemittanceCorpServProfitSharingStatus::REJECTED);
                    $entityProfitSharing->setApproveRejectAt(IappsDateTime::now());
                    
                    if( $object = $serviceProfitSharing->updateProfitSharing($entityProfitSharing) )
                    {
                        
                    }
                }
            }
            
            // 3. get pending pricing config (fee) list
            $servicePaymentMode = $this->_getServicePaymentMode();
            
            $servicePaymentModeFeeGroup = $this->_getServicePaymentModeFeeGroupService();
            if( $listPaymentModeGroupObject = $servicePaymentMode->getApprovalPricingFeeListing(100,1,$remittanceConfigurationId, NULL,NULL,NULL,NULL,NULL, PaymentModeFeeGroupStatus::CODE_PENDING) )
            {
                $status = new SystemCode();
                $status->setCode(PaymentModeFeeGroupStatus::CODE_REJECTED);
                
                foreach ($listPaymentModeGroupObject->result as $entityPaymentModeFeeGroupArray) {
                    $paymentModeFeeGroupId = $entityPaymentModeFeeGroupArray['id'];
                    if( $entityPaymentModeFeeGroup = $servicePaymentModeFeeGroup->getPaymentModeFeeGroupById($paymentModeFeeGroupId) )
                    {
                        $entityPaymentModeFeeGroup->setStatus($status);
                        $entityPaymentModeFeeGroup->setApproveRejectRemark($remarks);
                        $entityPaymentModeFeeGroup->setApproveRejectAt(IappsDateTime::now());
                        $entityPaymentModeFeeGroup->setApproveRejectBy($this->getUpdatedBy());
                        
                        if( $servicePaymentModeFeeGroup->updatePaymentModeFeeGroupStatus($entityPaymentModeFeeGroup) )
                        {
                            
                        }
                    }
                }
            }
            
            // 4. get pending pricing config (cost) list
            $servicePaymentModeCostGroup = $this->_getServicePaymentModeCostGroupService();
            if( $listPaymentModeGroupObject = $servicePaymentMode->getApprovalPricingCostListing(100,1,$remittanceConfigurationId, NULL,NULL,NULL,NULL,NULL, PaymentModeCostGroupStatus::CODE_PENDING) )
            {
                $status = new SystemCode();
                $status->setCode(PaymentModeCostGroupStatus::CODE_REJECTED);
                
                foreach ($listPaymentModeGroupObject->result as $entityPaymentModeCostGroupArray) {
                    $paymentModeCostGroupId = $entityPaymentModeCostGroupArray['id'];
                    if( $entityPaymentModeCostGroup = $servicePaymentModeCostGroup->getPaymentModeCostGroupById($paymentModeCostGroupId) )
                    {
                        $entityPaymentModeCostGroup->setStatus($status);
                        $entityPaymentModeCostGroup->setApproveRejectRemark($remarks);
                        $entityPaymentModeCostGroup->setApproveRejectAt(IappsDateTime::now());
                        $entityPaymentModeCostGroup->setApproveRejectBy($this->getUpdatedBy());
                        
                        if( $servicePaymentModeCostGroup->updatePaymentModeCostGroupStatus($entityPaymentModeCostGroup) )
                        {
                            
                        }
                    }
                }
            }
            
            
            //10. Update remittance_configuration, status, approve_reject_by, approve_reject_at, approve_reject_remark
                        
            $entityRemittanceConfig->setStatus($status);
            $entityRemittanceConfig->setApproveRejectRemark($remarks);
            $entityRemittanceConfig->setApproveRejectBy($this->getUpdatedBy());
            $entityRemittanceConfig->setApproveRejectAt(IappsDateTime::now());
            
        }
        else
        {
            //10. Update remittance_configuration, status, approve_reject_by, approve_reject_at, approve_reject_remark
                        
            $entityRemittanceConfig->setStatus($status);
            $entityRemittanceConfig->setApproveRejectRemark($remarks);
            $entityRemittanceConfig->setApproveRejectBy($this->getUpdatedBy());
            $entityRemittanceConfig->setApproveRejectAt(IappsDateTime::now());
            
        }
        
        if( $this->getRepository()->updateRemittanceConfigStatus($entityRemittanceConfig) )
        {
            //dispatch event to auditLog
            $this->fireLogEvent('iafb_remittance.remittance_config', AuditLogAction::UPDATE, $entityRemittanceConfig->getId(), $entityRemittanceConfigOld);

            if( $entityRemittanceConfigNew = $this->getRemittanceConfigById($remittanceConfigurationId) )
            {
                $this->setResponseCode(MessageCode::CODE_EDIT_REMITTANCE_CONFIG_SUCCESS);
                return $entityRemittanceConfigNew;
            }
            $this->setResponseCode(MessageCode::CODE_EDIT_REMITTANCE_CONFIG_SUCCESS);
            return $entityRemittanceConfig;
        }
        
        $this->setResponseCode(MessageCode::CODE_EDIT_REMITTANCE_CONFIG_FAILED);
        return false;
    }

    public function updateRemittanceConfig(RemittanceConfig $remittanceConfiguration)
    {
        $remittanceConfiguration->setUpdatedBy($this->getUpdatedBy());
        if( $result = $this->getRepository()->edit($remittanceConfiguration) )
        {
            $this->setResponseCode(MessageCode::CODE_EDIT_REMITTANCE_CONFIG_SUCCESS);
            return $result;
        }

        $this->setResponseCode(MessageCode::CODE_EDIT_REMITTANCE_CONFIG_FAILED);
        return false;
    }
    /*
     * Get Active channel that can be purchased by public
     */

    public function getActiveChannel($fromCountryCode = NULL, $isArray = true ) {
        if ( $collection = $this->_getAllActiveChannel($fromCountryCode) )
        {
            $bestRatesCollection = $collection->getLowestRateByRemittanceService();

            $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_CONFIG_SUCCESS);
            if( $isArray )
                return $bestRatesCollection->getSelectedField(array('id', 'from_country_currency_code', 'to_country_currency_code', 'min_limit', 'max_limit', 'display_rate'));
            else
                return $bestRatesCollection;
        }

        $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_SERVICE_CONFIG_FAILED);
        return false;
    }

    protected function _getAllActiveChannel($fromCountryCode = NULL)
    {
        //filter active record
        $configFilter = new RemittanceConfig();
        $configFilter->setIsActive(1);  //must get active channel
        $configFilter->setStatus(RemittanceConfigStatus::APPROVED);

        $collection = new RemittanceConfigCollection();
        if ($fromCountryCode != NULL) {//get remittance service
            if ($reservCol = $this->_getRemittanceService()->getAllRemittanceServiceConfigByFromCountryCode($fromCountryCode)) {
                $ids = array();
                foreach ($reservCol AS $reserv)
                    $ids[] = $reserv['id'];

                if ($info = $this->getRepository()->findByRemittanceServiceIds($ids, $configFilter))
                    $collection = $info->result;
            }
        }
        else {
            if ($info = $this->getRemittanceConfigBySearchFilter($configFilter))
                $collection = $info->result;
        }

        if( count($collection) > 0 )
        {
            $this->_extractRelatedRecord($collection);
            return $collection;
        }

        return false;
    }

    /*
     * to get corporate service payment mode fee by remittance config id
     */

    public function getCorpServicePaymentModeAndFeeByRemittanceConfigId($remittance_config_id, $self_service = false) {
        $resultObject = new \StdClass;
        $resultObject->cash_in_payment_mode = null;
        $resultObject->cash_out_payment_mode = null;

        if ($remittanceConfig = $this->getRemittanceConfigById($remittance_config_id)) {

            $corp_serv_payment_mode_serv = CorporateServicePaymentModeExtendedServiceFactory::build();

            //get in payment mode based on in corp serv id
            $paymentMode = new CorporateServicePaymentMode();
            $paymentMode->setCorporateServiceId($remittanceConfig->getInCorporateService()->getId());
            $paymentMode->setDirection(PaymentDirection::IN);
            $paymentMode->setIsActive(1);
            if ($supported_payment_mode_arr = $corp_serv_payment_mode_serv->getCorpServPaymentModeAndFee($paymentMode, true, $self_service)) {
                $resultObject->cash_in_payment_mode = $supported_payment_mode_arr;
            }
            else
            {
                $this->setResponseCode($corp_serv_payment_mode_serv::CODE_CORPORATE_SERVICE_PAYMENT_MODE_NOT_FOUND);
                return false;
            }

            //get out payment mode based on out corp serv id
            $paymentMode = new CorporateServicePaymentMode();
            $paymentMode->setCorporateServiceId($remittanceConfig->getOutCorporateService()->getId());
            $paymentMode->setDirection(PaymentDirection::OUT);
            $paymentMode->setIsActive(1);
            if ($supported_payment_mode_arr = $corp_serv_payment_mode_serv->getCorpServPaymentModeAndFee($paymentMode, true, $self_service)) {
                $resultObject->cash_out_payment_mode = $supported_payment_mode_arr;
            }
            else
            {
                $this->setResponseCode($corp_serv_payment_mode_serv::CODE_CORPORATE_SERVICE_PAYMENT_MODE_NOT_FOUND);
                return false;
            }

            $this->setResponseCode($corp_serv_payment_mode_serv::CODE_GET_CORPORATE_SERVICE_PAYMENT_MODE_SUCCESS);
            return $resultObject;
        }

        $this->setResponseCode(MessageCode::CODE_REMITTANCE_CONFIG_NOT_FOUND);
        return false;
    }
    
    public function findByCorporateServiceIds(array $cashInCorporateServiceIds = NULL, array $cashOutCorporateServiceIds = NULL, RemittanceConfig $configFilter = NULL, $limit = NULL, $page = NULL )
    {
        if ($collection = $this->getRepository()->findByCorporateServiceIds($cashInCorporateServiceIds, $cashOutCorporateServiceIds, $configFilter, $limit, $page)) {
            $this->_extractRelatedRecord($collection->result);

            $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_CONFIG_SUCCESS);
            return $collection;
        }

        $this->setResponseCode(MessageCode::CODE_REMITTANCE_CONFIG_NOT_FOUND);
        return false;
    }
    
    public function getExistsRemittanceConfigList($limit, $page, $remittanceConfigId = NULL, $fromCountryCurrencyCode, $toCountryCurrencyCode, $fromCountryPartnerId, $toCountryPartnerId,array $status = NULL)
    {
        // check exists
        if($existsCollection = $this->getRepository()->findExistsRemittanceConfig($limit, $page, $remittanceConfigId,
                $fromCountryCurrencyCode,
                $toCountryCurrencyCode,
                $fromCountryPartnerId,
                $toCountryPartnerId,
                $status))
        {
            
            $this->_extractRelatedRecord($existsCollection->result);

            $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_CONFIG_SUCCESS);
            return $existsCollection;
        }
        $this->setResponseCode(MessageCode::CODE_REMITTANCE_CONFIG_NOT_FOUND);
        return false;
    }

    /**
     * @return mixed
     */
    protected function _getCorporateService() {
        return CorporateServServiceFactory::build();
    }

    /**
     * @return mixed
     */
    protected function _getRemittanceService() {
        return RemittanceServiceConfigServiceFactory::build();
    }

    /**
     * @param array $ids
     * @return object
     */
    protected function _getCorporateServicefindById(array $ids) {
        $corporate_service_object = array();

        $corporateServService = $this->_getCorporateService();

        foreach ($ids as $value) {

            if ($corporateServService->getCorporateService($value))
                $corporate_service_object[] = $corporateServService->getCorporateService($value);
        }

        return $corporate_service_object;
    }

    protected function _extractRelatedRecord(RemittanceConfigCollection $collection) {

        if( self::$_remittanceconfig_memory == NULL )
            self::$_remittanceconfig_memory = new RemittanceConfigCollection();

        $result = new RemittanceConfigCollection();
        $collection_tobeextracted = new RemittanceConfigCollection();
        foreach($collection AS $remittanceConfig )
        {
            if( !$memory = self::$_remittanceconfig_memory->getById($remittanceConfig->getId()) )
                $collection_tobeextracted->addData($remittanceConfig);
            else
            {
                $result->addData($memory);
            }
        }

        if( count($collection_tobeextracted) > 0 )
        {
            $remittance_service_ids = array();
            $cash_in_corporate_service_ids = array();
            $cash_out_corporate_service_ids = array();
            $from_country_partner_ids = array();
            $to_country_partner_ids = array();
            $user_ids = array();

            foreach ($collection_tobeextracted AS $remittanceConfig) {
                if( $remittanceConfig->getCashinCorporateServiceId() )
                    $cash_in_corporate_service_ids[] = $remittanceConfig->getCashinCorporateServiceId();
                if( $remittanceConfig->getCashoutCorporateServiceId() )
                    $cash_out_corporate_service_ids[] = $remittanceConfig->getCashoutCorporateServiceId();
                if( $remittanceConfig->getRemittanceServiceId() )
                    $remittance_service_ids[] = $remittanceConfig->getRemittanceServiceId();
                if( $remittanceConfig->getCreatedBy() )
                    $user_ids[] = $remittanceConfig->getCreatedBy();
                if( $remittanceConfig->getApproveRejectBy() )
                    $user_ids[] = $remittanceConfig->getApproveRejectBy();
            }

            if ($cash_in_corporate_service_data = $this->_getCorporateService()->findByIds($cash_in_corporate_service_ids))
                $collection_tobeextracted->joinInCorporateService($cash_in_corporate_service_data->result);

            if ($cash_out_corporate_service_data = $this->_getCorporateService()->findByIds($cash_out_corporate_service_ids))
                $collection_tobeextracted->joinOutCorporateService($cash_out_corporate_service_data->result);

            if ($remittance_service_data = $this->_getRemittanceService()->findByIds(array_unique($remittance_service_ids)))
                $collection_tobeextracted->joinRemittanceService($remittance_service_data->result);

            foreach ($collection_tobeextracted AS $remittanceConfig) {
                $from_country_partner_ids[] = $remittanceConfig->getFromCountryPartnerId();
                $to_country_partner_ids[] = $remittanceConfig->getToCountryPartnerId();
            }

            if ($user_datas = $this->_getAccountService()->getUsers(array_merge(array_unique($user_ids), $from_country_partner_ids, $to_country_partner_ids)))
            {
                $collection_tobeextracted->joinCreatorName ($user_datas);
                $collection_tobeextracted->joinApproveRejectBy($user_datas);
                $collection_tobeextracted->joinFromCountryPartner($user_datas);
                $collection_tobeextracted->joinToCountryPartner($user_datas);
            }

            if($pricing_update_data = $this->_getServicePaymentMode()->getLastApprovedAtByRemittanceConfigurationCollection($collection_tobeextracted))
                $collection_tobeextracted->joinPricingLastUpdated($pricing_update_data);

            foreach ($collection_tobeextracted AS $remittanceConfig) {
                if( $remittanceConfig instanceof RemittanceConfig )
                {
                    $cashInCorporateService = $remittanceConfig->getCashInCorporateService();
                    $cashOutCorporateService = $remittanceConfig->getCashOutCorporateService();
                    if($cashInCorporateService != NULL && $cashOutCorporateService != NULL && $cashInCorporateService->getConversionRemittanceService() != NULL && $cashOutCorporateService->getConversionRemittanceService() != NULL )
                    {
                        $intermediaryCurrencyCode = "";
                        $casInToCurrencyCode = $cashInCorporateService->getConversionRemittanceService()->getToCountryCurrencyCode();
                        $casOutFromCurrencyCode = $cashOutCorporateService->getConversionRemittanceService()->getFromCountryCurrencyCode();
                        if($casInToCurrencyCode == $casOutFromCurrencyCode)
                            $intermediaryCurrencyCode = $casInToCurrencyCode;
                        else
                            $intermediaryCurrencyCode = 'NONE';
                        $remittanceConfig->setIntermediaryCurrency($intermediaryCurrencyCode);
                    }
                }
            }

            $remcoServ = RemittanceCompanyServiceFactory::build();
            if( $remcos = $remcoServ->getByServiceProviderIds($collection_tobeextracted->getInServiceProviderIds()) )
            {
                $collection_tobeextracted->joinRemittanceCompany($remcos);
            }

            //add to memory
            foreach ($collection_tobeextracted AS $remittanceConfig)
            {
                self::$_remittanceconfig_memory->addData($remittanceConfig);
                $result->addData($remittanceConfig);
            }
        }


        //combined collection
        foreach( $result AS $remConfig )
            $collection->replaceElement($remConfig);

        $collection->rewind();
        return $collection;
    }

    protected function _getAccountService() {

        return AccountServiceFactory::build();
    }

    protected function _currencyCodeValidate($code) {
        $v = CurrencyCodeValidator::make($code);
        if ($v->fails()) {
            $this->setResponseCode(MessageCode::CODE_INVALID_SALARYCURRENCY);
            return true;
        }

        return false;
    }

    protected function _userTypeValidate($service_provider_id) {
        $acc_serv = AccountServiceFactory::build();
        if ($corporateInfo = $acc_serv->getCorporateInfo($service_provider_id)) {
            return $corporateInfo;
        }
        $this->setResponseCode(MessageCode::CODE_INVALID_SERVICE_PROVIDER);
        return false;
    }

    protected function _remittanceServiceValidate($fromCountryCurrencyCode, $toCountryCurrencyCode) {
        
        $remittanceServiceConfigService = $this->_getRemittanceService();

        $results = $remittanceServiceConfigService->getRemittanceServiceConfigInfoByFromAndTo($fromCountryCurrencyCode, $toCountryCurrencyCode);
        if ($results) {
            
            return $results->getId();
            
        } else {
            
            // add new remittance service for current fromCountryCurrencyCode and toCountryCurrencyCode
            
            $entityRemittanceServiceConfig = new RemittanceServiceConfig();
            $entityRemittanceServiceConfig->setId(GuidGenerator::generate());
            $entityRemittanceServiceConfig->setFromCountryCurrencyCode($fromCountryCurrencyCode);
            $entityRemittanceServiceConfig->setToCountryCurrencyCode($toCountryCurrencyCode);
            $entityRemittanceServiceConfig->setCreatedBy($this->getUpdatedBy());
            
            if( $remittanceServiceConfigService->addRemittanceServiceConfig($entityRemittanceServiceConfig) )
            {
                return $entityRemittanceServiceConfig->getId();
            }
            return false;
        }
    }

}
