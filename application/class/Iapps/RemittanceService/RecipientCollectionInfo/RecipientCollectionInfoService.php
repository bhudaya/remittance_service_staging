<?php

namespace Iapps\RemittanceService\RecipientCollectionInfo;

use Iapps\Common\AuditLog\AuditLogAction;
use Iapps\Common\Core\IappsBaseService;
use Iapps\Common\Helper\GuidGenerator;
use Iapps\RemittanceService\Common\MessageCode;
use Iapps\RemittanceService\Recipient\Recipient;
use Iapps\RemittanceService\ValueObject\EncryptedFieldFactory;
use Iapps\RemittanceService\Common\CoreConfigDataServiceFactory;
use Iapps\RemittanceService\Common\CoreConfigType;


class RecipientCollectionInfoService extends IappsBaseService{

    public function getByRecipientId($recipient_id)
    {
        if( $info = $this->getRepository()->findByRecipientId($recipient_id) )
        {
            $info->result->mapAccountNo();
            return $info->result;
        }

        return false;
    }

    public function getByRecipientIds(array $recipient_ids)
    {
        if( $info = $this->getRepository()->findByRecipientIds($recipient_ids) )
        {
            $info->result->mapAccountNo();
            return $info->result;
        }

        return false;
    }

    public function findById($collection_info_id)
    {
        if( $info = $this->getRepository()->findById($collection_info_id) )
        {
            $info->mapAccountNo();
            return $info;
        }

        return false;
    }

    /*
     * This should just add collection info, check if the collection info exists
     */
    public function addRecipientCollectionInfo(Recipient $recipient, $country_code, array $option)
    {
        $collectionInfo = new RecipientCollectionInfo();
        $collectionInfo->setId(GuidGenerator::generate());
        $collectionInfo->setRecipientId($recipient->getId());
        $collectionInfo->setCountryCode($country_code);
        $collectionInfo->getOption()->setValue(json_encode($option));
        $collectionInfo->setCreatedBy($this->getUpdatedBy());

		//validate limits
		if( !$this->_checkCollectionInfoLimit($recipient) )
			return false;
		
        if( !$this->_checkExists($recipient, $collectionInfo) )
            return false;

        if ($this->getRepository()->insert($collectionInfo)) {
            return true;
        }

        return false;
    }

    public function editRecipientCollectionInfo($collection_info_id, $country_code, array $option)
    {
        if( $collectionInfo = $this->getRepository()->findById($collection_info_id) )
        {
            if( $collectionInfo instanceof RecipientCollectionInfo )
            {
                $collectionInfo->setCountryCode($country_code);
                $collectionInfo->getOption()->setValue(json_encode($option));
                $collectionInfo->setUpdatedby($this->getUpdatedBy());

                $recipient = new Recipient();
                $recipient->setId($collectionInfo->getRecipientId());
                if( !$this->_checkExists($recipient, $collectionInfo) )
                    return false;

                if ($this->getRepository()->update($collectionInfo)) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function _checkExists(Recipient $recipient, RecipientCollectionInfo $collectionInfo)
    {
        if( $info = $this->getRepository()->findByRecipientId($recipient->getId()) )
        {//check if the collection info exists, if yes, return false, otherwise, add a new collection info
            $collectionInfoCol = $info->result;

            if( $collectionInfoCol->exists($collectionInfo) )
                return false;
        }

        return true;
    }
	
	protected function _checkCollectionInfoLimit(Recipient $recipient)
	{
		$configServ = CoreConfigDataServiceFactory::build();
		if( $limit = $configServ->getConfig(CoreConfigType::MAX_COLLECTION_INFO) AND $limit>0 )
		{
			if( $info = $this->getRepository()->findByRecipientId($recipient->getId()) )
        	{//check if the collection info exceed max limit
            	$collection = $info->result;
				if( count($collection) >= $limit )
				{//reaching limit
					$this->setResponseCode(MessageCode::CODE_MAX_LIMIT_REACHED);
					return false;
				}
			}
		}

        return true;
	}
}