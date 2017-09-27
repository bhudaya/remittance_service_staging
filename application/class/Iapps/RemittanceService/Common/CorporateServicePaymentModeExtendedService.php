<?php

namespace Iapps\RemittanceService\Common;

use Iapps\Common\AuditLog\AuditLogAction;
use Iapps\Common\Core\IappsBaseService;
use Iapps\Common\CorporateService\CorporateServicePaymentMode;
use Iapps\Common\Helper\GuidGenerator;
use Iapps\RemittanceService\Common\MessageCode;
use Iapps\Common\Microservice\AccountService\AccountService;

use Iapps\Common\CorporateService\CorporateService;
use Iapps\Common\CorporateService\CorporateServiceRepository;
use Iapps\RemittanceService\Common\CorporateServiceFeeServiceFactory;
use Iapps\RemittanceService\Common\TransactionTypeValidator;
use Iapps\Common\CorporateService\CorporateServService;
use Iapps\RemittanceService\Common\TransactionType;
use Iapps\RemittanceService\Common\CurrencyCodeValidator;
use Iapps\Common\CorporateService\CorporateServiceFee;
use Iapps\Common\CorporateService\CorporateServiceFeeRepository;
use Iapps\Common\CorporateService\CorporateServiceFeeService;
use Iapps\Common\CorporateService\CorporateServicePaymentModeService;
use Iapps\RemittanceService\Common\CorporateServServiceFactory;
use Iapps\RemittanceService\Common\CorporateServicePaymentModeServiceFactory;
use Iapps\RemittanceService\Common\CorporateServicePaymentModeFeeServiceFactory;
use Iapps\RemittanceService\Common\FeeTypeValidator;
use Iapps\Common\CorporateService\CorporateServiceFeeCollection;
use Iapps\Common\CorporateService\CorporateServicePaymentModeFeeCollection;
use Iapps\Common\CorporateService\CorporateServicePaymentModeCollection;

use Iapps\Common\Microservice\PaymentService\PaymentServiceFactory;
use Iapps\Common\Microservice\PaymentService\PaymentDirection;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeFeeGroup;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeFeeGroupCollection;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeFeeGroupServiceFactory;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeFeeServiceFactory;
use Iapps\Common\CorporateService\FeeType;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeFeeCollection;


class CorporateServicePaymentModeExtendedService extends CorporateServicePaymentModeService
{

    public function getCorpServicePaymentModeWithFeeByCorpServId($corporate_service_id)
    {
        if ($object = $this->getSupportedPaymentMode($corporate_service_id)) {
            $result_array = $object->result->getSelectedField(array('id', 'direction', 'corporate_service_id', 'role_id', 'is_default', 'payment_code'));
            $count_result = count($result_array);

            //fee service factory
            $corp_serv_payment_mode_fee_serv = CorporateServicePaymentModeFeeServiceFactory::build();

            for ($i = 0; $i < $count_result; $i++) {
                if ($fee_object = $corp_serv_payment_mode_fee_serv->getPaymentModeFeeByCorporateServicePaymentModeId($result_array[$i]['id'])) {
                    $result_array[$i]['fee'] = $fee_object->result->toArray();
                }
            }

            return $result_array;
        }

        return false;
    }


