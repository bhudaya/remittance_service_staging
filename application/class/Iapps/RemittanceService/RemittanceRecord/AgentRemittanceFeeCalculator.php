<?php

namespace Iapps\RemittanceService\RemittanceRecord;

use Iapps\RemittanceService\RemittanceConfig\AgentRemittanceConfigServiceFactory;

class AgentRemittanceFeeCalculator extends RemittanceFeeCalculator{

    protected static function _getRemittanceConfigService()
    {
        return AgentRemittanceConfigServiceFactory::build();
    }

    protected function _getCalculator()
    {
        return new AgentRemittanceFeeCalculator();
    }
}