<?php

namespace Iapps\RemittanceService\RemittanceConfig;

use Iapps\Common\Core\EncryptedField;
use Iapps\Common\Core\IappsBaseEntity;
use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\CorporateService\CorporateService;
use Iapps\RemittanceService\ExchangeRate\ExchangeRate;
use Iapps\RemittanceService\ExchangeRate\RateType;
use Iapps\RemittanceService\RemittanceCompany\RemittanceCompany;
use Iapps\RemittanceService\RemittanceCorporateService\RemittanceCorporateService;
use Iapps\RemittanceService\RemittanceCorporateService\RemittanceCorporateServiceCollection;
use Iapps\RemittanceService\RemittanceServiceConfig\RemittanceServiceConfig;
use Iapps\RemittanceService\ValueObject\EncryptedFieldFactory;

class RemittanceConfig extends IappsBaseEntity{
    protected $min_limit;
    protected $max_limit;
    protected $step_amount;
    protected $is_default;
    protected $approving_notification_emails;
    protected $channelID;
    protected $status = RemittanceConfigStatus::PENDING;
    protected $approve_reject_remark;
    protected $approve_reject_at;
    protected $approve_reject_by;
    protected $is_active;

    protected $require_face_to_face_trans;
    protected $require_face_to_face_recipient;
    protected $home_collection_enabled;
    protected $cashin_expiry_period;

    protected $intermediary_currency;
    protected $ratesSetter;

    protected $in_corporate_service;
    protected $out_corporate_service;
    protected $remittance_service;

    protected $from_country_partner_name;
    protected $to_country_partner_name;
    protected $cashin_profit_sharing_by_corp_ser_id;
    protected $cashout_profit_sharing_by_corp_ser_id;
    
    protected $approve_reject_by_name;
    protected $last_pricing_approve_at;
    protected $remittanceCompany;

    function __construct()
    {
        parent::__construct();

        $this->in_corporate_service = new RemittanceCorporateService();
        $this->out_corporate_service = new RemittanceCorporateService();
        $this->remittance_service = new RemittanceServiceConfig();
        $this->approving_notification_emails = EncryptedFieldFactory::build();
        $this->remittanceCompany = new RemittanceCompany();
    }
    
    public function getCashInCorporateService()
    {
        return $this->in_corporate_service;
    }
    
    public function getCashOutCorporateService()
    {
        return $this->out_corporate_service;
    }

    /**
     * @return mixed
     */
    public function getCashInCorporateServiceId()
    {
        return $this->getInCorporateService()->getId();
    }

