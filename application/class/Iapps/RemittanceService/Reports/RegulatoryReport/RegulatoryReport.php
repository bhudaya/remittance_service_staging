<?php

namespace Iapps\RemittanceService\Reports\RegulatoryReport;

use Iapps\Common\Core\IappsBaseEntity;
use Iapps\Common\SystemCode\SystemCode;

class RegulatoryReport extends IappsBaseEntity{
    // protected $user_profile_id;


    function __construct()
    {
        parent::__construct();
        $this->status = new SystemCode();
    }

    // public function set($)
    // {
    //     $this-> = $;
    //     return true;
    // }

    // public function getUserProfileId()
    // {
    //     return $this->;
    // }
   

    // public function jsonSerialize()
    // {
    //     $json = parent::jsonSerialize();

    //     $json['']   = $this->();
    //     return $json;
    // }
}