<?php

namespace Iapps\RemittanceService\Common;

use Iapps\Common\Helper\FileUploader\S3ImageFileUploader;
use Iapps\Common\Helper\S3Helper\AwsS3HelperFactory;
use Aws\Common\Enum\Region;

class FlagImageS3Uploader extends S3ImageFileUploader{

    function __construct($code, $key = NULL)
    {
        if( ENVIRONMENT == 'testing' )
        {
            $aws = AwsS3HelperFactory::build(Region::SINGAPORE, NULL,
                                             'AKIAJFZS4GMLHQNKU6PA',
                                             'lR4szDL5ax6oDn7JU0ijtpcJcpSa675ONfCFJdob');
            parent::__construct($aws);
        }
        else
            parent::__construct();

        $this->setS3Folder('flag/');
        $this->setMaxUploadSize(500000);
        if( $key != NULL )
            $this->setFileName($key);
        else
            $this->setFileName('flag_' . $code);
    }
}