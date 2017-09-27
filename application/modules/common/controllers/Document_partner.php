<?php defined('BASEPATH') OR exit('No direct script access allowed');

use Iapps\RemittanceService\UploadDocument\UploadDocument;
use Iapps\RemittanceService\UploadDocument\UploadDocumentServiceFactory;
use Iapps\Common\Helper\GuidGenerator;
use Iapps\RemittanceService\Common\DocumentFileS3Uploader;
use Iapps\RemittanceService\Common\MessageCode;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\Common\Core\IpAddress;
use Iapps\RemittanceService\Common\FunctionCode;
use Iapps\RemittanceService\UploadDocument\DocumentType;
use Iapps\Common\Helper\InputValidator;
use Iapps\Common\Microservice\AccountService\AccessType;

class Document_partner extends Partner_Base_Controller
{
    protected $_service;

    function __construct()
    {
        parent::__construct();

        $this->_service = UploadDocumentServiceFactory::build();
        $this->_service->setIpAddress(IpAddress::fromString($this->_getIpAddress()));
    }


    public function uploadDocument()
    {
        if( !$admin_id = $this->_getUserProfileId(FunctionCode::PARTNER_UPLOAD_DOCUMENT) )
            return false;

        if( !$this->is_required($this->input->post(), array('doc_name','type','tag_id', 'remark')))
            return false;

        if( !$this->is_required($_FILES, array('doc_file')) )
            return false;

        $doc_name = $this->input->post("doc_name");
        $type = $this->input->post("type");
        $tag_id = $this->input->post("tag_id");
        $remark = $this->input->post("remark");

        if( !$this->_validateType($type) )
            return false;

        $id = GuidGenerator::generate();    //file id
        $docFileName = $this->_uploadDoc('doc_file', $id);
        
        if($docFileName == false)
        {
            $this->_respondWithCode(MessageCode::CODE_UPLOAD_DOCUMENT_FAIL, ResponseHeader::HEADER_NOT_FOUND);
            return false;
        }

        $document_model = new UploadDocument();
        $document_model->setId($id);
        $document_model->setDocumentType($type);
        $document_model->setDocName($doc_name);
        $document_model->setDocFile($docFileName);
        $document_model->setType($type);
        $document_model->setTagId($tag_id);
        $document_model->setRemark($remark);
        $document_model->setCreatedBy($admin_id);

        $this->_service->setUpdatedBy($admin_id);
        if ($document_model = $this->_service->createDocument($document_model)){
            $this->_respondWithSuccessCode($this->_service->getResponseCode());
            return true;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function removeDocument()
    {
        if( !$admin_id = $this->_getUserProfileId(FunctionCode::PARTNER_DELETE_DOCUMENT))
            return false;

        if( !$this->is_required($this->input->post(), array('id', 'type')))
            return false;

        $id = $this->input->post("id");
        $type = $this->input->post("type");

        $document_model = new UploadDocument();
        $document_model->setId($id);
        $document_model->setDocumentType($type);
        $document_model->setDeletedBy($admin_id);

        if( !$this->_validateType($type) )
            return false;

        $this->_service->setUpdatedBy($admin_id);
        if ($document_model = $this->_service->removeDocument($document_model)){
            $this->_respondWithSuccessCode($this->_service->getResponseCode());
            return true;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getDocument()
    {
        if( !$admin_id = $this->_getUserProfileId(FunctionCode::PARTNER_VIEW_DOCUMENT, AccessType::READ) )
            return false;

        if( !$this->is_required($this->input->get(), array('type')))
            return false;

        $id = $this->input->get("id");
        $type = $this->input->get("type");
        $tag_id = $this->input->get("tag_id");
        $limit = $this->_getLimit();
        $page = $this->_getPage();

        if( !$this->_validateType($type) )
            return false;

        $document_model = new UploadDocument();
        $document_model->setId($id);
        $document_model->setDocumentType($type);
        $document_model->setTagId($tag_id);

        $this->_service->setUpdatedBy($admin_id);
        if ($object = $this->_service->getDocument($document_model , $limit, $page)){
            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $object->result, 'total' => $object->total));
            return true;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    protected function _uploadDoc($file_name, $file_id)
    {
        $documentFileS3Uploader = new DocumentFileS3Uploader($file_id);

        if ($documentFileS3Uploader->uploadtoS3($file_name)) {
            return $documentFileS3Uploader->getFileName();
        }

        $this->_respondWithCode(MessageCode::CODE_UPLOAD_DOCUMENT_FAIL);
        return false;
    }

    protected function _validateType($type)
    {
        if( !DocumentType::validate($type) )
        {
            $this->_response(InputValidator::constructInvalidParamResponse(
                InputValidator::getInvalidParamMessage('type')
            ));
            return false;
        }

        return true;
    }
}