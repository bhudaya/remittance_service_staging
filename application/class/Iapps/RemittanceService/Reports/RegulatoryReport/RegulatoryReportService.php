<?php

namespace Iapps\RemittanceService\Reports\RegulatoryReport;
use Iapps\Common\Core\IappsBaseService;
use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\RemittanceService\Common\MessageCode;
use Iapps\Common\Core\IappsDateTime;
use Iapps\RemittanceService\Common\CorporateServServiceFactory;
use Iapps\RemittanceService\Common\SystemCodeServiceFactory;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfigServiceFactory;
use Iapps\RemittanceService\Common\TransactionType;
use Iapps\RemittanceService\RemittanceRecord\RemittanceRecordServiceFactory;
use Iapps\RemittanceService\RemittanceRecord\RemittanceRecord;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfig;
use Iapps\Common\Microservice\AccountService\AccountService;
use Iapps\Common\Microservice\AccountService\UserType;
use Iapps\Common\Microservice\AccountService\PartnerAccountServiceFactory;
use Iapps\RemittanceService\Recipient\RecipientServiceFactory;

class RegulatoryReportService extends IappsBaseService
{   
    
    protected function _getUpline()
    {
        $partnerServ = PartnerAccountServiceFactory::build();
        $uplines = $partnerServ->getAgentUplineStructure();

        if ($uplines) {
            if ($uplines->first_upline != NULL) {
                if ($uplines->first_upline->getUser() != NULL) {
                    return $uplines->first_upline->getUser();
                }
            }
            return false;
        }

        return false;
    }

    public function getFundsAcceptedSummaryReport($start_time,$end_time)
    {    

        $corporateSer = CorporateServServiceFactory::build();
        $results = array();
        $result = array();

        if (!$upline = $this->_getUpline())
            return false;


        $service_provider_id = $upline->getId();
        $result['partner_name'] = $upline->getName() ? $upline->getName() : NULL;

        if (!$service_provider_id)
            return false;

        $result['start_time'] = $start_time;
        $result['end_time'] = $end_time;

        $result['non_resident']['outward']['individual_no_of_remittance'] = 0;
        $result['non_resident']['outward']['individual_amount'] = 0;

        $result['non_resident']['outward']['company_no_of_remittance'] = 0;
        $result['non_resident']['outward']['company_amount'] = 0;

        $result['non_resident']['inward']['individual_no_of_remittance'] = 0;
        $result['non_resident']['inward']['individual_amount'] = 0;

        $result['non_resident']['inward']['company_no_of_remittance'] = 0;
        $result['non_resident']['inward']['company_amount'] = 0;

        $result['non_resident']['total_outward_no_of_remittance'] = 0;
        $result['non_resident']['total_inward_no_of_remittance'] = 0;
        $result['non_resident']['total_outward_amount'] = 0;
        $result['non_resident']['total_inward_amount'] = 0;

        $result['resident']['outward']['individual_no_of_remittance'] = 0;
        $result['resident']['outward']['individual_amount'] = 0;

        $result['resident']['outward']['company_no_of_remittance'] = 0;
        $result['resident']['outward']['company_amount'] = 0;

        $result['resident']['inward']['individual_no_of_remittance'] = 0;
        $result['resident']['inward']['individual_amount'] = 0;

        $result['resident']['inward']['company_no_of_remittance'] = 0;
        $result['resident']['inward']['company_amount'] = 0;

        $result['resident']['total_outward_no_of_remittance'] = 0;
        $result['resident']['total_inward_no_of_remittance'] = 0;
        $result['resident']['total_outward_amount'] = 0;
        $result['resident']['total_inward_amount'] = 0;

        if ($corporateCollection = $corporateSer->getCorporateServiceByServiceProId($service_provider_id)) {
            
            foreach ($corporateCollection->result as $corporateCollectionEach) {
                
                $systemCodeService = SystemCodeServiceFactory::build();
                $reConfigService = RemittanceConfigServiceFactory::build();
                $reConfig = new RemittanceConfig();

                $sysCodeServ =  $systemCodeService->getById($corporateCollectionEach->getTransactionTypeId());

                if ($sysCodeServ->getCode() == TransactionType::CODE_CASH_IN) {
                    // will be outward
                    $reConfig->setCashInCorporateServiceId($corporateCollectionEach->getId());
                }else if ($sysCodeServ->getCode() == TransactionType::CODE_CASH_OUT) {
                    // will be inward
                    $reConfig->setCashOutCorporateServiceId($corporateCollectionEach->getId());
                }

                if ($reCollection = $reConfigService->getRemittanceConfigBySearchFilter($reConfig)) {
                        
                    foreach ($reCollection->result as $eachData) {

                        $remittanceService = RemittanceRecordServiceFactory::build();
                        $remittance        = new RemittanceRecord();
                        $remittance->setRemittanceConfigurationId($eachData->getId());

                        $start_time = IappsDateTime::fromString($start_time)->getUnix();
                        $end_time = IappsDateTime::fromString($end_time)->getUnix();

                        if ($remittanceCollection = $remittanceService->getRepository()->reportFindByParam($remittance,$start_time,$end_time)) {
                            
                            $accountService = AccountServiceFactory::build();

                            foreach ($remittanceCollection->result as $remittanceCollectionEach) {

                                if ($user = $accountService->getUser(NULL,$remittanceCollectionEach->getSenderUserProfileId())) {

                                    $result['resident']['total_outward_amount'] += $remittanceCollectionEach->getFromAmount();

                                    if ($user->getUserType() == UserType::USER) {
                                        $result['resident']['outward']['individual_no_of_remittance'] += 1;
                                        $result['resident']['outward']['individual_amount'] += $remittanceCollectionEach->getFromAmount();

                                    }else if($user->getUserType() == UserType::CORPORATE) {
                                        $result['resident']['outward']['company_no_of_remittance'] += 1;
                                        $result['resident']['outward']['company_amount'] += $remittanceCollectionEach->getFromAmount();
                                    }
                                }
                            }
                            $result['resident']['total_outward_no_of_remittance'] = $result['resident']['outward']['individual_no_of_remittance'] + $result['resident']['outward']['company_no_of_remittance'];
                        }
                        $results[] = $result;
                    }
                }
            }




            $this->setResponseCode(MessageCode::CODE_GET_REGULATORY_REPORT_SUCCESS);
            return $results;
        }

        $this->setResponseCode(MessageCode::CODE_GET_REGULATORY_REPORT_FAILED);
        return false;
    }
    
