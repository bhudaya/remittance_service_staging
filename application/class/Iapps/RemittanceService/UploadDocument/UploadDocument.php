<?php

namespace  Iapps\RemittanceService\UploadDocument;

use Iapps\Common\Core\IappsBaseEntity;
use Iapps\RemittanceService\Common\DocumentFileS3Uploader;

class UploadDocument extends  IappsBaseEntity{

    protected $type;
    protected $document_type;
    protected $doc_name;
    protected $doc_file;
    protected $tag_id;
    protected $remark;
    protected $doc_file_url;



    public function __construct()
    {
        parent::__construct();

    }
    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getDocumentType()
    {
        return $this->document_type;
    }

    /**
     * @param mixed $document_type
     */
    public function setDocumentType($document_type)
    {
        $this->document_type = $document_type;
    }
    /**
     * @return mixed
     */
    public function getTagId()
    {
        return $this->tag_id;
    }

    /**
     * @param mixed $tag_id
     */
    public function setTagId($tag_id)
    {
        $this->tag_id = $tag_id;
    }


    /**
     * @return mixed
     */
    public function getRemark()
    {
        return $this->remark;
    }
    /**
     * @param mixed $remark
     */
    public function setRemark($remark)
    {
        $this->remark = $remark;
    }

    /**
     * @return mixed
     */
    public function getDocName()
    {
        return $this->doc_name;
    }

    /**
     * @param mixed $doc_name
     */
    public function setDocName($doc_name)
    {
        $this->doc_name = $doc_name;
    }

    /**
     * @return mixed
     */
    public function getDocFile()
    {
        return $this->doc_file;
    }

    /**
     * @param mixed $doc_file
     */
    public function setDocFile($doc_file)
    {
        $this->doc_file = $doc_file;
    }



    /**
     * @return mixed
     */
    public function getDocFileUrl()
    {
        $DocumentFileS3Uploader =  new DocumentFileS3Uploader($this->getDocFile());


        return $DocumentFileS3Uploader->getUrl();
    }

    /**
     * @param mixed $doc_file_url
     */
    public function setDocFileUrl($doc_file_url)
    {
        $this->doc_file_url = $doc_file_url;
    }


    public function jsonSerialize()
    {
        $json = parent::jsonSerialize();

        $json['document_type']   = $this->getDocumentType();
        $json['doc_name']        = $this->getDocName();
        $json['doc_file']        = $this->getDocFile();
        $json['remark']      = $this->getRemark();
        $json['tag_id']      = $this->getTagId();
        $json['remark']      = $this->getRemark();
        $json['doc_file_url']      = $this->getDocFileUrl();


        return $json;
    }



}