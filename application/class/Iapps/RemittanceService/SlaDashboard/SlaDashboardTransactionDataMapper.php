<?php

namespace Iapps\RemittanceService\SlaDashboard;

use Iapps\Common\Core\IappsBaseDataMapper;

interface SlaDashboardTransactionDataMapper extends IappsBaseDataMapper{

    public function getSLAListPendingTransaction($channelFilter,$date_from,$date_to);
    public function getSLATotalRemittanceTransactionStatus($channelFilter,$date_from,$date_to,$sla_remittance_on_time);
    public function getAllPendingCollection($channelFilter);
    public function getOverTimeAndWaringTime($channelFilter,$now,$sla_remittance_on_time,$sla_remittance_warning_time);

}