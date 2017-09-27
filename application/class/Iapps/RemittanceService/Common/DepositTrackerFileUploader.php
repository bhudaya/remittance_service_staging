<?php

namespace Iapps\RemittanceService\Common;
use Iapps\Common\Helper\S3Helper\AwsS3HelperFactory;
use Iapps\Common\Helper\FileUploader\S3FileUploader;
use Aws\Common\Enum\Region;


class DepositTrackerFileUploader extends S3FileUploader
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


        $this->setMaxUploadSize(500000);
        $this->setFileName($file_name);
        $this->getS3()->setValidPeriod(NULL);

    }


    public function uploadtoS3($file_name)
    {
        $target_filename = $this->getUploadPath() . $this->getFileName();
        if(move_uploaded_file($_FILES[$file_name]['tmp_name'], $target_filename)){
             $this->getS3()->createPublicObject($this->getUploadPath().$this->getFileName(),$this->getS3Folder().$this->getFileName().'.pdf');
             $this->removeImage();
        }
    }


    public function removeImage()
    {
        if (file_exists($this->getUploadPath() . $this->getFileName()))
            unlink($this->getUploadPath() . $this->getFileName());
    }



}