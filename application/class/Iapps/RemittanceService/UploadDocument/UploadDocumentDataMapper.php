<?php

namespace Iapps\RemittanceService\UploadDocument;

use Iapps\Common\Core\IappsBaseDataMapper;

interface UploadDocumentDataMapper extends IappsBaseDataMapper{



    public function createDocument(UploadDocument $uploadDocument);
    public function removeDocument(UploadDocument $uploadDocument);
    public function getDocument(UploadDocument $uploadDocument , $limit, $page);



}