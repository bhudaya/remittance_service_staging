<?php

namespace Iapps\RemittanceService\RemittanceTransaction;

use Iapps\Common\Core\IappsBaseEntity;

class TransactionProfitCost extends IappsBaseEntity{

    protected $transaction_id;
    protected $type;
    protected $item_id;
    protected $beneficiary_party_id;
    protected $country_currency_code;
    protected $amount;

    public function setTransactionId($transaction_id)
    {
        $this->transaction_id = $transaction_id;
        return $this;
    }

    public function getTransactionId()
    {
        return $this->transaction_id;
    }

    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setItemId($item_id)
    {
        $this->item_id = $item_id;
        return $this;
    }

    public function getItemId()
    {
        return $this->item_id;
    }

    public function setBeneficiaryPartyId($beneficiary_id)
    {
        $this->beneficiary_party_id = $beneficiary_id;
        return $this;
    }

    public function getBeneficiaryPartyId()
    {
        return $this->beneficiary_party_id;
    }

    public function setCountryCurrencyCode($currency_code)
    {
        $this->country_currency_code = $currency_code;
        return $this;
    }

    public function getCountryCurrencyCode()
    {
        return $this->country_currency_code;
    }

    public function setAmount($amount)
    {
        $this->amount = $amount;
        return $this;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function jsonSerialize()
    {
        $json = parent::jsonSerialize();

        $json['transaction_id'] = $this->getTransactionId();
        $json['type'] = $this->getType();
        $json['item_id'] = $this->getItemId();
        $json['beneficiary_party_id'] = $this->getBeneficiaryPartyId();
        $json['country_currency_code'] = $this->getCountryCurrencyCode();
        $json['amount'] = $this->getAmount();

        return $json;
    }
}