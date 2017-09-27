<?php

namespace Iapps\RemittanceService\RemittanceProfitSharing;

use Iapps\Common\Core\IappsBaseService;
use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\RemittanceService\Common\MessageCode;
use Iapps\Common\Helper\GuidGenerator;
use Iapps\Common\AuditLog\AuditLogAction;
use Iapps\RemittanceService\RemittanceProfitSharing\RemittanceProfitSharingParty;
use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\Microservice\AccountService\AccountService;
use Iapps\Common\Microservice\AccountService\User;

class RemittanceProfitSharingPartyService extends IappsBaseService{
    

    public function addProfitSharingParty(RemittanceProfitSharingParty $profitSharingParty)
    {   

        $this->getRepository()->startDBTransaction();

        if ( $this->getRepository()->insert($profitSharingParty) )
            return true;

        //roll back db trans
        $this->getRepository()->rollbackDBTransaction();
        return false;
    }

    public function getProfitSharingPartyList(RemittanceProfitSharingParty $profitSharingParty, $isArray = true)
    {   
        if( $object = $this->getRepository()->findAllByCorporateServProfitSharingId(new RemittanceProfitSharingPartyCollection(), $profitSharingParty) )
        {

            if( $object->result instanceof RemittanceProfitSharingPartyCollection )
            {
                $accountSer = AccountServiceFactory::build();
                $infos = $object->result;
                $results = array();
                
                $users = $accountSer->getUsers($infos->getFieldValues("corporate_id"));
                
                foreach ($infos as $info) {

                    if( !$users )
                        continue;
                    
                    if($userInfo = $users->getById($info->getCorporateId()) )
                    {
                        $result = $info->getSelectedField(array('id','percentage', 'corporate_id', 'corporate_service_profit_sharing_id', 'created_by_name','created_at', 'updated_at','updated_by','deleted_at','deleted_by'));
                        $result['parties_name'] = $userInfo->getName();

                        $results[] = $result;
                    }
                }

                if( $isArray )
                    return $results;
                else
                    return $infos;
            }
        }

        return false;
    }

}