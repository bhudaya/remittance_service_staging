<?php

namespace Iapps\RemittanceService\RemittanceConfig;

use Iapps\Common\Core\IappsBaseDataMapper;

interface IRemittanceConfigDataMapper extends IappsBaseDataMapper{

    public function findAll($limit, $page);
    public function findByIdArr(array $remittance_config_id_arr);
    public function findBySearchFilter(RemittanceConfig $remittanceConfig,$limit = NULL , $page = NULL);
    public function findByRemittanceServiceIds(array $remittanceServiceIds, RemittanceConfig $configFilter, $limit = NULL, $page = NULL );
    public function insert(RemittanceConfig $config);
    public function update(RemittanceConfig $config);
    public function updateStatus(RemittanceConfig $config);
    public function findByCorporateServiceIds(array $cashInCorporateServiceIds = NULL, array $cashOutCorporateServiceIds = NULL, RemittanceConfig $configFilter = NULL, $limit = NULL, $page = NULL );
    public function findExists($limit, $page, $remittanceConfigId = NULL, $cashInCountryCurrencyCode, $cashOutCountryCurrencyCode, $cashInCountryPartnerId, $cashOutCountryPartnerId, array $status = NULL);
}