    public function getFundsRemittedSummaryReport($start_time,$end_time)
    {

        $results = array();
        $result = array();

        if (!$upline = $this->_getUpline())
            return false;


        $service_provider_id = $upline->getId();
        $result['partner_name'] = $upline->getName() ? $upline->getName() : NULL;

        if (!$service_provider_id)
            return false;

        $result['start_time'] = $start_time;
        $result['end_time'] = $end_time;
        $result['to_country_partner_company'] = 0;
        $result['foreign_agent'] = 0;

        $start_time = IappsDateTime::fromString($start_time)->getUnix();
        $end_time = IappsDateTime::fromString($end_time)->getUnix();

        $corporateSer = CorporateServServiceFactory::build();

        if ($corporateCollection = $corporateSer->getCorporateServiceByServiceProId($service_provider_id)) {
            
            foreach ($corporateCollection->result as $corporateCollectionEach) {
                
                $systemCodeService = SystemCodeServiceFactory::build();
                $reConfigService = RemittanceConfigServiceFactory::build();
                $reConfig = new RemittanceConfig();

                $sysCodeServ =  $systemCodeService->getById($corporateCollectionEach->getTransactionTypeId());

                if ($sysCodeServ->getCode() == TransactionType::CODE_CASH_IN) {
                    $reConfig->setCashInCorporateServiceId($corporateCollectionEach->getId());
                }else if ($sysCodeServ->getCode() == TransactionType::CODE_CASH_OUT) {
                    $reConfig->setCashOutCorporateServiceId($corporateCollectionEach->getId());
                }

                if ($reCollection = $reConfigService->getRemittanceConfigBySearchFilter($reConfig)) {
                        
                    foreach ($reCollection->result as $eachData) {

                        $remittanceService = RemittanceRecordServiceFactory::build();
                        $remittance        = new RemittanceRecord();
                        $remittance->setRemittanceConfigurationId($eachData->getId());

                        if ($remittanceCollection = $remittanceService->getRepository()->reportFindByParam($remittance,$start_time,$end_time)) {
                            
                            foreach ($remittanceCollection->result as $remittanceCollectionEach) {

                                $result['to_country_partner_company'] += $remittanceCollectionEach->getFromAmount();
                            }

                            $results[] = $result;
                        }

                    }

                }
            }

        $this->setResponseCode(MessageCode::CODE_GET_REGULATORY_REPORT_SUCCESS);
        return $results;
        }
        $this->setResponseCode(MessageCode::CODE_GET_REGULATORY_REPORT_FAILED);
        return false;
    }

