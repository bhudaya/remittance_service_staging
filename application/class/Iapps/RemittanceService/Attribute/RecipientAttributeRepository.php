<?php

namespace Iapps\RemittanceService\Attribute;

use Iapps\Common\Core\IappsBaseRepository;
use Iapps\RemittanceService\Common\CacheKey;
use Iapps\Common\Core\PaginatedResult;
use Iapps\RemittanceService\Attribute\RecipientAttributeCollection;

class RecipientAttributeRepository extends IappsBaseRepository{

    public function findByRecipientId($recipient_id, $attribute_code = NULL)
    {
        if( !$attribute_code )
        {//use cache
            $cacheKey = CacheKey::RECIPIENT_ATTRIBUTE_RECIPIENT_ID . $recipient_id;
            if( !$result = $this->getElasticCache($cacheKey) )
            {
                $result = $this->getDataMapper()->findByRecipientId($recipient_id, $attribute_code);
                if( $result )
                    $this->setElasticCache($cacheKey, $result);
            }
            
            return $result;
        }
        return $this->getDataMapper()->findByRecipientId($recipient_id, $attribute_code);
    }

    public function findByRecipientIds(array $recipient_ids)
    {
        //try to get from cache
        list($result, $remaining_ids) = $this->_getListFromCache($recipient_ids, CacheKey::RECIPIENT_ATTRIBUTE_RECIPIENT_ID, new RecipientAttributeCollection());        
        
        if( count($remaining_ids) > 0)
        {
            $additional_result = $this->getDataMapper()->findByRecipientIds($remaining_ids);
            if( $additional_result )
            {
                $this->_setListToCacheAsPaginatedResult("recipient_id", $remaining_ids, $additional_result, CacheKey::RECIPIENT_ATTRIBUTE_RECIPIENT_ID);
                $result->combineCollection($additional_result->getResult());                
            }
        }

        if( count($result->getResult()) > 0)
            return $result;
        
        return false;
    }

    public function insert(RecipientAttribute $user_attribute)
    {
        $this->_removeCache($user_attribute);
        return $this->getDataMapper()->insert($user_attribute);
    }

    public function update(RecipientAttribute $user_attribute)
    {
        $this->_removeCache($user_attribute);
        return $this->getDataMapper()->update($user_attribute);
    }

    public function delete(RecipientAttribute $user_attribute)
    {
        $this->_removeCache($user_attribute);
        return $this->getDataMapper()->delete($user_attribute);
    }
    
    protected function _removeCache(RecipientAttribute $user_attribute)
    {
        $cacheKeys = array(CacheKey::RECIPIENT_ATTRIBUTE_RECIPIENT_ID . $user_attribute->getRecipientId());
        foreach( $cacheKeys AS $key)
        {
            $this->deleteElastiCache($key);
        }
    }
}