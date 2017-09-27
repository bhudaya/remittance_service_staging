<?php

namespace Iapps\RemittanceService\RemittanceRecord;

use Iapps\Common\Core\IappsBasicBaseService;
use Iapps\RemittanceService\Attribute\AttributeValue;
use Iapps\RemittanceService\Attribute\RemittanceAttributeServiceFactory;
use Iapps\RemittanceService\Attribute\AttributeCode;
use Iapps\RemittanceService\Attribute\RemittanceAttributeCollection;
use Iapps\Common\Core\IappsDateTime;
use Iapps\RemittanceService\Recipient\Recipient;

/**
 * This class is responsible to create snapshot of user & recipient data per remittance
 */
class UserDataSnapshotService extends IappsBasicBaseService{
            
    /**
     * 
     * @param \Iapps\RemittanceService\RemittanceRecord\RemittanceRecord $remittance
     * @return \Iapps\RemittanceService\RemittanceRecord\RemittanceRecord
     */
    public function buildSnapshot(RemittanceRecord $remittance)            
    {        
        $this->snapSender($remittance);
        $this->snapRecipient($remittance);
        
        return $remittance;        
    }
        
    /**
     * 
     * @param \Iapps\RemittanceService\RemittanceRecord\RemittanceRecord $remittance
     * @return RemittanceRecord remittance with user/recipient data from snapshot
     */
    public function fromSnapShot(RemittanceRecord $remittance)
    {
        $attrServ = RemittanceAttributeServiceFactory::build();
        $attrServ->setUpdatedBy($this->getUpdatedBy());
        $attrServ->setIpAddress($this->getIpAddress());
        
        if( $attributes = $attrServ->getAllRemittanceAttribute($remittance->getId()) )
        {
            $this->getSenderFromSnap($remittance, $attributes); 
            $this->getRecipientFromSnap($remittance, $attributes);
        }
                
        return $remittance;
    }

    protected function snapSender(RemittanceRecord $remittance)
    {
        //sender full name
        if( $remittance->getSender()->getFullName() )
            $this->setAttribute($remittance, AttributeCode::SENDER_FULL_NAME, $remittance->getSender()->getFullName());
        
        //sender display name
        if( $remittance->getSender()->getName() )
            $this->setAttribute($remittance, AttributeCode::SENDER_NAME, $remittance->getSender()->getName());
                
        //sender address
        if( isset($remittance->getSender()->getHostAddress()->address) )
            $this->setAttribute($remittance, AttributeCode::SENDER_RESIDENTIAL_ADDRESS, $remittance->getSender()->getHostAddress()->address);
        
        if( isset($remittance->getSender()->getHostAddress()->postal_code) )
            $this->setAttribute($remittance, AttributeCode::SENDER_RESIDENTIAL_POSTAL_CODE, $remittance->getSender()->getHostAddress()->postal_code);
        
        if( isset($remittance->getSender()->getHostAddress()->city) )
            $this->setAttribute($remittance, AttributeCode::SENDER_RESIDENTIAL_CITY, $remittance->getSender()->getHostAddress()->city);
        
        if( isset($remittance->getSender()->getHostAddress()->province) )
            $this->setAttribute($remittance, AttributeCode::SENDER_RESIDENTIAL_PROVINCE, $remittance->getSender()->getHostAddress()->province);
        
        if( isset($remittance->getSender()->getHostAddress()->country) )
            $this->setAttribute($remittance, AttributeCode::SENDER_RESIDENTIAL_COUNTRY, $remittance->getSender()->getHostAddress()->country);
        
        if( $remittance->getSender()->getDOB() )
            $this->setAttribute($remittance, AttributeCode::SENDER_DOB, $remittance->getSender()->getDOB()->getUnix());

        if( $remittance->getSender()->getMobileNumberObj()->getDialingCode() )
            $this->setAttribute($remittance, AttributeCode::SENDER_DIALING_CODE, $remittance->getSender()->getMobileNumberObj()->getDialingCode());

        if( $remittance->getSender()->getMobileNumberObj()->getMobileNumber() )
            $this->setAttribute($remittance, AttributeCode::SENDER_MOBILE_NUMBER, $remittance->getSender()->getMobileNumberObj()->getMobileNumber());
        
        if( $remittance->getSender()->getAccountID() )
            $this->setAttribute($remittance, AttributeCode::SENDER_ACCOUNTID, $remittance->getSender()->getAccountID());

        if( isset($remittance->getSender()->getHostIdentity()->number) )
            $this->setAttribute($remittance, AttributeCode::SENDER_ID_CARD, $remittance->getSender()->getHostIdentity()->number);
        
        if( isset($remittance->getSender()->getHostIdentity()->expired_date) )
            $this->setAttribute($remittance, AttributeCode::SENDER_ID_CARD_EXPIRED_AT, IappsDateTime::fromString($remittance->getSender()->getHostIdentity()->expired_date)->getUnix());
        
        if( isset($remittance->getSender()->getHostIdentity()->issue_date) )
            $this->setAttribute($remittance, AttributeCode::SENDER_ID_CARD_ISSUED_AT, IappsDateTime::fromString($remittance->getSender()->getHostIdentity()->issue_date)->getUnix());

        foreach ($remittance->getSender()->getAttributes() as $att) 
        {
            //sender_nationality
            if ($att->getAttribute()->getCode() == 'nationality')
                $this->setAttribute($remittance, AttributeCode::SENDER_NATIONALITY, $att->getValue());
             
            //sender_id_type
            if ($att->getAttribute()->getCode() == 'id_type')
                $this->setAttribute($remittance, AttributeCode::SENDER_ID_TYPE, $att->getValue());
        }
    }
    
