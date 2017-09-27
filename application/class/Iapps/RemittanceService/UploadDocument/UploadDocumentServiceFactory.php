<?php

namespace Iapps\RemittanceService\UploadDocument;

class UploadDocumentServiceFactory
{
    protected static $_instance = NULL;

    public static function build()
    {
        if( self::$_instance == NULL )
        {
            $_ci = get_instance();
            $_ci->load->model('common/Document_model');
            $repo = new UploadDocumentRepository($_ci->Document_model);
            self::$_instance = new UploadDocumentService($repo);
        }

        return self::$_instance;
    }
}