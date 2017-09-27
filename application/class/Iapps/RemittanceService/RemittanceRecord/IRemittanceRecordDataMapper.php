<?php

namespace Iapps\RemittanceService\RemittanceRecord;

use Iapps\Common\Core\IappsBaseDataMapper;

interface IRemittanceRecordDataMapper extends IappsBaseDataMapper{

    public function findRemittanceTransactionList($limit, $page, $start_time = null, $end_time = null, $prelim_check = null, $status = null);

    public function findByInTransactionId($inTransaction_id);
    public function findByOutTransactionId($outTransaction_id);
    public function findByRemittanceID($remittanceID);
    public function insert(RemittanceRecord $record);
    public function updateStatus(RemittanceRecord $record);
    public function update(RemittanceRecord $record);
    public function updateRequestCollectionId(RemittanceRecord $remittanceRecord);
    public function findByParam(RemittanceRecord $record, $limit, $page, $recipient_id_arr = NULL);
    public function reportFindByParam(RemittanceRecord $record ,$start_time = NULL, $end_time = NULL);
}