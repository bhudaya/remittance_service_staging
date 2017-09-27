<?php

namespace Iapps\RemittanceService\Common;

use Iapps\Common\CoreConfigData\CoreConfigDataService;
use Iapps\Common\CoreConfigData\CoreConfigData;
use Iapps\RemittanceService\Common\MessageCode;

class RemittanceCoreConfigDataService extends CoreConfigDataService
{
    public function updateCoreConfigs(array $configs)
    {
        $success = false;
        
        $this->getRepository()->startDBTransaction();
        foreach( $configs AS $config )
        {
            if( isset($config['unique_code']) AND
                isset($config['value']) )
            {
                $unique_code = $config['unique_code'];
                $value = $config['value'];
            
                //get by unique code
                if( $config = $this->getRepository()->findByCode($unique_code) AND
                    $config instanceof CoreConfigData)
                {
                    $config->setValue($value);
                    $config->setUpdatedBy($this->getUpdatedBy());
                    if( $this->getRepository()->update($config) )
                        $success = true;
                    else
                    {
                        $success = false;
                        break;
                    }
                }
                else
                {
                    $success = false;
                        break;
                }
            }
            else
            {
                $success = false;
                break;
            }                
        }
        
        if( $success )
        {
            $this->getRepository()->completeDBTransaction();
            $this->setResponseCode(MessageCode::CODE_UPDATE_SETTING_SUCCESS);
            return true;
        }
        
        $this->getRepository()->rollbackDBTransaction();
        $this->setResponseCode(MessageCode::CODE_UPDATE_SETTING_FAILED);
        return false;
    }
    
    public function updateRecipientSetting($max_recipient, $max_collection_info)
    {
        $configs = array();
        
        $config['unique_code'] = CoreConfigType::MAX_RECIPIENT;
        $config['value'] = $max_recipient;
        $configs[] = $config;
        
        $config['unique_code'] = CoreConfigType::MAX_COLLECTION_INFO;
        $config['value'] = $max_collection_info;
        $configs[] = $config;
        
        return $this->updateCoreConfigs($configs);
    }
    
    public function getRecipientSetting()
    {
        $max_recipient = $this->getConfig(CoreConfigType::MAX_RECIPIENT);
        $max_collection_info = $this->getConfig(CoreConfigType::MAX_COLLECTION_INFO);
                
        if( $max_recipient !== false AND
            $max_collection_info !== false ) 
        {
            $result['max_recipient'] = $max_recipient;
            $result['max_collection_info'] = $max_collection_info;
            
            $this->setResponseCode(MessageCode::CODE_GET_SETTING_SUCCESS);
            return $result;
        }
        
        $this->setResponseCode(MessageCode::CODE_GET_SETTING_FAILED);
        return false;
    }
}