    public function getCorpServPaymentModeAndFee(CorporateServicePaymentMode $paymentMode, $isArray = true, $self_service = false)
    {
        $supportedCorpServPaymentModeColl = new CorporateServicePaymentModeCollection();
        $result_pm_array = array();
        $corp_serv_payment_mode_id_arr = array();
        $payment_mode_fee_group_arr = array();
        $supp_payment_mode_arr = array();

        if ($rawCorpServPaymentModes = $this->getSupportedPaymentMode($paymentMode->getCorporateServiceId())) {

            //filter by payment direction and payment mode?
            $corpServPaymentModes = new CorporateServicePaymentModeCollection();
            foreach ($rawCorpServPaymentModes->result as $rawCorpServPaymentMode) {
                if( $rawCorpServPaymentMode->getIsActive() == 0 )
                    continue;

                if( $paymentMode->getDirection() AND $rawCorpServPaymentMode->getDirection() != $paymentMode->getDirection() )
                    continue;

                if( $paymentMode->getPaymentCode() AND $rawCorpServPaymentMode->getPaymentCode() != $paymentMode->getPaymentCode() )
                    continue;

                $corpServPaymentModes->addData($rawCorpServPaymentMode);
            }

            if( count($corpServPaymentModes) <= 0 )
                return false;


            //no filter required for self service
            if($paymentMode->getDirection() == PaymentDirection::IN AND !$self_service) {
                //filter with supported payment mode from payment service
                $payment_serv = PaymentServiceFactory::build();
                $supp_payment_mode_arr = $payment_serv->getSupportedPayment(PaymentDirection::IN);
            } else {
                foreach ($corpServPaymentModes as $corpServPaymentMode) {
                    $supp_payment_mode_arr[] = $corpServPaymentMode->getPaymentCode();
                }
            }

            $groupCollection = new PaymentModeFeeGroupCollection();
            $feeGroup = new PaymentModeFeeGroup();
            if($supp_payment_mode_arr) {
                foreach ($corpServPaymentModes as $corpServPaymentMode) {
                    if (in_array($corpServPaymentMode->getPaymentCode(), $supp_payment_mode_arr)) {
                        $corp_serv_payment_mode_id_arr[] = $corpServPaymentMode->getId();
                        $supportedCorpServPaymentModeColl->addData($corpServPaymentMode);
                    }
                }

                if( count($corp_serv_payment_mode_id_arr) <= 0 )
                    return false;

                //get payment mode fee group and its fee all at once
                $payment_mode_fee_group_serv = PaymentModeFeeGroupServiceFactory::build();
                $payment_mode_fee_serv = PaymentModeFeeServiceFactory::build();
                $paymentModeFeeColl = new PaymentModeFeeCollection();
                $paymentModeFeeCollByPaymentMode = new PaymentModeFeeCollection();
                if ($paymentModeFeeGroups = $payment_mode_fee_group_serv->getActiveByCorporateServicePaymentModeIds($corp_serv_payment_mode_id_arr)) {
                    foreach ($paymentModeFeeGroups as $paymentModeFeeGroup) {
                        $payment_mode_fee_group_arr[] = $paymentModeFeeGroup->getId();
                    }

                    $paymentModeFeeColl = $payment_mode_fee_serv->getListByGroupIds(1000, 1, $payment_mode_fee_group_arr);
                }

                //loop each in payment mode to set the fee
                foreach ($supportedCorpServPaymentModeColl as $corpServPaymentModeEach) {
                    $feeGroup = NULL;

                    $result_pm = $corpServPaymentModeEach->getSelectedField(array('direction', 'corporate_service_id', 'payment_code', 'is_default', 'role_id'));

                    $total_service_fee = 0;
                    $total_service_fee_percentage = 0;
                    $total_payment_mode_fee = 0;
                    $total_payment_mode_fee_percentage = 0;

                    $paymentModeFeeCollByPaymentMode = new PaymentModeFeeCollection();
                    foreach ($paymentModeFeeGroups as $paymentModeFeeGroup) {
                        if ($paymentModeFeeGroup->getCorporateServicePaymentModeId() == $corpServPaymentModeEach->getId()) {
                            foreach ($paymentModeFeeColl->result as $paymentModeFeeEach) {

                                if ($paymentModeFeeEach->getCorporateServicePaymentModeFeeGroupId() == $paymentModeFeeGroup->getId()) {
                                    $paymentModeFeeCollByPaymentMode->addData($paymentModeFeeEach);

                                    //loop for payment mode fee to set the total fee for each type
                                    if ($paymentModeFeeGroup->getFeeType()->getCode() == FeeType::SERVICE_FEE) {
                                        if ($paymentModeFeeEach->getIsPercentage() == (int)true) {
                                            $total_service_fee_percentage += $paymentModeFeeEach->getFee();
                                        } else {
                                            $total_service_fee += $paymentModeFeeEach->getFee();
                                        }
                                    } else if ($paymentModeFeeGroup->getFeeType()->getCode() == FeeType::PAYMENT_MODE_FEE) {
                                        if ($paymentModeFeeEach->getIsPercentage() == (int)true) {
                                            $total_payment_mode_fee_percentage += $paymentModeFeeEach->getFee();
                                        } else {
                                            $total_payment_mode_fee += $paymentModeFeeEach->getFee();
                                        }
                                    }
                                }
                            }

                            $feeGroup = $paymentModeFeeGroup;
                            $feeGroup->setPaymentModeFeeItems($paymentModeFeeCollByPaymentMode);
                            break;
                        }


                    }

                    if( $isArray )
                    {
                        $result_pm['fee'] = $paymentModeFeeCollByPaymentMode->toArray();

                        $result_pm['total_service_fee'] = $total_service_fee;
                        $result_pm['total_service_fee_percentage'] = $total_service_fee_percentage;
                        $result_pm['total_payment_mode_fee'] = $total_payment_mode_fee;
                        $result_pm['total_payment_mode_fee_percentage'] = $total_payment_mode_fee_percentage;
                    }
                    else
                    {
                        if($feeGroup != NULL) {
                            $groupCollection->addData($feeGroup);
                        }
                    }

                    $result_pm_array[] = $result_pm;
                }
            }

            if( $isArray )
                return $result_pm_array;
            else
                return $groupCollection;
        }

        return false;
    }

}