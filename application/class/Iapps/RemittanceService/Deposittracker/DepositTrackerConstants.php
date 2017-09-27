<?php

namespace Iapps\RemittanceService\DepositTracker;

class DepositTrackerConstants
{
    const APPROVED = 'approved';
    const DEDUCTION = 'deduction';
    const TRANSACTION = 'transaction';
    const LOWTHRESHOLDSTATUS = 'low';
    const NORMALTHRESHOLDSTATUS = 'normal';
    const DEPOSIT_STATUS_PENDING = 'pending';
    const DEPOSIT_STATUS_REJECTED = 'rejected';
    const DEPOSIT_STATUS_CANCELLED = 'cancelled';
    const ERROR_REMITTANCE_CONFIG_NOT_EXIST = "Remittance configuration id doesn't exist";
    const ERROR_REMITTANCE_NOT_YET_COLLECTED = "Remittance was not yet collected";
    const ERROR_DEPOSIT_INSUFFICIENT_BALANCE = "Deposit has insufficient balance. An Email Notification has been dispatched";
    const ERROR_CHANNEL_NOT_AVAILABLE = "Failed to get channel";
    const DEPOSIT_STATUS_APPROVED = "approved";
    const MESSAGE_DEDUCTION_FAILED = 'Approve Deduction Failed!. Insufficient Deposit balance to deduct from';
    const GET_DATA_SUCCESS = 555;
    const MESSAGE_GET_DATA_SUCCESS = 'data has been fetched successfully';
    const GET_DATA_FAILED = 666;
    const PUT_DATA_SUCCESS = 777;
    const PUT_DATA_FAILED = 999;
    const MESSAGE_EXISTING_PENDING_CONFIG = 'There is a pending config that was submitted';
    const REJECT_DEPOSIT_SUCCESS_MESSAGE = "Deposit has been rejected";
    const CORP_IAPPS = "iAPPS";
    const MESSAGE_INSUFFICIENT_BALANCE = "The deposit balance is not enough for deduction";
    const MESSAGE_APPROVE_DEDUCTION_FAILED = "Approve deduction failed";
    const MESSAGE_DEDUCTION_PROCESS_FAILED = "Process deduction failed";
    const MESSAGE_REJECT_DEDUCTION_SUCCESS = 'Deduction request has been rejected';
    const EDIT_DEPOSIT_SUCCESS_MESSAGE = "Updated deposit configuration have been submitted for approval";
    const APPROVE_DEPOSIT_SUCCESS_MESSAGE = "Deposit has been approved";

    
}

