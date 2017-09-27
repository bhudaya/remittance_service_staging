<?php

namespace Iapps\RemittanceService\Common;

use Iapps\Common\CoreConfigData\ICoreConfigDataMapper;
use Iapps\Common\CoreConfigData\CoreConfigData;

interface IRemittanceCoreConfigDataMapper extends ICoreConfigDataMapper{
    
    public function update(CoreConfigData $config);
}

