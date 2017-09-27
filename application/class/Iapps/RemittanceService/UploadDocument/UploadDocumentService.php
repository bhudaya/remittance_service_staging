<?php
/**
 * Created by PhpStorm.
 * User: zhoulin
 * Date: 06/06/16
 * Time: 下午3:21
 */

namespace Iapps\RemittanceService\UploadDocument;


use Composer\Package\Loader\ValidatingArrayLoader;
use Iapps\Common\Core\IappsBaseService;

use Iapps\RemittanceService\Common\MessageCode;
use Iapps\RemittanceService\Common\DocumentFileS3Uploader;

class UploadDocumentService extends IappsBaseService
{
    public function createDocument(UploadDocument $document)
    {
        $v = UploadDocumentValidator::make($document);
        if( !$v->fails() )
        {
            $this->getRepository()->startDBTransaction();
            if ($this->getRepository()->createDocument($document)) {
                $this->setResponseCode(MessageCode::CODE_UPLOAD_DOCUMENT_SUCCESS);
                //commit db trans
                $this->getRepository()->completeDBTransaction();
                return true;
            }
            $this->getRepository()->rollbackDBTransaction();
        }


        $this->setResponseCode(MessageCode::CODE_UPLOAD_DOCUMENT_FAIL);
        return false;
    }

    public function removeDocument(UploadDocument $document)
    {
        //retrieve
        if( $document = $this->getRepository()->findById($document->getId()) )
        {
            $this->getRepository()->startDBTransaction();
            if ($this->getRepository()->removeDocument($document)) {
                $this->setResponseCode(MessageCode::CODE_REMOVE_DOCUMENT_SUCCESS);
                //commit db trans
                $this->getRepository()->completeDBTransaction();
                return true;
            }
            $this->getRepository()->rollbackDBTransaction();
        }

        $this->setResponseCode(MessageCode::CODE_REMOVE_DOCUMENT_FAIL);
        return false;
    }

    public function getDocument($document , $limit, $page)
    {
        if ($info = $this->getRepository()->getDocument($document ,$limit, $page))
        {
            $this->setResponseCode(MessageCode::CODE_GET_DOCUMENT_SUCCESS);
            return $info;
        }

        $this->setResponseCode(MessageCode::CODE_GET_DOCUMENT_FAIL);
        return false;
    }

}