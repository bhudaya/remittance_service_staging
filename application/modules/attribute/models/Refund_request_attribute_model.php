<?php

use Iapps\RemittanceService\Attribute\RefundRequestAttributeDataMapper;
use Iapps\RemittanceService\Attribute\RefundRequestAttribute;
use Iapps\RemittanceService\Attribute\RefundRequestAttributeCollection;
use Iapps\Common\Core\IappsDateTime;
use Iapps\RemittanceService\ValueObject\EncryptedFieldFactory;

class Refund_request_attribute_model extends Base_Model
    implements RefundRequestAttributeDataMapper{

    public function map(\stdClass $data)
    {
        $encrypted_code = EncryptedFieldFactory::build();

        $entity = new RefundRequestAttribute();

        if( isset($data->refund_request_attribute_id) )
            $entity->setId($data->refund_request_attribute_id);

        if( isset($data->attribute_id) )
            $entity->getAttribute()->setId($data->attribute_id);

        if( isset($data->attribute_input_type) )
            $entity->getAttribute()->setInputType($data->attribute_input_type);

        if( isset($data->attribute_selection_only) )
            $entity->getAttribute()->setSelectionOnly($data->attribute_selection_only);

        if( isset($data->attribute_code) ){
            $entity->getAttribute()->setCode($data->attribute_code);
        }

        if( isset($data->attribute_name) )
            $entity->getAttribute()->setName($data->attribute_name);

        if( isset($data->attribute_description) )
            $entity->getAttribute()->setDescription($data->attribute_description);

        if( isset($data->refund_request_id) )
            $entity->setRefundRequestId($data->refund_request_id);

        if( isset($data->attribute_value_id) )
            $entity->setAttributeValueId($data->attribute_value_id);

        if( isset($data->other_value) ){
            $encrypted_code->setEncryptedValue($data->other_value);
            $entity->setValue($encrypted_code->getValue());
        }

        if( isset($data->created_at) )
            $entity->getCreatedAt()->setDateTimeUnix($data->created_at);

        if( isset($data->created_by) )
            $entity->setCreatedBy($data->created_by);

        if( isset($data->updated_at) )
            $entity->getUpdatedAt()->setDateTimeUnix($data->updated_at);

        if( isset($data->updated_by) )
            $entity->setUpdatedBy($data->updated_by);

        if( isset($data->deleted_at) )
            $entity->getDeletedAt()->setDateTimeUnix($data->deleted_at);

        if( isset($data->deleted_by) )
            $entity->setDeletedBy($data->deleted_by);

        return $entity;
    }

    public function findById($id, $deleted = false)
    {
        $this->db->select('v.id as refund_request_attribute_id,
                           v.attribute_id,
                           a.input_type attribute_input_type,
                           a.selection_only attribute_selection_only,
                           a.code attribute_code,
                           a.name attribute_name,
                           a.description attribute_description,
                           v.refund_request_id,
                           v.attribute_value_id,
                           v.other_value,
                           v.created_at,
                           v.created_by,
                           v.updated_at,
                           v.updated_by,
                           v.deleted_at,
                           v.deleted_by');
        $this->db->from('iafb_remittance.refund_request_attribute v');
        $this->db->join('iafb_remittance.attribute a', 'v.attribute_id = a.id');
        if( !$deleted )
            $this->db->where('v.deleted_at', NULL);
        $this->db->where('v.id', $id);

        $query = $this->db->get();
        if( $query->num_rows () > 0 )
        {
            return $this->map($query->row());
        }

        return false;
    }

    public function findByRefundRequestId($refund_request_id, $attribute_code = NULL)
    {
        $this->db->select('v.id as refund_request_attribute_id,
                           v.attribute_id,
                           a.input_type attribute_input_type,
                           a.selection_only attribute_selection_only,
                           a.code attribute_code,
                           a.name attribute_name,
                           a.description attribute_description,
                           v.refund_request_id,
                           v.attribute_value_id,
                           v.other_value,
                           v.created_at,
                           v.created_by,
                           v.updated_at,
                           v.updated_by,
                           v.deleted_at,
                           v.deleted_by');
        $this->db->from('iafb_remittance.refund_request_attribute v');
        $this->db->join('iafb_remittance.attribute a', 'v.attribute_id = a.id');
        $this->db->where('v.deleted_at', NULL);
        $this->db->where('v.refund_request_id', $refund_request_id);
        if( $attribute_code != NULL )
            $this->db->where('a.code', $attribute_code);

        $query = $this->db->get();
        if( $query->num_rows () > 0 )
        {
            return $this->mapCollection($query->result(), new RefundRequestAttributeCollection(), $query->num_rows());
        }

        return false;
    }

    public function insert(RefundRequestAttribute $ref_attr)
    {
        $encrypted_code = EncryptedFieldFactory::build();

        $this->db->set('id', $ref_attr->getId());
        $this->db->set('attribute_id', $ref_attr->getAttribute()->getId());
        $this->db->set('refund_request_id', $ref_attr->getRefundRequestId());
        $this->db->set('attribute_value_id', $ref_attr->getAttributeValueId());
        $this->db->set('other_value', $encrypted_code->setValue($ref_attr->getValue())->getEncodedValue());
        $this->db->set('hashed_other_value',$encrypted_code->setValue($ref_attr->getValue())->getHashedValue());
        $this->db->set('created_at', IappsDateTime::now()->getUnix());
        $this->db->set('created_by', $ref_attr->getCreatedBy());

        if( $this->db->insert('iafb_remittance.refund_request_attribute') )
        {
            return true;
        }

        return false;
    }

    public function update(RefundRequestAttribute $ref_attr)
    {
        $encrypted_code = EncryptedFieldFactory::build();
        $this->db->set('other_value', $encrypted_code->setValue($ref_attr->getValue())->getEncodedValue());
        $this->db->set('attribute_value_id', $ref_attr->getAttributeValueId());
        $this->db->set('hashed_other_value',$encrypted_code->setValue($ref_attr->getValue())->getHashedValue());
        $this->db->set('updated_at', IappsDateTime::now()->getUnix());
        $this->db->set('updated_by', $ref_attr->getUpdatedBy());

        $this->db->where('id', $ref_attr->getId());
        $this->db->update('iafb_remittance.refund_request_attribute');

        if( $this->db->affected_rows() > 0 )
        {
            return true;
        }

        return false;
    }

    public function delete(RefundRequestAttribute $refund_request_attribute)
    {
        $deleted_at = IappsDateTime::now()->getUnix();
        $this->db->set('deleted_at', $deleted_at);
        $this->db->set('deleted_by', $refund_request_attribute->getDeletedBy());
        $this->db->where('id', $refund_request_attribute->getId());

        if( $this->db->update('iafb_remittance.refund_request_attribute') )
        {
            $refund_request_attribute->getDeletedAt()->setDateTimeUnix($deleted_at);
            return true;
        }

        return false;
    }
}