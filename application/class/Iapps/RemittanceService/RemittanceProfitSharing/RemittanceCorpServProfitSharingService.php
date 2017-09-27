<?php

namespace Iapps\RemittanceService\RemittanceProfitSharing;

use Iapps\Common\Core\IappsBaseService;
use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\RemittanceService\Common\MessageCode;
use Iapps\Common\Helper\GuidGenerator;
use Iapps\Common\Core\IappsDateTime;
use Iapps\RemittanceService\RemittanceProfitSharing\RemittanceCorpServProfitSharingServiceFactory;
use Iapps\RemittanceService\RemittanceProfitSharing\RemittanceCorpServProfitSharingStatus;
use Iapps\RemittanceService\RemittanceProfitSharing\RemittanceCorpServProfitSharingActiveStatus;
use Iapps\RemittanceService\Common\CorporateServServiceFactory;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfigServiceFactory;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfig;
use Iapps\RemittanceService\Common\SystemCodeServiceFactory;
use Iapps\RemittanceService\Common\TransactionType;
use Iapps\Common\Microservice\AccountService\AccountService;
use Iapps\Common\Microservice\AccountService\User;

class RemittanceCorpServProfitSharingService extends IappsBaseService
{   
    
    public function searchProfitSharing($limit, $page,  $status = NULL, $remittance_config_id = NULL, $from_country_currency_code = NULL, $to_country_currency_code = NULL, $from_country_partner_id = NULL, $to_country_partner_id = NULL)
    {

        $temp_status = $status ? array($status) : NULL;
        $reConfigSer = RemittanceConfigServiceFactory::build();
        $accountSer = AccountServiceFactory::build();
        $results = array();

        if ($remittance_config_id != NULL || $from_country_currency_code != NULL || $to_country_currency_code != NULL || $from_country_partner_id != NULL || $to_country_partner_id != NULL) {
            
            if ( $reCollection = $reConfigSer->getExistsRemittanceConfigList(999, 1, $remittance_config_id, $from_country_currency_code, $to_country_currency_code, $from_country_partner_id, $to_country_partner_id, NULL) ) {

                foreach ($reCollection->result as $eachReColl) {

                    $corporateServiceIds[] = $eachReColl->getCashInCorporateServiceId();
                    $corporateServiceIds[] = $eachReColl->getCashOutCorporateServiceId();
                }

                if ( $object = $this->getRepository()->findAllList($limit, $page, $corporateServiceIds, NULL, $temp_status) ) {
                    
                    $result  = array();

                    foreach ($object->result as $value) {

                        $result = $value->getSelectedField(array('status', 'corporate_service_id','approve_reject_remark', 'approve_reject_at','is_active','created_at'));
                        $result['approve_reject_by'] = NULL;
                        $result['profit_sharing_id'] = $value->getId();
                        $result['last_update_on'] = $value->getUpdatedAt()->getString();

                        $result['remittance_config_id'] = NULL;
                        $result['channel_id'] = NULL;
                        $result['from_country_currency_code'] = NULL;
                        $result['to_country_currency_code'] = NULL;
                        $result['from_country_partner_name'] = NULL;
                        $result['to_country_partner_name'] = NULL;
                        $result['updated_by'] = NULL;
                        $result['created_by'] = NULL;

                        if ($corporate_service_info = $this->getCorporateServiceInfo($value->getCorporateServiceId())) {

                            if ($userInfo = $accountSer->getUser($accountID = NULL, $value->getApproveRejectBy())) {
                                $result['approve_reject_by'] = $userInfo->getName();
                            }

                            if ($userInfo = $accountSer->getUser($accountID = NULL, $value->getUpdatedBy())) {

                                $result['updated_by'] = $userInfo->getName();
                            }

                            if ($userInfo = $accountSer->getUser($accountID = NULL, $value->getCreatedBy())) {

                                $result['created_by'] = $userInfo->getName();
                            }

                            $proSharingPartyFac = RemittanceProfitSharingPartyServiceFactory::build();
                            $proSharingParty = new RemittanceProfitSharingParty();
                            $proSharingParty->setCorporateServProfitSharingId($value->getId());

                            $profit_sharing_party_info = $proSharingPartyFac->getProfitSharingPartyList($proSharingParty);

                            $result['profit_sharing_party_info'] = $profit_sharing_party_info;

                            $reConfig = new RemittanceConfig();
                            $systemCodeService = SystemCodeServiceFactory::build();
                            $reConfigSer = RemittanceConfigServiceFactory::build();
                        
                            $reConfig->setStatus(NULL);

                            $sysCodeServ = $systemCodeService->getById($corporate_service_info->getTransactionTypeId());

                            if ($sysCodeServ->getCode() == TransactionType::CODE_CASH_IN OR
                                $sysCodeServ->getCode() == TransactionType::CODE_LOCAL_CASH_IN) {
                                $reConfig->setCashInCorporateServiceId($corporate_service_info->getId());
                            }else if ($sysCodeServ->getCode() == TransactionType::CODE_CASH_OUT OR
                                      $sysCodeServ->getCode() == TransactionType::CODE_LOCAL_CASH_OUT) {
                                $reConfig->setCashOutCorporateServiceId($corporate_service_info->getId());
                            }


                            if ($newReCollection = $reConfigSer->getRemittanceConfigBySearchFilter($reConfig)) {
                                
                                foreach ($newReCollection->result as $eachData) {
                                    $result['remittance_config_id'] = $eachData->getId();
                                    $result['channel_id'] = $eachData->getChannelID();
                                    $result['from_country_currency_code'] = $eachData->getFromCountryCurrencyCode();
                                    $result['to_country_currency_code'] = $eachData->getToCountryCurrencyCode();
                                    $result['from_country_partner_name'] = $eachData->getFromCountryPartnerName();
                                    $result['to_country_partner_name'] = $eachData->getToCountryPartnerName();
                                }
                            }

                            $results[] = $result;
                        }
                    }

                    $object->result = $results;
                    $this->setResponseCode(MessageCode::CODE_LIST_PROFIT_SHARING_SUCCESS);
                    return $object;
                }
                $this->setResponseCode(MessageCode::CODE_LIST_PROFIT_SHARING_FAIL);
                return false;
            }
            $this->setResponseCode(MessageCode::CODE_LIST_PROFIT_SHARING_FAIL);
            return false;

        }else{
            if ($object = $this->getProfitSharingList($limit, $page, NULL, $status, NULL) ) {
                $this->setResponseCode(MessageCode::CODE_LIST_PROFIT_SHARING_SUCCESS);
                return $object;
            }
            $this->setResponseCode(MessageCode::CODE_LIST_PROFIT_SHARING_FAIL);
            return false;
        }

        $this->setResponseCode(MessageCode::CODE_LIST_PROFIT_SHARING_FAIL);
        return false;

    }

