<?php

namespace Iapps\RemittanceService\ExchangeRate;

use Iapps\Common\Core\IappsBaseEntityCollection;
use Iapps\Common\Core\IappsBaseService;
use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\Helper\CreatorNameExtractor;
use Iapps\Common\Helper\GuidGenerator;
use Iapps\Common\AuditLog\AuditLogAction;
use Iapps\Common\Microservice\AccountService\AccountService;
use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\Common\Microservice\AccountService\User;
use Iapps\RemittanceService\Common\ChannelType;
use Iapps\RemittanceService\Common\ChannelTypeValidator;
use Iapps\RemittanceService\Common\CorporateServServiceFactory;
use Iapps\RemittanceService\Common\MessageCode;
use Iapps\RemittanceService\RemittanceConfig\ConversionType;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfig;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfigCollection;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfigServiceFactory;
use Iapps\RemittanceService\RemittanceCorporateService\PartnerNameExtractor;
use Iapps\RemittanceService\RemittanceCorporateService\RemittanceCorporateService;

class ExchangeRateService extends IappsBaseService{

    protected $channel;

    public function setChannelCode($code)
    {
        if( $channel = ChannelTypeValidator::validate($code) )
        {
            $this->channel = $channel;
            return $this;
        }

        return false;
    }

    public function getChannel()
    {
        if( !$this->channel )
        {
            $this->setResponseCode(MessageCode::CODE_INVALID_CHANNEL);
            return false;
        }

        return $this->channel;
    }

    /*
     * This function to add both in/out rates together
     */
    public function addExchangeRates($remittanceConfigurationId, ExchangeRateCollection $exchangeRates)
    {
        //get remittance config
        $rcServ = RemittanceConfigServiceFactory::build();
        if( $reConfig = $rcServ->getRemittanceConfigById($remittanceConfigurationId) )
        {
            if( $reConfig instanceof RemittanceConfig )
            {
                if( $reConfig->isDirectExchange() )
                {
                    $rateProvider = $reConfig->getRateProvider();
                    $rateRider = $reConfig->getRateRider();

                    if( $providerRate = $exchangeRates->getByCorporateService($rateProvider) AND
                        $riderRate = $exchangeRates->getByCorporateService($rateRider) )
                    {
                        $this->getRepository()->startDBTransaction();

                        if( !$this->addExchangeRate($remittanceConfigurationId, $providerRate) )
                        {
                            $this->getRepository()->rollbackDBTransaction();
                            return false;
                        }

                        $riderRate->getRefExchangeRate()->setId($providerRate->getId());
                        if( !$this->addExchangeRate($remittanceConfigurationId, $riderRate) )
                        {
                            $this->getRepository()->rollbackDBTransaction();
                            return false;
                        }


                        $this->getRepository()->completeDBTransaction();
                        return true;
                    }
                    else
                    {
                        return false;
                    }
                }
                else if( $reConfig->isIntermediaryExchange() )
                {
                    if( $inRate = $exchangeRates->getByCorporateService($reConfig->getInCorporateService()) AND
                        $outRate = $exchangeRates->getByCorporateService($reConfig->getOutCorporateService()) )
                    {
                        $this->getRepository()->startDBTransaction();
                        if( !$this->addExchangeRate($remittanceConfigurationId, $inRate) )
                        {
                            $this->getRepository()->rollbackDBTransaction();
                            return false;
                        }

                        if( !$this->addExchangeRate($remittanceConfigurationId, $outRate) )
                        {
                            $this->getRepository()->rollbackDBTransaction();
                            return false;
                        }

                        $this->getRepository()->completeDBTransaction();
                        return true;
                    }
                    else
                    {
                        return false;
                    }
                }
            }
        }

        if( !$this->getResponseCode() )
            $this->setResponseCode(MessageCode::CODE_REMITTANCE_CONFIG_NOT_FOUND);
        return false;
    }

