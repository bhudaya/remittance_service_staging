<?php

namespace Iapps\RemittanceService\RemittanceRecord;

use Iapps\Common\Core\IappsBaseRepository;
use Iapps\Common\Helper\RequestHeader;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\RemittanceService\RemittanceCompanyRecipient\AgentRemittanceCompanyRecipientServiceFactory;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfig;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfigCollection;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfigServiceFactory;
use Iapps\RemittanceService\RemittanceRecord\RecipientCompulsoryRemittanceCheckerFactory;

class AgentRemittanceRecordService extends RemittanceRecordService{

    protected $isNFF = false;
    
    function __construct(IappsBaseRepository $rp, $ipAddress='127.0.0.1', $updatedBy=NULL, RemittancePaymentInterface $interface = NULL)
    {
        parent::__construct($rp, $ipAddress, $updatedBy);
        $this->paymentInterface = new AgentRemittancePayment();
    }

    protected function _isRequestForPayment(RemittanceFeeCalculator $calculator)
    {
        return true;
    }
    
    protected function _getRecipientChecker(RemittanceConfig $remittanceConfig)
    {
        $checker = RecipientCompulsoryRemittanceCheckerFactory::build($remittanceConfig, true);
        $checker->setUpdatedBy($this->getUpdatedBy());
        $checker->setIpAddress($this->getIpAddress());
        return $checker;
    }

    protected function _completeAction(RemittanceRecord $remittanceRecord)
    {
        //set location coordinate
        $request_header = RequestHeader::get();
        $location = array_key_exists(ResponseHeader::FIELD_X_LOCATION, $request_header) ? $request_header[ResponseHeader::FIELD_X_LOCATION] : NULL;
        if($location != NULL) {
            $location_arr = explode( ',', $location );
            if(count($location_arr) == 2) {
                $remittanceRecord->setLat($location_arr[0]);
                $remittanceRecord->setLon($location_arr[1]);
            }
        }

        //check main agent validation
        if(!$mainAgent = $this->_getMainAgent()){
            return false;
        }

        $remConfigServ = RemittanceConfigServiceFactory::build();
        if (!$remConfig = $remConfigServ->getRemittanceConfigById($remittanceRecord->getRemittanceConfigurationId())) {
            return false;
        }

        if ($remConfig instanceof RemittanceConfig) {
            if($mainAgent->getId() != $remConfig->getCashInCorporateService()->getServiceProviderId()) {
                return false;
            }
        }

        return true;       
    }


    protected function _filterByServiceProvider(RemittanceConfigCollection $remConfigColl){

        if($mainAgent = $this->_getMainAgent()) {
            $filteredRemConfigColl = $remConfigColl->getByMainAgent($mainAgent);
            return $filteredRemConfigColl;
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