    public function getProfitSharingList($limit, $page, $is_active = NULL, $status = NULL, $corporate_service_id = NULL)
    {   

        if( $object = $this->getRepository()->findAllByStatus(new RemittanceCorpServProfitSharingCollection(), $limit, $page, $is_active, $status, $corporate_service_id) )
        {
            if( $object->result instanceof RemittanceCorpServProfitSharingCollection )
            {   

                $results = array();
                $result  = array();

                foreach ($object->result as $value) {
                    
                    $result = $value->getSelectedField(array('status', 'corporate_service_id','approve_reject_remark', 'approve_reject_at','is_active','created_at'));
                    $result['approve_reject_by'] = NULL;
                    $result['profit_sharing_id'] = $value->getId();
                    $result['last_update_on'] = $value->getUpdatedAt()->getString();

                    $result['remittance_config_id'] = NULL;
                    $result['channel_id'] = NULL;
                    $result['from_country_currency_code'] = NULL;
                    $result['to_country_currency_code'] = NULL;
                    $result['from_country_partner_name'] = NULL;
                    $result['to_country_partner_name'] = NULL;
                    $result['updated_by'] = NULL;
                    $result['created_by'] = NULL;

                    if ( $corporate_service_info = $this->getCorporateServiceInfo($value->getCorporateServiceId()) ) {

                        $accountSer = AccountServiceFactory::build();
                        $reConfig = new RemittanceConfig();
                        $systemCodeService = SystemCodeServiceFactory::build();
                        $reConfigSer = RemittanceConfigServiceFactory::build();

                        if ($userInfo = $accountSer->getUser($accountID = NULL, $value->getApproveRejectBy())) {
                            $result['approve_reject_by'] = $userInfo->getName();
                        }

                        if ($userInfo = $accountSer->getUser($accountID = NULL, $value->getUpdatedBy())) {

                            $result['updated_by'] = $userInfo->getName();
                        }

                        if ($userInfo = $accountSer->getUser($accountID = NULL, $value->getCreatedBy())) {

                            $result['created_by'] = $userInfo->getName();
                        }


                        $sysCodeServ =  $systemCodeService->getById($corporate_service_info->getTransactionTypeId());

                        if ($sysCodeServ->getCode() == TransactionType::CODE_CASH_IN OR
                            $sysCodeServ->getCode() == TransactionType::CODE_LOCAL_CASH_IN) {
                            $reConfig->setCashInCorporateServiceId($corporate_service_info->getId());
                        }else if ($sysCodeServ->getCode() == TransactionType::CODE_CASH_OUT OR
                                  $sysCodeServ->getCode() == TransactionType::CODE_LOCAL_CASH_OUT) {
                            $reConfig->setCashOutCorporateServiceId($corporate_service_info->getId());
                        }

                        $reConfig->setStatus(NULL);

                        if ($reCollection = $reConfigSer->getRemittanceConfigBySearchFilter($reConfig)) {
                            
                            foreach ($reCollection->result as $eachData) {
                                $result['remittance_config_id'] = $eachData->getId();
                                $result['channel_id'] = $eachData->getChannelID();
                                $result['from_country_currency_code'] = $eachData->getFromCountryCurrencyCode();
                                $result['to_country_currency_code'] = $eachData->getToCountryCurrencyCode();
                                $result['from_country_partner_name'] = $eachData->getFromCountryPartnerName();
                                $result['to_country_partner_name'] = $eachData->getToCountryPartnerName();
                            }
                        }

                        $proSharingPartyFac = RemittanceProfitSharingPartyServiceFactory::build();
                        $proSharingParty = new RemittanceProfitSharingParty();
                        $proSharingParty->setCorporateServProfitSharingId($value->getId());

                        $profit_sharing_party_info = $proSharingPartyFac->getProfitSharingPartyList($proSharingParty);


                        $result['profit_sharing_party_info'] = $profit_sharing_party_info;

                        $results[] = $result;
                    }
                }

                $object->result = $results;
                $this->setResponseCode(MessageCode::CODE_LIST_PROFIT_SHARING_SUCCESS);
                return $object;
            }
        }

        $this->setResponseCode(MessageCode::CODE_LIST_PROFIT_SHARING_FAIL);
        return false;
    }

