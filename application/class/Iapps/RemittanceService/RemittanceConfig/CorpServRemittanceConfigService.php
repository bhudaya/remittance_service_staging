<?php

namespace Iapps\RemittanceService\RemittanceConfig;

use Iapps\Common\CorporateService\CorporateService;
use Iapps\Common\CorporateService\CorporateServiceRepository;
use Iapps\Common\Core\IappsBaseService;
use Iapps\RemittanceService\Common\CorporateServiceFeeServiceFactory;
use Iapps\RemittanceService\Common\TransactionTypeValidator;
use Iapps\Common\CorporateService\CorporateServService;
use Iapps\RemittanceService\Common\TransactionType;
use Iapps\RemittanceService\Common\ServiceProviderValidator;
use Iapps\RemittanceService\Common\CountryCodeValidator;
use Iapps\RemittanceService\Common\CurrencyCodeValidator;
use Iapps\RemittanceService\Common\MessageCode;
use Iapps\Common\Helper\GuidGenerator;
use Iapps\Common\AuditLog\AuditLogAction;
use Iapps\Common\CorporateService\CorporateServiceFee;
use Iapps\Common\CorporateService\CorporateServiceFeeRepository;
use Iapps\Common\CorporateService\CorporateServiceFeeService;
use Iapps\RemittanceService\RemittanceServiceConfig\RemittanceServiceConfigServiceFactory;
use Iapps\RemittanceService\Common\CorporateServServiceFactory;
use Iapps\RemittanceService\Common\CorporateServicePaymentModeServiceFactory;
use Iapps\RemittanceService\Common\CorporateServicePaymentModeFeeServiceFactory;
use Iapps\RemittanceService\Common\CorporateServiceFeeExtendedServiceFactory;
use Iapps\RemittanceService\RemittanceConfig\CorpServRemittanceConfigCollection;
use Iapps\RemittanceService\Common\FeeTypeValidator;
use Iapps\RemittanceService\Common\FeeType;
use Iapps\Common\CorporateService\CorporateServiceFeeCollection;
use Iapps\Common\CorporateService\CorporateServicePaymentModeFeeCollection;
use Iapps\Common\CorporateService\CorporateServicePaymentModeCollection;


class CorpServRemittanceConfigService extends RemittanceConfigService{

    /* Corporate Service Remittance Config (add, get by remittance service id) */