    /**
     * @param mixed $cash_in_corporate_service_id
     */
    public function setCashInCorporateServiceId($cash_in_corporate_service_id)
    {
        $this->getInCorporateService()->setId($cash_in_corporate_service_id);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCashOutCorporateServiceId()
    {
        return $this->getOutCorporateService()->getId();
    }

    /**
     * @param mixed $cash_out_corporate_service_id
     */
    public function setCashOutCorporateServiceId($cash_out_corporate_service_id)
    {
        $this->getOutCorporateService()->setId($cash_out_corporate_service_id);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRemittanceServiceId()
    {
        return $this->getRemittanceService()->getId();
    }

    /**
     * @param mixed $remittance_service_id
     */
    public function setRemittanceServiceId($remittance_service_id)
    {
        $this->getRemittanceService()->setId($remittance_service_id);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMinLimit()
    {
        return $this->min_limit;
    }

    /**
     * @param mixed $min_limit
     */
    public function setMinLimit($min_limit)
    {
        $this->min_limit = $min_limit;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMaxLimit()
    {
        return $this->max_limit;
    }

    /**
     * @param mixed $max_limit
     */
    public function setMaxLimit($max_limit)
    {
        $this->max_limit = $max_limit;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getStepAmount()
    {
        return $this->step_amount;
    }

    /**
     * @param mixed $step_amount
     */
    public function setStepAmount($step_amount)
    {
        $this->step_amount = $step_amount;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIsDefault()
    {
        return $this->is_default;
    }

    /**
     * @param mixed $is_default
     */
    public function setIsDefault($is_default)
    {
        $this->is_default = $is_default;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIsActive()
    {
        return $this->is_active;
    }

    /**
     * @param mixed $is_active
     */
    public function setIsActive($is_active)
    {
        $this->is_active = $is_active;
        return $this;
    }

    public function getRequireFaceToFaceTrans()
    {//make it compatible
        if( $this->getRemittanceService()->isInternational() )
            return $this->getRemittanceCompany()->getRequiredFaceToFaceTrans();
        
        return false;
    }

    //obsolete
    public function setRequireFaceToFaceTrans($require_face_to_face_trans)
    {
        $this->require_face_to_face_trans = $require_face_to_face_trans;
        return $this;
    }

    public function getRequireFaceToFaceRecipient()
    {//make it compatible
        if( $this->getRemittanceService()->isInternational() )
            return $this->getRemittanceCompany()->getRequiredFaceToFaceRecipient();
        
        return false;
    }

    //obsolete
    public function setRequireFaceToFaceRecipient($require_face_to_face_recipient)
    {
        $this->require_face_to_face_recipient = $require_face_to_face_recipient;
        return $this;
    }

    public function getHomeCollectionEnabled()
    {
        return $this->home_collection_enabled;
    }

    public function setHomeCollectionEnabled($home_collection_enabled)
    {
        $this->home_collection_enabled = $home_collection_enabled;
        return $this;
    }

    public function getCashinExpiryPeriod()
    {
        return $this->cashin_expiry_period;
    }

    public function setCashinExpiryPeriod($cashin_expiry_period)
    {
        $this->cashin_expiry_period = $cashin_expiry_period;
        return $this;
    }

    public function setApprovingNotificationEmails(EncryptedField $field)
    {
        $this->approving_notification_emails = $field;
        return $this;
    }

    public function getApprovingNotificationEmails()
    {
        return $this->approving_notification_emails;
    }

    public function getApprovingNotificationEmailsArray()
    {
        if($this->approving_notification_emails->getValue())
        {
            $result = array();

            if($emails = json_decode($this->getApprovingNotificationEmails()->getValue(), true) )
            {
                foreach($emails AS $email)
                {
                    $result[] = $email['email'];
                }
            }

            return $result;
        }
        else
            return array();
    }

    public function addApprovingNotificationEmail($email, $user_id = NULL)
    {
        $emails = json_decode($this->getApprovingNotificationEmails()->getValue(), true);

        if( $user_id )
        {
            $this->removeApprovingNotificationEmailByUserId($user_id);
        }

        if( !in_array($email, $this->getApprovingNotificationEmailsArray()) )
        {
            $emails[] = array("user_id" => $user_id, "email" => $email);
            $this->getApprovingNotificationEmails()->setValue(json_encode($emails));
            return $emails;
        }

        return false;
    }

    public function removeApprovingNotificationEmail($email)
    {
        if( $emails = json_decode($this->getApprovingNotificationEmails()->getValue(), true) )
        {
            foreach($emails AS $key => $value)
            {
                if( $value['email'] == $email )
                {
                    unset($emails[$key]);

                    $this->getApprovingNotificationEmails()->setValue(json_encode($emails));
                    return $emails;
                }
            }
        }

        return false;
    }

    public function removeApprovingNotificationEmailByUserId($user_id)
    {
        if( $emails = json_decode($this->getApprovingNotificationEmails()->getValue(), true) )
        {
            foreach($emails AS $key => $value)
            {
                if( $value['user_id'] == $user_id )
                {
                    unset($emails[$key]);

                    $this->getApprovingNotificationEmails()->setValue(json_encode($emails));
                    return $emails;
                }
            }
        }

        return false;
    }

    /**
     * @return mixed
     */
    public function getChannelID()
    {
        return $this->channelID;
    }

    /**
     * @param mixed $channelID
     */
    public function setChannelID($channelID)
    {
        $this->channelID = $channelID;
        return $this;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getApproveRejectRemark()
    {
        return $this->approve_reject_remark;
    }

    /**
     * @param mixed $approve_reject_remark
     */
    public function setApproveRejectRemark($approve_reject_remark)
    {
        $this->approve_reject_remark = $approve_reject_remark;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getApproveRejectAt()
    {
        return $this->approve_reject_at;
    }

    /**
     * @param mixed $approve_reject_at
     */
    public function setApproveRejectAt(IappsDateTime $approve_reject_at)
    {
        $this->approve_reject_at = $approve_reject_at;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getApproveRejectBy()
    {
        return $this->approve_reject_by;
    }

    /**
     * @param mixed $approve_reject_by
     */
    public function setApproveRejectBy($approve_reject_by)
    {
        $this->approve_reject_by = $approve_reject_by;
        return $this;
    }
    
    public function setApproveRejectByName($approve_reject_by_name)
    {
        $this->approve_reject_by_name = $approve_reject_by_name;
        return $this;
    }
    
    public function getApproveRejectByName()
    {
        return $this->approve_reject_by_name;
    }

    /**
     * @return mixed
     */
    public function getFromCountryCurrencyCode()
    {
        return $this->getRemittanceService()->getFromCountryCurrencyCode();
    }

    /**
     * @param mixed $from_country_currency_code
     */
    public function setFromCountryCurrencyCode($from_country_currency_code)
    {
        $this->getRemittanceService()->setFromCountryCurrencyCode($from_country_currency_code);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getToCountryCurrencyCode()
    {
        return $this->getRemittanceService()->getToCountryCurrencyCode();
    }

    /**
     * @param mixed $to_country_currency_code
     */
    public function setToCountryCurrencyCode($to_country_currency_code)
    {
        $this->getRemittanceService()->setToCountryCurrencyCode($to_country_currency_code);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getFromCountryPartnerId()
    {
        return $this->getInCorporateService()->getServiceProviderId();
    }

    /**
     * @param mixed $from_country_partner_id
     */
    public function setFromCountryPartnerId($from_country_partner_id)
    {
        $this->getInCorporateService()->setServiceProviderId($from_country_partner_id);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getToCountryPartnerId()
    {
        return $this->getOutCorporateService()->getServiceProviderId();
    }

    /**
     * @param mixed $to_country_partner_id
     */
    public function setToCountryPartnerId($to_country_partner_id)
    {
        $this->getOutCorporateService()->setServiceProviderId($to_country_partner_id);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIntermediaryCurrency()
    {
        return $this->intermediary_currency;
    }

    /**
     * @param mixed $intermediary_currency
     */
    public function setIntermediaryCurrency($intermediary_currency)
    {
        $this->intermediary_currency = $intermediary_currency;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRatesSetter()
    {
        return $this->ratesSetter;
    }

    /**
     * @param mixed $ratesSetter
     */
    public function setRatesSetter($ratesSetter)
    {
        $this->ratesSetter = $ratesSetter;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getToConversionRemittanceServiceId()
    {
        return $this->getOutCorporateService()->getConversionRemittanceService()->getId();
    }

    /**
     * @param mixed $to_conversion_remittance_service_id
     */
    public function setToConversionRemittanceServiceId($to_conversion_remittance_service_id)
    {
        $this->getOutCorporateService()->getConversionRemittanceService()->setId($to_conversion_remittance_service_id);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getFromConversionRemittanceServiceId()
    {
        return $this->getInCorporateService()->getConversionRemittanceService()->getId();
    }

    /**
     * @param mixed $from_conversion_remittance_service_id
     */
    public function setFromConversionRemittanceServiceId($from_conversion_remittance_service_id)
    {
        $this->getInCorporateService()->getConversionRemittanceService()->setId($from_conversion_remittance_service_id);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getFromCountryPartnerName()
    {
        return $this->from_country_partner_name;
    }

    /**
     * @param mixed $from_country_partner_name
     */
    public function setFromCountryPartnerName($from_country_partner_name)
    {
        $this->from_country_partner_name = $from_country_partner_name;
    }

    /**
     * @return mixed
     */
    public function getToCountryPartnerName()
    {
        return $this->to_country_partner_name;
    }

    /**
     * @param mixed $to_country_partner_name
     */
    public function setToCountryPartnerName($to_country_partner_name)
    {
        $this->to_country_partner_name = $to_country_partner_name;
    }

    /**
     * @return mixed
     */
    public function getCashInProfitSharingByCorpSerId()
    {
        return $this->cashin_profit_sharing_by_corp_ser_id;
    }

    public function getCashOutProfitSharingByCorpSerId()
    {
        return $this->cashout_profit_sharing_by_corp_ser_id;
    }

    /**
     * @param mixed $profit_sharing_by_corp_ser_id
     */
    public function setCashInProfitSharingByCorpSerId($cashin_profit_sharing_by_corp_ser_id)
    {
        $this->cashin_profit_sharing_by_corp_ser_id = $cashin_profit_sharing_by_corp_ser_id;
    }

    public function setCashOutProfitSharingByCorpSerId($cashout_profit_sharing_by_corp_ser_id)
    {
        $this->cashout_profit_sharing_by_corp_ser_id = $cashout_profit_sharing_by_corp_ser_id;
    }

    public function setLastPricingApproveAt(IappsDateTime $last_pricing_approve_at)
    {
        $this->last_pricing_approve_at = $last_pricing_approve_at;
        return $this;
    }
    
    public function getLastPricingApproveAt()
    {
        return $this->last_pricing_approve_at;
    }

    public function setRemittanceCompany(RemittanceCompany $remittanceCompany)
    {
        $this->remittanceCompany = $remittanceCompany;
        return $this;
    }

    public function getRemittanceCompany()
    {
        return $this->remittanceCompany;
    }

    public function jsonSerialize()
    {
        $json = parent::jsonSerialize();

        $json['remittance_service_id']         = $this->getRemittanceServiceId();
        $json['cashin_corporate_service_id']   = $this->getCashinCorporateServiceId();
        $json['cashout_corporate_service_id']  = $this->getCashoutCorporateServiceId();
        $json['from_country_currency_code']    = $this->getFromCountryCurrencyCode();
        $json['to_country_currency_code']      = $this->getToCountryCurrencyCode();
        $json['from_country_partner_id']       = $this->getFromCountryPartnerId();
        $json['to_country_partner_id']         = $this->getToCountryPartnerId();
        $json['from_country_partner_name']       = $this->getFromCountryPartnerName();
        $json['to_country_partner_name']         = $this->getToCountryPartnerName();

        $json['to_conversion_remittance_service_id']   = $this->getToConversionRemittanceServiceId();
        $json['from_conversion_remittance_service_id'] = $this->getFromConversionRemittanceServiceId();
        $json['intermediary_currency'] = $this->getIntermediaryCurrency();
        $json['min_limit']                     = $this->getMinLimit();
        $json['max_limit']                     = $this->getMaxLimit();
        $json['step_amount']                   = $this->getStepAmount();
        $json['is_default']                    = (bool)$this->getIsDefault();
        $json['channel_id']                    = $this->getChannelID();
        $json['status']                        = $this->getStatus();
        $json['approve_reject_remark'] = $this->getApproveRejectRemark();
        $json['approve_reject_at'] = $this->getApproveRejectAt() ? $this->getApproveRejectAt()->getString() : NULL;
        $json['approve_reject_by'] = $this->getApproveRejectBy();
        $json['approve_reject_by_name'] = $this->getApproveRejectByName();

        $json['require_face_to_face_trans'] = (bool)$this->getRequireFaceToFaceTrans();
        $json['require_face_to_face_recipient'] = (bool)$this->getRequireFaceToFaceRecipient();
        $json['home_collection_enabled'] = (bool)$this->getHomeCollectionEnabled();
        $json['cashin_expiry_period'] = $this->getCashinExpiryPeriod();

        $json['conversion_type'] = $this->getConversionType() ? $this->getConversionType() : NULL;
        $json['display_rate'] = $this->getDisplayRate() ? $this->getDisplayRate() : NULL;
        $json['cashin_profit_sharing_by_corp_ser_id'] = (bool)$this->getCashInProfitSharingByCorpSerId();
        $json['cashout_profit_sharing_by_corp_ser_id'] = (bool)$this->getCashOutProfitSharingByCorpSerId();
        
        $json['last_pricing_approve_at'] = $this->getLastPricingApproveAt() ? $this->getLastPricingApproveAt()->getString() : NULL;

        return $json;
    }

    protected function checkRateType()
    {
        if( $this->getConversionType() == ConversionType::DIRECT )
        {
            $from = $this->getRateProvider()->getConversionRemittanceService()->getFromCountryCurrencyCode();
            $to = $this->getRateProvider()->getConversionRemittanceService()->getToCountryCurrencyCode();

            $this->getRateRider()->setRateType(RateType::RATE_RIDER);
            $this->getRateRider()->getConversionRemittanceService()->setFromCountryCurrencyCode($from);
            $this->getRateRider()->getConversionRemittanceService()->setToCountryCurrencyCode($to);

            $this->getRateProvider()->setRateType(RateType::RATE_PROVIDER);
        }

        return $this;
    }

    public function setInCorporateService(RemittanceCorporateService $corpServ)
    {
        $this->in_corporate_service = $corpServ;
        $this->checkRateType();
        return $this;
    }

    public function getInCorporateService()
    {
        return $this->in_corporate_service;
    }

    public function setOutCorporateService(RemittanceCorporateService $corpServ)
    {
        $this->out_corporate_service = $corpServ;
        $this->checkRateType();
        return $this;
    }

    public function getOutCorporateService()
    {
        return $this->out_corporate_service;
    }

    public function setRemittanceService(RemittanceServiceConfig $reServ)
    {
        $this->remittance_service = $reServ;
        return $this;
    }

    public function getRemittanceService()
    {
        return $this->remittance_service;
    }

    public function rateBelongsTo(ExchangeRate $rate)
    {
        if( $rate->getCorporateServiceId() == $this->getInCorporateService()->getId() )
        {
            return $this->getInCorporateService();
        }
        elseif( $rate->getCorporateServiceId() == $this->getOutCorporateService()->getId() )
        {
            return $this->getOutCorporateService();
        }

        return false;
    }

    public function corporateBelongsToThis(RemittanceCorporateService $corpServ)
    {
        return (( $this->getInCorporateService()->getId() == $corpServ->getId() ) OR
                ( $this->getOutCorporateService()->getId() == $corpServ->getId() ));
    }

    public function serviceProviderBelongsTo($serviceProviderId)
    {
        $rateProvider = $this->getRateProvider();

        if( $rateProvider instanceof CorporateService )
        {//check rate provider first
            if( $rateProvider->getServiceProviderId() == $serviceProviderId )
                return $rateProvider;
        }

        if( $this->getInCorporateService()->getServiceProviderId() == $serviceProviderId )
            return $this->getInCorporateService();
        elseif( $this->getOutCorporateService()->getServiceProviderId() == $serviceProviderId )
            return $this->getOutCorporateService();

        return false;
    }

    public function getConversionType()
    {
        if( $this->isDirectExchange() )
            return ConversionType::DIRECT;
        else if( $this->isIntermediaryExchange() )
            return ConversionType::INTERMEDIARY;

        return false;
    }

    public function isDirectExchange()
    {
        return ($this->getRateProvider() && $this->getRateRider());
    }

    public function isIntermediaryExchange()
    {
        return ( $this->getInCorporateService()->getConversionRemittanceService()->getId() != NULL AND
                 $this->getOutCorporateService()->getConversionRemittanceService()->getId() != NULL );
    }

    public function isRider(CorporateService $corporateService)
    {
        return ( $this->getRateRider()->getId() == $corporateService->getId() );
    }

    public function isProvider(CorporateService $corporateService)
    {
        return ( $this->getRateProvider()->getId() == $corporateService->getId() );
    }

    public function getRateRider()
    {
        if( $this->getInCorporateService()->getConversionRemittanceService()->getId() == NULL AND
            $this->getOutCorporateService()->getConversionRemittanceService()->getId() != NULL )
        {
            return $this->getInCorporateService();
        }
        else if( $this->getInCorporateService()->getConversionRemittanceService()->getId() != NULL AND
                 $this->getOutCorporateService()->getConversionRemittanceService()->getId() == NULL )
        {
            return $this->getOutCorporateService();
        }

        return false;
    }

    public function getRateProvider()
    {
        if( $this->getInCorporateService()->getConversionRemittanceService()->getId() == NULL AND
            $this->getOutCorporateService()->getConversionRemittanceService()->getId() != NULL )
        {
            return $this->getOutCorporateService();
        }
        else if( $this->getInCorporateService()->getConversionRemittanceService()->getId() != NULL AND
            $this->getOutCorporateService()->getConversionRemittanceService()->getId() == NULL )
        {
            return $this->getInCorporateService();
        }

        return false;
    }

    public function getDisplayRate()
    {
        if( $this->isDirectExchange() )
        {
            if( $rate1 = $this->getRateProvider()->getExchangeRate() AND
                $margin1 = $this->getRateProvider()->getMargin() AND
                $margin2 = $this->getRateRider()->getMargin() )
                return $rate1 - $margin1 - $margin2;
        }
        elseif( $this->isIntermediaryExchange() )
        {
            if( $rate1 = $this->getInCorporateService()->getExchangeRate() AND
                $rate2 = $this->getOutCorporateService()->getExchangeRate() AND
                $margin1 = $this->getInCorporateService()->getMargin() AND
                $margin2 = $this->getOutCorporateService()->getMargin() )
                return ($rate1 - $margin1)*($rate2 - $margin2);
        }

        return false;
    }

    public function getDisplayRateByCorporate($serviceProviderId)
    {
        if( $corp = $this->serviceProviderBelongsTo($serviceProviderId) )
        {
            if( $this->isDirectExchange() )
            {
                if( $this->isProvider($corp) )
                {
                    return $this->getRateProvider()->getExchangeRate() - $this->getRateProvider()->getMargin();
                }
                elseif( $this->isRider($corp) )
                {
                    return $this->getRateProvider()->getExchangeRate() - $this->getRateProvider()->getMargin() - $this->getRateRider()->getMargin();
                }

            }
            elseif( $this->isIntermediaryExchange() )
            {
                return $corp->getExchangeRate() - $corp->getMargin();
            }
        }

        return false;
    }

    public function getCorporateServiceCollection()
    {
        $collection = new RemittanceCorporateServiceCollection();
        $collection->addData($this->getInCorporateService());
        $collection->addData($this->getOutCorporateService());
        return $collection;
    }

    public function getInSellingPrice()
    {
        if( $this->isDirectExchange() )
        {
            if( $this->isRider($this->getInCorporateService()) )
                return $this->getRateProvider()->getExchangeRate() - $this->getRateProvider()->getMargin();
            elseif( $this->isProvider($this->getInCorporateService()) )
                return $this->getInCorporateService()->getExchangeRate();
        }
        elseif( $this->isIntermediaryExchange() )
            return $this->getInCorporateService()->getExchangeRate();

        return false;
    }

    public function getInBuyingPrice()
    {
        if( $selling_price = $this->getInSellingPrice() )
            return $selling_price - $this->getInCorporateService()->getMargin();

        return false;
    }

    public function getOutSellingPrice()
    {
        if( $this->isDirectExchange() )
        {
            if( $this->isRider($this->getOutCorporateService()) )
                return $this->getRateProvider()->getExchangeRate() - $this->getRateProvider()->getMargin();
            elseif( $this->isProvider($this->getOutCorporateService()) )
                return $this->getOutCorporateService()->getExchangeRate();
        }
        elseif( $this->isIntermediaryExchange() )
            return $this->getOutCorporateService()->getExchangeRate();

        return false;
    }

    public function getOutBuyingPrice()
    {
        if( $selling_price = $this->getOutSellingPrice() )
            return $selling_price - $this->getOutCorporateService()->getMargin();

        return false;
    }

    public function getInProfit($receivingAmount)
    {
        if( $this->isIntermediaryExchange() )
        {
            $receivingAmount = $receivingAmount/$this->getOutSellingPrice();
        }

        if( $buyingPrice = $this->getInBuyingPrice() AND
            $sellingPrice = $this->getInSellingPrice() )
        {
            return round(($receivingAmount/$buyingPrice - $receivingAmount/$sellingPrice), 4);
        }

        return false;
    }

    public function getOutProfit($receivingAmount)
    {
        if( $buyingPrice = $this->getOutBuyingPrice() AND
            $sellingPrice = $this->getOutSellingPrice() )
        {
            return round(($receivingAmount/$buyingPrice - $receivingAmount/$sellingPrice)*$sellingPrice, 4);
        }

        return false;
    }

    public function getLastRateUpdatedAt()
    {
        $inCreatedAt = $this->getInCorporateService()->getExchangeRateObj()->getCreatedAt();
        $outCreatedAt = $this->getOutCorporateService()->getExchangeRateObj()->getCreatedAt();

        if( $inCreatedAt->isNull() AND $outCreatedAt->isNull() )
            return NULL;

        if( $inCreatedAt->isNull() AND !$outCreatedAt->isNull() )
            return $outCreatedAt->getString();

        if( !$inCreatedAt->isNull() AND $outCreatedAt->isNull() )
            return $inCreatedAt->getString();

        if( $inCreatedAt->getUnix() > $outCreatedAt->getUnix() )
            return $inCreatedAt->getString();
        else
            return $outCreatedAt->getString();
    }

    public function getFormattedExchangeRate()
    {
        if( $rate = $this->getDisplayRate() )
        {
            return 1 . ' ' . substr($this->getFromCountryCurrencyCode(), -3) . ":" . number_format($rate, 4) . ' ' . substr($this->getToCountryCurrencyCode(), -3);
        }

        return NULL;
    }

    public function getFormattedExchangeRateByCorporate($serviceProviderId)
    {
        if( $corp = $this->serviceProviderBelongsTo($serviceProviderId) )
        {
            $inCountryCurrencyCode = NULL;
            $outCountryCurrencyCode = NULL;
            $rate = $this->getDisplayRateByCorporate($serviceProviderId);

            if( $this->isDirectExchange() )
            {
                $inCountryCurrencyCode = $this->getFromCountryCurrencyCode();
                $outCountryCurrencyCode = $this->getToCountryCurrencyCode();
            }
            elseif( $this->isIntermediaryExchange() )
            {
                $inCountryCurrencyCode = $corp->getConversionRemittanceService()->getFromCountryCurrencyCode();
                $outCountryCurrencyCode = $corp->getConversionRemittanceService()->getFromCountryCurrencyCode();
            }

            if( $inCountryCurrencyCode AND
                $outCountryCurrencyCode AND
                $rate)
            {
                return 1 . ' ' . substr($inCountryCurrencyCode, -3) . ":" . number_format($rate, 4) . ' ' . substr($outCountryCurrencyCode, -3);
            }
        }

        return NULL;
    }
}