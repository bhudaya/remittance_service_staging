<?php

namespace Iapps\RemittanceService\RemittanceRecord;

use Iapps\RemittanceService\RemittanceConfig\RemittanceConfig;

class RecipientCompulsoryRemittanceCheckerFactory{

    public static function build(RemittanceConfig $config, $for_agent = false)
    {
        if( $config->getRemittanceService()->isDomestic() )
        {
            return new LocalRecipientCompulsoryRemittanceChecker();
        }
        else
        {
            if( !$for_agent )
            {
                return new RecipientCompulsoryRemittanceChecker();                 
            }
            else 
            {
                return new AgentRecipientCompulsoryRemittanceChecker(); 
            }
        }            
    }
}