    public function getDefaultCorpServiceRemittanceConfigInfo($remittance_service_id, $limit, $page)
    {
        $result = array();
        $corpServiceRemittanceConfigColl = new CorpServRemittanceConfigCollection();
        $remittanceConfig = new RemittanceConfig();
        $remittanceConfig->setRemittanceServiceId($remittance_service_id);
        $remittanceConfig->setIsDefault((int)true);
        if($remittanceConfigList = $this->getRepository()->findBySearchFilter($remittanceConfig,$limit, $page))
        {
            $check_result = false;
            $result_array = array();
            $corpServRemittanceConfig = new CorpServRemittanceConfig();

            $corp_serv_serv = CorporateServServiceFactory::build();
            $cash_in_corp = new \Iapps\Common\CorporateService\CorporateService();
            $cash_out_corp = new \Iapps\Common\CorporateService\CorporateService();

            foreach($remittanceConfigList->result as $remittanceConfigEach) {
                $corpServRemittanceConfig->setId($remittanceConfigEach->getId());
                $corpServRemittanceConfig->setCashinCorporateServiceId($remittanceConfigEach->getCashinCorporateServiceId());
                $corpServRemittanceConfig->setCashoutCorporateServiceId($remittanceConfigEach->getCashoutCorporateServiceId());
                $corpServRemittanceConfig->setRemittanceServiceId($remittanceConfigEach->getRemittanceServiceId());
                $corpServRemittanceConfig->setMinLimit($remittanceConfigEach->getMinLimit());
                $corpServRemittanceConfig->setMaxLimit($remittanceConfigEach->getMaxLimit());
                $corpServRemittanceConfig->setStepAmount($remittanceConfigEach->getStepAmount());
                $corpServRemittanceConfig->setIsDefault($remittanceConfigEach->getIsDefault());

                $cash_in_corp = $corp_serv_serv->getCorporateService($remittanceConfigEach->getCashinCorporateServiceId());
                if ($cash_in_corp)
                {
                    $corpServRemittanceConfig->setCashInCorpServName($cash_in_corp->getName());
                    $corpServRemittanceConfig->setCashInCorpServDesc($cash_in_corp->getDescription());
                    $corpServRemittanceConfig->setCashInDailyLimit($cash_in_corp->getDailyLimit());
                }

                $cash_out_corp = $corp_serv_serv->getCorporateService($remittanceConfigEach->getCashoutCorporateServiceId());
                if ($cash_out_corp)
                {
                    $corpServRemittanceConfig->setCashOutCorpServName($cash_out_corp->getName());
                    $corpServRemittanceConfig->setCashOutCorpServDesc($cash_out_corp->getDescription());
                    $corpServRemittanceConfig->setCashOutDailyLimit($cash_out_corp->getDailyLimit());
                }

                break;
            }

            $result = $corpServRemittanceConfig;
            $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_CONFIG_SUCCESS);
        }
        else
        {
            $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_CONFIG_FAILED);
        }

        return $result;
    }

    private function populateCorpServiceRemittanceConfigObject(RemittanceConfig $remittanceConfig)
    {
        $corpServRemittanceConfig = new CorpServRemittanceConfig();
        $corpServRemittanceConfig->setId($remittanceConfig->getId());
        $corpServRemittanceConfig->setCashinCorporateServiceId($remittanceConfig->getCashinCorporateServiceId());
        $corpServRemittanceConfig->setCashoutCorporateServiceId($remittanceConfig->getCashoutCorporateServiceId());
        $corpServRemittanceConfig->setRemittanceServiceId($remittanceConfig->getRemittanceServiceId());
        $corpServRemittanceConfig->setMinLimit($remittanceConfig->getMinLimit());
        $corpServRemittanceConfig->setMaxLimit($remittanceConfig->getMaxLimit());
        $corpServRemittanceConfig->setStepAmount($remittanceConfig->getStepAmount());
        $corpServRemittanceConfig->setIsDefault($remittanceConfig->getIsDefault());

        $remittance_serv = RemittanceServiceConfigServiceFactory::build();
        if($remitService = $remittance_serv->getRemittanceServiceConfigInfo($remittanceConfig->getRemittanceServiceId()))
        {
            $corpServRemittanceConfig->setFromCountryCurrencyCode($remitService->getFromCountryCurrencyCode());
            $corpServRemittanceConfig->setToCountryCurrencyCode($remitService->getToCountryCurrencyCode());
        }

        $corp_serv_serv = CorporateServServiceFactory::build();
        $cash_in_corp = new \Iapps\Common\CorporateService\CorporateService();
        $cash_out_corp = new \Iapps\Common\CorporateService\CorporateService();

        $cash_in_corp = $corp_serv_serv->getCorporateService($remittanceConfig->getCashinCorporateServiceId());
        if ($cash_in_corp)
        {
            $corpServRemittanceConfig->setServiceProviderId($cash_in_corp->getServiceProviderId());
            $corpServRemittanceConfig->setCashInCorpServName($cash_in_corp->getName());
            $corpServRemittanceConfig->setCashInCorpServDesc($cash_in_corp->getDescription());
            $corpServRemittanceConfig->setCashInDailyLimit($cash_in_corp->getDailyLimit());
        }

        $cash_out_corp = $corp_serv_serv->getCorporateService($remittanceConfig->getCashoutCorporateServiceId());
        if ($cash_out_corp)
        {
            $corpServRemittanceConfig->setCashOutCorpServName($cash_out_corp->getName());
            $corpServRemittanceConfig->setCashOutCorpServDesc($cash_out_corp->getDescription());
            $corpServRemittanceConfig->setCashOutDailyLimit($cash_out_corp->getDailyLimit());
        }

        return $corpServRemittanceConfig;
    }

    public function getCorpServiceRemittanceConfigInfo($remittance_config_id)
    {
        $result = array();
        $corpServRemittanceConfig = new CorpServRemittanceConfig();
        if ($remittanceConfig = $this->getRepository()->findById($remittance_config_id)) {
            $result = $this->populateCorpServiceRemittanceConfigObject($remittanceConfig);
            $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_CONFIG_SUCCESS);
        }
        else
        {
            $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_CONFIG_FAILED);
        }

        return $result;
    }

    public function getAllCorpServiceRemittanceConfig($limit, $page)
    {
        $result = new \StdClass;
        $result->result = null;
        $result->total = null;
        $corpServRemittanceConfigColl = new CorpServRemittanceConfigCollection;

        if($remittanceConfigColl = $this->getRepository()->findAll($limit, $page))
        {
            foreach($remittanceConfigColl->result as $remittanceConfig) {
                $corpServRemittanceConfigColl->addData($this->populateCorpServiceRemittanceConfigObject($remittanceConfig));
            }

            $result->result = $corpServRemittanceConfigColl;
            $result->total = $remittanceConfigColl->total;
            $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_CONFIG_SUCCESS);
        }
        else
        {
            $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_CONFIG_FAILED);
        }

        return $result;
    }

    public function addCorpServiceRemittanceConfig(CorpServRemittanceConfig $corpServRemittanceConfig)
    {
        $remit_serv = RemittanceServiceConfigServiceFactory::build();
        $remit_serv->setUpdatedBy($this->getUpdatedBy());
        $remit_serv->setIpAddress($this->getIpAddress());
        if( $remit_serv_object = $remit_serv->getRemittanceServiceConfigInfo($corpServRemittanceConfig->getRemittanceServiceId()) )
        {
            $commit_trans = false;
            //start db trans
            $this->getRepository()->startDBTransaction();

            $corp_serv_serv = CorporateServServiceFactory::build();
            $corp_serv_serv->setIpAddress($this->getIpAddress());
            $corp_serv_serv->setUpdatedBy($this->getUpdatedBy());

            $cash_in_corp = new \Iapps\Common\CorporateService\CorporateService();
            $cash_in_corp->setCountryCurrencyCode($remit_serv_object->getFromCountryCurrencyCode());
            $cash_in_corp->setServiceProviderId($corpServRemittanceConfig->getServiceProviderId());
            $cash_in_corp->setName($corpServRemittanceConfig->getCashInCorpServName());
            $cash_in_corp->setDescription($corpServRemittanceConfig->getCashInCorpServDesc());
            $cash_in_corp->setDailyLimit($corpServRemittanceConfig->getCashInDailyLimit());
            $transactionTypeObj = TransactionTypeValidator::validate(TransactionType::CODE_CASH_IN);
            $cash_in_corp->setTransactionTypeId($transactionTypeObj->getId());

            if( !$cash_in_corp = $corp_serv_serv->addService($cash_in_corp) )
            {
                //roll back db trans
                $this->getRepository()->rollbackDBTransaction();
                $this->setResponseCode($corp_serv_serv->getResponseCode());
                return false;
            }

            $cash_out_corp = new \Iapps\Common\CorporateService\CorporateService();
            $cash_out_corp->setCountryCurrencyCode($remit_serv_object->getToCountryCurrencyCode());
            $cash_out_corp->setServiceProviderId($corpServRemittanceConfig->getServiceProviderId());
            $cash_out_corp->setName($corpServRemittanceConfig->getCashOutCorpServName());
            $cash_out_corp->setDescription($corpServRemittanceConfig->getCashOutCorpServDesc());
            $cash_out_corp->setDailyLimit($corpServRemittanceConfig->getCashOutDailyLimit());
            $transactionTypeObj = TransactionTypeValidator::validate(TransactionType::CODE_CASH_OUT);
            $cash_out_corp->setTransactionTypeId($transactionTypeObj->getId());

            if( !$cash_out_corp = $corp_serv_serv->addService($cash_out_corp) )
            {
                //roll back db trans
                $this->getRepository()->rollbackDBTransaction();
                $this->setResponseCode($corp_serv_serv->getResponseCode());
                return false;
            }

            $corpServRemittanceConfig->setCashinCorporateServiceId($cash_in_corp->getId());
            $corpServRemittanceConfig->setCashoutCorporateServiceId($cash_out_corp->getId());

            $corpServRemittanceConfig->setId(GuidGenerator::generate());
            $corpServRemittanceConfig->setCreatedBy($this->getUpdatedBy());
            if($this->getRepository()->add($corpServRemittanceConfig))
            {
                $this->fireLogEvent('iafb_remittance.remittance_configuration', AuditLogAction::CREATE, $corpServRemittanceConfig->getId());
                $commit_trans = true;
            }

            if($commit_trans)
            {
                $this->setResponseCode(MessageCode::CODE_ADD_REMITTANCE_CONFIG_SUCCESS);
                //commit db trans
                $this->getRepository()->completeDBTransaction();
                return true;
            }

            //roll back db trans
            $this->getRepository()->rollbackDBTransaction();
        }

        $this->setResponseCode(MessageCode::CODE_ADD_REMITTANCE_CONFIG_FAILED);
        return false;
    }

    public function editCorpServiceRemittanceConfig(CorpServRemittanceConfig $corpServRemittanceConfig)
    {
        if ($oriRemittanceConfigInfo = $this->getRepository()->findById($corpServRemittanceConfig->getId()))
        {
            $commit_trans = false;
            //start db trans
            $this->getRepository()->startDBTransaction();

            $corpServRemittanceConfig->setCashinCorporateServiceId($oriRemittanceConfigInfo->getCashinCorporateServiceId());
            $corpServRemittanceConfig->setCashoutCorporateServiceId($oriRemittanceConfigInfo->getCashoutCorporateServiceId());
            $corpServRemittanceConfig->setRemittanceServiceId($oriRemittanceConfigInfo->getRemittanceServiceId());
            $corpServRemittanceConfig->setUpdatedBy($this->getUpdatedBy());
            if($this->getRepository()->edit($corpServRemittanceConfig))
            {
                $this->fireLogEvent('iafb_remittance.remittance_configuration', AuditLogAction::UPDATE, $corpServRemittanceConfig->getId(), $oriRemittanceConfigInfo);

                $corp_serv_serv = CorporateServServiceFactory::build();
                $corp_serv_serv->setIpAddress($this->getIpAddress());
                $corp_serv_serv->setUpdatedBy($this->getUpdatedBy());

                $cash_in_corp = new \Iapps\Common\CorporateService\CorporateService();
                $cash_out_corp = new \Iapps\Common\CorporateService\CorporateService();

                $cash_in_corp = $corp_serv_serv->getCorporateService($oriRemittanceConfigInfo->getCashinCorporateServiceId());
                if ($cash_in_corp)
                {
                    $cash_in_corp->setName($corpServRemittanceConfig->getCashInCorpServName());
                    $cash_in_corp->setDescription($corpServRemittanceConfig->getCashInCorpServDesc());
                    $cash_in_corp->setDailyLimit($corpServRemittanceConfig->getCashInDailyLimit());
                    if( !$cash_in_corp = $corp_serv_serv->updateService($cash_in_corp) )
                    {
                        //roll back db trans
                        $this->getRepository()->rollbackDBTransaction();
                        $this->setResponseCode($corp_serv_serv->getResponseCode());
                        return false;
                    }
                }

                $cash_out_corp = $corp_serv_serv->getCorporateService($oriRemittanceConfigInfo->getCashoutCorporateServiceId());
                if ($cash_out_corp)
                {
                    $cash_out_corp->setName($corpServRemittanceConfig->getCashOutCorpServName());
                    $cash_out_corp->setDescription($corpServRemittanceConfig->getCashOutCorpServDesc());
                    $cash_out_corp->setDailyLimit($corpServRemittanceConfig->getCashOutDailyLimit());

                    if( !$cash_out_corp = $corp_serv_serv->updateService($cash_out_corp) )
                    {
                        //roll back db trans
                        $this->getRepository()->rollbackDBTransaction();
                        $this->setResponseCode($corp_serv_serv->getResponseCode());
                        return false;
                    }
                }

                $commit_trans = true;

            }

            if($commit_trans)
            {
                $this->setResponseCode(MessageCode::CODE_EDIT_REMITTANCE_CONFIG_SUCCESS);
                //commit db trans
                $this->getRepository()->completeDBTransaction();
                return true;
            }
            else
            {
                //roll back db trans
                $this->getRepository()->rollbackDBTransaction();
                $this->setResponseCode(MessageCode::CODE_EDIT_REMITTANCE_CONFIG_FAILED);
            }
        }
        else
        {
            $this->setResponseCode(MessageCode::CODE_REMITTANCE_CONFIG_NOT_FOUND);
        }

        return false;
    }

    public function getCorpServiceFeeByRemittanceConfigId($remittance_config_id, $limit = 100, $page = 1)
    {
        $resultObject = new \StdClass;
        $resultObject->cash_in_fee = null;
        $resultObject->cash_in_payment_mode = null;
        $resultObject->cash_out_fee = null;
        $resultObject->cash_out_payment_mode = null;

        if ($remittanceConfig = $this->getRepository()->findById($remittance_config_id)) {
            $corp_serv_serv = CorporateServServiceFactory::build();
            $corp_serv_fee_serv = CorporateServiceFeeExtendedServiceFactory::build();
            $corp_serv_payment_mode_serv = CorporateServicePaymentModeServiceFactory::build();
            $corp_serv_payment_mode_fee_serv = CorporateServicePaymentModeFeeServiceFactory::build();
            $result_pm_array = array();

            //why find by id needs mapCollection ???
            //$cashInCorp = $corp_serv_serv->getCorporateService($remittanceConfig->getCashinCorporateServiceId());
            //$check_result = $cashInCorp ? $cashInCorp->total > 0 : false;
            if ($cashInCorp = $corp_serv_serv->getCorporateService($remittanceConfig->getCashinCorporateServiceId()))
            {
                if($resultFeeObject = $corp_serv_fee_serv->getCorpServiceFeeByCorpServId($cashInCorp->getId(), $limit, $page))
                {
                    $resultObject->cash_in_fee = $resultFeeObject;
                }

                if ($cashInCorpServPaymentModeColl = $corp_serv_payment_mode_serv->getSupportedPaymentMode($cashInCorp->getId())) {
                    foreach($cashInCorpServPaymentModeColl->result as $corpServPaymentModeEach)
                    {
                        $result_pm = $corpServPaymentModeEach->getSelectedField(array('direction','corporate_service_id','payment_code','is_default','role_id'));
                        if($fee_object = $corp_serv_payment_mode_fee_serv->getPaymentModeFeeByCorporateServicePaymentModeId($corpServPaymentModeEach->getId()))
                        {
                            $result_pm['fee'] = $fee_object->result->toArray();
                            $total_fee_per_payment_mode = 0;
                            $total_fee_percentage_per_payment_mode = 0;
                            foreach($fee_object->result as $feeObjectEach)
                            {
                                if($feeObjectEach->getIsPercentage() == (int)true)
                                {
                                    $total_fee_percentage_per_payment_mode += $feeObjectEach->getConvertedFee();
                                }
                                else
                                {
                                    $total_fee_per_payment_mode += $feeObjectEach->getConvertedFee();
                                }
                            }

                            $result_pm['total_fee_by_payment_mode'] = $total_fee_per_payment_mode;
                            $result_pm['total_fee_percentage_per_payment_mode'] = $total_fee_percentage_per_payment_mode;
                        }

                        $result_pm_array[] = $result_pm;
                    }

                    $resultObject->cash_in_payment_mode = $result_pm_array;
                }
            }

            //why find by id needs mapCollection ???
            //$cashOutCorp = $corp_serv_serv->getCorporateService($remittanceConfig->getCashoutCorporateServiceId());
            //$check_result = $cashOutCorp ? $cashOutCorp->total > 0 : false;
            if ($cashOutCorp = $corp_serv_serv->getCorporateService($remittanceConfig->getCashoutCorporateServiceId()))
            {
                if($resultFeeObject = $corp_serv_fee_serv->getCorpServiceFeeByCorpServId($cashOutCorp->getId(), $limit, $page))
                {
                    $resultObject->cash_out_fee = $resultFeeObject;
                }

                $result_pm_array = array();
                if ($cashOutCorpServPaymentModeColl = $corp_serv_payment_mode_serv->getSupportedPaymentMode($cashOutCorp->getId())) {
                    foreach($cashOutCorpServPaymentModeColl->result as $corpServPaymentModeEach)
                    {
                        $result_pm = $corpServPaymentModeEach->getSelectedField(array('direction','corporate_service_id','payment_code','is_default','role_id'));
                        if($fee_object = $corp_serv_payment_mode_fee_serv->getPaymentModeFeeByCorporateServicePaymentModeId($corpServPaymentModeEach->getId()))
                        {
                            $result_pm['fee'] = $fee_object->result->toArray();
                            $total_fee_per_payment_mode = 0;
                            $total_fee_percentage_per_payment_mode = 0;
                            foreach($fee_object->result as $feeObjectEach)
                            {
                                if($feeObjectEach->getIsPercentage() == (int)true)
                                {
                                    $total_fee_percentage_per_payment_mode += $feeObjectEach->getConvertedFee();
                                }
                                else
                                {
                                    $total_fee_per_payment_mode += $feeObjectEach->getConvertedFee();
                                }
                            }

                            $result_pm['total_fee_by_payment_mode'] = $total_fee_per_payment_mode;
                            $result_pm['total_fee_percentage_per_payment_mode'] = $total_fee_percentage_per_payment_mode;
                        }

                        $result_pm_array[] = $result_pm;
                    }

                    $resultObject->cash_out_payment_mode = $result_pm_array;
                }
            }
        }
        $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_CONFIG_SUCCESS);

        return $resultObject;
    }
}