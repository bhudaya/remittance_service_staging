<?php

namespace Iapps\RemittanceService\RemittanceRecord;

use Iapps\RemittanceService\Common\IncrementIDAttribute;
use Iapps\RemittanceService\Common\IncrementIDServiceFactory;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfig;

class RemittanceIDGenerator{

    public static function generate(RemittanceConfig $config)
    {
        $inc_serv = IncrementIDServiceFactory::build();
        if( $config->getRemittanceService()->isInternational() )
        {//generate normal ID
            //get by remittance company code
            if( $code = $config->getRemittanceCompany()->getCompanyCode() )
            {
                $incrementID = $inc_serv->getRawIncrementID($code . IncrementIDAttribute::REMITTANCE_ID);
                return date("Y") . date("m") . date("d") . $code . $incrementID;
            }

            return false;
        }
        else
        {//local trx ID
            return $inc_serv->getIncrementID(IncrementIDAttribute::LOCAL_REMITTANCE_ID);
        }
    }
}