    public function addExchangeRate($remittanceConfigurationId, ExchangeRate $exchangeRate)
    {
        if( !$channel = $this->getChannel() )
            return false;

        //get remittance config
        $rcServ = RemittanceConfigServiceFactory::build();
        if( $reConfig = $rcServ->getRemittanceConfigById($remittanceConfigurationId) )
        {
            if( $reConfig instanceof RemittanceConfig )
            {
                if( $corpServ = $reConfig->rateBelongsTo($exchangeRate) )
                {
                    //validate if admin is employee of the corporate
                    if( !$this->_validateRelationShip($corpServ->getServiceProviderId()) )
                        return false;

                    //process previous request
                    if( $this->_processPreviousRequest($corpServ) )
                    {
                        $exchangeRate->setId(GuidGenerator::generate());
                        $exchangeRate->setIsActive(0);
                        $exchangeRate->setChannel($channel);
                        $exchangeRate->setCreatedBy($this->getUpdatedBy());

                        //reference to approved provider's rate if not given
                        if( $reConfig->isDirectExchange() )
                        {
                            if( $reConfig->isRider($corpServ) AND $exchangeRate->getRefExchangeRate()->getId() == NULL )
                                $exchangeRate->getRefExchangeRate()->setId($reConfig->getRateProvider()->getExchangeRateId());
                        }

                        $v = ExchangeRateValidator::make($exchangeRate, $corpServ);
                        if( !$v->fails() )
                        {
                            if($this->getRepository()->insert($exchangeRate))
                            {
                                $this->fireLogEvent('iafb_remittance.exchange_rate', AuditLogAction::CREATE, $exchangeRate->getId());

                                $this->setResponseCode(MessageCode::CODE_ADD_EXCHANGE_RATE_SUCCESS);
                                return true;
                            }
                        }

                        $this->setResponseCode($v->getErrorCode());
                    }
                }
            }
        }

        if( !$this->getResponseCode() )
            $this->setResponseCode(MessageCode::CODE_ADD_EXCHANGE_RATE_FAILED);
        return false;
    }

    public function cancelExchangeRate(ExchangeRate $rate)
    {
        $tobecancelled = array($rate);

        //cancel both pending parent and child if any
        //assumming only one pending parent/child
        if( $parentCol = $this->_getPendingParentExchangeRate($rate) )
        {
            $parentCol->rewind();
            $tobecancelled[] = $parentCol->current();
        }
        else if( $childCol = $this->_getPendingChildExchangeRate($rate) )
        {
            $childCol->rewind();
            $tobecancelled[] = $childCol->current();
        }

        $this->getRepository()->startDBTransaction();
        foreach($tobecancelled AS $rate)
        {
            if( $rate->getStatus() == ExchangeRateStatus::PENDING )
            {
                $ori_rate = clone($rate);

                $rate->setStatus(ExchangeRateStatus::AUTOCANCELLED);
                $rate->setIsActive(0);
                $rate->setUpdatedBy($this->getUpdatedBy());

                if( !$this->getRepository()->update($rate) )
                {
                    $this->getRepository()->rollbackDBTransaction();

                    $this->setResponseCode(MessageCode::CODE_EDIT_EXCHANGE_RATE_FAILED);
                    return false;
                }

                $this->fireLogEvent('iafb_remittance.exchange_rate', AuditLogAction::UPDATE, $rate->getId(), $ori_rate);
            }
        }

        $this->getRepository()->completeDBTransaction();
        $this->setResponseCode(MessageCode::CODE_EDIT_EXCHANGE_RATE_SUCCESS);
        return true;
    }

    protected function _getPendingParentExchangeRate(ExchangeRate $rate)
    {
        if( $rate->getRefExchangeRate()->getId() )
        {
            $exchangeRateFilter = new ExchangeRate();
            $exchangeRateFilter->setId($rate->getRefExchangeRate()->getId());
            $exchangeRateFilter->setStatus(ExchangeRateStatus::PENDING);

            if( $info = $this->getRepository()->findByParam($exchangeRateFilter) )
            {
                return $info->result;
            }
        }

        return false;
    }

