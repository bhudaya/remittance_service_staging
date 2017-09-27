<?php

namespace Iapps\RemittanceService\Attribute;

use Iapps\RemittanceService\Common\MessageCode;
use Iapps\Common\AuditLog\AuditLogAction;
use Iapps\Common\Core\IappsBaseService;

class RecipientAttributeService extends IappsBaseService{

    public function getRecipientAttributeByCode($recipient_id, $attribute_code)
    {
        if( $user_attribute = $this->getRepository()->findByRecipientId($recipient_id, $attribute_code) )
        {
            $this->setResponseCode(MessageCode::CODE_RECIPIENT_ATTRIBUTE_FOUND);
            return $user_attribute;
        }

        $this->setResponseCode(MessageCode::CODE_RECIPIENT_ATTRIBUTE_NOT_FOUND);
        return false;
    }

    public function getAllRecipientAttribute($recipient_id)
    {
        if( $info = $this->getRepository()->findByRecipientId($recipient_id) )
        {
            $this->setResponseCode(MessageCode::CODE_RECIPIENT_ATTRIBUTE_FOUND);
            return $info->result;
        }

        $this->setResponseCode(MessageCode::CODE_RECIPIENT_ATTRIBUTE_NOT_FOUND);
        return false;
    }

    public function getByRecipientIds(array $recipientIds)
    {
        if( $info = $this->getRepository()->findByRecipientIds($recipientIds) )
        {
            $this->setResponseCode(MessageCode::CODE_RECIPIENT_ATTRIBUTE_FOUND);
            return $info->result;
        }

        $this->setResponseCode(MessageCode::CODE_RECIPIENT_ATTRIBUTE_NOT_FOUND);
        return false;
    }

    public function setRecipientAttribute($recipient_id, AttributeValue $attributeValue)
    {
        //populate attribute value by id
        if( !$attributeValue->getValue() )
        {
            $attrvalServ = AttributeValueServiceFactory::build();
            if( !$value = $attrvalServ->getValueById($attributeValue) )
            {
                $this->setResponseCode(MessageCode::CODE_RECIPIENT_ATTRIBUTE_ADD_FAILED);
                return false;
            }
        }

        if( $info = $this->getRecipientAttributeByCode($recipient_id, $attributeValue->getAttribute()->getCode()) )
        {
            $ref_attr = $info->result->current();
            $ori = NULL;
            if( $ref_attr instanceof RecipientAttribute)
                $ori = clone($ref_attr);

            $ref_attr->setAttributeValueId($attributeValue->getId());
            $ref_attr->setValue($attributeValue->getValue());
            $ref_attr->setUpdatedBy($this->getUpdatedBy());

            //validate
            $v = RecipientAttributeValidation::make($ref_attr);

            if( !$v->fails() )
            {
                if( $this->getRepository()->update($ref_attr) )
                {
                    //log
                    $this->fireLogEvent('iafb_remittance.recipient_attribute', AuditLogAction::UPDATE, $ref_attr->getId(), $ori);

                    $this->setResponseCode(MessageCode::CODE_RECIPIENT_ATTRIBUTE_ADD_SUCCESS);
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
                $ref_attr = RecipientAttribute::create($recipient_id, $attributeValue);
                $ref_attr->setCreatedBy($this->getUpdatedBy());

                //validate
                $v =RecipientAttributeValidation::make($ref_attr);
                if( !$v->fails() )
                {   
                    if( $this->getRepository()->insert($ref_attr) )
                    {
                        //log
                        $this->fireLogEvent('iafb_remittance.recipient_attribute', AuditLogAction::CREATE, $ref_attr->getId());

                        $this->setResponseCode(MessageCode::CODE_RECIPIENT_ATTRIBUTE_ADD_SUCCESS);
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

        $this->setResponseCode(MessageCode::CODE_RECIPIENT_ATTRIBUTE_ADD_FAILED);
        return false;
    }

    public function removeUserAttribute($recipient_id, $attribute_code)
    {
        if( $info = $this->getRecipientAttributeByCode($recipient_id, $attribute_code) ) {
            $ref_attr = $info->result->current();
            $ori = NULL;
            if ($ref_attr instanceof RecipientAttribute)
                $ori = clone($ref_attr);

            $ref_attr->setDeletedBy($this->getUpdatedBy());

            //validate
            $v = RecipientAttributeValidation::make($ref_attr);

            if ($this->getRepository()->delete($ref_attr)) {
                //log
                $this->fireLogEvent('iafb_remittance.recipient_attribute', AuditLogAction::DELETE, $ref_attr->getId(), $ori);

                $this->setResponseCode(MessageCode::CODE_RECIPIENT_ATTRIBUTE_DELETE_SUCCESS);
                return true;
            }
        }

        $this->setResponseCode(MessageCode::CODE_RECIPIENT_ATTRIBUTE_DELETE_FAILED);
        return false;
    }
}