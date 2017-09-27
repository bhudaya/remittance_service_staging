<?php

namespace Iapps\RemittanceService\Common;

/*
* Predefined Access Function Code Is Here
*/
class FunctionCode{
    /*
     * For Admin Panel
     */
    const ADMIN_ADD_RATES = 'admin_add_rates';
    const ADMIN_LIST_RATES = 'admin_list_rates';
    const ADMIN_UPDATE_RATES_STATUS = 'admin_update_rates_status';
    const ADMIN_VIEW_PENDING_RATES = 'admin_view_pending_rates';
    const ADMIN_LIST_CONFIG_WITH_PENDING_RATES = 'admin_list_config_with_pending_rates';
    const ADMIN_VIEW_RATES_TREND = 'admin_view_rates_trend';
    
    
    const ADMIN_ADD_CHANNEL                     = 'admin_add_channel';
    const ADMIN_LIST_CHANNEL                    = 'admin_list_channel';
    const ADMIN_UPDATE_CHANNEL                  = 'admin_update_channel';
    const ADMIN_UPDATE_CHANNEL_STATUS           = 'admin_update_channel_status';
    const ADMIN_LIST_PRICING_FEE                = 'admin_list_pricing_fee';
    const ADMIN_LIST_PRICING_COST               = 'admin_list_pricing_cost';
    const ADMIN_LIST_PAYMENT_MODE               = 'admin_list_payment_mode';
    const ADMIN_LIST_COLLECTION_MODE            = 'admin_list_collection_mode';
    const ADMIN_ADD_PAYMENT_MODE                = 'admin_add_payment_mode';
    const ADMIN_ADD_COLLECTION_MODE             = 'admin_add_collection_mode';
    const ADMIN_UPDATE_PAYMENT_MODE_STATUS      = 'admin_update_payment_mode_status';
    const ADMIN_ADD_PAYMENT_MODE_FEE            = 'admin_add_payment_mode_fee';
    const ADMIN_ADD_COLLECTION_MODE_FEE         = 'admin_add_collection_mode_fee';
    const ADMIN_LIST_PAYMENT_MODE_FEE_GROUP     = 'admin_list_payment_mode_fee_group';
    const ADMIN_LIST_PAYMENT_MODE_FEE           = 'admin_list_payment_mode_fee';
    const ADMIN_UPDATE_PAYMENT_MODE_FEE_GROUP_STATUS = 'admin_update_payment_mode_fee_group_status';
    const ADMIN_UPDATE_PAYMENT_MODE_FEE         = 'admin_update_payment_mode_fee';
    const ADMIN_ADD_PAYMENT_MODE_COST           = 'admin_add_payment_mode_cost';
    const ADMIN_ADD_COLLECTION_MODE_COST        = 'admin_add_collection_mode_cost';
    const ADMIN_LIST_PAYMENT_MODE_COST_GROUP    = 'admin_list_payment_mode_cost_group';
    const ADMIN_LIST_PAYMENT_MODE_COST          = 'admin_list_payment_mode_cost';
    const ADMIN_UPDATE_PAYMENT_MODE_COST_GROUP_STATUS = 'admin_update_payment_mode_cost_group_status';
    const ADMIN_UPDATE_PAYMENT_MODE_COST        = 'admin_update_payment_mode_cost';
    const ADMIN_LIST_PROFIT_SHARING             = 'admin_list_profit_sharing';
    const ADMIN_ADD_PROFIT_SHARING              = 'admin_add_profit_sharing';
    const ADMIN_UPDATE_PROFIT_SHARING           = 'admin_update_profit_sharing';
    const ADMIN_CANCEL_PROFIT_SHARING           = 'admin_cancel_profit_sharing';
    const ADMIN_GENERATE_REGULATORY_REPORT      = 'admin_generate_regulatory_report';


    const ADMIN_LIST_REMITTANCE_TRANSACTION_FOR_OTHERS = 'admin_list_remittance_transaction_for_others';

    const ADMIN_LIST_REFUND_REQUEST_FOR_REQUESTER = 'admin_list_refund_request_for_requester';
    const ADMIN_GET_REFUND_REQUEST_FOR_REQUESTER = 'admin_get_refund_request_for_requester';
    const ADMIN_LIST_REFUND_REQUEST_FOR_CHECKER = 'admin_list_refund_request_for_checker';
    const ADMIN_GET_REFUND_REQUEST_FOR_CHECKER = 'admin_get_refund_request_for_checker';
    const ADMIN_UPDATE_REFUND_REQUEST_APPROVAL_STATUS = 'admin_update_refund_request_approval_status';