    /**
     * 
     * @param \Iapps\RemittanceService\RemittanceRecord\RemittanceRecord $remittance
     * @param RemittanceAttributeCollection $attributes
     * @return RemittanceRecord remittance with snapped sender
     */
    protected function getSenderFromSnap(RemittanceRecord $remittance, RemittanceAttributeCollection $attributes)
    {
        if( $full_name = $attributes->hasAttribute(AttributeCode::SENDER_FULL_NAME) )
            $remittance->getSender()->setFullName($full_name);
        
        if( $name = $attributes->hasAttribute(AttributeCode::SENDER_NAME) )
            $remittance->getSender()->setName($name);
                
        if( $addr = $attributes->hasAttribute(AttributeCode::SENDER_RESIDENTIAL_ADDRESS) )
            $remittance->getSender()->getHostAddress()->address = $addr;
        
        if( $postal_code = $attributes->hasAttribute(AttributeCode::SENDER_RESIDENTIAL_POSTAL_CODE) )
            $remittance->getSender()->getHostAddress()->postal_code = $postal_code;
        
        if( $city = $attributes->hasAttribute(AttributeCode::SENDER_RESIDENTIAL_CITY) )
            $remittance->getSender()->getHostAddress()->city = $city;
        
        if( $province = $attributes->hasAttribute(AttributeCode::SENDER_RESIDENTIAL_PROVINCE) )
            $remittance->getSender()->getHostAddress()->province = $province;        
        
        if( $country = $attributes->hasAttribute(AttributeCode::SENDER_RESIDENTIAL_COUNTRY) )
        {
            $remittance->getSender()->getHostAddress()->country = $country;
            $remittance->getSender()->setHostCountryCode($country);    
        }
                
        if( $dob = $attributes->hasAttribute(AttributeCode::SENDER_DOB) )
            $remittance->getSender()->setDOB(IappsDateTime::fromUnix($dob));
        
        if( $dialing_code = $attributes->hasAttribute(AttributeCode::SENDER_DIALING_CODE) )
            $remittance->getSender()->getMobileNumberObj()->setDialingCode($dialing_code);
        
        if( $mobile_number = $attributes->hasAttribute(AttributeCode::SENDER_MOBILE_NUMBER) )
            $remittance->getSender()->getMobileNumberObj()->setMobileNumber($mobile_number);

        if( $accountID = $attributes->hasAttribute(AttributeCode::SENDER_ACCOUNTID) )
            $remittance->getSender()->setAccountID($accountID);
                        
        if( $id_card =  $attributes->hasAttribute(AttributeCode::SENDER_ID_CARD) )
        {
            $remittance->getSender()->getHostIdentity()->number = $id_card;
            $remittance->getSender()->setHostIdentityCard($id_card);
        }
        
        if( $id_card_expiry =  $attributes->hasAttribute(AttributeCode::SENDER_ID_CARD_EXPIRED_AT) )
            $remittance->getSender()->getHostIdentity()->expired_date = $id_card_expiry;
        
        if( $id_card_issue =  $attributes->hasAttribute(AttributeCode::SENDER_ID_CARD_ISSUED_AT) )
            $remittance->getSender()->getHostIdentity()->issue_date = $id_card_issue;
                                
        foreach ($remittance->getSender()->getAttributes() as $att) 
        {
            //sender_nationality
            if ($att->getAttribute()->getCode() == 'nationality')
            {
                if( $nationality = $attributes->hasAttribute(AttributeCode::SENDER_NATIONALITY) )
                    $att->setValue($nationality);                
            }

            //sender_id_type
            if ($att->getAttribute()->getCode() == 'id_type')
            {
                if( $id_type = $attributes->hasAttribute(AttributeCode::SENDER_ID_TYPE) )
                    $att->setValue($id_type);
            }
        }
    }
    
