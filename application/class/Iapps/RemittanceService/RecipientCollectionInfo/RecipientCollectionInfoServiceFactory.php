<?php

namespace Iapps\RemittanceService\RecipientCollectionInfo;

require_once './application/modules/recipient/models/Recipient_collection_info_model.php';

class RecipientCollectionInfoServiceFactory {

    protected static $_instance;

    public static function build()
    {
        if( self::$_instance == NULL )
        {
            $dm = new \Recipient_collection_info_model();
            $repo = new RecipientCollectionInfoRepository($dm);
            self::$_instance = new RecipientCollectionInfoService($repo);
        }

        return self::$_instance;
    }
}