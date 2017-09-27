<?php

namespace Iapps\RemittanceService\RemittanceTransaction;

class RemittanceTransactionEventType{

    const REMITTANCE_TRANSACTION_CREATED = 'remittance.transaction.created';
    const REMITTANCE_STATUS_CHANGED = 'remittance.status.changed';
    const REMITTANCE_APPROVAL_REQUIRED_CHANGED = 'remittance.approvalRequired.changed';
    const REMITTANCE_COMPLETED = 'remittance.completed';
    const REMITTANCE_TRANSACTION_USER_CONVERTED = 'remittance.transaction.userConverted';
    const REMITTANCE_TRANSACTION_STATUS_CHANGED = 'remittance.transaction.statusChanged';
}