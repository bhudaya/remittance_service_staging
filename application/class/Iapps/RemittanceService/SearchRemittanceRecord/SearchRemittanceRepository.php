<?php

namespace Iapps\RemittanceService\SearchRemittanceRecord;

use Iapps\Common\Core\IappsBaseRepository;
use Iapps\RemittanceService\RemittanceRecord\RemittanceRecordCollection;
use Iapps\Common\Core\IappsDateTime;

class SearchRemittanceRepository extends IappsBaseRepository{
    
    public function findByFilters(RemittanceRecordCollection $filters, $limit = NULL, $page = NULL)
    {
        return $this->getDataMapper()->findByFilters($filters, $limit, $page);
    }
}

