<?php

namespace Iapps\RemittanceService\RemittanceTransaction;

use Iapps\Common\Helper\MessageBroker\BroadcastWithKeyEventProducer;

class RemittanceTransactionEventBroadcastWithKeyProducer extends BroadcastWithKeyEventProducer{

    protected $module_code;
    protected $transactionID;
    protected $status;

    public function setTransactionID($transactionID)
    {
        $this->transactionID = $transactionID;
        return $this;
    }

    public function getTransactionID()
    {
        return $this->transactionID;
    }

    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setModuleCode($module_code)
    {
        $this->module_code = $module_code;
        return $this;
    }

    public function getModuleCode()
    {
        return $this->module_code;
    }

    public function getMessage()
    {
        $temp['transactionID'] = $this->getTransactionID();
        $temp['status'] = $this->getStatus();
        $temp['module_code'] = $this->getModuleCode();
        return json_encode($temp);
    }

    public static function publishTransactionStatusChanged($transactionID, $status)
    {
        $e = new RemittanceTransactionEventBroadcastWithKeyProducer();

        $e->setModuleCode(getenv('MODULE_CODE'));
        $e->setTransactionID($transactionID);
        $e->setStatus($status);

        return $e->trigger(RemittanceTransactionEventType::REMITTANCE_TRANSACTION_STATUS_CHANGED, $status, $e->getMessage());
    }
}