    protected function _getPendingChildExchangeRate(ExchangeRate $rate)
    {
        if( $rate->getId() )
        {
            $exchangeRateFilter = new ExchangeRate();
            $exchangeRateFilter->getRefExchangeRate()->setId($rate->getId());
            $exchangeRateFilter->setStatus(ExchangeRateStatus::PENDING);

            if( $info = $this->getRepository()->findByParam($exchangeRateFilter) )
            {
                return $info->result;
            }
        }

        return false;
    }

    protected function _processPreviousRequest(RemittanceCorporateService $corpServ)
    {
        //if will failed if there is pending request by default
        $exchangeRateFilter = new ExchangeRate();
        $exchangeRateFilter->setCorporateServiceId($corpServ->getId());
        $exchangeRateFilter->setStatus(ExchangeRateStatus::PENDING);

        if( $result = $this->getRepository()->findByParam($exchangeRateFilter) )
        {
            //pending requests
            $this->setResponseCode(MessageCode::CODE_PREVIOUS_RATE_REQUEST_ACTIVE);
            return false;
        }

        //no pending request
        return true;
    }

    public function getEditableRates($remittance_config_id)
    {
        //get remittance config
        $rcServ = RemittanceConfigServiceFactory::build();
        if( $reConfig = $rcServ->getRemittanceConfigById($remittance_config_id) )
        {
            if( $reConfig instanceof RemittanceConfig )
            {
                if( $corporateIds = $this->_getRelatedCorporateServiceIds($reConfig) )
                {
                    $result['conversion_type'] = $reConfig->getConversionType();
                    $result['channel_info'] = $reConfig->getSelectedField(
                        array('channel_id', 'from_country_currency_code', 'to_country_currency_code',
                            'from_country_partner_name', 'to_country_partner_name'));
                    $result['channel_info']['display_rate'] = $this->_getDisplayRate($reConfig);
                    $result['channel_info']['formatted_display_rate'] = $this->_getFormattedDisplayRate($reConfig);

                    $editableRates = array();

                    $approved_exchangeRateId = null;
                    if( in_array($reConfig->getInCorporateService()->getId(), $corporateIds ) )
                    {
                        $inRate = $reConfig->getInCorporateService()->getSelectedField(array('id', 'rate_type','service_provider_name'));
                        $inRate['currencies'] = $reConfig->getInCorporateService()->getConversionRemittanceService()->getSelectedField(array('from_country_currency_code','to_country_currency_code'));
                        $inRate['approved_rate'] = $reConfig->getInCorporateService()->getSelectedField(array('margin'));
                        $inRate['approved_rate']['exchange_rate'] = $reConfig->getInSellingPrice();

                        if( !$approved_exchangeRateId )
                            $approved_exchangeRateId = $reConfig->getInCorporateService()->getExchangeRateId();

                        $editableRates[] = $inRate;
                    }

                    if( in_array($reConfig->getOutCorporateService()->getId(), $corporateIds ) )
                    {
                        $outRate = $reConfig->getOutCorporateService()->getSelectedField(array('id', 'rate_type', 'service_provider_name'));
                        $outRate['currencies'] = $reConfig->getOutCorporateService()->getConversionRemittanceService()->getSelectedField(array('from_country_currency_code','to_country_currency_code'));
                        $outRate['approved_rate'] = $reConfig->getOutCorporateService()->getSelectedField(array('margin'));
                        $outRate['approved_rate']['exchange_rate'] = $reConfig->getOutSellingPrice();

                        if( !$approved_exchangeRateId )
                        {
                            $approved_exchangeRateId = $reConfig->getOutCorporateService()->getExchangeRateId();
                        }
                        else if( $reConfig->getConversionType() == ConversionType::DIRECT )
                        {
                            $approved_exchangeRateId = $reConfig->getRateProvider()->getExchangeRateId();
                        }

                        $editableRates[] = $outRate;
                    }

                    if( !$approved_exchangeRateId )
                    {
                        $result['status_info'] = array('status' => 'new',
                                                       'created_by_name' => null,
                                                       'created_at' => null,
                                                       'approve_reject_at' => null,
                                                       'approve_reject_by' => null,
                                                       'approve_reject_by_name' => null);
                    }
                    elseif( $approved_exchangeRateInfo = $this->getRepository()->findById($approved_exchangeRateId) )
                    {
                        $col = new ExchangeRateCollection();
                        $col->addData($approved_exchangeRateInfo);
                        ExchangeRateNameExtractor::extract($col);
                        $col->rewind();
                        $result['status_info'] = $col->current()->getSelectedField(array('status', 'created_by_name', 'created_at', 'approve_reject_at', 'approve_reject_by', 'approve_reject_by_name'));
                    }

                    //sort editable rates
                    usort($editableRates, array($this, "cmp"));

                    //$editableRates
                    $result['editable_rates'] = $editableRates;


                    $this->setResponseCode(MessageCode::CODE_GET_EXCHANGE_RATE_LIST_SUCCESS);
                    return $result;
                }
            }
        }

        $this->setResponseCode(MessageCode::CODE_GET_EXCHANGE_RATE_LIST_FAILED);
        return false;
    }


