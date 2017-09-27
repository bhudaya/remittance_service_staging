<?php

namespace Iapps\RemittanceService\RemittanceCompanyRecipient;

use Iapps\Common\Core\IappsBaseDataMapper;

interface IRemittanceCompanyRecipientDataMapper extends IappsBaseDataMapper{

    public function findByFilter(RemittanceCompanyRecipient $remittanceCompanyRecipient);
    public function update(RemittanceCompanyRecipient $remittanceCompanyRecipient, $checkNull = true);
    public function insert(RemittanceCompanyRecipient $remittanceCompanyRecipient);
}