    const ADMIN_GET_REMITTANCE_TRANSACTION_DETAIL = 'admin_get_remittance_transaction_detail';

    const ADMIN_GET_WORLD_CHECK               = 'admin_get_world_check';

    const ADMIN_LIST_COMPANY = 'admin_list_company';
    const ADMIN_GET_COMPANY = 'admin_get_company';
    const ADMIN_EDIT_COMPANY = 'admin_edit_company';

    const ADMIN_VOID = 'admin_void';
    
    const ADMIN_EDIT_RECIPIENT_SETTING = 'admin_edit_recipient_setting';
    const ADMIN_GET_RECIPIENT_SETTING = 'admin_get_recipient_setting';

    /*
     * For Partner Panel
     */
    const PARTNER_ADD_RATE = 'partner_add_rate';
    const PARTNER_LIST_RATES = 'partner_list_rates';
    const PARTNER_UPDATE_RATE_STATUS = 'partner_update_rate_status';
    const PARTNER_VIEW_PENDING_RATE = 'partner_view_pending_rate';
    const PARTNER_LIST_CONFIG_WITH_PENDING_RATES = 'partner_list_config_with_pending_rates';
    const PARTNER_VIEW_RATES_TREND = 'partner_view_rates_trend';

    const PARTNER_COMPLETE_REMITTANCE_CASH_OUT = 'partner_complete_remittance_cash_out';
    
    const PARTNER_GET_WORLD_CHECK               = 'partner_get_world_check';
    const PARTNER_ADD_WORLD_CHECK               = 'partner_add_world_check';
    const PARTNER_LIST_REMITTANCE_TRANSACTION   = 'partner_list_remittance_transaction';
    const PARTNER_GET_REMITTANCE_TRANSACTION_DETAIL = 'partner_get_remittance_transaction_detail';
    const PARTNER_UPLOAD_MAS_DOCUMENT           = 'partner_upload_mas_document';
    const PARTNER_REMOVE_MAS_DOCUMENT           = 'partner_remove_mas_document';
    const PARTNER_GET_MAS_DOCUMENT              = 'partner_get_mas_document';
    const PARTNER_LIST_USER_RISK_LEVEL          = 'partner_list_user_risk_level';
    const PARTNER_UPDATE_USER_RISK_LEVEL        = 'partner_update_user_risk_level';
    const PARTNER_UPDATE_USER_RISK_LEVEL_STATUS = 'partner_update_user_risk_level_status';
    const PARTNER_GENERATE_REGULATORY_REPORT    = 'partner_generate_regulatory_report';

    const PARTNER_FINANCE_LIST_REMITTANCE_TRANSACTION    = 'partner_fin_list_remittance_transaction';
    const PARTNER_FINANCE_GET_REMITTANCE_TRANSACTION_DETAIL = 'partner_fin_get_remittance_transaction_detail';

    const PARTNER_VERFIY_USER = 'partner_verify_user';
    const PARTNER_GET_USER = 'partner_get_user';
    const PARTNER_UPLOAD_DOCUMENT = 'partner_upload_document';
    const PARTNER_VIEW_DOCUMENT = 'partner_view_document';
    const PARTNER_DELETE_DOCUMENT = 'partner_delete_document';

    const VIEW_TEKTAYA_REPORT    = 'view_tektaya_report';
    
    const ADMIN_LIST_TRANSACTION_FOR_OTHERS = 'admin_list_transaction_for_others' ;
    const PARTNER_LIST_TRANSACTION_FOR_OTHERS = 'partner_list_transaction_for_others' ;

    const VIEW_SUPPORT_TEAM_DASHBOARD = 'view_support_team_dashboard';
    const VIEW_REMITTANCE_DASHBOARD = 'view_remittance_dashboard';

    const SYSTEM_GET_REMITTANCE_INFO = 'system_get_remittance_info';

    const PARTNER_GET_COMPANY_INFO = 'partner_get_company_info';
    const PARTNER_EDIT_COMPANY_INFO = 'partner_edit_company_info';

}
