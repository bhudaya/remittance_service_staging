<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	http://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
$route['default_controller'] = 'welcome';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;


// attribute

$route['attribute/value/get'] = 'attribute/Attribute_value/getValues';
$route['attribute/value/get/all'] = 'attribute/Attribute_value/getAll';

$route['user/attribute/value/get'] = 'attribute/Attribute_value_user/getValues';
$route['user/attribute/value/get/all'] = 'attribute/Attribute_value_user/getAll';

/*
 * Agent - Remittance
 */
$route['agent/user/recipient/list'] = 'recipient/Recipient_agent/getList';
$route['agent/user/recipient/detail'] = 'recipient/Recipient_agent/getDetail';
$route['agent/user/recipient/add'] = 'recipient/Recipient_agent/add';
$route['agent/user/recipient/edit'] = 'recipient/Recipient_agent/edit';
$route['agent/user/recipient/deactivate'] = 'recipient/Recipient_agent/deactivate';
$route['agent/user/recipient/collectioninfo/list'] = 'recipient/Recipient_agent/listCollectionInfo';
$route['agent/user/recipient/collectioninfo/add'] = 'recipient/Recipient_agent/addCollectionInfo';
$route['agent/user/recipient/collectioninfo/edit'] = 'recipient/Recipient_agent/editCollectionInfo';
$route['agent/user/remco/recipient/add'] = 'remittancecompanyrecipient/Remittance_company_recipient_agent/add'; 
$route['agent/user/remco/recipient/list'] = 'remittancecompanyrecipient/Remittance_company_recipient_agent/getList'; 
$route['agent/user/remco/recipient/verify'] = 'remittancecompanyrecipient/Remittance_company_recipient_agent/verify';
$route['agent/attribute/value/get'] = 'attribute/Attribute_value_agent/getValues';
$route['agent/service/list'] = 'remittanceconfig/Remittance_config_agent/getRemittanceChannelList';
$route['agent/remittance/config/paymentmode/fee/get'] = 'remittanceconfig/Remittance_config_agent/getCorpServicePaymentModeAndFeeByRemittanceConfigId';
$route['agent/user/remittance/request'] = 'remittancerecord/Remittance_agent/request';
$route['agent/user/remittance/complete'] = 'remittancerecord/Remittance_agent/complete';
$route['agent/user/remittance/cancel'] = 'remittancerecord/Remittance_agent/cancel';
$route['agent/user/remittance/pending/list'] = 'remittancerecord/Remittance_agent/getPendingRemittanceList';

$route['agent/user/profile/complete'] = 'remittancecompanyuser/Remittance_company_user_agent/completeProfile';
$route['agent/user/profile/get'] = 'remittancecompanyuser/Remittance_company_user_agent/getProfile';

$route['agent/company/get'] = 'remittancecompany/Remittance_company_agent/getCompany';

$route['agent/setting/recipient/get'] = 'common/Core_config_agent/getRecipientSetting';

/*
 * User - Remittance
 */
$route['user/service/list'] = 'remittanceconfig/Remittance_config_user/getRemittanceChannelList';
$route['user/service/list/v2'] = 'remittanceconfig/Remittance_config_user_v2/getRemittanceChannelList';
$route['user/remittance/config/paymentmode/fee/get'] = 'remittanceconfig/Remittance_config_user/getCorpServicePaymentModeAndFeeByRemittanceConfigId';
$route['user/remittance/request'] = 'remittancerecord/Remittance_user/request';
$route['user/remittance/complete'] = 'remittancerecord/Remittance_user/complete';
$route['user/remittance/cancel'] = 'remittancerecord/Remittance_user/cancel';

$route['user/remittance/list'] = 'remittancerecord/Remittance_user/getRemittanceList';


/*
 * User - Recipient
 */
$route['user/recipient/list'] = 'recipient/Recipient_user/getUserRecipientList';
$route['user/recipient/list/v2'] = 'recipient/Recipient_user_v2/getList';
$route['user/recipient/local/list'] = 'recipient/Recipient_user/getLocalRecipientList';
$route['user/recipient/add'] = 'recipient/Recipient_user/addUserRecipient';
$route['user/recipient/add/v2'] = 'recipient/Recipient_user_v2/add';
$route['user/recipient/edit/v2'] = 'recipient/Recipient_user_v2/edit';
$route['user/recipient/local/add'] = 'recipient/Recipient_user/addLocalRecipient';
$route['user/recipient/get'] = 'recipient/Recipient_user/getUserRecipientDetail';
$route['user/recipient/get/v2'] = 'recipient/Recipient_user_v2/getDetail';
$route['user/recipient/deactivate'] = 'recipient/Recipient_user_v2/deactivate';
$route['user/recipient/photo/upload'] = 'recipient/Recipient_user/uploadPhoto';
$route['user/recipient/collectioninfo/add'] = 'recipient/Recipient_user_v2/addCollectionInfo';
$route['user/recipient/collectioninfo/edit'] = 'recipient/Recipient_user_v2/editCollectionInfo';

