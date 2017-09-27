<?php

namespace Iapps\RemittanceService\RemittanceCorporateService;

use Iapps\Common\CorporateService\CorporateService;
use Iapps\Common\CorporateService\ICorporateServiceMapper;

interface RemittanceCorporateServMapper extends ICorporateServiceMapper
{
    public function findByIds(array $corporate_service_ids);
    public function findByServiceProviderIds(array $service_provider_ids);
    public function getCorporateServiceByServiceProId($service_provider_id);
}