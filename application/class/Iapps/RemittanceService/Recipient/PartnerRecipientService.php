<?php

namespace Iapps\RemittanceService\Recipient;

use Iapps\Common\Microservice\AccountService\PartnerAccountServiceFactory;
use Iapps\Common\Microservice\UserCreditService\OFACStatus;
use Iapps\Common\Microservice\UserCreditService\PartnerUserCreditServiceFactory;
use Iapps\Common\Microservice\UserCreditService\RiskLevelStatus;
use Iapps\Common\Microservice\UserCreditService\WorldCheckStatus;
use Iapps\RemittanceService\Common\MessageCode;
use Iapps\RemittanceService\RemittanceCompany\RemittanceCompanyServiceFactory;
use Iapps\RemittanceService\RemittanceCompanyUser\PartnerRemittanceCompanyUserService;
use Iapps\RemittanceService\RemittanceCompanyUser\RemcoUserNameExtractor;
use Iapps\RemittanceService\RemittanceCompanyUser\RemittanceCompanyUserServiceFactory;
use Iapps\RemittanceService\RemittanceCompanyRecipient\RemittanceCompanyRecipientServiceFactory;
use Iapps\Common\Microservice\UserCreditService\SystemUserCreditServiceFactory;


class PartnerRecipientService extends RecipientServiceV2{

    public function getRecipientDetailWithUserInfo($recipient_id)
    {
        if( $recipientDetail = parent::getRecipientDetailWithUserInfo($recipient_id) )
        {
            //get remco recipient profile
            $recipientDetail['remittance_profile'] = NULL;
            $remcoRecipientServ = RemittanceCompanyRecipientServiceFactory::build();
            if( $remittanceCompany = $this->_getRemittanceCompany() )
            {
                if( $remittanceProfile = $remcoRecipientServ->getByCompanyAndRecipient($remittanceCompany, $recipient_id) )
                {
                    $recipientDetail['remittance_profile'] = $remittanceProfile->getSelectedField(array(
                                                                    'id',
                                                                    'recipient_status',
                                                                    'face_to_face_verified_at',
                                                                    'face_to_face_verified_by'                                                                    
                                                                ));
                    
                    $ucServ = SystemUserCreditServiceFactory::build();
                    $ucServ->setServiceProviderId($remittanceCompany->getServiceProviderId());
                    $recipientDetail['remittance_profile']['user_credit'] = array();
                    if( !$wcStat = $ucServ->getWorldCheckStatus(NULL, $recipient_id) )
                    {
                        $wcArr = array();
                        $wcArr['status'] = WorldCheckStatus::PENDING;
                        $wcArr['reference_no'] = NULL;
                    }
                    else
                    {
                        $wcArr = $wcStat->getSelectedField(array('status','reference_no'));
                    }

                    $ofacStat = null;
                    $watchlistStat = null;
                    if( $watchlist = $ucServ->getWatchlistStatusList(array(), array($recipient_id)) )
                    {
                        foreach($watchlist AS $watch)
                        {
                            if( $watch->recipient_id == $recipient_id )
                            {
                                $ofacStat = $watch->status->ofac->status;
                                $watchlistStat = $watch->status->watchlist->status;
                                break;
                            }
                        }
                    }
                        
                    if( !$riskStat = $ucServ->getRiskLevelStatus(NULL, $recipientDetail['id']) )
                        $riskStat = RiskLevelStatus::PENDING;

                    $recipientDetail['remittance_profile']['user_credit']['worldcheck'] = $wcArr;
                    $recipientDetail['remittance_profile']['user_credit']['ofac'] = $ofacStat;
                    $recipientDetail['remittance_profile']['user_credit']['risk_level'] = $riskStat;
                    $recipientDetail['remittance_profile']['user_credit']['watchlist'] = $watchlistStat;
                }
            }            

            return $recipientDetail;
        }

        return false;
    }

    protected function _getMainAgent()
    {
        $acc_serv = PartnerAccountServiceFactory::build();
        if( $structure = $acc_serv->getAgentUplineStructure() )
        {
            if( $upline = $structure->first_upline )
            {
                if( $upline->getRoles()->hasRole(array('main_agent')) )
                {
                    return $upline->getUser();
                }
            }

            if( $upline = $structure->second_upline )
            {
                if( $upline->getRoles()->hasRole(array('main_agent')) )
                {
                    return $upline->getUser();
                }
            }
        }

        return false;
    }

    protected function _getRemittanceCompany()
    {
        if( $mainAgent = $this->_getMainAgent() )
        {
            $rcServ = RemittanceCompanyServiceFactory::build();
            return $rcServ->getByServiceProviderId($mainAgent->getId());
        }

        $this->setResponseCode(MessageCode::CODE_REMCO_NOT_FOUND);
        return false;
    }
}