$route['user/company/list'] = 'remittancecompany/Remittance_company_user/getList';
$route['user/remittance/path/list'] = 'remittanceservice/Remittance_service_user/getList';
$route['user/profile/list'] = 'remittancecompanyuser/Remittance_company_user_user/getList';

$route['user/setting/recipient/get'] = 'common/Core_config_user/getRecipientSetting';

$route['partner/recipient/get']['get'] = 'recipient/Recipient_partner/getRecipientDetail';
$route['partner/remco/recipient/list']['get'] = 'remittancecompanyrecipient/Remittance_company_recipient_partner/getList';
$route['admin/recipient/get']['get'] = 'recipient/Recipient_admin/getRecipientDetail';
/*
 * Partner - Remittance
 */

$route['partner/remittance/cashout/complete'] = 'remittancerecord/Remittance_partner/completeCashOut';

/*
 * Service - Remittance
 */
$route['service/remittance/record/get'] = 'remittancerecord/Remittance_system/getRemittanceRecordById';
$route['service/recipient/get'] = 'recipient/Recipient_system/getRecipientDetail';
$route['service/recipient/getbyids'] = 'recipient/Recipient_system/getRecipientDetailByIds';

$route['system/company/get'] = 'remittancecompany/Remittance_company_system/get';
$route['service/company/getbycompanycode'] = 'remittancecompany/Microservice_Remittance_company/getByCompanycode';

//Remittance Service
$route['remittanceservice/list'] = 'remittanceservice/Remittance_service_admin/getAllRemittanceServiceConfig';
$route['remittanceservice/get'] = 'remittanceservice/Remittance_service_admin/getRemittanceServiceConfigInfo';
$route['remittanceservice/add'] = 'remittanceservice/Remittance_service_admin/addRemittanceServiceConfig';
$route['remittanceservice/edit'] = 'remittanceservice/Remittance_service_admin/editRemittanceServiceConfig'; //obsolete
$route['remittanceservice/rates/edit'] = 'remittanceservice/Remittance_service_admin/editRemittanceServiceConfigRates'; //obsolete
$route['remittanceservice/getbyfromcountrycode'] = 'remittanceservice/Remittance_service_admin/getAllRemittanceServiceConfigByFromCountryCode';
$route['remittanceservice/search'] = 'remittanceservice/Remittance_service_admin/getRemittanceServiceConfigBySearchFilter';

/*
 * Admin - Remittance Channel Config / Remittance Configuration
 */
$route['admin/remittanceconfig/list'] = 'remittanceconfig/Remittance_config_admin/getAllRemittanceConfig';
$route['admin/remittanceconfig/get'] = 'remittanceconfig/Remittance_config_admin/getRemittanceConfigInfo';
$route['admin/remittanceconfig/search'] = 'remittanceconfig/Remittance_config_admin/getExistsRemittanceConfigList';
$route['admin/remittanceconfig/add'] = 'remittanceconfig/Remittance_config_admin/addRemittanceConfig';
$route['admin/remittanceconfig/edit'] = 'remittanceconfig/Remittance_config_admin/editRemittanceConfig';
$route['admin/remittanceconfig/option/edit'] = 'remittanceconfig/Remittance_config_admin/editRemittanceConfigOption';
$route['admin/remittanceconfig/updatestatus'] = 'remittanceconfig/Remittance_config_admin/updateRemittanceConfigStatus';


//Corp Service Remittance Config
$route['corp/serv/remittanceconfig/list'] = 'remittanceconfig/Corp_serv_remittance_config/getAllCorpServiceRemittanceConfig';
$route['corp/serv/remittanceconfig/get/remittanceserviceid'] = 'remittanceconfig/Corp_serv_remittance_config/getDefaultRemittanceConfigByRemittanceServiceId';
$route['corp/serv/remittanceconfig/search'] = 'remittanceconfig/Corp_serv_remittance_config/getRemittanceConfigInfoBySearchFilter';
$route['corp/serv/remittanceconfig/add'] = 'remittanceconfig/Corp_serv_remittance_config/addCorpServiceRemittanceConfig';
$route['corp/serv/remittanceconfig/edit'] = 'remittanceconfig/Corp_serv_remittance_config/editCorpServiceRemittanceConfig';
$route['corp/serv/remittanceconfig/get'] = 'remittanceconfig/Corp_serv_remittance_config/getCorpServiceRemittanceConfigInfo';
$route['corp/serv/remittance/fee/get/remittanceconfigid'] = 'remittanceconfig/Corp_serv_remittance_config/getCorpServiceFeeByRemittanceConfigId';

//Corp Service Fee
$route['corp/serv/fee/add'] = 'common/Corporate_service_fee/addCorpServiceFee';
$route['corp/serv/fee/edit'] = 'common/Corporate_service_fee/editCorpServiceFee';
$route['corp/serv/fee/get'] = 'common/Corporate_service_fee/getCorporateServiceFeeInfo';
$route['corp/serv/fee/get/corpservid'] = 'common/Corporate_service_fee/getCorporateServiceFeeByCorpServId';

