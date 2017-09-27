<?php

namespace Iapps\RemittanceService\SlaDashboard;
use Iapps\Common\Core\IappsBaseRepository;

class SlaDashboardTransactionRepository extends IappsBaseRepository{

    public function getSLAListPendingTransaction($channelFilter,$date_from,$date_to)
    {
        return $this->getDataMapper()->getSLAListPendingTransaction($channelFilter,$date_from,$date_to);
    }

    public function getSLATotalRemittanceTransactionStatus($channelFilter,$date_from,$date_to,$sla_remittance_on_time)
    {
        return $this->getDataMapper()->getSLATotalRemittanceTransactionStatus($channelFilter,$date_from,$date_to,$sla_remittance_on_time);
    }
    
    public function getAllPendingCollection($channelFilter)
    {
        return $this->getDataMapper()->getAllPendingCollection($channelFilter);
    }
    
    public function getOverTimeAndWaringTime($channelFilter,$now,$sla_remittance_on_time,$sla_remittance_warning_time)
    {
        return $this->getDataMapper()->getOverTimeAndWaringTime($channelFilter,$now,$sla_remittance_on_time,$sla_remittance_warning_time);
    }
    
}