<?php

namespace Iapps\RemittanceService\RemittanceCompanyRecipient;

use Iapps\Common\Helper\MessageBroker\BroadcastEventProducer;

class RemittanceCompanyRecipientEventProducer extends BroadcastEventProducer{

    protected $recipient_id;
    protected $service_provider_id;

    public function setRecipientId($recipient_id)
    {
        $this->recipient_id = $recipient_id;
        return $this;
    }

    public function getRecipientId()
    {
        return $this->recipient_id;
    }
    
    public function setServiceProviderId($service_provider_id)
    {
        $this->service_provider_id = $service_provider_id;
        return $this;
    }

    public function getServiceProviderId()
    {
        return $this->service_provider_id;
    }


    public function getMessage()
    {
        $temp['recipient_id'] = $this->getRecipientId();
        $temp['service_provider_id'] = $this->getServiceProviderId();
        return json_encode($temp);
    }

    public static function publishRecipientCreated($recipient_id, $service_provider_id)
    {
        $e = new RemittanceCompanyRecipientEventProducer();
		$e->setRecipientId($recipient_id);
        $e->setServiceProviderId($service_provider_id);

        return $e->trigger(RemittanceCompanyRecipientEventType::RECIPIENT_CREATED, NULL, $e->getMessage());
    }
}