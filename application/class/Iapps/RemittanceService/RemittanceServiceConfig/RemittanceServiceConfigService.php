<?php

namespace Iapps\RemittanceService\RemittanceServiceConfig;

use Iapps\Common\Core\IappsBaseService;
use Iapps\Common\Core\PaginatedResult;
use Iapps\RemittanceService\Common\CacheKey;
use Iapps\RemittanceService\Common\MessageCode;
use Iapps\RemittanceService\RemittanceServiceConfig\RemittanceServiceConfigRepository;
use Iapps\RemittanceService\RemittanceServiceConfig\RemittanceServiceConfigValidator;
use Iapps\Common\Helper\GuidGenerator;
use Iapps\Common\AuditLog\AuditLogAction;
use Iapps\Common\Core\IappsBaseEntity;
use Iapps\Common\Core\IappsDateTime;
//use Iapps\Common\Microservice\CountryService\Country;
use Iapps\Common\SystemCode\SystemCode;
use Iapps\Common\Microservice\PaymentService\PaymentService;

class RemittanceServiceConfigService extends IappsBaseService
{
    public function getAllRemittanceServiceConfig($limit, $page, RemittanceServiceConfig $filter = NULL, $forInternational = NULL)
    {
        if( $object = $this->getRepository()->findAll() )
        {
            if( $object->result instanceof RemittanceServiceConfigCollection )
            {
                if( $filter )
                {
                    $object->result = $object->result->filter($filter);
                }

                if( $forInternational )
                {
                    $object->result = $object->result->getByForInternational($forInternational);
                }

                $paginatedResult = $object->result->pagination($limit, $page);
                $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_SERVICE_CONFIG_SUCCESS);
                //result_arr = $object->result->toArray();
                $result_arr = $paginatedResult->getResult()->getSelectedField(array(
                    'id', 'from_country_currency_code','to_country_currency_code',
                ));
                
                $remit_config_count = count($result_arr);
                if($remit_config_count > 0)
                {
                    //get individual country code and currency code
                    $payment_serv = new PaymentService();
                    if( $country_currency_list = $payment_serv->getAllCountryCurrency() )
                    {
                        
                        $cc_count = count($country_currency_list);
                        for ($row = 0; $row < $remit_config_count; $row++)
                        {
                            $mapped_remit_config = array();
                            for ($rowcc = 0; $rowcc < $cc_count; $rowcc++)
                            {
                                if($result_arr[$row]["from_country_currency_code"] == $country_currency_list[$rowcc]->getCode())
                                {
                                    $result_arr[$row]["from_country_code"] = $country_currency_list[$rowcc]->getCountryCode();
                                    $result_arr[$row]["from_currency_code"] = $country_currency_list[$rowcc]->getCurrencyCode();
                                }
                                if($result_arr[$row]["to_country_currency_code"] == $country_currency_list[$rowcc]->getCode())
                                {
                                    $result_arr[$row]["to_country_code"] = $country_currency_list[$rowcc]->getCountryCode();
                                    $result_arr[$row]["to_currency_code"] = $country_currency_list[$rowcc]->getCurrencyCode();
                                }
                            }
                        }
                    }
                }

                $paginatedResult->setResult($result_arr);   //set modified result

                return $paginatedResult;
            }
        }

