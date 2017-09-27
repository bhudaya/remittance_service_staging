<?php

namespace Iapps\RemittanceService\UploadDocument;

class DocumentType{

    const USER_DOCUMENT = 1;
    const TRANSACTION_DOCUMENT = 2;
    const RECIPIENT_DOCUMENT = 3;

    public static function validate($type)
    {
        return
            ($type == self::USER_DOCUMENT OR
             $type == self::TRANSACTION_DOCUMENT OR
             $type == self::RECIPIENT_DOCUMENT);
    }
}