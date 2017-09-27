<?php
/**
 * Created by PhpStorm.
 * User: zhoulin
 * Date: 06/06/16
 * Time: 下午3:30
 */

namespace Iapps\RemittanceService\UploadDocument;


use Iapps\Common\Core\IappsBaseRepository;

class UploadDocumentRepository extends IappsBaseRepository
{
    public function createDocument(UploadDocument $document)
    {
       return $this->getDataMapper()->createDocument($document);
    }

    public function removeDocument(UploadDocument $document)
    {
       return $this->getDataMapper()->removeDocument($document);
    }

    public function getDocument(UploadDocument $document,$limit, $page)
    {
        return $this->getDataMapper()->getDocument($document , $limit, $page);
    }
}
