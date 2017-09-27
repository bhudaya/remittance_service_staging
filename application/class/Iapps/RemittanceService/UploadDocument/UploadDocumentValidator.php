<?php

namespace Iapps\RemittanceService\UploadDocument;

use Iapps\Common\Validator\IappsValidator;
use Iapps\RemittanceService\Recipient\RecipientServiceFactory;
use Iapps\RemittanceService\RemittanceCompanyUser\RemittanceCompanyUserServiceFactory;
use Iapps\RemittanceService\RemittanceRecord\RemittanceRecordServiceFactory;

class UploadDocumentValidator extends IappsValidator{

    protected $_uploadDocument;
    public static function make(UploadDocument $uploadDocument)
    {
        $v = new UploadDocumentValidator();
        $v->_uploadDocument = $uploadDocument;
        $v->validate();

        return $v;
    }

    public function validate()
    {
        $this->isFailed = true;

        if( $this->_validateTagId() )
        {
            $this->isFailed = false;
        }
    }

    protected function _validateTagId()
    {
        if( $this->_uploadDocument instanceof UploadDocument )
        {
            if( $type = $this->_uploadDocument->getType() AND
                $tag_id = $this->_uploadDocument->getTagId() )
            {
                switch($type)
                {
                    case DocumentType::USER_DOCUMENT:
                        //tag id -> remittance user id
                        $remcoUserServ = RemittanceCompanyUserServiceFactory::build();
                        if( $remcoUser = $remcoUserServ->findById($tag_id) )
                            return true;
                        break;
                    case DocumentType::TRANSACTION_DOCUMENT:
                        //tag id -> remittance_id
                        $remitServ = RemittanceRecordServiceFactory::build();
                        if( $remit = $remitServ->getRemittanceInfoByRemittanceId($tag_id) )
                            return true;
                        break;
                    case DocumentType::RECIPIENT_DOCUMENT:
                        //tag id -> recipient_id
                        $recipientServ = RecipientServiceFactory::build();
                        if( $recipient = $recipientServ->getRecipient($tag_id) )
                            return true;
                        break;
                    default:
                        return false;

                }
            }
        }

        return false;
    }
}