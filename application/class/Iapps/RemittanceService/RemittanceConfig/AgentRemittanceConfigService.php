<?php

namespace Iapps\RemittanceService\RemittanceConfig;

use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\RemittanceService\Common\CorporateServicePaymentModeExtendedServiceFactory;
use Iapps\RemittanceService\Common\MessageCode;

class AgentRemittanceConfigService extends RemittanceConfigService{

    public function getActiveChannel($fromCountryCode = NULL, $isArray = true ) {
        if ( $collection = $this->_getAllActiveChannel($fromCountryCode) )
        {
            $collection = $collection->getChannel(true);    //get only internatinal channel

            //filter channel of agnet's main agent only
            if( $mainAgent = $this->_getMainAgent() )
            {
                $collection = $collection->getByMainAgent($mainAgent);
                if( count($collection) > 0 )
                {
                    $bestRatesCollection = $collection->getLowestRateByRemittanceService();

                    $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_CONFIG_SUCCESS);
                    if( $isArray )
                        return $bestRatesCollection->getSelectedField(array('id', 'from_country_currency_code', 'to_country_currency_code', 'min_limit', 'max_limit', 'display_rate'));
                    else
                        return $bestRatesCollection;
                }
            }
        }

        $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_SERVICE_CONFIG_FAILED);
        return false;
    }

    /*
     * overwrite this function to only allow agent to get remittance config that under its main agent
     */
    public function getRemittanceConfigById($remittance_configuration_id) {

        if( $reConfig = parent::getRemittanceConfigById($remittance_configuration_id) )
        {
            if( $reConfig instanceof RemittanceConfig )
            {
                if( $mainAgent = $this->_getMainAgent() )
                {
                    if( $reConfig->serviceProviderBelongsTo($mainAgent->getId()) )
                    {
                        return $reConfig;
                    }
                }
            }

            $this->setResponseCode(MessageCode::CODE_GET_REMITTANCE_CONFIG_FAILED);
            return false;
        }

        return false;
    }

    protected function _getMainAgent()
    {
        $acc_serv = AccountServiceFactory::build();
        if( $structure = $acc_serv->getAgentUplineStructure() )
        {
            if( $upline = $structure->first_upline )
            {
                if( $upline->getRoles()->hasRole(array('main_agent')) )
                {
                    return $upline->getUser();
                }
            }

            if( $upline = $structure->second_upline )
            {
                if( $upline->getRoles()->hasRole(array('main_agent')) )
                {
                    return $upline->getUser();
                }
            }
        }

        return false;
    }
}