<?php

namespace Iapps\RemittanceService\RefundRequest;

use Iapps\Common\Core\IappsBaseDataMapper;
use Iapps\Common\Core\IappsDateTime;

interface IRefundRequestDataMapper extends IappsBaseDataMapper{

    public function insert(RefundRequest $record);
    public function update(RefundRequest $record);
    public function findByParam(RefundRequest $refund, array $created_by_arr, $limit, $page, IappsDateTime $date_from = NULL, IappsDateTime $date_to = NULL);

    public function TransBegin();
    public function TransCommit();
    public function TransStatus();
}