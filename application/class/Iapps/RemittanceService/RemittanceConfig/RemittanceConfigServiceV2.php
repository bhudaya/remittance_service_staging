<?php

namespace Iapps\RemittanceService\RemittanceConfig;

use Iapps\Common\Core\IappsBaseService;
use Iapps\Common\Microservice\PaymentService\PaymentServiceFactory;
use Iapps\RemittanceService\Common\MessageCode;
use Iapps\RemittanceService\RemittanceServiceConfig\RemittanceServiceConfigServiceFactory;

class RemittanceConfigServiceV2 extends RemittanceConfigService{

    protected $service_provider_id_filter = array();
    protected $delivery_time_filter = array();
    protected $collection_mode_group_filter = array();
    protected $collection_mode_attribute_filter = array();

    public function setServiceProviderIdFilter(array $service_provider_ids)
    {
        $this->service_provider_id_filter = $service_provider_ids;
        return $this;
    }

    public function getServiceProviderIdFilter()
    {
        return $this->service_provider_id_filter;
    }

    public function setDeliveryTimeFilter(array $delivery_time_filter)
    {
        $this->delivery_time_filter = $delivery_time_filter;
        return $this;
    }

    public function getDeliveryTimeFilter()
    {
        return $this->delivery_time_filter;
    }

    public function setCollectionModeGroupFilter(array $collection_group_filter)
    {
        $this->collection_mode_group_filter = $collection_group_filter;
        return $this;
    }

    public function getCollectionGroupFilter()
    {
        return $this->collection_mode_group_filter;
    }

    public function setCollectionModeAttributeFilter(array $collection_attribute_filter)
    {
        $this->collection_mode_attribute_filter = $collection_attribute_filter;
        return $this;
    }

    public function getCollectionModeAttributeFilter()
    {
        return $this->collection_mode_attribute_filter;
    }

    public function getActiveChannelV2($fromCountryCurrencyCode, $toCountryCurrencyCode )
    {
        //get remittance service ids
        $rsServ = RemittanceServiceConfigServiceFactory::build();
        if( $rs = $rsServ->getRemittanceServiceConfigInfoByFromAndTo($fromCountryCurrencyCode, $toCountryCurrencyCode) )
        {
            $serviceId = $rs->getId();

            //get all remittane config by service ids
            $configFilter = new RemittanceConfig();
            $configFilter->setIsActive(1);  //must get active channel
            $configFilter->setStatus(RemittanceConfigStatus::APPROVED);
            if ($info = $this->getRepository()->findByRemittanceServiceIds(array($serviceId), $configFilter))
            {
                $this->_extractRelatedRecord($info->getresult());
                $availableChannel = array();

                //todo: get filtered collection modes, if given
                $filteredPM = null;
                if( count($this->getDeliveryTimeFilter()) > 0 OR
                    count($this->getCollectionGroupFilter()) > 0 OR
                    count($this->getCollectionModeAttributeFilter()) > 0)
                {
                    $paymentServ = PaymentServiceFactory::build();
                    $is_collection = true;
                    $delivery_codes = NULL;
                    if( count($this->getDeliveryTimeFilter()) > 0 )
                        $delivery_codes = $this->getDeliveryTimeFilter();
                    $group_codes = NULL;
                    if( count($this->getCollectionGroupFilter()) > 0 )
                        $group_codes = $this->getCollectionGroupFilter();
                    $attributes = NULL;
                    if( count($this->getCollectionModeAttributeFilter()) > 0 )
                        $attributes = $this->getCollectionModeAttributeFilter();
                    if( $filteredPaymentModes = $paymentServ->getAllPaymentModes($is_collection,
                                                                                NULL,
                                                                                $attributes,
                                                                                $group_codes,
                                                                                $delivery_codes) )
                    {
                        $filteredPM = array();
                        foreach($filteredPaymentModes AS $filteredPaymentMode)
                            $filteredPM[] = $filteredPaymentMode->code;
                    }
                    else
                    {
                        $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_SERVICE_CONFIG_FAILED);
                        return false;
                    }
                }

                //get collection modes and fees
                foreach($info->getresult() AS $remittanceConfig)
                {
                    if( $paymentModeAndFees = $this->getCorpServicePaymentModeAndFeeByRemittanceConfigId($remittanceConfig->getId(), true) )
                    {
                        if( count($this->getServiceProviderIdFilter()) > 0 AND !in_array($remittanceConfig->getInCorporateService()->getServiceProviderId(), $this->getServiceProviderIdFilter()) )
                            continue;   //dont include this

                        if( isset($filteredPM) AND count($filteredPM) > 0 )
                        {//filter collection mode
                            $filtered_cashout_mode = array();
                            foreach($paymentModeAndFees->cash_out_payment_mode AS $cashout_mode)
                            {
                                if( !in_array($cashout_mode['payment_code'], $filteredPM) )
                                    continue;

                                $filtered_cashout_mode[] = $cashout_mode;
                            }

                            if( count($filtered_cashout_mode) <= 0 )
                                continue;

                            $paymentModeAndFees->cash_out_payment_mode = $filtered_cashout_mode;
                        }

                        $temp = $remittanceConfig->getSelectedField(array('id', 'from_country_currency_code', 'to_country_currency_code', 'min_limit', 'max_limit', 'display_rate', 'is',
                            'require_face_to_face_trans', 'require_face_to_face_recipient', 'home_collection_enabled', 'cashin_expiry_period'));
                        $temp['service_provider_id'] = $remittanceConfig->getInCorporateService()->getServiceProviderId();
                        $temp['cash_in_payment_mode'] = $paymentModeAndFees->cash_in_payment_mode;
                        $temp['cash_out_payment_mode'] = $paymentModeAndFees->cash_out_payment_mode;
                        $availableChannel[] = $temp;
                    }
                }

                if( count($availableChannel) > 0 )
                {
                    $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_CONFIG_SUCCESS);
                    return $availableChannel;
                }
            }
        }

        $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_SERVICE_CONFIG_FAILED);
        return false;
    }
}