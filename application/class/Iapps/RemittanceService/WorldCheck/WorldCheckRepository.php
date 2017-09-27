<?php

namespace Iapps\RemittanceService\WorldCheck;

use Iapps\Common\Core\IappsBaseRepository;

class WorldCheckRepository extends IappsBaseRepository{


    public function updateWorldCheckProfile(WorldCheck $worldCheck)
    {
        return $this->getDataMapper()->updateWorldCheckProfile($worldCheck);
    }

    public function insertWorldCheckProfile(WorldCheck $worldCheck)
    {
        return $this->getDataMapper()->insertWorldCheckProfile($worldCheck);
    }

    public function findByUserProfileIDArr(Array $user_profile_id_arr)
    {
        return $this->getDataMapper()->findByUserProfileIDArr($user_profile_id_arr);
    }


}