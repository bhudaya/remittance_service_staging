<?php

namespace Iapps\RemittanceService\Recipient;

use Iapps\Common\Helper\MessageBroker\BroadcastEventProducer;

class RecipientEventProducer extends BroadcastEventProducer{

    protected $recipient_id;

    public function setRecipientId($recipient_id)
    {
        $this->recipient_id = $recipient_id;
        return $this;
    }

    public function getRecipientId()
    {
        return $this->recipient_id;
    }

    public function getMessage()
    {
        $temp['recipient_id'] = $this->getRecipientId();
        return json_encode($temp);
    }

    public static function publishRecipientCreated($recipient_id)
    {
        $e = new RecipientEventProducer();
		$e->setRecipientId($recipient_id);

        return $e->trigger(RecipientEventType::RECIPIENT_CREATED, NULL, $e->getMessage());
    }

	public static function publishRecipientChanged($recipient_id)
    {
        $e = new RecipientEventProducer();
		$e->setRecipientId($recipient_id);

        return $e->trigger(RecipientEventType::RECIPIENT_CHANGED, NULL, $e->getMessage());
    }
}