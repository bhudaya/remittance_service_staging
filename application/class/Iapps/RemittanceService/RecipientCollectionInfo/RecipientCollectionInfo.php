<?php

namespace Iapps\RemittanceService\RecipientCollectionInfo;

use Iapps\Common\Core\EncryptedField;
use Iapps\Common\Core\IappsBaseEntity;
use Iapps\RemittanceService\Recipient\Recipient;
use Iapps\RemittanceService\ValueObject\EncryptedFieldFactory;

class RecipientCollectionInfo extends IappsBaseEntity{

    protected $recipient_id;
    protected $country_code;
    protected $payment_code;    //obsolete, remained to support old version
    protected $option;
    
    function __construct()
    {
        parent::__construct();

        $this->option = EncryptedFieldFactory::build();
    }

    public function setRecipientId($recipient_id)
    {
        $this->recipient_id = $recipient_id;
        return $this;
    }

    public function getRecipientId()
    {
        return $this->recipient_id;
    }

    public function setCountryCode($country_code)
    {
        $this->country_code = $country_code;
        return $this;
    }

    public function getCountryCode()
    {
        return $this->country_code;
    }


    public function setPaymentCode($payment_code)
    {
        $this->payment_code = $payment_code;
        return $this;
    }

    public function getPaymentCode()
    {
        return $this->payment_code;
    }

    public function setOption(EncryptedField $option)
    {
        $this->option = $option;
        return $this;
    }

    public function getOption()
    {
        return $this->option;
    }

    public function jsonSerialize()
    {
        $json = parent::jsonSerialize();

        $json['recipient_id'] = $this->getRecipientId();
        $json['payment_code'] = $this->getPaymentCode();
        $json['country_code'] = $this->getCountryCode();
        $json['option'] = $this->getOption()->getValue();

        return $json;
    }

    public function belongsTo(Recipient $recipient)
    {
        return $this->getRecipientId() == $recipient->getId();
    }

    public function equals(RecipientCollectionInfo $recipientCollectionInfo)
    {
        if( $this->getRecipientId() == $recipientCollectionInfo->getRecipientId() AND
            $this->getCountryCode() == $recipientCollectionInfo->getCountryCode() )
        {
            $option = json_decode($this->getOption()->getValue(), true);
            $refOption = json_decode($recipientCollectionInfo->getOption()->getValue(), true);

            $diff = array_diff($option, $refOption);
            return count($diff) <= 0;   //equal if no diff
        }

        return false;
    }

    public function hasAttributes(array $attributes)
    {
        $option = json_decode($this->getOption()->getValue(), true);
        foreach( $attributes AS $attribute )
        {
            if( !array_key_exists($attribute, $option) )
                return false;
        }

        return true;
    }

    public function getAttribute($attribute)
    {
        $option = json_decode($this->getOption()->getValue(), true);

        if( array_key_exists($attribute, $option) )
            return $option[$attribute];

        return false;
    }

    public function mapAccountNo()
    {
        if( $options = json_decode($this->getOption()->getValue(), true) )
        {
            if( isset($options['account_no']) AND !isset($options['bank_account']))
            {
                $options['bank_account'] = $options['account_no'];
                $this->getOption()->setValue(json_encode($options));
            }
        }

        return $this;
    }
}