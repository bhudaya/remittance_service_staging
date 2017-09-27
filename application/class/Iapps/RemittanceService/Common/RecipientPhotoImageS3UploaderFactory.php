<?php

namespace Iapps\RemittanceService\Common;

class RecipientPhotoImageS3UploaderFactory{

    protected static $_instance;

    public static function build($key)
    {
        if( self::$_instance == NULL )
        {
            self::$_instance = new RecipientPhotoImageS3Uploader($key);
        }

        self::$_instance->setFileName($key);
        return self::$_instance;
    }
}