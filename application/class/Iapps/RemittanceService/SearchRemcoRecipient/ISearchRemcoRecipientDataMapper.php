<?php

namespace Iapps\RemittanceService\SearchRemcoRecipient;

use Iapps\Common\Core\IappsBaseDataMapperV2;
use Iapps\RemittanceService\RemittanceCompanyRecipient\RemittanceCompanyRecipientCollection;
use Iapps\RemittanceService\Recipient\RecipientCollection;
use Iapps\RemittanceService\Attribute\RecipientAttributeCollection;

interface ISearchRemcoRecipientDataMapper extends IappsBaseDataMapperV2{
    
    public function findByRecipientsAttributes(RemittanceCompanyRecipientCollection $remcoRecipientFilters,
                                               RecipientCollection $recipientFilters = NULL,
                                               RecipientAttributeCollection $attributeFilters = NULL);
}
