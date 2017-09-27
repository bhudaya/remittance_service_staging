<?php

namespace Iapps\RemittanceService\Recipient;

use Iapps\RemittanceService\RemittanceCompany\RemittanceCompanyServiceFactory;
use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\RemittanceService\RemittanceCompanyRecipient\AgentRemittanceCompanyRecipientServiceFactory;
use Iapps\RemittanceService\Attribute\RecipientAttributeCollection;

class AgentRecipientService extends RecipientServiceV2{
	
	//upon adding recipient, it will add remco recipient record if agent is a remco agent
	public function addRecipient($recipient_id, $user_profile_id, $dialing_code, $mobile_number, $alias, $recipient_type, RecipientAttributeCollection $attributes ,$country_code, array $collection_option, $photo_image_url=null, $recipient_user_profile_id=null)
	{
		if( $recipientInfo = parent::addRecipient($recipient_id, $user_profile_id, $dialing_code, $mobile_number, $alias, $recipient_type, $attributes, $country_code, $collection_option, $photo_image_url, $recipient_user_profile_id) )
		{
			$remcoRecipientServ = AgentRemittanceCompanyRecipientServiceFactory::build();
			$remcoRecipientServ->setIpAddress($this->getIpAddress());
			$remcoRecipientServ->setUpdatedBy($this->getUpdatedBy());
			$remcoRecipientServ->addRecipient($recipientInfo['id'], $user_profile_id);
			return $recipientInfo;				
		}
		
		return false;
	}	
}