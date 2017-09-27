<?php

namespace Iapps\RemittanceService\RemittanceProfitSharing;

use Iapps\Common\Core\IappsBaseEntity;
use Iapps\Common\SystemCode\SystemCode;

class RemittanceProfitSharingParty extends IappsBaseEntity{

    protected $corporate_service_profit_sharing_id;
    protected $corporate_id;
    protected $percentage;

    protected $profitInAmount;

    function __construct()
    {
        parent::__construct();
    }

    public function setCorporateId($corporate_id)
    {
        $this->corporate_id = $corporate_id;
        return true;
    }

    public function getCorporateId()
    {
        return $this->corporate_id;
    }

    public function setCorporateServProfitSharingId($corporate_service_profit_sharing_id)
    {
        $this->corporate_service_profit_sharing_id = $corporate_service_profit_sharing_id;
        return true;
    }

    public function getCorporateServProfitSharingId()
    {
        return $this->corporate_service_profit_sharing_id;
    }

    public function setPercentage($percentage)
    {
        $this->percentage = $percentage;
        return true;
    }

    public function getPercentage()
    {
        return $this->percentage;
    }


    public function jsonSerialize()
    {
        $json = parent::jsonSerialize();

        $json['corporate_service_profit_sharing_id']   = $this->getCorporateServProfitSharingId();
        $json['corporate_id']                          = $this->getCorporateId();
        $json['percentage']                            = $this->getPercentage();
        return $json;
    }

    public function calculateSharedProfit($profit)
    {
        $this->profitInAmount = round($profit*$this->getPercentage()/100,4);
        return $this->profitInAmount;
    }

    public function getProfitInAmount()
    {
        return $this->profitInAmount;
    }
}