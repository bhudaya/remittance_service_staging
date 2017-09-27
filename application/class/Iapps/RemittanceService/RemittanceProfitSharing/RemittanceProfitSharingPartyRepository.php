<?php

namespace Iapps\RemittanceService\RemittanceProfitSharing;

use Iapps\Common\Core\IappsBaseRepository;

class RemittanceProfitSharingPartyRepository extends IappsBaseRepository{

    public function insert(RemittanceProfitSharingParty $profitSharingParty)
    {
        return $this->getDataMapper()->insert($profitSharingParty);
    }

    public function findAllByCorporateServProfitSharingId($collection, RemittanceProfitSharingParty $profitSharingParty)
    {
        return $this->getDataMapper()->findAllByCorporateServProfitSharingId($collection, $profitSharingParty);
    }

    // public function insertWorldCheckProfile(WorldCheck $worldCheck)
    // {
    //     return $this->getDataMapper()->insertWorldCheckProfile($worldCheck);
    // }

}