<?php

namespace Iapps\RemittanceService\WorldCheck;

use Iapps\Common\Core\IappsBaseDataMapper;

interface WorldCheckDataMapper extends IappsBaseDataMapper{

    public function updateWorldCheckProfile(WorldCheck $worldCheck);
    public function insertWorldCheckProfile(WorldCheck $worldCheck);
    public function findByUserProfileIDArr(Array $user_profile_id_arr);
}