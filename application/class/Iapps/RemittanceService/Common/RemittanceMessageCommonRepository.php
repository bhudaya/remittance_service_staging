<?php

namespace Iapps\RemittanceService\Common;

use Iapps\Common\Core\Language;
use Iapps\Common\MessageCommon\MessageCommonRepository;

class RemittanceMessageCommonRepository extends MessageCommonRepository{

    protected $defaultCacheKey = CacheKey::REMITTANCE_MESSAGE_COMMON_ID;

    public function findByCode($code, Language $lang)
    {
        $cache_key = CacheKey::REMITTANCE_MESSAGE_COMMON_CODE_LANG . $code . $lang->getCode();
        if( !$result = $this->getElasticCache($cache_key) )
        {
            if( $result = parent::findByCode($code, $lang) )
            {
                $this->setElasticCache($cache_key, $result);
            }
        }

        return $result;
    }
}