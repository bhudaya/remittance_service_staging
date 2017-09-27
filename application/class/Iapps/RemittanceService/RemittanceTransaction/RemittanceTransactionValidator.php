<?php

namespace Iapps\RemittanceService\RemittanceTransaction;

use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransaction;

class RemittanceTransactionValidator {

    protected $transaction;
    protected $isFailed = true;

    public static function make(RemittanceTransaction $remittanceTransaction)
    {
        $v = new RemittanceTransactionValidator();
        $v->transaction = $remittanceTransaction;
        $v->validate();

        return $v;
    }

    public static function makeForEdit(RemittanceTransaction $remittanceTransaction)
    {
        $v = new RemittanceTransactionValidator();
        $v->transaction = $remittanceTransaction;
        $v->validateForEdit();
        
        return $v;
    }

    public static function makeForRates(RemittanceTransaction $remittanceTransaction)
    {
        $v = new RemittanceTransactionValidator();
        $v->transaction = $remittanceTransaction;
        $v->validateForRates();
        
        return $v;
    }

    public function fails()
    {
        return $this->isFailed;
    }

    public function setRemittanceTransaction(RemittanceTransaction $remittanceTransaction)
    {
        $this->transaction = $remittanceTransaction;
        return true;
    }

    public function getRemittanceTransaction()
    {
        return $this->transaction;
    }

    public function validate()
    {
        $this->isFailed = true;
//        if( $this->_validateCode($this->getRemittanceTransaction()->getFromCountryCurrencyCode()) AND
//            $this->_validateCode($this->getRemittanceTransaction()->getToCountryCurrencyCode()) AND
//            $this->_validatePercentage($this->getRemittanceTransaction()->getMarkupOnRate()) AND
//            $this->_validateNumeric($this->getRemittanceTransaction()->getSyncInterval()))
//        {
            $this->isFailed = false;
            return true;
//        }
        return false;
    }

    public function validateForEdit()
    {
        $this->isFailed = true;
//        if( $this->_validatePercentage($this->getRemittanceTransaction()->getMarkupOnRate()) AND
//            $this->_validateNumeric($this->getRemittanceTransaction()->getSyncInterval()))
//        {
            $this->isFailed = false;
            return true;
//        }
        return false;
    }

    public function validateForRates()
    {
        $this->isFailed = true;
//        if( $this->_validateNumeric($this->getRemittanceTransaction()->getExchangeRateLastValue()) AND
//            $this->getRemittanceTransaction()->getExchangeRateExpiryDate() != null)
//        {
            $this->isFailed = false;
            return true;
//        }
        return false;
    }

    protected function _validateCode($code)
    {//make sure it six character code
        return (strlen($code) == 6);// AND ctype_upper($code));
    }

    protected function _validateNumeric($number)
    {//make sure its digit
        return (is_numeric($number));
    }

    protected function _validatePercentage($number)
    {//make sure its digit and below or equal 100
        return (is_numeric($number) && $number <= 100);
    }

    public static function _autoCancel($amount)
    {
        return (is_numeric($amount) && $amount <= 999);
    }
}