    public function getRateListing($remittance_config_id, array $statuses = array(), $limit = NULL, $page = NULL, array $channel = array(), $isArray = true)
    {
        //get remittance config
        $rcServ = RemittanceConfigServiceFactory::build();
        if( $reConfig = $rcServ->getRemittanceConfigById($remittance_config_id) )
        {
            if( $corpIds = $this->_getRelatedCorporateServiceIds($reConfig) )
            {
                if( $info = $this->getRepository()->findByCorpServIdsAndStatuses($corpIds, $statuses, $channel, $limit, $page) )
                {
                    $col = $info->result;
                    $this->_getReferenceExchangeRate($col);

                    if( $col instanceof ExchangeRateCollection )
                    {
                        $this->setResponseCode(MessageCode::CODE_GET_EXCHANGE_RATE_LIST_SUCCESS);
                        if( $isArray )
                        {
                            ExchangeRateNameExtractor::extract($col);
                            PartnerNameExtractor::extract($reConfig->getCorporateServiceCollection());
                            $col->extractUpdatedByCorporate();

                            $info->result = $col->toListingFormat($reConfig);
                            return $info;
                        }
                        else
                            return $col;
                    }
                }
            }
        }

        $this->setResponseCode(MessageCode::CODE_GET_EXCHANGE_RATE_LIST_FAILED);
        return false;
    }

    public function getPendingApprovalRate($remittance_config_id)
    {
        if( !$channel = $this->getChannel() )
            return false;

        //get remittance config
        $rcServ = RemittanceConfigServiceFactory::build();
        if( $reConfig = $rcServ->getRemittanceConfigById($remittance_config_id) )
        {
            if( $reConfig instanceof RemittanceConfig )
            {
                $statuses = array(ExchangeRateStatus::PENDING);
                if( $rateCol = $this->getRateListing($remittance_config_id, $statuses, NULL, NULL, array($channel->getCode()), false) )
                {
                    $rateInfo = null;
                    foreach($rateCol AS $rate)
                    {
                        $corpServ = $reConfig->rateBelongsTo($rate);
                        $corpServ->setExchangeRate($rate->getExchangeRate());
                        $corpServ->setMargin($rate->getMargin());
                        if( $rateInfo == NULL )
                            $rateInfo = $rate;
                    }

                    $result['channel_info'] = $reConfig->getSelectedField(
                        array('channel_id', 'from_country_currency_code', 'to_country_currency_code',
                            'from_country_partner_name', 'to_country_partner_name'));

                    $result['channel_info']['display_rate'] = $this->_getDisplayRate($reConfig);
                    $result['channel_info']['formatted_display_rate'] = $this->_getFormattedDisplayRate($reConfig);

                    //$result['est_exchange_rate'] = $reConfig->getDisplayRate();
                    CreatorNameExtractor::extract($rateCol);
                    PartnerNameExtractor::extract($reConfig->getCorporateServiceCollection());
                    $result['pending_rates'] = $rateCol->toListingFormat($reConfig);

                    $result['status_info'] = NULL;
                    if( $rateInfo )
                        $result['status_info'] = $rateInfo->getSelectedField(array('status', 'created_by_name', 'created_at', 'approve_reject_at', 'approve_reject_by', 'approve_reject_by_name'));

                    return $result;
                }
            }
        }

        $this->setResponseCode(MessageCode::CODE_GET_EXCHANGE_RATE_LIST_FAILED);
        return false;
    }

