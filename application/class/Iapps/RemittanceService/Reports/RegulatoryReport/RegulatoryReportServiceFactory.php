<?php

namespace Iapps\RemittanceService\Reports\RegulatoryReport;

require_once __DIR__ . '/../../../../modules/regulatoryreport/models/Regulatory_report_model.php';

class RegulatoryReportServiceFactory{

    protected static $_instance = NULL;

    public static function build()
    {
        if( RegulatoryReportServiceFactory::$_instance == NULL )
        {
            $dm = new \Regulatory_report_model();
            $repo = new RegulatoryReportRepository($dm);
            RegulatoryReportServiceFactory::$_instance = new RegulatoryReportService($repo);
        }

        return RegulatoryReportServiceFactory::$_instance;
    }
}