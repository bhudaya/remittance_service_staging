<?php

namespace Iapps\RemittanceService\RecipientCollectionInfo;

use Iapps\Common\Core\IappsBaseRepository;
use Iapps\RemittanceService\Common\CacheKey;

class RecipientCollectionInfoRepository extends IappsBaseRepository{

    public function insert(RecipientCollectionInfo $info)
    {
    	$this->_removeCache($info);
        return $this->getDataMapper()->insert($info);
    }

    public function update(RecipientCollectionInfo $info)
    {
    	$this->_removeCache($info);
        return $this->getDataMapper()->update($info);
    }

    public function findByRecipientId($recipient_id)
    {
    	$cacheKey = CacheKey::REMITTANCE_RECIPIENT_COLLECTION_INFO_RECIPIENTID . $recipient_id;
		
		if( !$result = $this->getElasticCache($cacheKey) )
		{
			if( $result = $this->getDataMapper()->findByRecipientId($recipient_id) )
			{
				$this->setElasticCache($cacheKey, $result);
			}
		}
		
		return $result;        
    }

    public function findByRecipientIds(array $recipient_ids)
    {
        return $this->getDataMapper()->findByRecipientIds($recipient_ids);
    }

    public function findbyParm(RecipientCollectionInfo $info)
    {
        return $this->getDataMapper()->findbyParm($info);
    }
	
	protected function _removeCache(RecipientCollectionInfo $info)
	{
		$cacheKeys = array(
			CacheKey::REMITTANCE_RECIPIENT_COLLECTION_INFO_RECIPIENTID . $info->getRecipientId()
		);
		
		foreach($cacheKeys AS $cacheKey)
		{
			$this->deleteElastiCache($cacheKey);
		}
	}
}