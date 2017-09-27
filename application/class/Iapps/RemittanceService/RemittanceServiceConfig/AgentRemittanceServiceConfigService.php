<?php

namespace Iapps\RemittanceService\RemittanceServiceConfig;

use Iapps\Common\Helper\GuidGenerator;
use Iapps\RemittanceService\Common\MessageCode;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfig;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfigCollection;
use Iapps\RemittanceService\RemittanceConfig\CorpServRemittanceConfigServiceFactory;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfigServiceFactory;

class AgentRemittanceServiceConfigService extends RemittanceServiceConfigService{

    public function getAllComputedRemittanceServiceConfig($limit=100, $page=1)
    {
        if( $object = $this->getRepository()->findAll($limit, $page, false) )
        {
            $from = array();
            foreach($object->result AS $remittance)
            {
                //construct info
                if( !$remittance->isExpired() )
                {
                    $temp = $remittance->getSelectedField(array(
                        'id',
                        'to_country_currency_code',
                        'exchange_rate_expiry_date',
                        'exchange_rate_last_updated_at'
                    ));

                    //set additional spread from corp service fee (spread type)
                    $additinal_spread_arr = array();
                    //get default remittance config
                    $corp_serv_remittance_config_service = CorpServRemittanceConfigServiceFactory::build();
                    if($result = $corp_serv_remittance_config_service->getDefaultCorpServiceRemittanceConfigInfo($remittance->getId(),$limit, $page))
                    {
                        if($corpServRelatedFee = $corp_serv_remittance_config_service->getCorpServiceFeeByRemittanceConfigId($result->getId()))
                        {
                            $additinal_spread_arr[] = $corpServRelatedFee->cash_in_fee != null ?
                                                        $corpServRelatedFee->cash_in_fee->total_spread != null ?
                                                            $corpServRelatedFee->cash_in_fee->total_spread
                                                            : 0
                                                        : 0;
                            $additinal_spread_arr[] = $corpServRelatedFee->cash_out_fee != null ?
                                                        $corpServRelatedFee->cash_out_fee->total_spread != null ?
                                                            $corpServRelatedFee->cash_out_fee->total_spread
                                                            : 0
                                                        : 0;
                        }
                    }

                    $temp['display_rate'] = $remittance->getDisplayExchangeRate($additinal_spread_arr);
                    $temp['channel'] = array();
                    $temp['channel'][] = $this->_getDefaultChannel($remittance);

                    //add to result
                    if( !array_key_exists($remittance->getFromCountryCurrencyCode(), $from) )
                    {
                        $from[$remittance->getFromCountryCurrencyCode()]['from_country_currency_code'] = $remittance->getFromCountryCurrencyCode();
                        $from[$remittance->getFromCountryCurrencyCode()]['to'] = array();
                    }

                    $from[$remittance->getFromCountryCurrencyCode()]['to'][] = $temp;
                }
            }

            if( count($from) > 0 )
            {
                $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_SERVICE_CONFIG_SUCCESS);
                return $from;
            }
        }

        $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_SERVICE_CONFIG_FAILED);
        return false;
    }

    public function getCorpServRemittanceConfigInfo($remittance_service_id, $limit = 100, $page = 1)
    {
        $corp_serv_remittance_config_service = CorpServRemittanceConfigServiceFactory::build();
        if($result = $corp_serv_remittance_config_service->getDefaultCorpServiceRemittanceConfigInfo($remittance_service_id,$limit, $page))
        {
            $this->setResponseCode($corp_serv_remittance_config_service->getResponseCode());
            return $result;
        }

        $this->setResponseCode($corp_serv_remittance_config_service->getResponseCode());
        return false;
    }

    public function getCorpServFeeAndPaymentModeFeeForByRemittanceConfigId($remittance_config_id, $limit = 100, $page = 1)
    {
        $corp_serv_remittance_config_service = CorpServRemittanceConfigServiceFactory::build();
        if($result = $corp_serv_remittance_config_service->getCorpServiceFeeByRemittanceConfigId($remittance_config_id,$limit, $page))
        {
            $this->setResponseCode($corp_serv_remittance_config_service->getResponseCode());
            return $result;
        }

        $this->setResponseCode($corp_serv_remittance_config_service->getResponseCode());
        return false;
    }

    protected function _getDefaultChannel(RemittanceServiceConfig $remittanceServ)
    {
        $channel = null;
        $remit_config_serv = RemittanceConfigServiceFactory::build();

        $remittanceConfig = new RemittanceConfig();
        $remittanceConfig->setRemittanceServiceId($remittanceServ->getId());
        $remittanceConfig->setIsDefault((int)true);
        if($remittanceConfigColl = $remit_config_serv->getRemittanceConfigBySearchFilter($remittanceConfig, 1, 1))
        {
            $remit_config_arr = $remittanceConfigColl->result->toArray();
            $channel = $remit_config_arr[0];
        }

        /*
        //todo call remittance config service to grab data, temp use fake data.
        if( $remittanceServ->getToCountryCurrencyCode() == 'ID-IDR')
        {
            $channel = array(
                'id' => GuidGenerator::generate(),
                'min_limit' => 200000.00,
                'max_limit' => 5000000.00,
                'step_amout' => 100000.00
            );
        }
        elseif( $remittanceServ->getToCountryCurrencyCode() == 'MY-MYR')
        {
            $channel = array(
                'id' => GuidGenerator::generate(),
                'min_limit' => 100.00,
                'max_limit' => 2000.00,
                'step_amout' => 10.00
            );
        }
        elseif( $remittanceServ->getToCountryCurrencyCode() == 'SG-SGD')
        {
            $channel = array(
                'id' => GuidGenerator::generate(),
                'min_limit' => 100.00,
                'max_limit' => 2000.00,
                'step_amout' => 10.00
            );
        }
        else
            $channel = array();
        */

        return $channel;
    }
}