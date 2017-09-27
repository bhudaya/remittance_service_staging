<?php

namespace Iapps\RemittanceService\SlaDashboard;

use Iapps\Common\AuditLog\AuditLogAction;
use Iapps\RemittanceService\Common\MessageCode;
use Iapps\Common\Core\IappsBaseRepository;
use Iapps\Common\SystemCode\SystemCodeService;
use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\Core\IappsBaseService;
use Iapps\Common\Microservice\AccountService\PartnerAccountServiceFactory;
use Iapps\RemittanceService\Common\CorporateServServiceFactory;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfigServiceFactory;

class SlaDashboardTransactionService extends IappsBaseService{

    
    public function getServiceProviderId()
    {
        $acc_serv = PartnerAccountServiceFactory::build();
        if( $structure = $acc_serv->getAgentUplineStructure() )
        {
            if( $upline = $structure->first_upline )
            {
                if( $upline->getRoles()->hasRole(array('main_agent')) )
                {
                    return $upline->getUser()->getId();
                }
            }

            if( $upline = $structure->second_upline )
            {
                if( $upline->getRoles()->hasRole(array('main_agent')) )
                {
                    return $upline->getUser()->getId();
                }
            }
        }

        return false;
    }

    public function getSLARemittanceTransactionStatus($service_provider_id,$sla_commerce_time_from,
                                                 $sla_commerce_time_to,
                                                 $sla_remittance_cut_off_time,
                                                 $sla_remittance_on_time,
                                                 $sla_remittance_warning_time,
                                                 $date,
                                                 $is_admin = false)
    {

        if (!$is_admin) {
            
            $channelFilterArr = array();
            $channelFilterStr = NULL;
            $corporate_service_ids = array();
            $serviceCorporateServ = CorporateServServiceFactory::build();
            if( $collectionCorporateService = $serviceCorporateServ->findByServiceProviderIds(array($service_provider_id)) )
            {
                $corporate_service_ids = $collectionCorporateService->getIds();
                $corporate_service_ids = array_unique($corporate_service_ids);
                $serviceRemittanceConfiguration = RemittanceConfigServiceFactory::build();
                if( $collectionRemittanceConfiguration = $serviceRemittanceConfiguration->findByCorporateServiceIds($corporate_service_ids, NULL, NULL, MAX_VALUE, 1) )
                {
                    $channelFilterArr = $collectionRemittanceConfiguration->result->getIds();
                }
            }


            if ( count($channelFilterArr) > 0 ) {
                $channelFilterStr = "'" . join("','", $channelFilterArr) . "'";
            }
        }else{
            $channelFilterArr = array();
            $channelFilterStr = NULL;
        }
        

        $now = IappsDateTime::now();
        $result_over_waring = $this->getOverTimeAndWaringTime($channelFilterStr,$now->getUnix(),$sla_remittance_on_time,$sla_remittance_warning_time);

        $results = array();
        
        $results['transaction_status'] = $this->getSLATotalRemittanceTransactionStatus($channelFilterStr,$channelFilterArr,$sla_commerce_time_from,
                                                 $sla_commerce_time_to,
                                                 $sla_remittance_cut_off_time,
                                                 $sla_remittance_on_time,
                                                 $date);

        $pending_transactions = $this->getSLAListPendingTransaction($channelFilterArr,$sla_remittance_cut_off_time,$date);
        $pending_transactions_count = count($pending_transactions);
        $results['transaction_status']['total_pending_remittance_transactions'] = $pending_transactions_count;
        $results['pending_transactions'] = array_slice($pending_transactions,0,10);
        $results['transaction_status']['overtime_transactions'] = $result_over_waring['overtime_transactions'];
        $results['transaction_status']['waringtime_transactions'] = $result_over_waring['waringtime_transactions'];

        $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_TRANSACTION_SUCCESS);
        return $results;
    }
    
    public function getSLATotalRemittanceTransactionStatus($channelFilterStr,$channelFilterArr,$sla_commerce_time_from,
                                                 $sla_commerce_time_to,
                                                 $sla_remittance_cut_off_time,
                                                 $sla_remittance_on_time,
                                                 $date)
    {

        $temp_date = IappsDateTime::fromString($date.' '.$sla_remittance_cut_off_time)->subHour(8);
        $dateFilterTo = IappsDateTime::fromString($date.' '.$sla_remittance_cut_off_time)->subHour(8);
        $dateFilterFrom = $temp_date->subDay(1);

        $results = array();
        $results['total_completed_remittance_transactions'] = 0;
        $results['total_completed_remittance_transactions_within_sla'] = 0;
        $results['total_pending_remittance_transactions'] = 0;
        $results['total_remittance_transactions'] = 0;
        $results['total_pending_collection_remittance_transactions'] = 0;


        if ($result = $this->getRepository()->getSLATotalRemittanceTransactionStatus($channelFilterStr,$dateFilterFrom->getUnix(),$dateFilterTo->getUnix(),$sla_remittance_on_time)) {
            
            $results['total_completed_remittance_transactions'] = $result[0]->total_completed_remittance_transactions;
            $results['total_completed_remittance_transactions_within_sla'] = $result[0]->total_completed_remittance_transactions_within_sla;
            $results['total_pending_remittance_transactions'] = $result[0]->total_pending_remittance_transactions;
            $results['total_remittance_transactions'] = $result[0]->total_remittance_transactions;
            $total_pending_collection_remittance_transactions = $this->getAllPendingCollection($channelFilterArr);
            $results['total_pending_collection_remittance_transactions'] = $total_pending_collection_remittance_transactions['total_pending_collection_remittance_transactions'];

        }

        $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_TRANSACTION_SUCCESS);
        return $results;
    }
    
    public function getSLAListPendingTransaction($channelFilter,
                                                 $sla_remittance_cut_off_time,
                                                 $date)
    {

        $temp_date = IappsDateTime::fromString($date.' '.$sla_remittance_cut_off_time)->subHour(8);
        $dateFilterTo = IappsDateTime::fromString($date.' '.$sla_remittance_cut_off_time)->subHour(8);
        $dateFilterFrom = $temp_date->subDay(1);

        $fin_results = array();

        if ($result = $this->getRepository()->getSLAListPendingTransaction($channelFilter,$dateFilterFrom->getUnix(),$dateFilterTo->getUnix())) {
            
            foreach ($result as $v) {

                $results['created_at'] = IappsDateTime::fromUnix($v->paid_at)->getString();
                $results['country_currency_code'] = $v->country_currency_code;
                $results['transactionID'] = $v->transactionID;
                $results['remittanceID'] = $v->remittanceID;
                $results['transaction_id'] = $v->transaction_id;
                $results['amount'] = $v->amount;

                $fin_results[] = $results;
            }
        }

        $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_TRANSACTION_SUCCESS);
        return $fin_results;
    }

    public function getAllPendingCollection($channelFilter)
    {
        $results['total_pending_collection_remittance_transactions'] = 0;

        if ($result = $this->getRepository()->getAllPendingCollection($channelFilter)) {
            
            $results['total_pending_collection_remittance_transactions'] = count($result);
        }

        $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_TRANSACTION_SUCCESS);
        return $results;
    }

    public function getOverTimeAndWaringTime($channelFilter,$now,$sla_remittance_on_time,$sla_remittance_warning_time)
    {
        $results['overtime_transactions'] = 0;
        $results['waringtime_transactions'] = 0;

        if ($result = $this->getRepository()->getOverTimeAndWaringTime($channelFilter,$now,$sla_remittance_on_time,$sla_remittance_warning_time)) {
            
            $results['overtime_transactions'] = $result[0]->over_time_transaction;
            $results['waringtime_transactions'] = $result[0]->waring_time_transaction;
        }

        $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_TRANSACTION_SUCCESS);
        return $results;
    }
    
}