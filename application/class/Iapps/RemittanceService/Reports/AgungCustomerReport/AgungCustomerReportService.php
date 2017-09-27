<?php

namespace Iapps\RemittanceService\Reports\AgungCustomerReport;
use Iapps\Common\Core\IappsBasicBaseService;
use Iapps\RemittanceService\Common\MessageCode;
use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\Microservice\PaymentService\PaymentServiceFactory;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransactionServiceFactory;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfigServiceFactory;
use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\Common\Microservice\CountryService\CountryServiceFactory;
use Iapps\Common\Microservice\ReportService\CodeMapperServiceFactory;
use Iapps\Common\Microservice\ModuleCode;
use Iapps\Common\Microservice\ReportService\CodeMapperCollection;
use Iapps\Common\Microservice\ReportService\CodeMapper;
use Iapps\RemittanceService\Attribute\AttributeCode;
use Iapps\RemittanceService\Attribute\RecipientAttributeServiceFactory;
use Iapps\RemittanceService\Attribute\AttributeValueServiceFactory;
use Iapps\RemittanceService\RemittanceServiceConfig\RemittanceServiceConfigServiceFactory;

class AgungCustomerReportService extends IappsBasicBaseService
{   
    protected $INDO_COUNTRY_CODE = 'ID';
    protected $TIME_ZONE = 'Asia/Jakarta';