//Corporate Service Payment Mode
$route['corp/serv/paymentmode/get/corpservid'] = 'common/Corporate_service_payment_mode/getCorporateServicePaymentModeByCorporateServiceId';
$route['corp/serv/paymentmode/add'] = 'common/Corporate_service_payment_mode/addCorporateServicePaymentMode';
$route['corp/serv/paymentmode/edit'] = 'common/Corporate_service_payment_mode/editCorporateServicePaymentMode';
$route['corp/serv/paymentmode/remove'] = 'common/Corporate_service_payment_mode/removeCorporateServicePaymentMode';
$route['corp/serv/paymentmode/fee/get/corpservid'] = 'common/Corporate_service_payment_mode/getCorporateServicePaymentModeWithFeeByCorporateServiceId';
$route['corp/serv/paymentmode/get'] = 'common/Corporate_service_payment_mode/getCorporateServicePaymentModeInfo';

//Corporate Service Payment Mode Fee
$route['corp/serv/paymentmode/fee/get/paymentmodeid'] = 'common/Corporate_service_payment_mode_fee/getCorporateServicePaymentModeFeeByCorporateServicePaymentModeId';
$route['corp/serv/paymentmode/fee/add'] = 'common/Corporate_service_payment_mode_fee/addCorporateServicePaymentModeFee';
$route['corp/serv/paymentmode/fee/edit'] = 'common/Corporate_service_payment_mode_fee/editCorporateServicePaymentModeFee';
$route['corp/serv/paymentmode/fee/remove'] = 'common/Corporate_service_payment_mode_fee/removeCorporateServicePaymentModeFee';
$route['corp/serv/paymentmode/fee/get'] = 'common/Corporate_service_payment_mode_fee/getCorporateServicePaymentModeFeeInfo';


//ExchangeRate
$route['admin/rates/add'] = 'exchangerate/Exchange_rate_admin/addRates';
$route['admin/rates/list'] = 'exchangerate/Exchange_rate_admin/getRateListing';
$route['admin/rates/editable'] = 'exchangerate/Exchange_rate_admin/getEditableRates';
$route['admin/rates/pending'] = 'exchangerate/Exchange_rate_admin/getPendingRates';
$route['admin/rates/status/update'] = 'exchangerate/Exchange_rate_admin/updateRatesStatus';
$route['admin/rates/trend/get'] = 'exchangerate/Exchange_rate_admin/getTrendData';
$route['admin/config/rates/approving/list'] = 'exchangerate/Exchange_rate_admin/getRemittanceConfigListWithPendingRates';
$route['admin/config/rates/list'] = 'exchangerate/Exchange_rate_admin/getRemittanceConfigListWithRates';


$route['partner/rate/add'] = 'exchangerate/Exchange_rate_partner/addRate';
$route['partner/rate/list'] = 'exchangerate/Exchange_rate_partner/getRateListing';
$route['partner/rate/editable'] = 'exchangerate/Exchange_rate_partner/getEditableRate';
$route['partner/rate/pending'] = 'exchangerate/Exchange_rate_partner/getPendingRate';
$route['partner/rate/status/update'] = 'exchangerate/Exchange_rate_partner/updateRateStatus';
$route['partner/config/rates/approving/list'] = 'exchangerate/Exchange_rate_partner/getRemittanceConfigListWithPendingRates';
$route['partner/config/rates/list'] = 'exchangerate/Exchange_rate_partner/getRemittanceConfigListWithRates';

$route['service/exchangerate/get'] = 'exchangerate/Microservice_exchange_rate/getRateByServiceProviderId';

/*
 * Partner - RemittanceTransaction
 */
$route['partner/remittrancetransaction/list'] = 'remittancerecord/Remittance_partner/getRemittanceTransactionList';
$route['partner/remittrancetransaction/detail'] = 'remittancerecord/Remittance_partner/getRemittanceTransactionDetail';
$route['partner/transaction/history/detail/refid'] = 'remittancetransaction/Partner_remittance_transaction/getTransactionHistoryDetailByRefId';
$route['partner/transaction/related/get'] = 'remittancetransaction/Partner_remittance_transaction/getRelatedTransaction';
$route['partner/transaction/refund/get'] = 'refundrequest/Refund_request_partner/getByTransactionID';

/*FINANCE related transactions*/
$route['partner/transaction/remittance/list'] = 'remittancerecord/Remittance_partner/getFinanceRemittanceTransactionList';
$route['partner/transaction/remittance/detail'] = 'remittancetransaction/Partner_remittance_transaction/getFinanceRemittanceTransactionDetailByRefId';