    public function getRemittanceTransactionReport($start_time,$end_time)
    {
        $results = array();
        $result = array();

        if (!$upline = $this->_getUpline())
            return false;

        $service_provider_id = $upline->getId();
        $result['partner_detail']['partner_name'] = $upline->getName() ? $upline->getName() : NULL;

        if (!$service_provider_id)
            return false;

        $result['start_time'] = $start_time;
        $result['end_time'] = $end_time;
        $result['partner_detail']['address'] = NULL;
        $result['partner_detail']['country'] = NULL;
        $result['partner_detail']['postal_code'] = NULL;


        $result['transaction_detail']['recipient_name'] = NULL;
        $result['transaction_detail']['recipient_city'] = NULL;
        $result['transaction_detail']['recipient_province'] = NULL;
        $result['transaction_detail']['recipient_country'] = NULL;

        $result['transaction_detail']['sender_id_number'] = NULL;
        $result['transaction_detail']['sender_full_name'] = NULL;
        $result['transaction_detail']['sender_address'] = NULL;
        $result['transaction_detail']['sender_postal_code'] = NULL;
        $result['transaction_detail']['sender_city'] = NULL;
        $result['transaction_detail']['sender_province'] = NULL;
        $result['transaction_detail']['sender_country'] = NULL;
        $result['transaction_detail']['sender_dob'] = NULL;
        $result['transaction_detail']['sender_mobile_no'] = NULL;
        $result['transaction_detail']['sender_cityzenship'] = NULL;
        $result['transaction_detail']['date'] = NULL;
        $result['transaction_detail']['foreign_amount'] = 0;
        $result['transaction_detail']['foreign_currency'] = NULL;
        $result['transaction_detail']['rate'] = 0;
        $result['transaction_detail']['comm'] = 0;
        $result['transaction_detail']['local_amount'] = 0;

        $accountService = AccountServiceFactory::build();

        if ($user = $accountService->getUser(NULL,$service_provider_id)) {

            $result['partner_detail']['address'] = $user->getHostAddress()->address;
            $result['partner_detail']['country'] = $user->getHostAddress()->country;
            $result['partner_detail']['postal_code'] = $user->getHostAddress()->postal_code;
        }

        $results['partner_detail'] = $result['partner_detail'];

        $start_time = IappsDateTime::fromString($start_time)->getUnix();
        $end_time = IappsDateTime::fromString($end_time)->getUnix();

        $corporateSer = CorporateServServiceFactory::build();

        if ($corporateCollection = $corporateSer->getCorporateServiceByServiceProId($service_provider_id)) {
            
            foreach ($corporateCollection->result as $corporateCollectionEach) {
                
                $systemCodeService = SystemCodeServiceFactory::build();
                $reConfigService = RemittanceConfigServiceFactory::build();
                $reConfig = new RemittanceConfig();

                $sysCodeServ =  $systemCodeService->getById($corporateCollectionEach->getTransactionTypeId());

                if ($sysCodeServ->getCode() == TransactionType::CODE_CASH_IN) {
                    $reConfig->setCashInCorporateServiceId($corporateCollectionEach->getId());
                }else if ($sysCodeServ->getCode() == TransactionType::CODE_CASH_OUT) {
                    $reConfig->setCashOutCorporateServiceId($corporateCollectionEach->getId());
                }

                if ($reCollection = $reConfigService->getRemittanceConfigBySearchFilter($reConfig)) {
                        
                    foreach ($reCollection->result as $eachData) {

                        $remittanceService = RemittanceRecordServiceFactory::build();
                        $remittance        = new RemittanceRecord();
                        $remittance->setRemittanceConfigurationId($eachData->getId());

                        $recipientService  = RecipientServiceFactory::build();

                        if ($remittanceCollection = $remittanceService->getRepository()->reportFindByParam($remittance,$start_time,$end_time)) {
                            
                            foreach ($remittanceCollection->result as $remittanceCollectionEach) {

                                if ($remittanceDetail = $remittanceService->getRemittanceTransactionDetail($remittanceCollectionEach->getId())) {
                                    
                                    if ($user = $accountService->getUser(NULL,$remittanceDetail->getSenderUserProfileId())) {

                                        $result['transaction_detail']['sender_address'] = $user->getHostAddress()->address;
                                        $result['transaction_detail']['sender_postal_code'] = $user->getHostAddress()->postal_code;
                                        $result['transaction_detail']['sender_city'] = $user->getHostAddress()->city;
                                        $result['transaction_detail']['sender_province'] = $user->getHostAddress()->province;
                                        $result['transaction_detail']['sender_country'] = $user->getHostAddress()->country;
                                        $result['transaction_detail']['sender_id_number'] = $user->getHostIdentityCard();
                                        $result['transaction_detail']['sender_full_name'] = !empty($user->getFullName()) ? $user->getFullName() : $user->getName();
                                        $result['transaction_detail']['sender_dob'] = !empty($user->getDOB()) ? $user->getDOB()->getString() : NULL;
                                        $result['transaction_detail']['sender_mobile_no'] = $user->getMobileNumber()->dialing_code.' '.$user->getMobileNumber()->mobile_number;
                                        $result['transaction_detail']['sender_cityzenship'] = $user->getHostCountryCode();

                                    }

                                    if ($recipient_user = $recipientService->getRecipientDetail($remittanceDetail->getRecipient()->getId())) {

                                        if (!empty($recipient_user[0]['recipient_user_profile_id'])) {
                                            
                                        }else{
                                            $result['transaction_detail']['recipient_name'] = !empty($recipient_user[0]['full_name']) ? $recipient_user[0]['full_name'] : $recipient_user[0]['recipient_alias'];
                                            $result['transaction_detail']['recipient_country'] = !empty($recipient_user[0]['recipient_country_code']) ? $recipient_user[0]['recipient_country_code'] : NULL;
                                        }
                                    }

                                    $result['transaction_detail']['local_amount'] = $remittanceDetail->getFromAmount();
                                    $result['transaction_detail']['foreign_amount'] = $remittanceDetail->getToAmount();
                                    $result['transaction_detail']['rate'] = $remittanceDetail->getDisplayRate();
                                    $result['transaction_detail']['comm'] = $remittanceDetail->getFeesCharged();
                                    $result['transaction_detail']['date'] = $remittanceDetail->getCreatedAt()->getString();
                                    $result['transaction_detail']['foreign_currency'] = $remittanceDetail->getToCurrencyCode();


                                }
                                $results['transaction_detail'][] = $result['transaction_detail'];
                            }
                        }
                    }
                }
            }  
        $this->setResponseCode(MessageCode::CODE_GET_REGULATORY_REPORT_SUCCESS);
        return $results;  
        }
            
        $this->setResponseCode(MessageCode::CODE_GET_REGULATORY_REPORT_FAILED);
        return false;
    }
    
