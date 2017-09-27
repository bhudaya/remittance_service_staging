<?php

namespace Iapps\RemittanceService\SearchRemittanceRecord;

use Iapps\Common\Core\IappsBaseDataMapperV2;
use Iapps\RemittanceService\RemittanceRecord\RemittanceRecordCollection;

interface ISearchRemittanceRecordDataMapper extends IappsBaseDataMapperV2{
    
    public function findByFilters(RemittanceRecordCollection $filters, $limit = NULL, $page = NULL);
}

