<?php

namespace Iapps\RemittanceService\Common;

use Iapps\Common\CoreConfigData\CoreConfigData;
use Iapps\Common\CoreConfigData\CoreConfigDataRepository;

class RemittanceCoreConfigDataRepository extends CoreConfigDataRepository{
    
    public function update(CoreConfigData $config)
    {
        return $this->getDataMapper()->update($config);
    }
}

