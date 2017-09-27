<?php

namespace Iapps\RemittanceService\RemittanceProfitSharing;

use Iapps\Common\Core\IappsBaseDataMapper;

interface RemittanceCorpServProfitSharingDataMapper extends IappsBaseDataMapper
{
    public function update(RemittanceCorpServProfitSharing $corp_serv_profit_sharing);
    public function insert(RemittanceCorpServProfitSharing $corp_serv_profit_sharing);
    public function checkHasOtherPendingProfitSharing($corporate_service_id);
    public function findAllByStatus($collection, $limit, $page, $is_active = NULL, $status = NULL, $corporate_service_id = NULL);
    public function findByParam(RemittanceCorpServProfitSharing $corp_serv_profit_sharing, $limit = NULL, $page = NULL);
    public function findAllList($limit, $page, array $corporateServiceIds = NULL, $isActive = NULL, array $status = NULL);
}