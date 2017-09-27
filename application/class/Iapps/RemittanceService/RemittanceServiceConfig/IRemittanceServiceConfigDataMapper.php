<?php

namespace Iapps\RemittanceService\RemittanceServiceConfig;

use Iapps\Common\Core\IappsBaseDataMapper;

interface IRemittanceServiceConfigDataMapper extends IappsBaseDataMapper{

    public function findAll();
    public function findByFromAndToCountryCurrencyCode($from_country_currency_code, $to_country_currency_code);
    public function findByFromCountryCurrencyList($from_country_currency_list);
    public function insert(RemittanceServiceConfig $config);
    //public function update(RemittanceServiceConfig $config);
    //public function updateRates(RemittanceServiceConfig $config);
    public function findByIds(array $corporate_service_ids);
    
}