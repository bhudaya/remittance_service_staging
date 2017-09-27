<?php
namespace Iapps\RemittanceService\Common;

use Iapps\Common\Helper\FileUploader\S3ImageFileUploader;
use Iapps\Common\Helper\S3Helper\AwsS3HelperFactory;
use Aws\Common\Enum\Region;

class DepositTopupS3Uploader extends S3ImageFileUploader{

    function __construct($depositid, $key = NULL)
    {
        if(ENVIRONMENT == 'testing')
        {
            $aws = AwsS3HelperFactory::build(Region::SINGAPORE, NULL,
                'AKIAJFZS4GMLHQNKU6PA',
                'lR4szDL5ax6oDn7JU0ijtpcJcpSa675ONfCFJdob');
            parent::__construct($aws);
        }
        else
            parent::__construct($aws);

        $this->setS3Folder('topup_photo/');
        $this->setMaxUploadSize(1000000);
        if( $key != NULL )
            $this->setFileName($key);
        else
            $this->setFileName($depositid);

    }


    public function uploadtoS3($paramName)
    {
        if( parent::upload($paramName) )
        {

            $this->getS3()->setValidPeriod(NULL);



            if( !$this->_uploadOriginaltoS3AsPublic() )
                return false;

            if( !$this->_uploadSmalltoS3AsPublic() )
                return false;

            if( !$this->_uploadMediumtoS3AsPublic() )
                return false;

            if( !$this->_uploadLargetoS3AsPublic() )
                return false;

            $this->removeImages();  //remove from local server
            return true;
        }

        return false;
    }

    protected function _uploadOriginaltoS3AsPublic()
    {
        return $this->getS3()->createPublicObject($this->_getOriginalPath() . $this->getFileName(),
            $this->_getOriginalKey());
    }

    protected function _uploadSmalltoS3AsPublic()
    {
        return $this->getS3()->createPublicObject($this->_getSmallPath() . $this->getFileName(),
            $this->_getSmallKey());
    }

    protected function _uploadMediumtoS3AsPublic()
    {
        return $this->getS3()->createPublicObject($this->_getMediumPath() . $this->getFileName(),
            $this->_getMediumKey());
    }

    protected function _uploadLargetoS3AsPublic()
    {
        return $this->getS3()->createPublicObject($this->_getLargePath() . $this->getFileName(),
            $this->_getLargeKey());
    }

}