    public function getAllProfitSharingList($limit, $page, array $corporateServiceIds = NULL, $is_active = NULL, array $status = NULL, $isArray = false)
    {
        if( $object = $this->getRepository()->findAllList($limit, $page, $corporateServiceIds, $is_active, $status) )
        {
            if($isArray == false)
            {
                $this->setResponseCode(MessageCode::CODE_LIST_PROFIT_SHARING_SUCCESS);
                return $object;
            }
            else
            {
                if( $object->result instanceof RemittanceCorpServProfitSharingCollection )
                {
                    $results = array();
                    $result  = array();

                    foreach ($object->result as $value) {

                        $result = $value->getSelectedField(array('status', 'corporate_service_id','approve_reject_remark', 'approve_reject_at','is_active','created_at'));
                        $result['approve_reject_by'] = NULL;
                        $result['profit_sharing_id'] = $value->getId();
                        $result['last_update_on'] = $value->getUpdatedAt()->getString();

                        $result['remittance_config_id'] = NULL;
                        $result['channel_id'] = NULL;
                        $result['from_country_currency_code'] = NULL;
                        $result['to_country_currency_code'] = NULL;
                        $result['from_country_partner_name'] = NULL;
                        $result['to_country_partner_name'] = NULL;
                        $result['updated_by'] = NULL;
                        $result['created_by'] = NULL;

                        if (!$corporate_service_info = $this->getCorporateServiceInfo($value->getCorporateServiceId())) {
                            $this->setResponseCode(MessageCode::CODE_LIST_PROFIT_SHARING_FAIL);
                            return false;
                        }

                        $accountSer = AccountServiceFactory::build();
                        $reConfig = new RemittanceConfig();
                        $systemCodeService = SystemCodeServiceFactory::build();
                        $reConfigSer = RemittanceConfigServiceFactory::build();

                        if ($userInfo = $accountSer->getUser($accountID = NULL, $value->getApproveRejectBy())) {
                            $result['approve_reject_by'] = $userInfo->getName();
                        }

                        if ($userInfo = $accountSer->getUser($accountID = NULL, $value->getUpdatedBy())) {

                            $result['updated_by'] = $userInfo->getName();
                        }

                        if ($userInfo = $accountSer->getUser($accountID = NULL, $value->getCreatedBy())) {

                            $result['created_by'] = $userInfo->getName();
                        }


                        $sysCodeServ =  $systemCodeService->getById($corporate_service_info->getTransactionTypeId());

                        if ($sysCodeServ->getCode() == TransactionType::CODE_CASH_IN OR
                            $sysCodeServ->getCode() == TransactionType::CODE_LOCAL_CASH_IN) {
                            $reConfig->setCashInCorporateServiceId($corporate_service_info->getId());
                        }else if ($sysCodeServ->getCode() == TransactionType::CODE_CASH_OUT OR
                                  $sysCodeServ->getCode() == TransactionType::CODE_LOCAL_CASH_OUT) {
                            $reConfig->setCashOutCorporateServiceId($corporate_service_info->getId());
                        }

                        $reConfig->setStatus(NULL);

                        if ($reCollection = $reConfigSer->getRemittanceConfigBySearchFilter($reConfig)) {

                            foreach ($reCollection->result as $eachData) {
                                $result['remittance_config_id'] = $eachData->getId();
                                $result['channel_id'] = $eachData->getChannelID();
                                $result['from_country_currency_code'] = $eachData->getFromCountryCurrencyCode();
                                $result['to_country_currency_code'] = $eachData->getToCountryCurrencyCode();
                                $result['from_country_partner_name'] = $eachData->getFromCountryPartnerName();
                                $result['to_country_partner_name'] = $eachData->getToCountryPartnerName();
                            }
                        }

                        $proSharingPartyFac = RemittanceProfitSharingPartyServiceFactory::build();
                        $proSharingParty = new RemittanceProfitSharingParty();
                        $proSharingParty->setCorporateServProfitSharingId($value->getId());

                        if (!$profit_sharing_party_info = $proSharingPartyFac->getProfitSharingPartyList($proSharingParty)) {
                            $this->setResponseCode(MessageCode::CODE_LIST_PROFIT_SHARING_FAIL);
                            return false;
                        }

                        $result['profit_sharing_party_info'] = $profit_sharing_party_info;

                        $results[] = $result;
                    }

                    $object->result = $results;
                    $this->setResponseCode(MessageCode::CODE_LIST_PROFIT_SHARING_SUCCESS);
                    return $object;
                }
            }
        }

        $this->setResponseCode(MessageCode::CODE_LIST_PROFIT_SHARING_FAIL);
        return false;
    }
    