    public function getRemittanceConfigWithRates(RemittanceConfig $filter, $limit = NULL, $page = NULL, array $inCorpServId = array(), array $outCorpServId = array())
    {
        if( !$channel = $this->getChannel() )
            return false;

        $rcServ = RemittanceConfigServiceFactory::build();
        if( $info = $rcServ->findByCorporateServiceIds($inCorpServId, $outCorpServId, $filter, $limit, $page) )
        {
            $reCol = $info->result;

            if( $reCol instanceof RemittanceConfigCollection )
            {
                if( $ids = $reCol->getApprovedExchangeRateIds() )
                {
                    if( $rateInfo = $this->getRepository()->findByIds($ids) )
                    {
                        foreach($reCol AS $re)
                        {
                            if( $re instanceof RemittanceConfig )
                            {
                                $re->getCorporateServiceCollection()->joinExchangeRate($rateInfo->result);
                            }
                        }
                    }
                }

                $result = array();
                foreach ($reCol AS $config )
                {
                    $temp = $config->jsonSerialize();
                    $temp['rate_last_update'] = $config->getLastRateUpdatedAt();
                    $temp['display_rate'] = $this->_getDisplayRate($config);
                    $temp['formatted_display_rate'] = $this->_getFormattedDisplayRate($config);

                    $result[] = $temp;
                }

                $info->result = $result;
                $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_CONFIG_SUCCESS);
                return $info;
            }
        }

