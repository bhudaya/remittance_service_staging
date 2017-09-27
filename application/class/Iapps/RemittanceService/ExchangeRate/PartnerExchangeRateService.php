<?php

namespace Iapps\RemittanceService\ExchangeRate;

use Iapps\Common\Microservice\AccountService\PartnerAccountServiceFactory;
use Iapps\RemittanceService\Common\ChannelType;
use Iapps\RemittanceService\Common\EmployerValidator;
use Iapps\RemittanceService\Common\MessageCode;
use Iapps\RemittanceService\Common\TransactionType;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfig;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfigCollection;
use Iapps\RemittanceService\RemittanceCorporateService\RemittanceCorporateService;
use Iapps\RemittanceService\RemittanceCorporateService\RemittanceCorporateServService;
use Iapps\RemittanceService\RemittanceCorporateService\RemittanceCorporateServServiceFactory;

class PartnerExchangeRateService extends ExchangeRateService{

    protected $corpId = NULL;

    public function setUpdatedBy($updatedBy)
    {
        $result = parent::setUpdatedBy($updatedBy);
        $this->_getAdminCorporateId();
        return $result;
    }

    public function getRateListing($remittance_config_id, array $statuses = array(), $limit = NULL, $page = NULL, array $channel = array(), $isArray = true)
    {
        return parent::getRateListing($remittance_config_id, $statuses, $limit, $page, $this->_getAccessibleChannel(), $isArray);
    }

    public function getRemittanceConfigWithRates(RemittanceConfig $filter, $limit = NULL, $page = NULL, array $inCorpServId = array(), array $outCorpServId = array())
    {
        $corpServ = RemittanceCorporateServServiceFactory::build();
        if( $corp_id = $this->_getAdminCorporateId() )
        {
            if( $corpServCol = $corpServ->findByServiceProviderIds(array($corp_id)) ) {
                foreach ($corpServCol AS $corpServ) {
                    if ($corpServ instanceof RemittanceCorporateService) {
                        if ($corpServ->getTransactionType()->getCode() == TransactionType::CODE_CASH_IN OR
                            $corpServ->getTransactionType()->getCode() == TransactionType::CODE_LOCAL_CASH_IN)
                            $inCorpServId[] = $corpServ->getId();
                        elseif ($corpServ->getTransactionType()->getCode() == TransactionType::CODE_CASH_OUT OR
                                $corpServ->getTransactionType()->getCode() == TransactionType::CODE_LOCAL_CASH_OUT)
                            $outCorpServId[] = $corpServ->getId();
                    }
                }

                if (count($inCorpServId) > 0 or count($outCorpServId) > 0)
                {
                    return parent::getRemittanceConfigWithRates($filter, $limit, $page, $inCorpServId, $outCorpServId);
                }
            }
        }

        $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_CONFIG_FAILED);
        return false;
    }

    public function getRemittanceConfigWithPendingRates($limit = NULL, $page = NULL, array $corpServId = array())
    {
        $corpServ = RemittanceCorporateServServiceFactory::build();
        if( $corp_id = $this->_getAdminCorporateId() )
        {
            if( $corpServCol = $corpServ->findByServiceProviderIds(array($corp_id)) )
            {
                return parent::getRemittanceConfigWithPendingRates($limit, $page, $corpServCol->getIds());
            }
        }

        $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_CONFIG_FAILED);
        return false;
    }

    protected function _validateRelationShip($corporateId)
    {
        if( $this->getUpdatedBy() )
        {
            $v = EmployerValidator::make($this->getUpdatedBy(), $corporateId);
            if( !$v->fails() )
            {
                return true;
            }
        }

        $this->setResponseCode(MessageCode::CODE_ADMIN_IS_NOT_ALLOWED_TO_EDIT);
        return false;
    }

    protected function _processPreviousRequest(RemittanceCorporateService $corpServ)
    {
        //if will failed if there is pending request by default
        $exchangeRateFilter = new ExchangeRate();
        $exchangeRateFilter->setCorporateServiceId($corpServ->getId());
        $exchangeRateFilter->setStatus(ExchangeRateStatus::PENDING);

        if( $info = $this->getRepository()->findByParam($exchangeRateFilter) )
        {
            //failed if there is partner panel request
            $rateCol = $info->result;

            if( $rateCol instanceof ExchangeRateCollection )
            {
                if( $rateCol->getByChannel(ChannelType::CODE_PARTNER_PANEL) )
                {//pending requests
                    $this->setResponseCode(MessageCode::CODE_PREVIOUS_RATE_REQUEST_ACTIVE);
                    return false;
                }
                else if( $rate = $rateCol->getByChannel(ChannelType::CODE_ADMIN_PANEL) )
                {//cancel the previous request
                    if( $this->cancelExchangeRate($rate) )
                        return true;
                    else
                        return false;
                }
            }

            $this->setResponseCode(MessageCode::CODE_GET_EXCHANGE_RATE_LIST_FAILED);
            return false;
        }

        //no pending request
        return true;
    }

    protected function _getAdminCorporateId()
    {
        if( !$this->corpId )
        {
            if( $this->getUpdatedBy() )
            {
                $accServ = PartnerAccountServiceFactory::build();
                if( $upline = $accServ->getAgentUplineStructure($this->getUpdatedBy()) ) {
                    $this->corpId = $upline->first_upline->getUser()->getId();
                    return $this->corpId;
                }
            }

            return false;
        }

        return $this->corpId;
    }

    protected function _getRelatedCorporateServiceIds(RemittanceConfig $reConfig)
    {
        if( $corpId = $this->_getAdminCorporateId() )
        {
            if( $corpServ = $reConfig->serviceProviderBelongsTo($corpId) )
                return array($corpServ->getId());
        }

        return false;
    }

    protected function _getAccessibleChannel()
    {
        return array(ChannelType::CODE_PARTNER_PANEL);
    }

    protected function _getFormattedDisplayRate(RemittanceConfig $config)
    {
        if( $this->corpId )
            return $config->getFormattedExchangeRateByCorporate($this->corpId);

        return NULL;
    }

    protected function _getDisplayRate(RemittanceConfig $config)
    {
        if( $this->corpId )
            return $config->getDisplayRateByCorporate($this->corpId);

        return NULL;
    }
}