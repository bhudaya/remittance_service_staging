<?php

namespace Iapps\RemittanceService\RemittanceProfitSharing;

use Iapps\Common\Core\IappsBaseDataMapper;

interface RemittanceProfitSharingPartyDataMapper extends IappsBaseDataMapper{

    public function insert(RemittanceProfitSharingParty $profitSharingParty);
    public function findAllByCorporateServProfitSharingId($collection, RemittanceProfitSharingParty $profitSharingParty);
}