    public function getProfitSharingInfo($corporate_service_profit_sharing_id = NULL,$corporate_service_id = NULL)
    {   
        // $this->checkProfitSharingStatus($id);

        if ($corporate_service_profit_sharing_id) {
            if( !$info = $this->getRepository()->findById($corporate_service_profit_sharing_id) ){
                $this->setResponseCode(MessageCode::CODE_GET_PROFIT_SHARING_FAIL);
                return false;
            }
        }else{

            $entity = new RemittanceCorpServProfitSharing();
            $entity->setCorporateServiceId($corporate_service_id);
            $entity->setIsActive(RemittanceCorpServProfitSharingActiveStatus::ACTIVE);
            $entity->setStatus(RemittanceCorpServProfitSharingStatus::APPROVED);

            if( !$info = $this->getRepository()->findByParam($entity) ){
                $this->setResponseCode(MessageCode::CODE_GET_PROFIT_SHARING_FAIL);
                return false;
            }

            $info = $info->result->current();
        }


        $accountSer = AccountServiceFactory::build();
        $result  = array();

        $proSharingPartyFac = RemittanceProfitSharingPartyServiceFactory::build();
        $proSharingParty = new RemittanceProfitSharingParty();
        $proSharingParty->setCorporateServProfitSharingId($info->getId());

        if (!$profit_sharing_party_info = $proSharingPartyFac->getProfitSharingPartyList($proSharingParty)) {
            $this->setResponseCode(MessageCode::CODE_LIST_PROFIT_SHARING_FAIL);
            return false;
        }

        $result = $info->getSelectedField(array('status', 'corporate_service_id', 'approve_reject_remark', 'approve_reject_at','is_active','created_at'));
        $result['profit_sharing_id'] = $info->getId();
        $result['last_update_on'] = $info->getUpdatedAt()->getString();

        $result['remittance_config_id'] = NULL;
        $result['channel_id'] = NULL;
        $result['from_country_currency_code'] = NULL;
        $result['to_country_currency_code'] = NULL;
        $result['approve_reject_by'] = NULL;
        $result['updated_by'] = NULL;
        $result['from_country_partner_name'] = NULL;
        $result['to_country_partner_name'] = NULL;
        $result['created_by'] = NULL;

        if (!$corporate_service_info = $this->getCorporateServiceInfo($info->getCorporateServiceId())) {
            $this->setResponseCode(MessageCode::CODE_LIST_PROFIT_SHARING_FAIL);
            return false;
        }

        $reConfig = new RemittanceConfig();
        $systemCodeService = SystemCodeServiceFactory::build();
        $reConfigSer = RemittanceConfigServiceFactory::build();

        $sysCodeServ =  $systemCodeService->getById($corporate_service_info->getTransactionTypeId());

        if ($sysCodeServ->getCode() == TransactionType::CODE_CASH_IN OR
            $sysCodeServ->getCode() == TransactionType::CODE_LOCAL_CASH_IN) {
            $reConfig->setCashInCorporateServiceId($corporate_service_info->getId());
        }else if ($sysCodeServ->getCode() == TransactionType::CODE_CASH_OUT OR
                  $sysCodeServ->getCode() == TransactionType::CODE_LOCAL_CASH_OUT) {
            $reConfig->setCashOutCorporateServiceId($corporate_service_info->getId());
        }

        $reConfig->setStatus(NULL);

        if ($reCollection = $reConfigSer->getRemittanceConfigBySearchFilter($reConfig)) {
            
            foreach ($reCollection->result as $eachData) {
                $result['remittance_config_id'] = $eachData->getId();
                $result['channel_id'] = $eachData->getChannelID();
                $result['from_country_currency_code'] = $eachData->getFromCountryCurrencyCode();
                $result['to_country_currency_code'] = $eachData->getToCountryCurrencyCode();
                $result['from_country_partner_name'] = $eachData->getFromCountryPartnerName();
                $result['to_country_partner_name'] = $eachData->getToCountryPartnerName();
            }
        }

        $proSharingPartyFac = RemittanceProfitSharingPartyServiceFactory::build();
        $proSharingParty = new RemittanceProfitSharingParty();
        $proSharingParty->setCorporateServProfitSharingId($info->getId());

        if (!$profit_sharing_party_info = $proSharingPartyFac->getProfitSharingPartyList($proSharingParty)) {
            $this->setResponseCode(MessageCode::CODE_LIST_PROFIT_SHARING_FAIL);
            return false;
        }

        if ($userInfo = $accountSer->getUser($accountID = NULL, $info->getApproveRejectBy())) {

            $result['approve_reject_by'] = $userInfo->getName();
        }

        if ($userInfo = $accountSer->getUser($accountID = NULL, $info->getUpdatedBy())) {

            $result['updated_by'] = $userInfo->getName();
        }

        if ($userInfo = $accountSer->getUser($accountID = NULL, $info->getCreatedBy())) {

            $result['created_by'] = $userInfo->getName();
        }

        $result['profit_sharing_party_info'] = $profit_sharing_party_info;

        $this->setResponseCode(MessageCode::CODE_GET_PROFIT_SHARING_SUCCESS);
        return $result;
    }