$route['partner/user/profile/verify'] = 'remittancecompanyuser/Remittance_company_user_partner/verifyUser';
$route['partner/user/profile/get'] = 'remittancecompanyuser/Remittance_company_user_partner/get';
$route['partner/user/list'] = 'remittancecompanyuser/Remittance_company_user_partner/getList';

$route['partner/document/upload'] = 'common/Document/uploadDocument';
$route['partner/document/remove'] = 'common/Document/removeDocument';
$route['partner/document/get'] = 'common/Document/getDocument';

$route['partner/report/nff'] = 'regulatoryreport/Regulatory_report_partner/getNFFReport';
/*
 * Admin - RemittanceTransaction
 */
$route['admin/remittrancetransaction/detail'] = 'remittancerecord/Remittance_admin/getRemittanceTransactionDetail';
$route['admin/transaction/history/detail/refid'] = 'remittancetransaction/Admin_remittance_transaction/getTransactionHistoryDetailByRefId';
$route['admin/transaction/history/listbyidarr'] = 'remittancetransaction/Admin_remittance_transaction/getTransactionListByRefIDArr';
$route['admin/remittrancetransaction/listbytransidarr'] = 'remittancerecord/Remittance_admin/getRemittanceTransactionListByTransIDArr';
$route['admin/transaction/void'] = 'remittancetransaction/Admin_remittance_transaction/voidTransaction';



$route['remittrancetransaction/add'] = 'remittancetransaction/Remittance_transaction/addRemittanceTransaction';
$route['transaction/reject'] = 'remittancetransaction/Remittance_transaction/rejectTransaction';
$route['transaction/approve'] = 'remittancetransaction/Remittance_transaction/approveTransaction';



/*
 * AUDIT LOG
 */
$route['log/listen'] = 'common/Audit_log/listenLogEvent';
$route['log/list'] = 'common/Audit_log/getAllAuditLog';
$route['log/get'] = 'common/Audit_log/getAuditLogByTableName';
$route['log/search'] = 'common/Audit_log/getAuditLogByTableName';

$route['exchangerate/log'] = 'exchangerate/Exchange_rate/getAuditLog';
$route['recipient/log'] = 'recipient/Recipient/getAuditLog';
$route['remittanceconfig/log'] = 'remittanceconfig/Remittance_config_admin/getAuditLog';
$route['remittance/log'] = 'remittancerecord/Remittance/getAuditLog';
$route['remittanceservice/log'] = 'remittanceservice/Remittance_service/getAuditLog';
$route['transaction/log'] = 'remittancetransaction/Remittance_transaction/getAuditLog';

// WorldCheck 
$route['partner/worldcheck/worldcheckprofile/save'] = 'remittanceofficer/World_check/saveWorldCheckProfile';
$route['partner/worldcheck/worldcheckprofile/get'] = 'remittanceofficer/World_check/getWorldCheckProfileByUserProfileId';
$route['partner/worldcheck/worldcheckprofile/listbyidarr'] = 'remittanceofficer/World_check/getWorldCheckProfileInfoByUserProfileIDArr';

$route['admin/worldcheck/worldcheckprofile/get'] = 'remittanceofficer/World_check_admin/getWorldCheckProfileByUserProfileId';
$route['admin/worldcheck/worldcheckprofile/listbyidarr'] = 'remittanceofficer/World_check_admin/getWorldCheckProfileInfoByUserProfileIDArr';

// User risk level
$route['partner/userrisklevel/update'] = 'remittanceofficer/User_risk_level/updateUserRiskLevel';
$route['partner/userrisklevel/updatestatus'] = 'remittanceofficer/User_risk_level/updateUserRiskLevelStatus';
$route['partner/userrisklevel/get'] = 'remittanceofficer/User_risk_level/getUserRiskLevelByUserProfiledId';
$route['partner/userrisklevel/list'] = 'remittanceofficer/User_risk_level/getAllUserRiskLevel';

//Documents

$route['partner/document/upload'] = 'common/Document_partner/uploadDocument';
$route['partner/document/remove'] = 'common/Document_partner/removeDocument';
$route['partner/document/get'] = 'common/Document_partner/getDocument';

// test upload un xml file to db
$route['test/uploadXML'] = 'risksourcetitle/Risk_source_title/getXMLFile';

//risk source title
$route['risksource/getsourcefile'] = 'risksourcetitle/Risk_source_title/getXMLFile';
$route['risksourcetitle/list'] = 'risksourcetitle/Risk_source_title/getAvailableRiskSourceTitle';
$route['risksource/mas/upload'] = 'risksourcetitle/Risk_source_title/uploadRiskProfileForMAS';



// profit sharing
$route['admin/profit/sharing/add'] = 'remittanceprofitsharing/Remittance_profit_sharing/addProfitSharing';
$route['admin/profit/sharing/list'] = 'remittanceprofitsharing/Remittance_profit_sharing/getAllProfitSharing';
$route['admin/profit/sharing/get'] = 'remittanceprofitsharing/Remittance_profit_sharing/getProfitSharingInfo';
$route['admin/profit/sharing/update'] = 'remittanceprofitsharing/Remittance_profit_sharing/updateProfitSharing';
$route['admin/profit/sharing/cancel'] = 'remittanceprofitsharing/Remittance_profit_sharing/cancelProfitSharing';
$route['admin/profit/sharing/search'] = 'remittanceprofitsharing/Remittance_profit_sharing/searchProfitSharing';

