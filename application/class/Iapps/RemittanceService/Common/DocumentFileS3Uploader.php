<?php

namespace Iapps\RemittanceService\Common;
use Iapps\Common\Helper\S3Helper\AwsS3HelperFactory;
use Iapps\Common\Helper\FileUploader\S3FileUploader;
use Aws\Common\Enum\Region;


class DocumentFileS3Uploader extends S3FileUploader
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
        $this->setAllowedType('jpg|png|pdf|doc|docx');
        $this->setS3Folder('remittance/user/document/');
        $this->setMaxUploadSize(2 * 1024 * 1024);    // 2 * 1024 * 1024bytes(2mb)
        $this->setFileName($file_name);
    }

}