    public function getStatementOfRemittanceReport($start_time,$end_time)
    {
        $results = array();
        $result = array();

        if (!$upline = $this->_getUpline())
            return false;

        $service_provider_id = $upline->getId();
        $result['partner_name'] = $upline->getName() ? $upline->getName() : NULL;

        if (!$service_provider_id)
            return false;

        $result['start_time'] = $start_time;
        $result['end_time'] = $end_time;
        $result['total_of_remittance_trx'] = 0;
        $result['foreign_exchange_gain'] = 0;
        $result['foreign_exchange_loss'] = 0;
        $result['commission'] = 0;
        $result['fees'] = 0;
        $result['max_outstanding_tt'] = 0;
        $result['max_outstanding_mt'] = 0;
        $result['number_of_customer_out'] = 0;
        $result['number_of_customer_in'] = 0;

        $start_time = IappsDateTime::fromString($start_time)->getUnix();
        $end_time = IappsDateTime::fromString($end_time)->getUnix();

        $corporateSer = CorporateServServiceFactory::build();

        if ($corporateCollection = $corporateSer->getCorporateServiceByServiceProId($service_provider_id)) {
            
            foreach ($corporateCollection->result as $corporateCollectionEach) {
                
                $systemCodeService = SystemCodeServiceFactory::build();
                $reConfigService = RemittanceConfigServiceFactory::build();
                $reConfig = new RemittanceConfig();

                $sysCodeServ =  $systemCodeService->getById($corporateCollectionEach->getTransactionTypeId());

                if ($sysCodeServ->getCode() == TransactionType::CODE_CASH_IN) {
                    $reConfig->setCashInCorporateServiceId($corporateCollectionEach->getId());
                }else if ($sysCodeServ->getCode() == TransactionType::CODE_CASH_OUT) {
                    $reConfig->setCashOutCorporateServiceId($corporateCollectionEach->getId());
                }

                if ($reCollection = $reConfigService->getRemittanceConfigBySearchFilter($reConfig)) {
                        
                    foreach ($reCollection->result as $eachData) {

                        $remittanceService = RemittanceRecordServiceFactory::build();
                        $remittance        = new RemittanceRecord();
                        $remittance->setRemittanceConfigurationId($eachData->getId());

                        if ($remittanceCollection = $remittanceService->getRepository()->reportFindByParam($remittance,$start_time,$end_time)) {
                            
                            $result['total_of_remittance_trx'] += count($remittanceCollection->result);

                        }

                        $results[] = $result;
                    }

                }
            }

        $this->setResponseCode(MessageCode::CODE_GET_REGULATORY_REPORT_SUCCESS);
        return $results;
        }

        $this->setResponseCode(MessageCode::CODE_GET_REGULATORY_REPORT_FAILED);
        return false;
    }

}