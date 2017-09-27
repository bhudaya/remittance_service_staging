<?php

namespace Iapps\RemittanceService\RefundRequest;

use Iapps\Common\Core\IappsBaseRepository;
use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\Helper\IappsEvent;
use Iapps\Common\Helper\IappsEventDispatcher;

class RefundRequestRepository extends IappsBaseRepository{

    public function insert(RefundRequest $refund)
    {
        return $this->getDataMapper()->insert($refund);
    }

    public function update(RefundRequest $refund)
    {
        return $this->getDataMapper()->update($refund);
    }

    public function findByParam(RefundRequest $refund, array $created_by_arr, $limit, $page, IappsDateTime $date_from = NULL, IappsDateTime $date_to = NULL)
    {
        return $this->getDataMapper()->findByParam($refund, $created_by_arr, $limit, $page, $date_from, $date_to);
    }


    public function startDBTransaction($handle_transaction = true)
    {
        if($handle_transaction) {
            $result = $this->getDataMapper()->TransStart();
            IappsEventDispatcher::get()->dispatch(IappsEvent::DB_STARTED);
            return $result;
        }

        return true;
    }

    public function completeDBTransaction($handle_transaction = true)
    {
        if($handle_transaction) {
            $result = $this->getDataMapper()->TransComplete();
            IappsEventDispatcher::get()->dispatch(IappsEvent::DB_COMPLETED);
            return $result;
        }

        return true;
    }

    public function rollbackDBTransaction($handle_transaction = true)
    {
        if($handle_transaction) {
            $result = $this->getDataMapper()->TransRollback();
            IappsEventDispatcher::get()->dispatch(IappsEvent::DB_ROLLEDBACK);
            return $result;
        }

        return true;
    }


    public function beginDBTransaction()
    {
        return $this->getDataMapper()->TransBegin();
    }

    public function commitDBTransaction()
    {
        return $this->getDataMapper()->TransCommit();
    }

    public function statusDBTransaction()
    {
        return $this->getDataMapper()->TransStatus();
    }
}