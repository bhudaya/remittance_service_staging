<?php

namespace Iapps\RemittanceService\RemittanceRecord;

use Iapps\RemittanceService\RemittanceConfig\RemittanceConfig;

class LocalRecipientCompulsoryRemittanceChecker extends RecipientCompulsoryRemittanceChecker{
           
    public function checkRequestEligible($user_profile_id, $recipient_id, RemittanceConfig $remittanceConfig)
    {//nothing to check for domestic
        $this->setPass();
        return $this;
    }

    public function check($user_profile_id, $recipient_id, RemittanceConfig $remittanceConfig)
    {
        $this->setPass();
        return $this;
    }
}