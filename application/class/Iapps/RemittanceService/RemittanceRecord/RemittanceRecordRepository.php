<?php

namespace Iapps\RemittanceService\RemittanceRecord;

use Iapps\Common\Core\IappsBaseRepository;

class RemittanceRecordRepository extends IappsBaseRepository{

    public function findRemittanceTransactionList($limit, $page, $start_time = null, $end_time = null, $prelim_check = null, $status = null)
    {
        return $this->getDataMapper()->findRemittanceTransactionList($limit, $page, $start_time, $end_time, $prelim_check, $status);
    }

    public function findByOutTransactionId($outTransaction_id)
    {
        return $this->getDataMapper()->findByOutTransactionId($outTransaction_id);
    }
    
    public function findByInTransactionId($inTransaction_id)
    {
        return $this->getDataMapper()->findByInTransactionId($inTransaction_id);
    }

    public function findByRemittanceID($remittanceID)
    {
        return $this->getDataMapper()->findByRemittanceID($remittanceID);
    }

    public function findByTransactionIDArray(array $transactionID_arr){
        return $this->getDataMapper()->findByTransactionIDArray($transactionID_arr);
    }

    public function insert(RemittanceRecord $record)
    {
        return $this->getDataMapper()->insert($record);
    }

    public function updateStatus(RemittanceRecord $record)
    {
        return $this->getDataMapper()->updateStatus($record);
    }

    public function update(RemittanceRecord $record)
    {
        return $this->getDataMapper()->update($record);
    }

    public function updateRequestCollectionId(RemittanceRecord $remittanceRecord)
    {
        return $this->getDataMapper()->updateRequestCollectionId($remittanceRecord);
    }
    
    public function findByParam(RemittanceRecord $record, $limit, $page, array $recipient_id_arr = NULL, array $remittance_config_ids = NULL, $start_time = NULL, $end_time = NULL, $prelim_check = NULL, $status = NULL)
    {
        return $this->getDataMapper()->findByParam($record, $limit, $page, $recipient_id_arr, $remittance_config_ids, $start_time, $end_time, $prelim_check, $status);
    }

    public function reportFindByParam(RemittanceRecord $record ,$start_time = NULL, $end_time = NULL)
    {
        return $this->getDataMapper()->reportFindByParam($record, $start_time, $end_time);
    }
    
    public function getByPrimaryKey(RemittanceRecord $record)
    {
        return $this->getDataMapper()->findById($record->getId());
    }    
    
    public function findSenderRemittanceInfo(RemittanceRecord $record, $status, $start_time, $end_time)
    {
        return $this->getDataMapper()->findSenderRemittanceInfo($record, $status, $start_time, $end_time);
    }  

    public function findRecipintRemittanceInfo($status, $recipient_id_arr, $start_time, $end_time)
    {
        return $this->getDataMapper()->findRecipintRemittanceInfo($status, $recipient_id_arr, $start_time, $end_time);
    }  
    
    
}