    protected function snapRecipient(RemittanceRecord $remittance)
    {
        if( $full_name = $remittance->getRecipient()->getAttributes()->hasAttribute(AttributeCode::FULL_NAME) )
            $this->setAttribute($remittance, AttributeCode::RECIPIENT_FULL_NAME, $full_name);
        
        if( $remittance->getRecipient()->getRecipientAlias() )
            $this->setAttribute($remittance, AttributeCode::RECIPIENT_ALIAS, $remittance->getRecipient()->getRecipientAlias());
                
        if( $remittance->getRecipient()->getRecipientDialingCode()->getValue() )
            $this->setAttribute($remittance, AttributeCode::RECIPIENT_DIALING_CODE, $remittance->getRecipient()->getRecipientDialingCode()->getValue());
        
        if( $remittance->getRecipient()->getRecipientMobileNumber()->getValue() )
            $this->setAttribute($remittance, AttributeCode::RECIPIENT_MOBILE_NUMBER, $remittance->getRecipient()->getRecipientMobileNumber()->getValue());
        
        if( $remittance->getRecipient()->getUser()->getAccountID() )
            $this->setAttribute($remittance, AttributeCode::RECIPIENT_ACCOUNTID, $remittance->getRecipient()->getUser()->getAccountID());
        
        if( $addr = $remittance->getRecipient()->getAttributes()->hasAttribute(AttributeCode::RESIDENTIAL_ADDRESS) )
            $this->setAttribute($remittance, AttributeCode::RECIPIENT_RESIDENTIAL_ADDRESS, $addr);
        
        if( $pc = $remittance->getRecipient()->getAttributes()->hasAttribute(AttributeCode::RESIDENTIAL_POST_CODE) )
            $this->setAttribute($remittance, AttributeCode::RECIPIENT_RESIDENTIAL_POSTAL_CODE, $pc);
        
        if( $province = $remittance->getRecipient()->getAttributes()->hasAttribute(AttributeCode::RESIDENTIAL_PROVINCE) )
            $this->setAttribute($remittance, AttributeCode::RECIPIENT_RESIDENTIAL_PROVINCE, $province);
        
        if( $city = $remittance->getRecipient()->getAttributes()->hasAttribute(AttributeCode::RESIDENTIAL_CITY) )
            $this->setAttribute($remittance, AttributeCode::RECIPIENT_RESIDENTIAL_CITY, $city);
        
        if( $country = $remittance->getRecipient()->getAttributes()->hasAttribute(AttributeCode::RESIDENTIAL_COUNTRY) )
            $this->setAttribute($remittance, AttributeCode::RECIPIENT_RESIDENTIAL_COUNTRY, $country);
        
        if( $nationality = $remittance->getRecipient()->getAttributes()->hasAttribute(AttributeCode::NATIONALITY) )
            $this->setAttribute($remittance, AttributeCode::RECIPIENT_NATIONALITY, $nationality);
        
        if( $relation = $remittance->getRecipient()->getAttributes()->hasAttribute(AttributeCode::RELATIONSHIP_TO_SENDER) )
            $this->setAttribute($remittance, AttributeCode::RELATIONSHIP_TO_SENDER, $relation);
        
        if( $purpose = $remittance->getRecipient()->getAttributes()->hasAttribute(AttributeCode::PURPOSE_OF_REMITTANCE) )
            $this->setAttribute($remittance, AttributeCode::PURPOSE_OF_REMITTANCE, $purpose);
        
        return $remittance;
    }
    
