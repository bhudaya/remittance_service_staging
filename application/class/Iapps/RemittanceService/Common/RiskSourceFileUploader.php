<?php

namespace Iapps\RemittanceService\Common;

use Iapps\Common\Helper\S3Helper\AwsS3HelperFactory;
use Iapps\Common\Helper\FileUploader\S3FileUploader;
use Aws\Common\Enum\Region;

class RiskSourceFileUploader extends S3FileUploader
{
    function __construct($file_name)
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

        $this->setUploadPath('./upload/document/');
        $this->setAllowedType('xlsx|png|pdf|txt|jpg');
        $this->setS3Folder('remittance/risk/document/');
        $this->setMaxUploadSize(500000);
        $this->setFileName($file_name);
    }
}