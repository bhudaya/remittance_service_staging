<?php

namespace Iapps\RemittanceService\RemittanceRecord;

use Iapps\PaymentService\Payment\EWalletPaymentService;
use Iapps\RemittanceService\RemittanceRecord\Mode\BDOPaymentModeOption;
use Iapps\RemittanceService\RemittanceRecord\Mode\EwalletPaymentModeOption;
use Iapps\RemittanceService\RemittanceRecord\Mode\DefaultPaymentModeOption;
use Iapps\RemittanceService\RemittanceRecord\Mode\GPLPaymentModeOption;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransaction;
use Iapps\RemittanceService\RemittanceRecord\Mode\TMoneyPaymentModeOption;
use Iapps\RemittanceService\RemittanceRecord\Mode\BNIPaymentModeOption;
use Iapps\RemittanceService\RemittanceRecord\Mode\TransferToPaymentModeOption;
use Iapps\RemittanceService\RemittanceRecord\Mode\TransferToPaymentModeOption3;
use Iapps\RemittanceService\RemittanceRecord\Mode\TransferToCashPickupPaymentModeOption;





class RemittancePaymentModeOptionFactory{
    protected static $_instance;

    const EWALLET_PAYMENT_CODE = 'EWA';
    const BANK_TRANSFER_GPL = 'BT8';
    const TMONEY_PAYMENT_CODE = 'BT7';
    const BNI_PAYMENT_CODE = 'BT9';
    const TRANSFERTO_PAYMENT_CODE = 'TT1';
    const TRANSFERTO_PAYMENT_CODE_3 = 'TT3';
    const TRANSFERTO_CASH_PICKUP_PAYMENT_CODE = 'CP2';



    public static function build(RemittanceTransaction $transaction, $sender_id, $recipient_id, $ipAddress='127.0.0.1', $updatedBy=NULL)
    {
        switch($transaction->getConfirmPaymentCode()){
            case self::EWALLET_PAYMENT_CODE:
                self::$_instance = new EwalletPaymentModeOption($transaction, $sender_id, $recipient_id);
                break;
            case self::BANK_TRANSFER_GPL:
                self::$_instance = new GPLPaymentModeOption($transaction, $sender_id, $recipient_id, $ipAddress, $updatedBy);
                break;
            case self::TMONEY_PAYMENT_CODE:
                self::$_instance = new TMoneyPaymentModeOption($transaction, $sender_id, $recipient_id);
                break;
            case self::BNI_PAYMENT_CODE:
                self::$_instance = new BNIPaymentModeOption($transaction, $sender_id, $recipient_id);
                break;
            case self::TRANSFERTO_PAYMENT_CODE:
                self::$_instance = new TransferToPaymentModeOption($transaction, $sender_id, $recipient_id);
                break;
            case self::TRANSFERTO_PAYMENT_CODE_3:
                self::$_instance = new TransferToPaymentModeOption3($transaction, $sender_id, $recipient_id);
                break;
            case self::TRANSFERTO_CASH_PICKUP_PAYMENT_CODE:
                self::$_instance = new TransferToCashPickupPaymentModeOption($transaction, $sender_id, $recipient_id);
                break;
            default:
                self::$_instance = new DefaultPaymentModeOption($transaction, $sender_id, $recipient_id);
                break;
        }

        return self::$_instance;
    }
}