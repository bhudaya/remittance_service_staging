<?php

namespace Iapps\RemittanceService\RefundRequest;

require_once __DIR__ . '/../../../../modules/refundrequest/models/Refund_request_model.php';
require_once __DIR__ . '/../../../../modules/remittancetransaction/models/Remittance_transaction_model.php';
require_once __DIR__ . '/../../../../modules/remittancetransaction/models/Remittance_transaction_item_model.php';
require_once __DIR__ . '/../../../../modules/attribute/models/Refund_request_attribute_model.php';


use Iapps\RemittanceService\Attribute\AttributeCode;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransaction;
use Iapps\RemittanceService\Common\TransactionType;
use Iapps\RemittanceService\RemittanceTransaction\ItemType;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransactionItemRepository;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransactionItemService;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransactionRepository;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransactionService;
use Iapps\RemittanceService\Common\SystemCodeServiceFactory;
use Iapps\RemittanceService\Attribute\RefundRequestAttributeServiceFactory;
use Iapps\RemittanceService\Common\GeneralDescription;


class RefundRequestServiceFactory {

    protected static $_instance;

    public static function build()
    {
        if( self::$_instance == NULL )
        {
            $ti_dm = new \remittance_transaction_item_model();
            $ti_repo = new RemittanceTransactionItemRepository($ti_dm);
            $transaction_item_service = new RemittanceTransactionItemService($ti_repo);

            $system_code_service = SystemCodeServiceFactory::build();

            $t_dm = new \remittance_transaction_model();
            $t_repo = new RemittanceTransactionRepository($t_dm);
            $transaction_service = new RemittanceTransactionService($t_repo, $transaction_item_service, $system_code_service);

            $refund_attribute_service = RefundRequestAttributeServiceFactory::build();

            $dm = new \Refund_request_model();
            $repo = new RefundRequestRepository($dm);
            self::$_instance = new RefundRequestService($repo, $transaction_service, $system_code_service, $refund_attribute_service);

            self::$_instance->setRefundRequestTableName('iafb_remittance.refund_request');
            self::$_instance->setRefundTransactionTypeCode(TransactionType::CODE_REFUND);
            self::$_instance->setRefundTransactionTypSystemGroupCode(TransactionType::getSystemGroupCode());
            self::$_instance->setExcludeItemTypeCodeArr(array(ItemType::PAYMENT_FEE));
            self::$_instance->setExcludeItemTypeSystemGroupCode(ItemType::getSystemGroupCode());
            self::$_instance->setRefundTransaction(new RemittanceTransaction());
            self::$_instance->setRefundReasonAttributeCode(AttributeCode::REFUND_REASON);
            self::$_instance->setRefundRejectReasonAttributeCode(AttributeCode::REFUND_REJECT_REASON);
            self::$_instance->setPaymentInterface(new AdminRefundPayment());
            self::$_instance->setGeneralDescription(new GeneralDescription());
        }

        return self::$_instance;
    }
}