    /**
     * 
     * @param \Iapps\RemittanceService\RemittanceRecord\RemittanceRecord $remittance
     * @param RemittanceAttributeCollection $attributes
     * @return \Iapps\RemittanceService\RemittanceRecord\RemittanceRecord
     */
    protected function getRecipientFromSnap(RemittanceRecord $remittance, RemittanceAttributeCollection $attributes)
    {        
        if( $alias = $attributes->hasAttribute(AttributeCode::RECIPIENT_ALIAS) )
            $remittance->getRecipient()->setRecipientAlias($alias);
                
        if( $dialing_code = $attributes->hasAttribute(AttributeCode::RECIPIENT_DIALING_CODE) )
            $remittance->getRecipient()->getRecipientDialingCode()->setValue($dialing_code);
            
        if( $mobile_number = $attributes->hasAttribute(AttributeCode::RECIPIENT_MOBILE_NUMBER) )
            $remittance->getRecipient()->getRecipientMobileNumber()->setValue($mobile_number);        
        
        if( $accountID = $attributes->hasAttribute(AttributeCode::RECIPIENT_ACCOUNTID) )
            $remittance->getRecipient()->getUser()->setAccountID($accountID);
        
        if( $full_name = $attributes->hasAttribute(AttributeCode::RECIPIENT_FULL_NAME) )
            $this->overwriteRecipientAttribute($remittance->getRecipient(), AttributeCode::FULL_NAME, $full_name);
        
        if( $addr = $attributes->hasAttribute(AttributeCode::RECIPIENT_RESIDENTIAL_ADDRESS) )
            $this->overwriteRecipientAttribute($remittance->getRecipient(), AttributeCode::RESIDENTIAL_ADDRESS, $addr);
        
        if( $postal_code = $attributes->hasAttribute(AttributeCode::RECIPIENT_RESIDENTIAL_POSTAL_CODE) )
            $this->overwriteRecipientAttribute($remittance->getRecipient(), AttributeCode::RESIDENTIAL_POST_CODE, $postal_code);
        
        if( $province = $attributes->hasAttribute(AttributeCode::RECIPIENT_RESIDENTIAL_PROVINCE) )
            $this->overwriteRecipientAttribute($remittance->getRecipient(), AttributeCode::RESIDENTIAL_PROVINCE, $province);
        
        if( $city = $attributes->hasAttribute(AttributeCode::RECIPIENT_RESIDENTIAL_CITY) )
            $this->overwriteRecipientAttribute($remittance->getRecipient(), AttributeCode::RESIDENTIAL_CITY, $city);
        
        if( $country = $attributes->hasAttribute(AttributeCode::RECIPIENT_RESIDENTIAL_COUNTRY) )
            $this->overwriteRecipientAttribute($remittance->getRecipient(), AttributeCode::RESIDENTIAL_COUNTRY, $country);
        
        if( $nationality = $attributes->hasAttribute(AttributeCode::RECIPIENT_NATIONALITY) )
            $this->overwriteRecipientAttribute($remittance->getRecipient(), AttributeCode::NATIONALITY, $nationality);
        
        if( $relation = $attributes->hasAttribute(AttributeCode::RELATIONSHIP_TO_SENDER) )
            $this->overwriteRecipientAttribute($remittance->getRecipient(), AttributeCode::RELATIONSHIP_TO_SENDER, $relation);
        
        if( $purpose = $attributes->hasAttribute(AttributeCode::PURPOSE_OF_REMITTANCE) )
            $this->overwriteRecipientAttribute($remittance->getRecipient(), AttributeCode::PURPOSE_OF_REMITTANCE, $purpose);
                       
        return $remittance;
    }

    protected function setAttribute(RemittanceRecord $remittance, $key, $value)
    {
        $attrServ = RemittanceAttributeServiceFactory::build();
        $attrServ->setUpdatedBy($this->getUpdatedBy());
        $attrServ->setIpAddress($this->getIpAddress());
        
        $attrValue = new AttributeValue();
        $attrValue->getAttribute()->setCode($key);
        $attrValue->setValue($value);
        $remittance->getAttributes()->addData($attrValue);
        
        $attrServ->setRemittanceAttribute($remittance->getId(), $attrValue);
    }
    
    /**
     * 
     * This will NOT persist into database.
     * @param Recipient $recipient
     * @param type $key
     * @param type $value 
     */
    protected function overwriteRecipientAttribute(Recipient $recipient, $key, $value)
    {
        if( $attrValue = $recipient->getAttributes()->getByCode($key) )
        {
            $attrValue->setValue($value);
        }            
        else
        {
            $attrValue = new AttributeValue();
            $attrValue->getAttribute()->setCode($key);
            $attrValue->setValue($value);
            $recipient->getAttributes()->addData($attrValue);
        }
        
        return $recipient;
    }
}