    public function getAgungCustomerReport($admin_id, $date_from,$date_to,$report_lang)
    {
        $report_data = array();

        $payment_serv = PaymentServiceFactory::build();
        $transaction_serv = RemittanceTransactionServiceFactory::build();
        $remittance_config_serv = RemittanceConfigServiceFactory::build();
        $account_serv = AccountServiceFactory::build();
        $country_serv = CountryServiceFactory::build();
        $codemapper_serv = CodeMapperServiceFactory::build();
        $recipient_attr_serv  = RecipientAttributeServiceFactory::build();
        $attr_value_serv = AttributeValueServiceFactory::build();
        $remittanceSer_serv = RemittanceServiceConfigServiceFactory::build();

        if ($paymentInfos = $payment_serv->customerReportGetDataByParam('BT1',$date_from, $date_to)) {
        
            foreach ($paymentInfos->result as $paymentEach) {
                
                $final_results = array();
                $final_results['trx_refer_no'] = '';
                $final_results['ltkl_correction_no'] = '';
                $final_results['report_type'] = '1'; // hard code 
                $final_results['chd_act_as'] = '2';
                $final_results['sender_indi_account_no'] = '';
                $final_results['sender_indi_bank_name'] = '';
                $final_results['sender_indi_full_name'] = '';
                $final_results['sender_indi_dob'] = '';
                $final_results['sender_indi_city'] = '';
                $final_results['sender_indi_country'] = '';
                $final_results['sender_indi_other_country'] = '';
                $final_results['sender_indi_is_source_cash'] = '2';
                $final_results['sender_indi_address'] = '';
                $final_results['sender_indi_mobile_no'] = '';
                $final_results['sender_indi_id_type'] = '';
                $final_results['sender_indi_other_id_type'] = '';
                $final_results['sender_indi_id_number'] = '';
                $final_results['sender_corp_account_no'] = '';
                $final_results['sender_corp_bank_name'] = '';
                $final_results['sender_corp_name'] = '';
                $final_results['sender_corp_country'] = '';
                $final_results['sender_corp_other_country'] = '';
                $final_results['sender_corp_is_source_cash'] = '';
                $final_results['sender_corp_address'] = '';
                $final_results['sender_corp_mobile_no'] = '';
                $final_results['recipient_indi_trx_code'] = '';
                $final_results['recipient_indi_refer_no'] = '';
                $final_results['recipient_indi_bank_name'] = '';
                $final_results['recipient_indi_full_name'] = '';
                $final_results['recipient_indi_dob'] = '';
                $final_results['recipient_indi_address'] = '';
                $final_results['recipient_indi_province'] = '';
                $final_results['recipient_indi_other_province'] = '';
                $final_results['recipient_indi_city'] = '';
                $final_results['recipient_indi_other_city'] = '';
                $final_results['recipient_indi_address2'] = '';
                $final_results['recipient_indi_province2'] = '';
                $final_results['recipient_indi_other_province2'] = '';
                $final_results['recipient_indi_city2'] = '';
                $final_results['recipient_indi_other_city2'] = '';
                $final_results['recipient_indi_mobile_no'] = '';
                $final_results['recipient_indi_type'] = '1';
                $final_results['recipient_indi_other_type'] = '';
                $final_results['recipient_indi_id_number'] = '0';
                $final_results['recipient_corp_secret_code_of_trx'] = '';
                $final_results['recipient_corp_account_no'] = '';
                $final_results['recipient_corp_bank_name'] = '';
                $final_results['recipient_corp_name'] = '';
                $final_results['recipient_corp_address'] = '';
                $final_results['recipient_corp_province'] = '';
                $final_results['recipient_corp_other_province'] = '';
                $final_results['recipient_corp_city'] = '';
                $final_results['recipient_corp_other_city'] = '';
                $final_results['recipient_corp_mobile_no'] = '';
                $final_results['trx_date'] = '';
                $final_results['trx_effective_date'] = '';
                $final_results['trx_origin_country_currency'] = '';
                $final_results['trx_amount_in_origin_country_currency'] = '';
                $final_results['trx_exchange_rate'] = '';
                $final_results['trx_currency_accepted'] = '';
                $final_results['trx_amount_in_rupiah'] = '';
                $final_results['trx_purpose_of_remittance'] = '';
                $final_results['trx_source_of_found'] = '';


                $referenceV1 = '';
                $referenceV2 = '';
                $referenceV3 = '';
                $referenceV4 = '';
                $referenceV5 = '';
                $referenceV6 = '';
                $referenceV7 = '';
                $referenceV8 = '';

                $codemapper_collection = new CodeMapperCollection();

                if ($paymentRequestInfo = $payment_serv->getPaymentByPaymentRequestID($paymentEach->getPaymentRequestId())) {
                    
                    $paymentRequestInfo = $paymentRequestInfo->result;
                    $final_results['recipient_indi_refer_no']  = $paymentRequestInfo->reference_id;
                    $final_results['recipient_indi_bank_name'] = $paymentRequestInfo->bank_name;

                    if ($trxInfo = $transaction_serv->findByTransactionID($paymentRequestInfo->transactionID)) {
                        
                        if ($remittanceInfo = $transaction_serv->getRemittanceInfoByOutTransactionId($trxInfo->getId())) {

                            if( $sender = $account_serv->getUserProfile($remittanceInfo->getSenderUserProfileId()) )
                            {

                                $remittanceInfo->setSender($sender);
                                $final_results['sender_indi_full_name'] = $sender->getFullName();
                                $sender_indi_dob = $sender->getDOB()->getLocalDateTimeStr('d-m-Y H:i:s');
                                $final_results['sender_indi_dob'] = date("d/m/Y", strtotime($sender_indi_dob));
                                $final_results['sender_indi_address']   = $sender->getHostAddress()->address;
                                $final_results['sender_indi_mobile_no'] = $sender->getMobileNumber();
                                $final_results['sender_indi_id_number'] = $sender->getHostIdentityCard();

                                
                                foreach ($sender->getAttributes() as $att) {
                                    
                                    if ($att->getAttribute()->getCode() == AttributeCode::ID_TYPE) {
                                            
                                        $codemapper1 = new CodeMapper();
                                        $codemapper1->setCountryCode($this->INDO_COUNTRY_CODE);
                                        $codemapper1->setModuleCode(ModuleCode::REMITTANCE_MODULE);
                                        $codemapper1->setType('attribute');
                                        $codemapper1->setReferenceValue($att->getAttributeValueId());
                                        $codemapper_collection->addData($codemapper1);
                                        $referenceV1 = $att->getAttributeValueId();
                                    }

                                    if ($att->getAttribute()->getCode() == AttributeCode::SOURCE_OF_INCOME) {

                                        if ($report_lang == 'English') {
                                            $final_results['trx_source_of_found'] = $att->getValue();
                                        }else if ($report_lang == 'Bahasa') {
                                            $codemapper6 = new CodeMapper();
                                            $codemapper6->setCountryCode($this->INDO_COUNTRY_CODE);
                                            $codemapper6->setModuleCode(ModuleCode::ACCOUNT_SERVICE);
                                            $codemapper6->setType('attribute');
                                            $codemapper6->setReferenceValue($att->getAttributeValueId());
                                            $codemapper_collection->addData($codemapper6);
                                            $referenceV6 = $att->getAttributeValueId();
                                        }
                                    }
                                }

                                if ($res = $country_serv->getCityInfo($sender->getHostAddress()->city) ) {
                                    
                                    $final_results['sender_indi_city']  = $res->getName();
                                }

                                $codemapper2 = new CodeMapper();
                                $codemapper2->setCountryCode($this->INDO_COUNTRY_CODE);
                                $codemapper2->setModuleCode(ModuleCode::COUNTRY_MODULE);
                                $codemapper2->setType('country');
                                $codemapper2->setReferenceValue($sender->getHostAddress()->country);
                                $codemapper_collection->addData($codemapper2);
                                $referenceV2 = $sender->getHostAddress()->country;
                            }


                            if ( $recipientAttrInfo = $recipient_attr_serv->getAllRecipientAttribute($remittanceInfo->getRecipient()->getId()) ) {

                                foreach ($recipientAttrInfo as $att) {

                                    if ($att->getAttribute()->getCode() == AttributeCode::FULL_NAME) {

                                        $final_results['recipient_indi_full_name'] = $att->getValue();
                                    }

                                    if ($att->getAttribute()->getCode() == AttributeCode::RESIDENTIAL_ADDRESS) {

                                        $final_results['recipient_indi_address'] = $att->getValue();
                                        $final_results['recipient_indi_address2'] = $att->getValue();
                                    }
                                    
                                    if ($att->getAttribute()->getCode() == AttributeCode::RESIDENTIAL_PROVINCE) {

                                        $codemapper3 = new CodeMapper();
                                        $codemapper3->setCountryCode($this->INDO_COUNTRY_CODE);
                                        $codemapper3->setModuleCode(ModuleCode::COUNTRY_MODULE);
                                        $codemapper3->setType('province');
                                        $codemapper3->setReferenceValue($att->getValue());
                                        $codemapper_collection->addData($codemapper3);
                                        $referenceV3 = $att->getValue();
                                    }

                                    if ($att->getAttribute()->getCode() == AttributeCode::RESIDENTIAL_CITY) {

                                        $codemapper4 = new CodeMapper();
                                        $codemapper4->setCountryCode($this->INDO_COUNTRY_CODE);
                                        $codemapper4->setModuleCode(ModuleCode::COUNTRY_MODULE);
                                        $codemapper4->setType('city');
                                        $codemapper4->setReferenceValue($att->getValue());
                                        $codemapper_collection->addData($codemapper4);
                                        $referenceV4 = $att->getValue();
                                    }

                                    if ($att->getAttribute()->getCode() == AttributeCode::PURPOSE_OF_REMITTANCE) {
                                        
                                        if ($report_lang == 'English') {
                                            $final_results['trx_purpose_of_remittance'] = $att->getValue();
                                        }else if ($report_lang == 'Bahasa') {
                                            $codemapper5 = new CodeMapper();
                                            $codemapper5->setCountryCode($this->INDO_COUNTRY_CODE);
                                            $codemapper5->setModuleCode(ModuleCode::REMITTANCE_MODULE);
                                            $codemapper5->setType('attribute');
                                            $codemapper5->setReferenceValue($att->getAttributeValueId());
                                            $codemapper_collection->addData($codemapper5);
                                            $referenceV5 = $att->getAttributeValueId();
                                        }
                                    }
                                }
                            }

                            if ($remittanceInfo->getRecipient()) {
                                $final_results['recipient_indi_mobile_no'] = $remittanceInfo->getRecipient()->getRecipientDialingCode()->getValue().$remittanceInfo->getRecipient()->getRecipientMobileNumber()->getValue();
                            }


                            $trx_date = IappsDateTime::fromString($paymentRequestInfo->created_at);
                            $trx_date->setTimeZoneFormat($this->TIME_ZONE);
                            $temp_date = $trx_date->getLocalDateTimeStr('d-m-Y H:i:s');
                            $trx_date->setDateTimeString($temp_date);
                            $final_results['trx_date'] = date("d/m/Y", strtotime($trx_date->getString()));

                            if ($response = json_decode($paymentRequestInfo->response)) {
                                
                                if (isset($response->timestamp)) {

                                    $trx_effective_date = IappsDateTime::fromString($response->timestamp);
                                    $trx_effective_date->setTimeZoneFormat($this->TIME_ZONE);
                                    $temp_eff_date = $trx_effective_date->getLocalDateTimeStr('d-m-Y H:i:s');
                                    $trx_effective_date->setDateTimeString($temp_eff_date);
                                    $final_results['trx_effective_date'] = date("d/m/Y", strtotime($trx_effective_date->getString()));
                                }
                                if (isset($response->dest_refnumber)) {
                                    $final_results['trx_refer_no']  = $response->dest_refnumber;
                                }
                            }    

                            if ($remittanceConfigInfo = $remittance_config_serv->getRemittanceConfigById($remittanceInfo->getRemittanceConfigurationId())) {
                                
                                if ($remittanceSerInfo = $remittanceSer_serv->getRemittanceServiceConfigInfo($remittanceConfigInfo->getRemittanceServiceId())) {
                                    
                                    $codemapper7 = new CodeMapper();
                                    $codemapper7->setCountryCode($this->INDO_COUNTRY_CODE);
                                    $codemapper7->setModuleCode(ModuleCode::PAYMENT_SERVICE);
                                    $codemapper7->setType('currency');
                                    $codemapper7->setReferenceValue($remittanceSerInfo->getFromCountryCurrencyCode());
                                    $codemapper_collection->addData($codemapper7);
                                    $referenceV7 = $remittanceSerInfo->getFromCountryCurrencyCode();

                                    $codemapper8 = new CodeMapper();
                                    $codemapper8->setCountryCode($this->INDO_COUNTRY_CODE);
                                    $codemapper8->setModuleCode(ModuleCode::PAYMENT_SERVICE);
                                    $codemapper8->setType('currency');
                                    $codemapper8->setReferenceValue($remittanceSerInfo->getToCountryCurrencyCode());
                                    $codemapper_collection->addData($codemapper8);
                                    $referenceV8 = $remittanceSerInfo->getToCountryCurrencyCode();
                                }
                                
                                $final_results['trx_exchange_rate'] = $remittanceConfigInfo->getOutBuyingPrice();
                                $final_results['trx_amount_in_rupiah'] = $remittanceInfo->getToAmount();
                                $final_results['trx_amount_in_origin_country_currency'] = round($remittanceInfo->getToAmount()/$remittanceConfigInfo->getOutBuyingPrice(), 2);
                            }

                            if ($codemapperInfo = $codemapper_serv->getCodes($codemapper_collection)) {

                                foreach ($codemapperInfo as $each) {
                                    if ($each->getReferenceValue() == $referenceV1) {
                                        $final_results['sender_indi_id_type'] = $each->getMappedValue();
                                    }
                                    if ($each->getReferenceValue() == $referenceV2) {
                                        $final_results['sender_indi_country'] = $each->getMappedValue();
                                    }
                                    if ($each->getReferenceValue() == $referenceV3) {
                                        $final_results['recipient_indi_province'] = $each->getMappedValue();
                                        $final_results['recipient_indi_province2'] = $each->getMappedValue();
                                    }
                                    if ($each->getReferenceValue() == $referenceV4) {
                                        $final_results['recipient_indi_city'] = $each->getMappedValue();
                                        $final_results['recipient_indi_city2'] = $each->getMappedValue(); 
                                    }
                                    if ($each->getReferenceValue() == $referenceV5) {
                                        $final_results['trx_purpose_of_remittance'] = $each->getMappedValue();
                                    }
                                    if ($each->getReferenceValue() == $referenceV6) {
                                        $final_results['trx_source_of_found'] = $each->getMappedValue();
                                    }
                                    if ($each->getReferenceValue() == $referenceV7) {
                                        $final_results['trx_origin_country_currency'] = $each->getMappedValue();
                                    }
                                    if ($each->getReferenceValue() == $referenceV8) {
                                        $final_results['trx_currency_accepted'] = $each->getMappedValue();
                                    }
                                }
                            }
                        }
                    }
                }

                $report_data[] = $final_results;
            }
        }
        

        $this->setResponseCode(MessageCode::CODE_GET_CUSTOMER_REPORT_SUCCESS);
        return $report_data;
    }

}