        $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_SERVICE_CONFIG_FAILED);
        return false;
    }

    public function getAllRemittanceServiceConfigByFromCountryCode($from_country_code)
    {
        //get country currency based on from country code
        $payment_serv = new PaymentService();
        if( $country_currency_list = $payment_serv->getCountryCurrencyInfoByCountryCode($from_country_code) ) {
            $country_currency_code_list = array();
            foreach($country_currency_list as $country_currency_each)
            {
                $country_currency_code_list[] = $country_currency_each->getCode();
            }

            if ($object = $this->getRepository()->findByFromCountryCurrencyList($country_currency_code_list)) {
                if ($object->result instanceof RemittanceServiceConfigCollection) {
                    $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_SERVICE_CONFIG_SUCCESS);
                    //result_arr = $object->result->toArray();
                    $result_arr = $object->result->getSelectedField(array('id', 'from_country_currency_code', 'to_country_currency_code',
                        'markup_on_rate', 'sync_interval', 'start_time',
                        'exchange_rate_id', 'exchange_rate_last_value',
                        'exchange_rate_expiry_date', 'exchange_rate_last_updated_at'));

                    $remit_config_count = count($result_arr);
                    if ($remit_config_count > 0) {
                        //get individual country code and currency code
                        $payment_serv = new PaymentService();
                        if ($country_currency_list = $payment_serv->getAllCountryCurrency()) {

                            $cc_count = count($country_currency_list);
                            for ($row = 0; $row < $remit_config_count; $row++) {
                                $mapped_remit_config = array();
                                for ($rowcc = 0; $rowcc < $cc_count; $rowcc++) {
                                    if ($result_arr[$row]["from_country_currency_code"] == $country_currency_list[$rowcc]->getCode()) {
                                        $result_arr[$row]["from_country_code"] = $country_currency_list[$rowcc]->getCountryCode();
                                        $result_arr[$row]["from_currency_code"] = $country_currency_list[$rowcc]->getCurrencyCode();
                                    }
                                    if ($result_arr[$row]["to_country_currency_code"] == $country_currency_list[$rowcc]->getCode()) {
                                        $result_arr[$row]["to_country_code"] = $country_currency_list[$rowcc]->getCountryCode();
                                        $result_arr[$row]["to_currency_code"] = $country_currency_list[$rowcc]->getCurrencyCode();
                                    }
                                }
                            }
                        }
                    }

                    return $result_arr;
                }
            }
        }

        $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_SERVICE_CONFIG_FAILED);
        return false;
    }

    public function getRemittanceServiceConfigInfo($id)
    {
        if( $configInfo = $this->getRepository()->findById($id) )
        {
            $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_SERVICE_CONFIG_SUCCESS);
            return $configInfo;
        }

        $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_SERVICE_CONFIG_FAILED);
        return false;
    }

    public function getRemittanceServiceConfigInfoByFromAndTo($from_country_currency_code, $to_country_currency_code)
    {
        if( $configInfo = $this->getRepository()->findByFromAndToCountryCurrencyCode($from_country_currency_code, $to_country_currency_code) )
        {
            $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_SERVICE_CONFIG_SUCCESS);
            return $configInfo;
        }

        $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_SERVICE_CONFIG_FAILED);
        return false;
    }

    public function addRemittanceServiceConfig(RemittanceServiceConfig $config)
    {
        //validate remittance service config
        $v = RemittanceServiceConfigValidator::make($config);

        if( !$v->fails() )
        {
            //check if exists
            if( !$configInfo = $this->getRepository()->findByFromAndToCountryCurrencyCode($config->getFromCountryCurrencyCode(), $config->getToCountryCurrencyCode()) )
            { 
                //assign an id
                $config->setId(GuidGenerator::generate());
                $config->setCreatedBy($this->getUpdatedBy());

                if( $this->getRepository()->add($config) )
                {
                    $this->setResponseCode(MessageCode::CODE_ADD_REMITTANCE_SERVICE_CONFIG_SUCCESS);

                    //dispatch event to auditLog
                    $this->fireLogEvent('iafb_remittance.remittance_service', AuditLogAction::CREATE, $config->getId());

                    return $config;
                }
            }
        }

        $this->setResponseCode(MessageCode::CODE_ADD_REMITTANCE_SERVICE_CONFIG_FAILED);
        return false;
    }

    public function findByIds(array $corporate_service_ids)
    {
        $collection = new RemittanceServiceConfigCollection();
        foreach($corporate_service_ids AS $corporate_service_id)
        {
            if( !$corporate_service_id )
                continue;
            
            if( $data = $this->getRepository()->findById($corporate_service_id) )
            {
                $collection->addData($data);
            }
        }

        if( count($collection) > 0 )
        {
            $data = new PaginatedResult();
            $data->setResult($collection);
            $data->setTotal(count($collection));

            return $data;
        }else{

            return false;
        }

    }
}