    public function getActiveProfitSharingByCorporateService($corpServId)
    {
        $filter = new RemittanceCorpServProfitSharing();
        $filter->setCorporateServiceId($corpServId);
        $filter->setIsActive(1);

        if( $info = $this->getRepository()->findByParam($filter) )
        {
            $profitSharing = $info->result->current();

            if( $profitSharing instanceof RemittanceCorpServProfitSharing )
            {
                $proSharingPartyFac = RemittanceProfitSharingPartyServiceFactory::build();
                $proSharingParty = new RemittanceProfitSharingParty();
                $proSharingParty->setCorporateServProfitSharingId($profitSharing->getId());

                if (!$profit_sharing_party_info = $proSharingPartyFac->getProfitSharingPartyList($proSharingParty, false)) {
                    $this->setResponseCode(MessageCode::CODE_LIST_PROFIT_SHARING_FAIL);
                    return false;
                }

                $parties = $profit_sharing_party_info;
                $profitSharing->setParties($parties);
                return $profitSharing;
            }
        }

        $this->setResponseCode(MessageCode::CODE_GET_PROFIT_SHARING_FAIL);
        return false;
    }

    public function addProfitSharing(RemittanceCorpServProfitSharing $corp_serv_profit_sharing, $parties, $corporate_service_profit_sharing_id = NULL)
    {   

        if (!$corporate_service_info = $this->getCorporateServiceInfo($corp_serv_profit_sharing->getCorporateServiceId()))
            return false;

        $totalPercentage = 0;

        foreach ($parties as $value) {
            $totalPercentage += $value['percentage'];
        }

        if (!$this->_validatePercentage($totalPercentage))
            return false;

        if ($isOtherProfitSharing = $this->getRepository()->checkHasOtherPendingProfitSharing($corp_serv_profit_sharing->getCorporateServiceId())){
            $this->setResponseCode(MessageCode::CODE_ALREADY_HAVE_PENDING_PROFIT_SHARING);
            return false;
        }

        if ($corporate_service_profit_sharing_id) {

            if ($oriProfitSharing = $this->getRepository()->findById($corporate_service_profit_sharing_id)) {
                if ($oriProfitSharing->getStatus() == RemittanceCorpServProfitSharingStatus::PENDING) {
                    $this->setResponseCode(MessageCode::CODE_ALREADY_HAVE_PENDING_PROFIT_SHARING);
                    return false;
                }
            }
        }


        $corp_serv_profit_sharing->setId(GuidGenerator::generate());
        $corp_serv_profit_sharing->setStatus(RemittanceCorpServProfitSharingStatus::PENDING);
        $corp_serv_profit_sharing->setIsActive(RemittanceCorpServProfitSharingActiveStatus::UNACTIVE);

        $this->getRepository()->startDBTransaction();

        if ($this->getRepository()->insert($corp_serv_profit_sharing)) {
            
            $proSharingPartyFac = RemittanceProfitSharingPartyServiceFactory::build();

            foreach ($parties as $value) {

                $proSharingParty = new RemittanceProfitSharingParty();

                $proSharingParty->setId(GuidGenerator::generate());
                $proSharingParty->setCorporateServProfitSharingId($corp_serv_profit_sharing->getId());
                $proSharingParty->setCorporateId($value['service_provider_id']);
                $proSharingParty->setPercentage($value['percentage']);
                $proSharingParty->setCreatedBy($corp_serv_profit_sharing->getCreatedBy());

                if (!$proSharingPartyFac->addProfitSharingParty($proSharingParty)){
                    $this->setResponseCode(MessageCode::CODE_UPDATE_PROFIT_SHARING_FAIL);
                    return false;
                }

                $this->getRepository()->completeDBTransaction();
            }

            $this->getRepository()->completeDBTransaction();
            $this->setResponseCode(MessageCode::CODE_UPDATE_PROFIT_SHARING_SUCCESS);
            return $corp_serv_profit_sharing;
        }
        
        $this->getRepository()->rollbackDBTransaction();
        $this->setResponseCode(MessageCode::CODE_UPDATE_PROFIT_SHARING_FAIL);
        return false;
                           
    }

