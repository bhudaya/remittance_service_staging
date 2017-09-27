<?php

namespace Iapps\RemittanceService\RemittanceConfig;

use Iapps\Common\Helper\MessageBroker\BroadcastEventConsumer;
use Iapps\Common\Helper\RequestHeader;
use Iapps\Common\Microservice\AccountService\AccountService;
use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\Common\Microservice\AccountService\UserUpline;
use Iapps\Common\Microservice\PaymentService\PaymentService;
use Iapps\Common\Microservice\RemittanceService\RemittanceTransaction;

class RemittanceUpdateAdminEmailListener extends BroadcastEventConsumer{

    protected function doTask($msg)
    {
        $this->setForceAcknowledgement(false);

        try{
            $data = json_decode($msg->body);

            if( $data->user_profile_id )
                return $this->_updateAdminEmail($data->user_profile_id);

            return true;
        } catch (\Exception $e){
            return false;
        }
    }

    public function listenEvent()
    {
        $this->listen("account.partner.user.created", NULL, 'remittance.queue.updateAdminEmail');
        $this->listen("account.partner.user.edited", NULL, 'remittance.queue.updateAdminEmail');
    }

    protected function _updateAdminEmail($user_profile_id)
    {
        $accountServ = AccountServiceFactory::build();
        $configServ = RemittanceConfigServiceFactory::build();
        $configServ->setIpAddress($this->getIpAddress());
        $configServ->setUpdatedBy($this->getUpdatedBy());

        //get all remittance config
        if( $configCollection = $configServ->getAllRemittanceConfig(MAX_VALUE, 1) )
        {
            if( $info = $accountServ->getUplineStructure($user_profile_id) )
            {
                $self = $info->self;
                $upline = $info->first_upline;
                if( $self instanceof UserUpline and
                    $upline instanceof UserUpline)
                {
                    if( $self->getRoles()->hasRole(array('remittance_officer')) )
                    {
                        foreach( $configCollection->result AS $config)
                        {
                            if( $config instanceof RemittanceConfig )
                            {
                                if( $config->getInCorporateService()->getServiceProviderId() == $upline->getUser()->getId() )
                                {//update email

                                    $config->removeApprovingNotificationEmailByUserId($self->getUser()->getId());
                                    $config->addApprovingNotificationEmail($self->getUser()->getEmail(), $self->getUser()->getId());

                                    //update config
                                    $configServ->updateRemittanceConfig($config);
                                }
                            }
                        }
                    }
                }
            }

        }

        return true;
    }
}