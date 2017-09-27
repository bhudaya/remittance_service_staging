<?php

namespace Iapps\RemittanceService\Attribute;

use Iapps\RemittanceService\Common\MessageCode;
use Iapps\Common\AuditLog\AuditLogAction;
use Iapps\Common\Core\IappsBaseService;

class RemittanceAttributeService extends IappsBaseService{

    public function getRemittanceAttributeByCode($remittance_id, $attribute_code)
    {
        if( $user_attribute = $this->getRepository()->findByRemittanceId($remittance_id, $attribute_code) )
        {
            $this->setResponseCode(MessageCode::CODE_REMITTANCE_ATTRIBUTE_FOUND);
            return $user_attribute;
        }

        $this->setResponseCode(MessageCode::CODE_REMITTANCE_ATTRIBUTE_NOT_FOUND);
        return false;
    }

    public function getAllRemittanceAttribute($remittance_id)
    {
        if( $info = $this->getRepository()->findByRemittanceId($remittance_id) )
        {
            $this->setResponseCode(MessageCode::CODE_REMITTANCE_ATTRIBUTE_FOUND);
            return $info->result;
        }

        $this->setResponseCode(MessageCode::CODE_REMITTANCE_ATTRIBUTE_NOT_FOUND);
        return false;
    }

    public function setRemittanceAttribute($remittance_id, AttributeValue $attributeValue)
    {
        if( $info = $this->getRemittanceAttributeByCode($remittance_id, $attributeValue->getAttribute()->getCode()) )
        {
            $ref_attr = $info->result->current();
            $ori = NULL;
            if( $ref_attr instanceof RemittanceAttribute)
                $ori = clone($ref_attr);

            $ref_attr->setAttributeValueId($attributeValue->getId());
            $ref_attr->setValue($attributeValue->getValue());
            $ref_attr->setUpdatedBy($this->getUpdatedBy());

            //validate
            $v = RemittanceAttributeValidation::make($ref_attr);

            if( !$v->fails() )
            {
                if( $this->getRepository()->update($ref_attr) )
                {
                    //log
                    $this->fireLogEvent('iafb_remittance.remittance_attribute', AuditLogAction::UPDATE, $ref_attr->getId(), $ori);

                    $this->setResponseCode(MessageCode::CODE_REMITTANCE_ATTRIBUTE_ADD_SUCCESS);
                    return true;
                }
            }
        }
        else
        {//add attribute
            $attr_serv = AttributeServiceFactory::build();
            if( $attr = $attr_serv->getByCode($attributeValue->getAttribute()->getCode()) )
            {
                $attributeValue->setAttribute($attr);
                $ref_attr = RemittanceAttribute::create($remittance_id, $attributeValue);
                $ref_attr->setCreatedBy($this->getUpdatedBy());

                //validate
                $v = RemittanceAttributeValidation::make($ref_attr);
                if( !$v->fails() )
                {   
                    if( $this->getRepository()->insert($ref_attr) )
                    {
                        //log
                        $this->fireLogEvent('iafb_remittance.remittance_attribute', AuditLogAction::CREATE, $ref_attr->getId());

                        $this->setResponseCode(MessageCode::CODE_REMITTANCE_ATTRIBUTE_ADD_SUCCESS);
                        return true;
                    }
                }
            }
            else
            {
                $this->setResponseCode($attr_serv->getResponseCode());
                return false;
            }
        }

        $this->setResponseCode(MessageCode::CODE_REMITTANCE_ATTRIBUTE_ADD_FAILED);
        return false;
    }

    public function removeUserAttribute($remittance_id, $attribute_code)
    {
        if( $info = $this->getRemittanceAttributeByCode($remittance_id, $attribute_code) ) {
            $ref_attr = $info->result->current();
            $ori = NULL;
            if ($ref_attr instanceof RemittanceAttribute)
                $ori = clone($ref_attr);

            $ref_attr->setDeletedBy($this->getUpdatedBy());

            //validate
            $v = RemittanceAttributeValidation::make($ref_attr);

            if ($this->getRepository()->delete($ref_attr)) {
                //log
                $this->fireLogEvent('iafb_remittance.remittance_attribute', AuditLogAction::DELETE, $ref_attr->getId(), $ori);

                $this->setResponseCode(MessageCode::CODE_REMITTANCE_ATTRIBUTE_DELETE_SUCCESS);
                return true;
            }
        }

        $this->setResponseCode(MessageCode::CODE_REMITTANCE_ATTRIBUTE_DELETE_FAILED);
        return false;
    }
}