    public function updateProfitSharing(RemittanceCorpServProfitSharing $corp_serv_profit_sharing)
    {

        if( !$info = $this->getRepository()->findById($corp_serv_profit_sharing->getId()) )
            return false;

        if ($info->getStatus() == RemittanceCorpServProfitSharingStatus::PENDING) {
            
            if ($corp_serv_profit_sharing->getStatus() == RemittanceCorpServProfitSharingStatus::CANCELLED) {
                
                $corp_serv_profit_sharing->setIsActive(RemittanceCorpServProfitSharingActiveStatus::UNACTIVE);
                $this->getRepository()->startDBTransaction();

                if ($this->getRepository()->update($corp_serv_profit_sharing)) {
                    
                    $this->getRepository()->completeDBTransaction();
                    $this->setResponseCode(MessageCode::CODE_UPDATE_PROFIT_SHARING_SUCCESS);
                    return true;

                }else{
                    $this->getRepository()->rollbackDBTransaction();
                    $this->setResponseCode(MessageCode::CODE_UPDATE_PROFIT_SHARING_FAIL);
                    return false;
                }

            }
            else if ($corp_serv_profit_sharing->getStatus() == RemittanceCorpServProfitSharingStatus::APPROVED) {

                if ($avtiveProSharing = $this->getActiveProfitSharingByCorporateService($info->getCorporateServiceId())) {
                    
                    $avtiveProSharing->setIsActive(RemittanceCorpServProfitSharingActiveStatus::UNACTIVE);
                    $avtiveProSharing->setUpdatedBy($corp_serv_profit_sharing->getUpdatedBy());
                    $this->getRepository()->startDBTransaction();

                    if ($this->getRepository()->update($avtiveProSharing)) {
                        $this->getRepository()->completeDBTransaction();
                    }else{
                        $this->getRepository()->rollbackDBTransaction();
                        $this->setResponseCode(MessageCode::CODE_UPDATE_PROFIT_SHARING_FAIL);
                        return false;
                    }

                }

                if ($info->getIsActive() == RemittanceCorpServProfitSharingActiveStatus::UNACTIVE) {

                    $corp_serv_profit_sharing->setIsActive(RemittanceCorpServProfitSharingActiveStatus::ACTIVE);
                    $this->getRepository()->startDBTransaction();

                    if ($this->getRepository()->update($corp_serv_profit_sharing)) {
                        
                        $this->getRepository()->completeDBTransaction();
                        $this->setResponseCode(MessageCode::CODE_UPDATE_PROFIT_SHARING_SUCCESS);
                        return true;

                    }else{
                        $this->getRepository()->rollbackDBTransaction();
                        $this->setResponseCode(MessageCode::CODE_UPDATE_PROFIT_SHARING_FAIL);
                        return false;
                    }
                }

                $this->getRepository()->rollbackDBTransaction();
                $this->setResponseCode(MessageCode::CODE_UPDATE_PROFIT_SHARING_FAIL);
                return false; 

            }
            else{
                $corp_serv_profit_sharing->setIsActive(RemittanceCorpServProfitSharingActiveStatus::UNACTIVE);
                $this->getRepository()->startDBTransaction();

                if ($this->getRepository()->update($corp_serv_profit_sharing)) {
                    
                    $this->getRepository()->completeDBTransaction();
                    $this->setResponseCode(MessageCode::CODE_UPDATE_PROFIT_SHARING_SUCCESS);
                    return true;

                }else{
                    $this->getRepository()->rollbackDBTransaction();
                    $this->setResponseCode(MessageCode::CODE_UPDATE_PROFIT_SHARING_FAIL);
                    return false;
                }
            }
        }

        $this->setResponseCode(MessageCode::CODE_UPDATE_PROFIT_SHARING_FAIL);
        return false;     
    }

    public function getProfitSharingByCorpSerId($corporate_service_id)
    {   
        if (!$corporate_service_id) {
            return false;
        }

        if( $object = $this->getRepository()->findAllByStatus(new RemittanceCorpServProfitSharingCollection(), 10, 1, $is_active = NULL, $status = NULL, $corporate_service_id) )
        {
            return true;
        }

        return false;
    }


    protected function getCorporateServiceInfo($corporate_service_id)
    {
        $cservice_serv = CorporateServServiceFactory::build();

        if (!$corporate_service_info = $cservice_serv->getCorporateService($corporate_service_id)){
            $this->setResponseCode($cservice_serv::CODE_CORPORATE_SERVICE_NOT_FOUND);
            return false;
        }
        return $corporate_service_info;
    }

    protected function _validatePercentage($number)
    {//make sure its digit and equal 100
        return (is_numeric($number) && $number == 100);
    }
    
}