/*
 * Admin - Pricing Configuration
 */

// maker - view collection mode
$route['admin/pricing/collectionmode/list'] = 'remittancepaymentmode/Payment_mode_admin/getCollectionModeList';
$route['admin/pricing/collectionmode/add'] = 'remittancepaymentmode/Payment_mode_admin/addCollectionMode';

// maker - view collection mode - view fee
$route['admin/pricing/collectionmodefee/add'] = 'remittancepaymentmode/Payment_mode_admin/addCollectionModeFeeGroup';
$route['admin/pricing/collectionmodefee/get'] = 'remittancepaymentmode/Payment_mode_admin/getCollectionModeFeeGroupInfo';   //is last approved group info
$route['admin/pricing/collectionmodefee/list'] = 'remittancepaymentmode/Payment_mode_admin/getCollectionModeFeeListingByGroupId';    //is last approved fee listing

// maker - view collection mode - view cost
$route['admin/pricing/collectionmodecost/add'] = 'remittancepaymentmode/Payment_mode_admin/addCollectionModeCostGroup';
$route['admin/pricing/collectionmodecost/get'] = 'remittancepaymentmode/Payment_mode_admin/getCollectionModeCostGroupInfo';   //is last approved group info
$route['admin/pricing/collectionmodecost/list'] = 'remittancepaymentmode/Payment_mode_admin/getCollectionModeCostListingByGroupId';    //is last approved fee listing

// maker - view payment mode
$route['admin/pricing/paymentmode/list'] = 'remittancepaymentmode/Payment_mode_admin/getPaymentModeList';
$route['admin/pricing/paymentmode/add'] = 'remittancepaymentmode/Payment_mode_admin/addPaymentMode';

// maker - view payment mode - view fee
$route['admin/pricing/paymentmodefee/add'] = 'remittancepaymentmode/Payment_mode_admin/addPaymentModeFeeGroup';
$route['admin/pricing/paymentmodefee/get'] = 'remittancepaymentmode/Payment_mode_admin/getPaymentModeFeeGroupInfo';
$route['admin/pricing/paymentmodefee/list'] = 'remittancepaymentmode/Payment_mode_admin/getPaymentModeFeeListingByGroupId';

// maker - view payment mode - view cost
$route['admin/pricing/paymentmodecost/add'] = 'remittancepaymentmode/Payment_mode_admin/addPaymentModeCostGroup';
$route['admin/pricing/paymentmodecost/get'] = 'remittancepaymentmode/Payment_mode_admin/getPaymentModeCostGroupInfo';
$route['admin/pricing/paymentmodecost/list'] = 'remittancepaymentmode/Payment_mode_admin/getPaymentModeCostListing';


// checker - approval pricing fee
$route['admin/pricing/fee/list'] = 'remittancepaymentmode/Payment_mode_admin/getApprovalPricingFeeListing';
$route['admin/pricing/paymentmodefeegroup/get'] = 'remittancepaymentmode/Payment_mode_admin/getPaymentModeFeeGroupInfoByGroupId';
$route['admin/pricing/paymentmodefee/view'] = 'remittancepaymentmode/Payment_mode_admin/viewApprovalPricingFee';
$route['admin/pricing/paymentmodefee/updatestatus'] = 'remittancepaymentmode/Payment_mode_admin/approvalPaymentModeFeeGroup';
//$route['admin/pricing/fee/view'] = 'remittancepaymentmode/Payment_mode_admin/viewApprovalPricingFee';
//$route['admin/pricing/fee/approval'] = 'remittancepaymentmode/Payment_mode_admin/approvalPaymentModeFeeGroup';

// checker - approval pricing cost
$route['admin/pricing/cost/list'] = 'remittancepaymentmode/Payment_mode_admin/getApprovalPricingCostListing';
$route['admin/pricing/paymentmodecostgroup/get'] = 'remittancepaymentmode/Payment_mode_admin/getPaymentModeCostGroupInfoByGroupId';
$route['admin/pricing/paymentmodecost/view'] = 'remittancepaymentmode/Payment_mode_admin/viewApprovalPricingCost';
$route['admin/pricing/paymentmodecost/updatestatus'] = 'remittancepaymentmode/Payment_mode_admin/approvalPaymentModeCostGroup';
//$route['admin/pricing/cost/view'] = 'remittancepaymentmode/Payment_mode_admin/viewApprovalPricingCost';
//$route['admin/pricing/cost/approval'] = 'remittancepaymentmode/Payment_mode_admin/approvalPaymentModeCostGroup';

