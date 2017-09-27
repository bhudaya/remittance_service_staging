<?php

namespace Iapps\RemittanceService\Common;

/*
* Core config Data constanst to be added here
*/
class CoreConfigType
{
    const SENDER_EMAIL_TEMPLATE = 'sender_email_template';
    const RECEIVER_EMAIL_TEMPLATE = 'receiver_email_template';

    const RSURL_OFAC_SDN_ADVANCED       = 'rsurl_ofac_sdn_advanced';
    const RSURL_OFAC_SDN                = 'rsurl_ofac_sdn';
    const RSURL_OFAC_NON_SDN_ADVANCED   = 'rsurl_ofac_non_sdn_advanced';
    const RSURL_OFAC_NON_SDN_LIST       = 'rsurl_ofac_non_sdn_list';

    const RSURL_UN_1718                 = 'rsurl_un_1718';
    const RSURL_UN_1533                 = 'rsurl_un_1533';
    const RSURL_UN_1844                 = 'rsurl_un_1844';
    const RSURL_UN_2231                 = 'rsurl_un_2231';
    const RSURL_UN_1970                 = 'rsurl_un_1970';
    const RSURL_UN_2206                 = 'rsurl_un_2206';
    const RSURL_UN_1591                 = 'rsurl_un_1591';
    const RSURL_UN_2140                 = 'rsurl_un_2140';
    const RSURL_UN_1267                 = 'rsurl_un_1267';
    const RSURL_UN_1988                 = 'rsurl_un_1988';

    const RSURL_MAS                     = 'rsurl_mas';

    CONST SENDER_PROCESSING_MESSAGE = 'sender_processing_message';
    CONST SENDER_DELIVERING_MESSAGE = 'sender_delivering_message';
    CONST SENDER_DELIVERING_MESSAGE_CASH = 'sender_delivering_message_cash';
    CONST SENDER_COLLECTED_MESSAGE = 'sender_collected_message';
    CONST RECEIVER_DELIVERING_MESSAGE = 'receiver_delivering_message';
    CONST RECEIVER_DELIVERING_SMS = 'receiver_delivering_sms';
    CONST RECEIVER_DELIVERING_SMS_EWALLET = 'receiver_delivering_sms_ewallet';
    CONST RECEIVER_COLLECTED_MESSAGE = 'receiver_collected_message';
    CONST RECEIVER_COLLECTED_MESSAGE_EWALLET = 'receiver_collected_message_ewallet';
    CONST SENDER_DELIVERING_SMS_CASH = 'sender_delivering_sms_cash';
    CONST SENDER_DELIVERING_SMS = 'sender_delivering_sms';
    CONST SENDER_REJECTED_MESSAGE = 'sender_rejected_message';
    CONST SENDER_FAILED_MESSAGE = 'sender_failed_message';

    CONST CASHOUT_EXPIRY_PERIOD = 'cashout_expiry_period';


    CONST REFUND_REQUEST_REFUNDED_MESSAGE = 'refund_request_refunded_message';
    CONST REFUND_REQUEST_REFUNDED_SMS = 'refund_request_refunded_sms';

    const ACCOUNT_VERIFIED_EMAIL_SUBJECT = 'account_verified_email_subject';
    const ACCOUNT_VERIFIED_EMAIL_BODY = 'account_verified_email_body';
    const ACCOUNT_VERIFIED_MESSAGE = 'account_verified_message';
	
	CONST MAX_RECIPIENT = 'max_recipient';
	CONST MAX_COLLECTION_INFO = 'max_collection_info';
}