        $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_CONFIG_FAILED);
        return false;
    }

    public function getRemittanceConfigWithPendingRates($limit = NULL, $page = NULL, array $corpServId = array())
    {
        if( !$channel = $this->getChannel() )
            return false;

        //get pending request
        $status = array(ExchangeRateStatus::PENDING);
        $channel = array($channel->getCode());

        //cannot use normal way of pagination
        if( $rateInfo = $this->getRepository()->findByCorpServIdsAndStatuses($corpServId, $status, $channel) )
        {
            $pendingRatesCol = $rateInfo->result;
            //get remittance config
            $rcServ = RemittanceConfigServiceFactory::build();
            if( $info = $rcServ->getAllRemittanceConfig(MAX_VALUE, 1) )
            {
                $reCol = $info->result;
                if( $reCol instanceof RemittanceConfigCollection AND
                    $pendingRatesCol instanceof ExchangeRateCollection )
                {
                    $collection = $pendingRatesCol->joinRemittanceConfig($reCol);
                    $paginatedResult = $collection->pagination($limit, $page);

                    $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_CONFIG_SUCCESS);

                    $result = new \stdClass();

                    $formattedArray = array();
                    foreach($paginatedResult->getResult() AS $reConfig)
                    {
                        $temp = $reConfig->getSelectedField(array('id', 'channel_id', 'created_at', 'updated_at', 'created_by_name', 'remittance_service', 'status', 'display_rate', 'from_country_currency_code', 'to_country_currency_code'));
                        $temp['from_partner'] = $reConfig->getInCorporateService()->getServiceProviderName();
                        $temp['to_partner'] = $reConfig->getOutCorporateService()->getServiceProviderName();
                        $temp['rate_last_update'] = $reConfig->getLastRateUpdatedAt();
                        $temp['display_rate'] = $this->_getDisplayRate($reConfig);
                        $temp['formatted_display_rate'] = $this->_getFormattedDisplayRate($reConfig);

                        $formattedArray[] = $temp;
                    }

                    $result->result = $formattedArray;
                    $result->total = $paginatedResult->getTotal();
                    return $result;
                }
            }
        }

        $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_CONFIG_FAILED);
        return false;
    }

    /*
     * approve of reject exchange rate in pair
     */
    public function updateExchangeRatesStatus($remittance_config_id, array $exchange_rate_ids, $status, $remark)
    {
        $rcServ = RemittanceConfigServiceFactory::build();
        if( $reConfig = $rcServ->getRemittanceConfigById($remittance_config_id) ) {
            if ($reConfig instanceof RemittanceConfig) {
                if ($info = $this->getRepository()->findByIds($exchange_rate_ids)) {
                    $rateCol = $info->result;

                    $v = ExchangeRatePairValidator::make($rateCol, $reConfig);
                    if (!$v->fails()) {

                        $this->getRepository()->startDBTransaction();
                        //process first rate
                        if( !$this->updateExchangeRateStatus($reConfig->getId(), $v->getFirstRate()->getId(), $status, $remark))
                        {
                            $this->getRepository()->rollbackDBTransaction();
                            return false;
                        }

                        //process second rate
                        if( !$this->updateExchangeRateStatus($reConfig->getId(), $v->getSecondRate()->getId(), $status, $remark))
                        {
                            $this->getRepository()->rollbackDBTransaction();
                            return false;
                        }

                        $this->getRepository()->completeDBTransaction();
                        return true;
                    }
                }
            }
        }

        if( !$this->getResponseCode() )
            $this->setResponseCode(MessageCode::CODE_RATE_STATUS_UPDATE_FAILED);
        return false;
    }

    public function updateExchangeRateStatus($remittance_config_id, $exchange_rate_id, $status, $remark)
    {
        $admin = new User();
        $admin->setId($this->getUpdatedBy());

        if( !$channel = $this->getChannel() )
            return false;

        $rcServ = RemittanceConfigServiceFactory::build();
        if( $reConfig = $rcServ->getRemittanceConfigById($remittance_config_id) )
        {
            if( $reConfig instanceof RemittanceConfig )
            {
                if( $rate = $this->getRepository()->findById($exchange_rate_id) )
                {
                    if( $corpServ = $reConfig->rateBelongsTo($rate) )
                    {
                        if( !$this->_isAdminAccessible($reConfig, $corpServ) )
                            return false;

                        //only can approve/reject from the same channel
                        if( $rate->getChannel()->getCode() !== $channel->getCode() )
                        {
                            $this->setResponseCode(MessageCode::CODE_INVALID_CHANNEL);
                            return false;
                        }

                        $this->getRepository()->startDBTransaction();
                        if( $rate->approveRejectRate($status, $remark, $admin) )
                        {
                            $rate->setUpdatedBy($this->getUpdatedBy());

                            if( $status == ExchangeRateStatus::APPROVED )
                            {
                                if( !$this->_updateApprovedRate($reConfig, $rate) )
                                {
                                    $this->getRepository()->rollbackDBTransaction();
                                    $this->setResponseCode(MessageCode::CODE_RATE_STATUS_UPDATE_FAILED);
                                    return false;
                                }
                            }

                            if( $this->getRepository()->update($rate) )
                            {
                                $this->getRepository()->completeDBTransaction();
                                $this->setResponseCode(MessageCode::CODE_RATE_STATUS_UPDATE_SUCCESS);
                                return true;
                            }
                        }
                        $this->getRepository()->rollbackDBTransaction();
                    }
                }
            }
        }

        if( !$this->getResponseCode() )
            $this->setResponseCode(MessageCode::CODE_RATE_STATUS_UPDATE_FAILED);
        return false;
    }

    public function getTrendData($remittance_config_id, IappsDateTime $from_date, IappsDateTime $to_date)
    {
        $rcServ = RemittanceConfigServiceFactory::build();
        if( $reConfig = $rcServ->getRemittanceConfigById($remittance_config_id) )
        {
            $corpIds = array($reConfig->getInCorporateService()->getId(),
                             $reConfig->getOutCorporateService()->getId());
            $statuses = array(ExchangeRateStatus::APPROVED);

            if( $info = $this->getRepository()->findByCorpServIdsAndStatuses($corpIds, $statuses, array(), NULL, NULL, $from_date, $to_date) )
            {
                $col = $info->result;

                if( $col instanceof ExchangeRateCollection )
                {
                    $data = $col->getTrendData();

                    $this->setResponseCode(MessageCode::CODE_GET_EXCHANGE_RATE_LIST_SUCCESS);
                    return $data;
                }
            }
        }

        $this->setResponseCode(MessageCode::CODE_GET_EXCHANGE_RATE_LIST_FAILED);
        return false;
    }
    
    public function findExchangeRateById($exchange_rate_id)
    {
        if( $rate = $this->getRepository()->findById($exchange_rate_id) )
        {
            $this->setResponseCode(MessageCode::CODE_GET_EXCHANGE_RATE_SUCCESS);
            return $rate;
        }
        $this->setResponseCode(MessageCode::CODE_GET_EXCHANGE_RATE_FAILED);
        return false;
    }

    protected function _updateApprovedRate(RemittanceConfig $reConfig, ExchangeRate $approvedRate)
    {
        if( !$channel = $this->getChannel() )
            return false;

        if( $corpServ = $reConfig->rateBelongsTo($approvedRate) )
        {
            $this->getRepository()->startDBTransaction();
            if( $reConfig->isDirectExchange() ) {
                if ($reConfig->isProvider($corpServ)) {//if it is rate provider
                    //update pending ref rate that is tagged to the previous approved rate

                    if( $corpServ->getExchangeRateId() )
                    {//do this only there was previous approved rate
                        $exchangeRateFilter = new ExchangeRate();
                        $exchangeRateFilter->getRefExchangeRate()->setId($corpServ->getExchangeRateId());
                        $exchangeRateFilter->setStatus(ExchangeRateStatus::PENDING);

                        if ($info = $this->getRepository()->findByParam($exchangeRateFilter)) {
                            $affectedRate = $info->result->current();
                            $affectedRate->getRefExchangeRate()->setId($approvedRate->getId());
                            $affectedRate->setUpdatedBy($this->getUpdatedBy());
                            if (!$this->getRepository()->update($affectedRate)) {
                                $this->getRepository()->rollbackDBTransaction();
                                return false;
                            }
                        }
                    }

                    // no rider's approved rate with reference to the new provider's approved rate,
                    // then add a new record to reflect the new price
                    $exchangeRateFilter = new ExchangeRate();
                    $exchangeRateFilter->getRefExchangeRate()->setId($approvedRate->getId());
                    $exchangeRateFilter->setStatus(ExchangeRateStatus::APPROVED);
                    if (!$info = $this->getRepository()->findByParam($exchangeRateFilter)) {
                        if ($rider = $reConfig->getRateRider()) {
                            $autoRate = new ExchangeRate();
                            $autoRate->setId(GuidGenerator::generate());
                            $autoRate->setCorporateServiceId($rider->getId());
                            $autoRate->setStatus(ExchangeRateStatus::AUTOAPPROVED);
                            $autoRate->setMargin($rider->getMargin());
                            $autoRate->setCreatedBy($this->getUpdatedBy());
                            $autoRate->getRefExchangeRate()->setId($approvedRate->getId());
                            $autoRate->setChannel($channel);
                            $autoRate->setIsActive(1);
                            $autoRate->setApproveRejectBy($this->getUpdatedBy());
                            $autoRate->setApproveRejectAt(IappsDateTime::now());

                            $v = ExchangeRateValidator::make($autoRate, $rider);
                            if ($v->fails()) {
                                $this->getRepository()->rollbackDBTransaction();
                                return false;
                            }

                            if (!$this->getRepository()->insert($autoRate)) {
                                $this->getRepository()->rollbackDBTransaction();
                                return false;
                            }

                            if (!$this->_updateApprovedRate($reConfig, $autoRate)) {
                                $this->getRepository()->rollbackDBTransaction();
                                return false;
                            }
                        }
                    }
                }
            }

            //inactive previous approved rate, if there were an approved rate
            if( $corpServ->getExchangeRateId() )
            {
                if( !$this->_inactivateRate($corpServ->getExchangeRateId()) )
                {
                    $this->getRepository()->rollbackDBTransaction();
                    return false;
                }
            }

            //update corporate service with newly approved rate
            $serv = CorporateServServiceFactory::build();
            $serv->setUpdatedBy($this->getUpdatedBy());
            $serv->setIpAddress($this->getIpAddress());

            if( $serv->updateCorporateServiceApprovedRate($corpServ->getId(), $approvedRate) )
            {
                $approvedRate->setApproveRate($reConfig->getDisplayRate());
                $this->getRepository()->completeDBTransaction();
                return true;
            }

            $this->getRepository()->rollbackDBTransaction();
            return false;
        }

        $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_CONFIG_FAILED);
        return false;
    }

    protected function _inactivateRate($exchange_rate_id)
    {
        if( $rate = $this->getRepository()->findById($exchange_rate_id) )
        {
            $rate->setIsActive(0);
            $rate->setUpdatedBy($this->getUpdatedBy());

            if( $this->getRepository()->update($rate) )
                return true;
        }

        return false;
    }

    protected function _validateRelationShip($corporate_id)
    {//for admin, not validation required
        return true;
    }

    protected function _getRelatedCorporateServiceIds(RemittanceConfig $reConfig)
    {
        return array($reConfig->getInCorporateService()->getId(),
                     $reConfig->getOutCorporateService()->getId());
    }

    protected function _isAdminAccessible(RemittanceConfig $reConfig, RemittanceCorporateService $corpServ)
    {
        if( in_array($corpServ->getId(), $this->_getRelatedCorporateServiceIds($reConfig) ) )
            return true;

        $this->setResponseCode(MessageCode::CODE_ADMIN_IS_NOT_ALLOWED_TO_EDIT);
        return false;
    }

    protected function _getReferenceExchangeRate(ExchangeRateCollection $collection)
    {
        if( $refIds = $collection->getRefExchangeRateIds() )
        {
            if( $info = $this->getRepository()->findByIds($refIds) )
            {
                $collection->joinRefExchangeRate($info->result);
            }
        }

        return $collection;
    }

    protected function _getAccessibleChannel()
    {
        return array(ChannelType::CODE_ADMIN_PANEL, ChannelType::CODE_PARTNER_PANEL);
    }

    protected function _getDisplayRate(RemittanceConfig $config)
    {
        return $config->getDisplayRate();
    }

    protected function _getFormattedDisplayRate(RemittanceConfig $config)
    {
        return $config->getFormattedExchangeRate();
    }

    function cmp($a, $b)
    {
        if ($a['rate_type'] == 'rate_provider')
            return -1;

        if( $b['rate_type'] == 'rate_provider')
            return 1;

        return 0;
    }

    public function getBuyingRateByExchangeRateId($id)
    {
        if($info = $this->getRepository()->findByIds(array($id)) ){
            $col = $info->result;
            $this->_getReferenceExchangeRate($col);
            $exchangeRate = 0;
            $margin = 0;
            if( $col instanceof ExchangeRateCollection )
            {
                foreach($col as $colEach){
                    $exchangeRate = $colEach->getExchangeRate();
                    $margin = $colEach->getMargin();
                    $exchangeRate += $colEach->getRefExchangeRate()->getExchangeRate();
                    $margin += $colEach->getRefExchangeRate()->getMargin();
                }

                return ($exchangeRate - $margin);
            }
        }
        return 0;

    }
}

