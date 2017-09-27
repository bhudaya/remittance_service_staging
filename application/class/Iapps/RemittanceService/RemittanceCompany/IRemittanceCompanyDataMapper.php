<?php

namespace Iapps\RemittanceService\RemittanceCompany;

use Iapps\Common\Core\IappsBaseDataMapper;

interface IRemittanceCompanyDataMapper extends IappsBaseDataMapper{

    public function findByFilter(RemittanceCompany $remittanceCompany);
    public function updateByServiceProviderId(RemittanceCompany $remittanceCompany);
}