// checker - active collection/payment mode
$route['admin/pricing/collectionmode/active'] = 'remittancepaymentmode/Payment_mode_admin/activeCollectionMode';
$route['admin/pricing/paymentmode/active'] = 'remittancepaymentmode/Payment_mode_admin/activePaymentMode';


/*
    admin refund
*/

$route['admin/refund/request/list/requester'] = 'refundrequest/Refund_request_admin/getRefundRequestForRequester';
$route['admin/refund/request/list/checker'] = 'refundrequest/Refund_request_admin/getRefundRequestForChecker';

$route['admin/refund/request/get/requester'] = 'refundrequest/Refund_request_admin/getRefundRequestDetailForRequester';
$route['admin/refund/request/get/checker'] = 'refundrequest/Refund_request_admin/getRefundRequestDetailForChecker';
$route['admin/refund/request/approval/update'] = 'refundrequest/Refund_request_admin/updateRefundRequestApprovalStatus';


/*
  agent transaction history
*/
$route['agent/transaction/history/list'] = 'remittancetransaction/Agent_remittance_transaction/getTransactionHistoryList';
$route['agent/transaction/history/listbydate'] = 'remittancetransaction/Agent_remittance_transaction/getTransactionHistoryListByDate';
$route['agent/transaction/history/detail'] = 'remittancetransaction/Agent_remittance_transaction/getTransactionHistoryDetailByTransactionId';
$route['agent/transaction/history/detail/refid'] = 'remittancetransaction/Agent_remittance_transaction/getTransactionHistoryDetailByRefId';

$route['agent/transaction/history/user/list'] = 'remittancetransaction/Agent_remittance_transaction/getTransactionHistoryUserList';
$route['agent/transaction/history/user/listbydate'] = 'remittancetransaction/Agent_remittance_transaction/getTransactionHistoryUserListByDate';
$route['agent/transaction/history/foruser/listbyidarr'] = 'remittancetransaction/Agent_remittance_transaction/getTransactionListForUserByRefIDArr';

/*
  user transaction history
*/
$route['user/transaction/history/list'] = 'remittancetransaction/User_remittance_transaction/getTransactionHistoryList';
$route['user/transaction/history/detail'] = 'remittancetransaction/User_remittance_transaction/getTransactionHistoryDetailByTransactionId';
$route['user/transaction/history/detail/refid'] = 'remittancetransaction/User_remittance_transaction/getTransactionHistoryDetailByRefId';
$route['user/transaction/history/listbydate'] = 'remittancetransaction/User_remittance_transaction/getTransactionHistoryListByDate';

$route['user/transaction/history/listbyidarr'] = 'remittancetransaction/User_remittance_transaction/getTransactionListByRefIDArr';

/*
  admin transaction history
*/
$route['admin/transaction/listbyrefidarr'] = 'remittancetransaction/Admin_remittance_transaction/getTransactionListForUserByRefIDArr';
$route['admin/transaction/history/detail'] = 'remittancetransaction/Admin_remittance_transaction/getTransactionHistoryDetailByTransactionId';


/*
  partner transaction history
*/
$route['partner/transaction/listbyrefidarr'] = 'remittancetransaction/Partner_remittance_transaction/getTransactionListForUserByRefIDArr';
$route['partner/transaction/history/detail'] = 'remittancetransaction/Partner_remittance_transaction/getTransactionHistoryDetailByTransactionId';




$route['admin/regulatory/report'] = 'regulatoryreport/Regulatory_report_admin/getReport';
$route['partner/regulatory/report'] = 'regulatoryreport/Regulatory_report_partner/getReport';

/*
 * Batch Job
 */
$route['job/remittance/receipt/generate'] = 'common/Batch_job/listenGenerateReceipt';
$route['job/remittance/status/notify'] = 'common/Batch_job/listenRemittanceStatusChanged';
$route['job/remittance/autocancel'] = 'common/Batch_job/autoCancelTransaction';
$route['job/remittance/convertuser'] = 'common/Batch_job/listenUserConversion';
$route['job/remittance/completeewalletcashout'] = 'common/Batch_job/listenCompleteEwaletCashout';
$route['job/remittance/notifyadmin'] = 'common/Batch_job/listenNotifyAdmin';
$route['job/remittance/updateadminemail'] = 'common/Batch_job/listenUpdateAdminEmail';
$route['job/remittance/processdelivery'] = 'common/Batch_job/listenProcessDelivery';
$route['job/remittance/paymentrequestchanged'] = 'common/Batch_job/listenPaymentRequestChanged';
$route['job/remittance/processrefund'] = 'common/Batch_job/listenRefundInitiated';
$route['job/remittance/refundrequestchanged'] = 'common/Batch_job/listenRefundRequestChanged';
$route['job/remittance/accountverified/notify'] = 'common/Batch_job/listenNotifyAccountVerified';

/*
 * partner deposit tracker
 */
