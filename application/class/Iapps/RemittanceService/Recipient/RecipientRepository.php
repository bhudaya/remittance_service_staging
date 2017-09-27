<?php

namespace Iapps\RemittanceService\Recipient;

use Iapps\Common\Core\IappsBaseRepository;
use Iapps\RemittanceService\Common\CacheKey;

class RecipientRepository extends IappsBaseRepository{

    protected $defaultCacheKey = CacheKey::RECIPIENT_ID;
    
    public function findByUserProfileId($user_profile_id)
    {
        return $this->getDataMapper()->findByUserProfileId($user_profile_id);
    }

    public function findByMobileNumber($user_profile_id, $dialing_code, $mobile_number)
    {
        return $this->getDataMapper()->findByMobileNumber($user_profile_id, $dialing_code, $mobile_number);
    }
    
    public function findByIds(array $ids)
    {
        $ids = array_unique($ids);
        list($result, $remaining_ids) = $this->_getListFromCache($ids, CacheKey::RECIPIENT_ID, new RecipientCollection());        
        if( count($remaining_ids) > 0)
        {                     
            $additional_result = $this->findByParam(new Recipient(), $remaining_ids, MAX_VALUE, 1);
            if( $additional_result )
            {
                $this->_setListToCache("id", $remaining_ids, $additional_result, CacheKey::RECIPIENT_ID);
                $result->combineCollection($additional_result->getResult());                
            }            
        }        
        
        if( count($result->getResult()) > 0 )
            return $result;
        
        return false;
    }
    
    public function findByRecipientUserProfileId($recipient_user_profile_id)
    {
        if( $recipient_user_profile_id == NULL )
            return false;
        
        $cacheKey = CacheKey::RECIPIENT_RECIPIENT_USER_PROFILE_ID . $recipient_user_profile_id;        
        if( !$result = $this->getElasticCache($cacheKey) )
        {
            $recipient = (new Recipient())->setRecipientUserProfileId($recipient_user_profile_id);
            $result = $this->findByParam($recipient, NULL, MAX_VALUE, 1);
            
            if( $result )
                $this->setElasticCache($cacheKey, $result);
        }
        
        return $result;
    }

    public function insert(Recipient $recipient)
    {
        $this->_removedCache($recipient);
        return $this->getDataMapper()->insert($recipient);
    }

    public function update(Recipient $recipient)
    {
        $this->_removedCache($recipient);
        return $this->getDataMapper()->update($recipient);
    }

    public function findByParam(Recipient $recipient, array $recipient_id_arr = NULL, $limit, $page)
    {
        return $this->getDataMapper()->findByParam($recipient, $recipient_id_arr, $limit, $page);
    }

    public function findByHashedMobileNumber($hashed_dialing_code, $hashed_mobile_number)
    {
        return $this->getDataMapper()->findByHashedMobileNumber($hashed_dialing_code, $hashed_mobile_number);
    }
    
    protected function _removedCache(Recipient $recipient)
    {
        $cacheKeys = array(
            CacheKey::RECIPIENT_ID . $recipient->getId(),
            CacheKey::REMITTANCE_RECIPIENT_LIST . $recipient->getUserProfileId(),
            CacheKey::REMITTANCE_RECIPIENT_DETAIL . $recipient->getId(),
            CacheKey::RECIPIENT_RECIPIENT_USER_PROFILE_ID . $recipient->getRecipientUserProfileId()
        );
        
        foreach ($cacheKeys as $cacheKey) {
            $this->deleteElastiCache($cacheKey);            
        }
    }

}