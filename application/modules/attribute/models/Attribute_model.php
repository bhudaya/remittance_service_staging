<?php


use Iapps\RemittanceService\Attribute\IAttributeDataMapper;
use Iapps\RemittanceService\Attribute\Attribute;
use Iapps\Common\Core\IappsDateTime;
use Iapps\RemittanceService\Attribute\AttributeCollection;

class Attribute_model extends Base_Model
                      implements IAttributeDataMapper{

    public function map(\stdClass $data)
    {
        $entity = new Attribute();

        if( isset($data->attribute_id) )
            $entity->setId($data->attribute_id);

        if( isset($data->input_type) )
            $entity->setInputType($data->input_type);

        if( isset($data->selection_only) )
            $entity->setSelectionOnly($data->selection_only);

        if( isset($data->code) )
            $entity->setCode($data->code);

        if( isset($data->name) )
            $entity->setName($data->name);

        if( isset($data->description) )
            $entity->setDescription($data->description);

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
        $this->db->select('id as attribute_id,
                           input_type,
                           selection_only,
                           code,
                           name,
                           description,
                           created_at,
                           created_by,
                           updated_at,
                           updated_by,
                           deleted_at,
                           deleted_by');
        $this->db->from('iafb_remittance.attribute');
        if( !$deleted )
            $this->db->where('deleted_at', NULL);
        $this->db->where('id', $id);

        $query = $this->db->get();
        if( $query->num_rows() > 0 )
        {
            return $this->map($query->row());
        }

        return false;
    }

    public function findAll()
    {
        $this->db->select('id as attribute_id,
                           input_type,
                           selection_only,
                           code,
                           name,
                           description,
                           created_at,
                           created_by,
                           updated_at,
                           updated_by,
                           deleted_at,
                           deleted_by');
        $this->db->from('iafb_remittance.attribute');
        $this->db->where('deleted_at', NULL);

        $query = $this->db->get();
        if( $query->num_rows() > 0 )
        {
            return $this->mapCollection($query->result(), new AttributeCollection(), $query->num_rows());
        }

        return false;
    }

    public function findByCode($code)
    {
        $this->db->select('id as attribute_id,
                           input_type,
                           selection_only,
                           code,
                           name,
                           description,
                           created_at,
                           created_by,
                           updated_at,
                           updated_by,
                           deleted_at,
                           deleted_by');
        $this->db->from('iafb_remittance.attribute');
        $this->db->where('deleted_at', NULL);
        $this->db->where('code', $code);

        $query = $this->db->get();
        if( $query->num_rows() > 0 )
        {
            return $this->map($query->row());
        }

        return false;
    }

    public function insert(Attribute $attribute)
    {
        $this->db->set('id', $attribute->getId());
        $this->db->set('input_type', $attribute->getInputType());
        $this->db->set('selection_only', $attribute->getSelectionOnly());
        $this->db->set('code', $attribute->getCode());
        $this->db->set('name', $attribute->getName());
        $this->db->set('description', $attribute->getDescription());
        $this->db->set('created_at', IappsDateTime::now()->getUnix());
        $this->db->set('created_by', $attribute->getCreatedBy());

        if( $this->db->insert('iafb_remittance.attribute') )
        {
            return true;
        }

        return false;
    }

    public function update(Attribute $attribute)
    {
        $this->db->set('input_type', $attribute->getInputType());
        $this->db->set('selection_only', $attribute->getSelectionOnly());
        $this->db->set('name', $attribute->getName());
        $this->db->set('description', $attribute->getDescription());
        $this->db->set('updated_at', IappsDateTime::now()->getUnix());
        $this->db->set('updated_by', $attribute->getUpdatedBy());

        $this->db->where('id', $attribute->getId());
        $this->db->update('iafb_remittance.attribute');

        if( $this->db->affected_rows() > 0 )
        {
            return true;
        }

        return false;
    }
}