$route['partner/deposittracker/config/list'] = 'deposittracker/Deposit_tracker_partner/getAllRemittanceConfig';
$route['partner/deposittracker/view'] = 'deposittracker/Deposit_tracker_partner/partnerViewDeposit';
$route['partner/deposittracker/list'] = 'deposittracker/Deposit_tracker_partner/listDepositPartner';
$route['partner/deposittracker/deposit/transactions/get'] = 'deposittracker/Deposit_tracker_partner/partnerListTransaction';
$route['partner/deposittracker/topup/listpendingtopup'] = 'deposittracker/Deposit_tracker_partner/listPendingTopup';
$route['partner/deposittracker/topup/list'] = 'deposittracker/Deposit_tracker_partner/listTopup';
$route['partner/deposittracker/topup/view'] = 'deposittracker/Deposit_tracker_partner/partnerViewTopup';
$route['partner/deposittracker/topup/reject'] = 'deposittracker/Deposit_tracker_partner/partnerRejectTopup';
$route['partner/deposittracker/topup/approve'] = 'deposittracker/Deposit_tracker_partner/approveTopup';
$route['partner/deposittracker/deduction/list'] = 'deposittracker/Deposit_tracker_partner/partnerListDeduction';
$route['partner/deposittracker/deduction/view'] = 'deposittracker/Deposit_tracker_partner/partnerViewDeduction';
$route['partner/deposittracker/deduction/photo/upload'] = 'deposittracker/Deposit_tracker_partner/deductionUpload';
$route['partner/deposittracker/deduction/process'] = 'deposittracker/Deposit_tracker_partner/processDeduction';
$route['partner/deposittracker/topup/pendingtopup'] = 'deposittracker/Deposit_tracker_partner/getPartnerPendingTopup';
$route['partner/deposittracker/deduction/pendingdeductionlist'] = 'deposittracker/Deposit_tracker_partner/partnerListPendingDeduction';
$route['partner/deposittracker/deposit/history/check'] = 'deposittracker/Deposit_tracker_partner/historyCheck';
$route['partner/deposittracker/deposit/history/lastapproved'] = 'deposittracker/Deposit_tracker_partner/getLastApprovedConfig';
$route['partner/deposittracker/history/history/lastusers'] = 'deposittracker/Deposit_tracker_partner/getLastApprovedUsers';
$route['partner/deposittracker/deposit/reasons'] = 'deposittracker/Deposit_tracker_partner/getDepositReason';
$route['partner/deposittracker/deposit/remittance/get'] = 'deposittracker/Deposit_tracker_partner/getRemittanceByRemittanceId';


/*
 * admin deposit tracker
 */
