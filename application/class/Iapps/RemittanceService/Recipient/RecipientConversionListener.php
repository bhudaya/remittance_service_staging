<?php

namespace Iapps\RemittanceService\Recipient;

use Iapps\Common\Helper\MessageBroker\BroadcastEventConsumer;

class RecipientConversionListener extends BroadcastEventConsumer{

    protected function doTask($msg)
    {
        $data = json_decode($msg->body);

        try
        {
            $serv = new RecipientConversionService($this->getIpAddress()->getString(), $this->getUpdatedBy());
            $this->setForceAcknowledgement(false);

            return $serv->process($data->user_profile_id);
        }
        catch(\Exception $e)
        {
            return false;
        }
    }

    public function listenEvent()
    {
        $this->listen('account.user.created', NULL, 'remittance.queue.convertRecipient');
        $this->listen('account.user.verified', NULL, 'remittance.queue.convertRecipient');
    }
}