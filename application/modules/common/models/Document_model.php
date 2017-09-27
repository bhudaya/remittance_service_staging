<?php

use Iapps\RemittanceService\UploadDocument\UploadDocumentDataMapper;
use Iapps\RemittanceService\UploadDocument\UploadDocument;
use Iapps\Common\Core\IappsDateTime;
use Iapps\RemittanceService\UploadDocument\UploadDocumentCollection;

class Document_model extends Base_Model implements UploadDocumentDataMapper {


    public function map(stdClass $data)
    {
        $entity = new UploadDocument();

        if (isset( $data->id) )
            $entity->setId($data->id);

        if (isset( $data->type) )
            $entity->setDocumentType($data->type);


        if( isset($data->doc_name) )
            $entity->setDocName($data->doc_name);

        if( isset($data->doc_name) )
            $entity->setDocFile($data->doc_file);


        if( isset($data->tag_id) )
            $entity->setTagId($data->tag_id);


        if( isset($data->remark) )
            $entity->setRemark($data->remark);


        if( isset($data->created_at) )
            $entity->setCreatedAt(IappsDateTime::fromUnix($data->created_at));

        if( isset($data->created_by) )
            $entity->setCreatedBy($data->created_by);

        if( isset($data->deleted_at) )
            $entity->setDeletedAt(IappsDateTime::fromUnix($data->deleted_at));

        if( isset($data->deleted_by) )
            $entity->setDeletedBy($data->deleted_by);

        return $entity;
    }

    public function findById($id, $deleted = false)
    {
        $this->db->select('*');
        $this->db->from('iafb_remittance.document');
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

    public function createDocument(UploadDocument $uploadDocument)
    {
        $this->db->set('id', $uploadDocument->getId());

        $this->db->set('type', $uploadDocument->getDocumentType());
        $this->db->set('tag_id', $uploadDocument->getTagId());
        $this->db->set('remark', $uploadDocument->getRemark());
        $this->db->set('doc_name', $uploadDocument->getDocName());
        $this->db->set('doc_file', $uploadDocument->getDocFile());
        $this->db->set('created_at', IappsDateTime::now()->getUnix());
        $this->db->set('created_by', $uploadDocument->getCreatedBy());
        $this->db->insert('iafb_remittance.document');
        if( $this->db->affected_rows() > 0 )
        {
            return true;
        }

        return false;
    }

    public function removeDocument(UploadDocument $uploadDocument)
    {

        $this->db->set('type', $uploadDocument->getDocumentType());
        $this->db->set('deleted_by', $uploadDocument->getDeletedBy());
        $this->db->set('deleted_at' , IappsDateTime::now()->getUnix());
        $this->db->where('id', $uploadDocument->getId());
        $this->db->where('deleted_at', NULL);

        if ($this->db->update('iafb_remittance.document'))
        {
            return $this->db->affected_rows();
        }

        return false;
    }

    public function getDocument(UploadDocument $uploadDocument , $limit, $page)
    {

        $offset = ($page - 1) * $limit;

        $this->db->from('iafb_remittance.document');
        $this->db->select('*');
        if ($uploadDocument->getId())
            $this->db->where('id' , $uploadDocument->getId());
        if ($uploadDocument->getTagId())
            $this->db->where('tag_id' , $uploadDocument->getTagId());
        $this->db->where('type' , $uploadDocument->getDocumentType());
        $this->db->where('deleted_at', NULL);

        $this->db->limit($limit, $offset);

        $query = $this->db->get();
        $total = $query->num_rows();


        if( $query->num_rows() > 0 )
        {

            return $this->mapCollection($query->result() , new UploadDocumentCollection() , $total);
        }

        return false;

    }
}