$route['admin/deposittracker/create'] = 'deposittracker/Deposit_tracker_admin/addDeposit';
$route['admin/deposittracker/view'] = 'deposittracker/Deposit_tracker_admin/viewDeposit';
$route['admin/deposittracker/update'] = 'deposittracker/Deposit_tracker_admin/editDeposit';
$route['admin/deposittracker/list'] = 'deposittracker/Deposit_tracker_admin/listDeposit';
$route['admin/deposittracker/listpendingdeposit'] = 'deposittracker/Deposit_tracker_admin/listPendingDeposit';
$route['admin/deposittracker/deduction/listpendingdeduction'] = 'deposittracker/Deposit_tracker_admin/listPendingDeduction';
$route['admin/deposittracker/alllist'] = 'deposittracker/Deposit_tracker_admin/getAllDepositsForAdminMaker';
$route['admin/deposittracker/approve'] = 'deposittracker/Deposit_tracker_admin/approveDeposit';
$route['admin/deposittracker/reject'] = 'deposittracker/Deposit_tracker_admin/rejectDeposit';
$route['admin/deposittracker/topup/add'] = 'deposittracker/Deposit_tracker_admin/addTopup';
$route['admin/deposittracker/topup/list'] = 'deposittracker/Deposit_tracker_admin/listTopup';
$route['admin/deposittracker/topup/view'] = 'deposittracker/Deposit_tracker_admin/viewTopup';
$route['admin/deposittracker/topup/approve'] = 'deposittracker/Deposit_tracker_admin/approveTopup';
$route['admin/deposittracker/topup/reject'] = 'deposittracker/Deposit_tracker_admin/rejectTopup';
$route['admin/deposittracker/topup/cancel'] = 'deposittracker/Deposit_tracker_admin/cancelTopup';
$route['admin/deposittracker/deduction/add'] = 'deposittracker/Deposit_tracker_admin/addDeduction';
$route['admin/deposittracker/deduction/list'] = 'deposittracker/Deposit_tracker_admin/listDeduction';
$route['admin/deposittracker/deduction/view'] = 'deposittracker/Deposit_tracker_admin/viewDeduction';
$route['admin/deposittracker/deduction/cancel'] = 'deposittracker/Deposit_tracker_admin/cancelDeduction';
$route['admin/deposittracker/deduction/approve'] = 'deposittracker/Deposit_tracker_admin/approveDeduction';
$route['admin/deposittracker/deduction/reject'] = 'deposittracker/Deposit_tracker_admin/rejectDeduction';
//$route['admin/deposittrackerrequest/deposit/create'] = 'deposittracker/Deposit_tracker_request/addDepositRequest';
//$route['admin/deposittrackerrequest/deposit/view'] = 'deposittracker/Deposit_tracker_request/viewDepositRequest';
$route['admin/deposittracker/deposit/history/get'] = 'deposittracker/Deposit_tracker_admin/listHistory';
$route['admin/deposittracker/deposit/transactions/get'] = 'deposittracker/Deposit_tracker_admin/listTransaction';
$route['admin/deposittracker/deposit/history/tracker'] = 'deposittracker/Deposit_tracker_admin/getHistoryTracker';
$route['admin/deposittracker/deposit/history/historytrackers'] = 'deposittracker/Deposit_tracker_admin/getOldTrackers';
$route['admin/deposittracker/topup/photo/upload'] = 'deposittracker/Deposit_tracker_admin/topupUpload';
$route['admin/deposittracker/deposit/email/list'] = 'deposittracker/Deposit_tracker_admin/listEmailTracker';
$route['admin/deposittracker/deposit/config/get'] = 'deposittracker/Deposit_tracker_admin/getConfig';
$route['admin/deposittracker/deposit/config/listConfig'] = 'deposittracker/Deposit_tracker_admin/listConfig';
$route['admin/deposittracker/deposit/config/getallconfig'] = 'deposittracker/Deposit_tracker_admin/getAllConfig';
$route['admin/deposittracker/deposit/config/list'] = 'deposittracker/Deposit_tracker_admin/getConfigList';
$route['admin/deposittracker/deposit/config/bydeposit'] = 'deposittracker/Deposit_tracker_admin/getConfigByDeposit';
$route['admin/deposittracker/deposit/history/user/get'] = 'deposittracker/Deposit_tracker_admin/getHistoryUser';
$route['admin/deposittracker/trackers/previous/get'] = 'deposittracker/Deposit_tracker_admin/getPreviousUser';
$route['admin/deposittracker/deposit/history/email/get'] = 'deposittracker/Deposit_tracker_admin/getPreviousEmail';
$route['admin/deposittracker/deposit/email/tracker/get']= 'deposittracker/Deposit_tracker_admin/getEmailByDepositId';
$route['admin/deposittracker/deposit/email/tracker/pending'] = 'deposittracker/Deposit_tracker_admin/getPendingTrackerEmail';
$route['admin/deposittracker/deposit/email/tracker/lastapproved'] = 'deposittracker/Deposit_tracker_admin/getLastApprovedEmail';
$route['admin/deposittracker/deposit/holders/list'] = 'deposittracker/Deposit_tracker_admin/getApprovedDepositHolders';
$route['admin/deposittracker/deposit/history/check'] = 'deposittracker/Deposit_tracker_admin/historyCheck';
$route['admin/deposittracker/deposit/reasons'] = 'deposittracker/Deposit_tracker_admin/getDepositReason';
$route['job/deposittracker/depositdeduct'] = 'deposittracker/Remittance_deduction_batch/listenRemittanceDeduction';

/*
 * agung customer report
 */

$route['report/agungcustomer'] = 'agungreport/Agung_customer_report/getAgungCustomerReport';

/*
* SLA Dashboard
*/

$route['admin/sla/remittance/status'] = 'sladashboard/Sla_dashboard_admin/getSLARemittanceTransactionStatus';
$route['partner/sla/remittance/status'] = 'sladashboard/Sla_dashboard_partner/getSLARemittanceTransactionStatus';

/*
* Prelim check
*/
$route['prelim/remittance/get'] = 'remittancerecord/Remittance_system/getRemittanceInfo';
$route['prelim/remittance/sender'] = 'remittancerecord/Remittance_system/getSenderRemittanceInfo';
$route['prelim/remittance/recipient'] = 'remittancerecord/Remittance_system/getRecipientRemittanceInfo';

$route['system/remittance/get'] = 'remittancerecord/Remittance_system/getRemittanceRecord';
$route['system/recipient/get'] = 'recipient/Recipient_system/getRecipient';

/*
* GPL receipt
*/
$route['partner/company/get'] = 'remittancecompany/Remittance_company_partner/getCompany';
$route['partner/company/edit'] = 'remittancecompany/Remittance_company_partner/editCompany';

$route['admin/company/list'] = 'remittancecompany/Remittance_company_admin/listCompany';
$route['admin/company/get'] = 'remittancecompany/Remittance_company_admin/getCompany';
$route['admin/company/edit'] = 'remittancecompany/Remittance_company_admin/editCompany';

$route['admin/setting/recipient/get'] = 'common/Core_config_admin/getRecipientSetting';
$route['admin/setting/recipient/edit'] = 'common/Core_config_admin/editRecipientSetting';
