<?php

use Iapps\RemittanceService\Reports\RegulatoryReport\RegulatoryReportDataMapper;
use Iapps\RemittanceService\Reports\RegulatoryReport\RegulatoryReport;
use Iapps\Common\Core\IappsDateTime;
use Iapps\RemittanceService\Reports\RegulatoryReport\RegulatoryReportCollection;

class Regulatory_report_model extends Base_Model implements RegulatoryReportDataMapper{

    public function map(stdClass $data)
    {
        $entity = new RegulatoryReport();

        // if(isset($data->remittance_config_id)){
        //     $entity->setId($data->remittance_config_id);
        // }

        // if(isset($data->remittance_service_id)){
        //     $entity->setRemittanceServiceId($data->remittance_service_id);
        // }

        // if(isset($data->min_limit)){
        //     $entity->setMinLimit($data->min_limit);
        // }

        // if(isset($data->max_limit)){
        //     $entity->setMaxLimit($data->max_limit);
        // }

        // if(isset($data->step_amount)){
        //     $entity->setStepAmount($data->step_amount);
        // }

        // if(isset($data->cashin_corporate_service_id)){
        //     $entity->setCashinCorporateServiceId($data->cashin_corporate_service_id);
        // }

        // if(isset($data->cashout_corporate_service_id)){
        //     $entity->setCashoutCorporateServiceId($data->cashout_corporate_service_id);
        // }

        // if(isset($data->channel_id)){
        //     $entity->setChannelID($data->channel_id);
        // }

        // if(isset($data->is_default)){
        //     $entity->setIsDefault($data->is_default);
        // }

        // if(isset($data->status)){
        //     $entity->setStatus($data->status);
        // }

        // if(isset($data->approve_reject_remark)){
        //     $entity->setApproveRejectRemark($data->approve_reject_remark);
        // }

        // if(isset($data->approve_reject_at)){
        //     $entity->setApproveRejectAt(IappsDateTime::fromUnix($data->approve_reject_at));
        // }

        // if(isset($data->approve_reject_by)){
        //     $entity->setApproveRejectBy($data->approve_reject_by);
        // }


        // if( isset($data->created_at) )
        //     $entity->setCreatedAt(IappsDateTime::fromUnix($data->created_at));

        // if( isset($data->created_by) )
        //     $entity->setCreatedBy($data->created_by);

        // if( isset($data->updated_at) )
        //     $entity->setUpdatedAt(IappsDateTime::fromUnix($data->updated_at));

        // if( isset($data->updated_by) )
        //     $entity->setUpdatedBy($data->updated_by);

        // if( isset($data->deleted_at) )
        //     $entity->setDeletedAt(IappsDateTime::fromUnix($data->deleted_at));

        // if( isset($data->deleted_by) )
        //     $entity->setDeletedBy($data->deleted_by);

        return $entity;
    }

    public function findById($id, $deleted = false)
    {
        
    }
}