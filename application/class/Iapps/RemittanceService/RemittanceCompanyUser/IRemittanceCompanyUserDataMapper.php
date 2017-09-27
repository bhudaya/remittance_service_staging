<?php

namespace Iapps\RemittanceService\RemittanceCompanyUser;

use Iapps\Common\Core\IappsBaseDataMapper;

interface IRemittanceCompanyUserDataMapper extends IappsBaseDataMapper{

    public function findByFilter(RemittanceCompanyUser $remittanceCompanyUser);
    public function update(RemittanceCompanyUser $remittanceCompanyUser, $checkNull = true);
    public function insert(RemittanceCompanyUser $remittanceCompanyUser);
}