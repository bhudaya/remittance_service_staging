<?php

namespace Iapps\RemittanceService\SearchRemcoRecipient;

use Iapps\Common\Core\IappsBaseRepository;
use Iapps\RemittanceService\RemittanceCompanyRecipient\RemittanceCompanyRecipientCollection;
use Iapps\RemittanceService\Recipient\RecipientCollection;
use Iapps\RemittanceService\Attribute\RecipientAttributeCollection;

class SearchRemcoRecipientRepository extends IappsBaseRepository{
    
    public function findByRecipientsAttributes(RemittanceCompanyRecipientCollection $remcoRecipientFilters,
                                               RecipientCollection $recipientFilters = NULL,
                                               RecipientAttributeCollection $attributeFilters = NULL){
        return $this->getDataMapper()->findByRecipientsAttributes($remcoRecipientFilters, $recipientFilters, $attributeFilters);
    }
}
