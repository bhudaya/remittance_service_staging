<?php

namespace Iapps\RemittanceService\Common;

use Iapps\Common\AuditLog\AuditLogAction;
use Iapps\Common\Core\IappsBaseService;
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
use Iapps\RemittanceService\Common\CorporateServServiceFactory;
use Iapps\RemittanceService\Common\CorporateServicePaymentModeServiceFactory;
use Iapps\RemittanceService\Common\CorporateServicePaymentModeFeeServiceFactory;
use Iapps\RemittanceService\Common\FeeTypeValidator;
use Iapps\RemittanceService\Common\FeeType;
use Iapps\Common\CorporateService\CorporateServiceFeeCollection;
use Iapps\Common\CorporateService\CorporateServicePaymentModeFeeCollection;
use Iapps\Common\CorporateService\CorporateServicePaymentModeCollection;


class CorporateServiceFeeExtendedService extends CorporateServiceFeeService {

    public function addCorpServiceFee(CorporateServiceFee $corporateServiceFee, $fee_type_code)
    {
        if(!$feeType = FeeTypeValidator::validate($fee_type_code))
        {
            $this->setResponseCode(MessageCode::CODE_INVALID_FEE_TYPE);
            return false;
        }
        $corporateServiceFee->setFeeTypeId($feeType->getId());
        $is_percentage = (int)false;
        if($feeType->getCode() == FeeType::CODE_SPREAD)
        {
            $is_percentage = (int)true;
        }
        $corporateServiceFee->setIsPercentage($is_percentage);
        $corporateServiceFee->setId(GuidGenerator::generate());

        if( $corporateServiceFee = $this->addFee($corporateServiceFee) )
        {
            $this->setResponseCode($this->getResponseCode());
            return true;
        }

        $this->setResponseCode($this->getResponseCode());
        return false;
    }

    public function editCorpServiceFee(CorporateServiceFee $corporateServiceFee, $fee_type_code)
    {
        if(!$feeType = FeeTypeValidator::validate($fee_type_code))
        {
            $this->setResponseCode(MessageCode::CODE_INVALID_FEE_TYPE);
            return false;
        }
        $corporateServiceFee->setFeeTypeId($feeType->getId());
        $is_percentage = (int)false;
        if($feeType->getCode() == FeeType::CODE_SPREAD)
        {
            $is_percentage = (int)true;
        }
        $corporateServiceFee->setIsPercentage($is_percentage);

        if( $corporateServiceFee = $this->updateFee($corporateServiceFee) )
        {
            $this->setResponseCode($this->getResponseCode());
            return true;
        }

        $this->setResponseCode($this->getResponseCode());
        return false;
    }

    public function getCorpServiceFeeByCorpServId($corp_service_id, $limit = 100, $page = 1)
    {
        if ($corpServFeeList = $this->getFeesByCorporateServiceId($corp_service_id, $limit, $page)) {
            $total_service_fee = 0;
            $total_service_spread = 0;
            $resultFeeObject = new \StdClass;
            $resultFeeObject->items = $corpServFeeList->result->toArray();

            $feeTypeSpread = FeeTypeValidator::validate(FeeType::CODE_SPREAD);
            $feeTypeFee = FeeTypeValidator::validate(FeeType::CODE_FEE);

            foreach($corpServFeeList->result as $corpServFeeEach)
            {
                if($corpServFeeEach->getFeeTypeId() == $feeTypeSpread->getId())
                {
                    $total_service_spread += $corpServFeeEach->getTransactionFee();
                }
                else if($corpServFeeEach->getFeeTypeId() == $feeTypeFee->getId())
                {
                    $total_service_fee += $corpServFeeEach->getTransactionFee();
                }
            }
            $resultFeeObject->total_fee = $total_service_fee;
            $resultFeeObject->total_spread = $total_service_spread;

            $this->setResponseCode($this->getResponseCode());

            return $resultFeeObject;
        }

        return false;
    }


}