<?php

namespace Iapps\RemittanceService\RemittanceRecord;

use Iapps\Common\Core\IappsBasicBaseService;
use Iapps\Common\CorporateService\CorporateService;
use Iapps\Common\CorporateService\CorporateServiceFee;
use Iapps\Common\CorporateService\CorporateServicePaymentMode;
use Iapps\Common\CorporateService\CorporateServicePaymentModeFee;
use Iapps\Common\Helper\GuidGenerator;
use Iapps\Common\Microservice\PaymentService\CountryCurrency;
use Iapps\Common\Microservice\PaymentService\PaymentService;
use Iapps\Common\Microservice\PromoCode\PromoTransactionType;
use Iapps\Common\Microservice\PromoCode\UserPromoReward;
use Iapps\Common\Transaction\TransactionItem;
use Iapps\Common\Transaction\TransactionItemCollection;
use Iapps\RemittanceService\Common\CorporateServicePaymentModeExtendedServiceFactory;
use Iapps\RemittanceService\Common\GeneralDescription;
use Iapps\RemittanceService\Common\Logger;
use Iapps\RemittanceService\Common\PaymentDirection;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfig;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfigServiceFactory;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeCostGroupCollection;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeCostGroupServiceFactory;
use Iapps\RemittanceService\RemittancePaymentMode\PaymentModeFeeGroup;
use Iapps\RemittanceService\RemittanceProfitSharing\RemittanceCorpServProfitSharing;
use Iapps\RemittanceService\RemittanceProfitSharing\RemittanceCorpServProfitSharingServiceFactory;
use Iapps\RemittanceService\RemittanceTransaction\ItemType;
use Iapps\RemittanceService\RemittanceTransaction\ProfitCostType;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransactionItem;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransactionItemCollection;
use Iapps\Common\CorporateService\FeeType;
use Iapps\RemittanceService\RemittanceTransaction\TransactionProfitCost;
use Iapps\RemittanceService\RemittanceTransaction\TransactionProfitCostCollection;

class RemittanceFeeCalculator extends IappsBasicBaseService{

    protected $transaction_items;
    protected $profitCost_items;

    protected $in_amount = 0;
    protected $out_amount = 0;
    protected $remittance_config;
    protected $payment_mode;
    protected $collection_mode;
    protected $from_currency;
    protected $to_currency;
    protected $inProfitSharing;
    protected $outProfitSharing;
    protected $paymentCost;

    protected $calculation_direction = RemittanceCalculationDirection::DIR_TO;

    function __construct()
    {
        parent::__construct();
        $this->transaction_items = new RemittanceTransactionItemCollection();
        $this->profitCost_items = new TransactionProfitCostCollection();
        $this->remittance_config = new RemittanceConfig();
        $this->from_currency = new CountryCurrency();
        $this->to_currency = new CountryCurrency();
        $this->inProfitSharing = new RemittanceCorpServProfitSharing();
        $this->outProfitSharing = new RemittanceCorpServProfitSharing();
        $this->paymentCost = new PaymentModeCostGroupCollection();
    }

    public function setRemittanceConfig(RemittanceConfig $config)
    {
        $this->remittance_config = $config;
        return $this;
    }

    /**
    * @return RemittanceConfig remittance config entity 
    */
    public function getRemittanceConfig()
    {
        return $this->remittance_config;
    }

    public function setInCurrency(CountryCurrency $currency)
    {
        $this->from_currency = $currency;
        return $this;
    }

    public function getInCurrency()
    {
        return $this->from_currency;
    }

    public function setInAmount($amount)
    {
        $this->in_amount = $this->getInCurrency()->roundAmount($amount);
        return $this;
    }

    public function getInAmount()
    {
        return $this->in_amount;
    }

    public function setOutCurrency(CountryCurrency $currency)
    {
        $this->to_currency = $currency;
        return $this;
    }

    public function getOutCurrency()
    {
        return $this->to_currency;
    }

    public function setOutAmount($amount)
    {//to remove denomination check
        $this->out_amount = $this->getOutCurrency()->roundAmount($amount);

        return $this;
    }

    protected function _computeInAmount()
    {
        $inAmount = $this->out_amount/$this->getRemittanceConfig()->getDisplayRate();
        $this->setInAmount($inAmount);
        return $this;
    }

    protected function _computeOutAmount()
    {
        $inAmount = $this->in_amount*$this->getRemittanceConfig()->getDisplayRate();
        $this->setOutAmount($inAmount);
        return $this;
    }

    public function getOutAmount()
    {
        return $this->out_amount;
    }

    public function setPaymentMode($mode)
    {
        $this->payment_mode = $mode;
        return $this;
    }

    public function getPaymentMode()
    {
        return $this->payment_mode;
    }

    public function setCollectionMode($mode)
    {
        $this->collection_mode = $mode;
        return $this;
    }

    public function getCollectionMode()
    {
        return $this->collection_mode;
    }

    public function getTransactionItems()
    {
        return $this->transaction_items;
    }

    public function getProfitCostItems()
    {
        return $this->profitCost_items;
    }

    public function setInProfitSharing(RemittanceCorpServProfitSharing $profitSharing)
    {
        $this->inProfitSharing = $profitSharing;
        return $this;
    }

    public function getInProfitSharing()
    {
        return $this->inProfitSharing;
    }

    public function setOutProfitSharing(RemittanceCorpServProfitSharing $profitSharing)
    {
        $this->outProfitSharing = $profitSharing;
        return $this;
    }

    public function getOutProfitSharing()
    {
        return $this->outProfitSharing;
    }

    public function setPaymentCostCollection(PaymentModeCostGroupCollection $groupCollection)
    {
        $this->paymentCost = $groupCollection;
        return $this;
    }

    public function getPaymentCostCollection()
    {
        return $this->paymentCost;
    }

    public function setCalcDirection($calcDir)
    {
        if( $calcDir == RemittanceCalculationDirection::DIR_FROM )
            $this->calculation_direction = $calcDir;
        else
            $this->calculation_direction = RemittanceCalculationDirection::DIR_TO; //$default

        return $this;
    }

    public function getCalcDirection()
    {
        return $this->calculation_direction;
    }

    public function generateRemittanceRecord()
    {
        $remittance = new RemittanceRecord();
        $remittance->setId(GuidGenerator::generate());
        $remittance->setRemittanceConfigurationId($this->getRemittanceConfig()->getId());
        $remittance->setInExchangeRateId($this->getRemittanceConfig()->getInCorporateService()->getExchangeRateId());
        $remittance->setOutExchangeRateId($this->getRemittanceConfig()->getOutCorporateService()->getExchangeRateId());
        $remittance->setDisplayRate($this->getRemittanceConfig()->getDisplayRate());
        $remittance->setFromAmount($this->getInAmount());
        $remittance->setToAmount($this->getOutAmount());
        $remittance->getStatus()->setCode(RemittanceStatus::INITIATE);

        return $remittance;
    }

    public function getDescription()
    {
        $description = new GeneralDescription();

        if( $this->getRemittanceConfig()->getRemittanceService()->isDomestic() )
        {
            $description->add('Amount', $this->getOutCurrency()->getCurrencyInfo()->getSymbol() . " " . number_format($this->getOutAmount(),2));
        }
        else
        {
            $description->add('Remittance Amount', $this->getOutCurrency()->getCurrencyInfo()->getSymbol() . " " . number_format($this->getOutAmount(),2));
            $description->add('Exchange Rate', "1".$this->getInCurrency()->getCurrencyInfo()->getCode().":".
                $this->getRemittanceConfig()->getDisplayRate().$this->getOutCurrency()->getCurrencyInfo()->getCode());
            $description->add($this->getInCurrency()->getCurrencyInfo()->getSymbol()." Equivalent", number_format($this->getInAmount(),2));
        }

        return $description;
    }

    protected static function _getRemittanceConfigService()
    {
        return RemittanceConfigServiceFactory::build();
    }

    public static function calculate($remittance_config_id,
                                     $amount,
                                     $payment_mode,
                                     $collection_mode,
                                     $self_service = true,
                                     UserPromoReward $promo = NULL,
                                     $calc_dir = RemittanceCalculationDirection::DIR_TO)
    {
        /*
         * Get Remittance Configuration
         */
        $rconfig_serv = self::_getRemittanceConfigService();
        if (!$rconfig = $rconfig_serv->getRemittanceConfigById($remittance_config_id)) {
            Logger::debug("[Remittance Calculator]: Failed to get remittance config [$remittance_config_id]");
            return false;
        }

        if( !$rconfig->getIsActive() )
        {
            Logger::debug("[Remittance Calculator]: Inactive Channel [$remittance_config_id]");
            return false;
        }

        /*
         * Get Currency Information
         */
        $payment_serv = new PaymentService();
        if (!$from_cc = $payment_serv->getCountryCurrencyInfo($rconfig->getRemittanceService()->getFromCountryCurrencyCode())) {
            Logger::debug("[Remittance Calculator]: Failed to get From Currency Info [" . $rconfig->getRemittanceService()->getFromCountryCurrencyCode() . "]");
            return false;
        }


        if (!$to_cc = $payment_serv->getCountryCurrencyInfo($rconfig->getRemittanceService()->getToCountryCurrencyCode())) {
            Logger::debug("[Remittance Calculator]: Failed to get From Currency Info [" . $rconfig->getRemittanceService()->getToCountryCurrencyCode() . "]");
            return false;
        }


        /*
         * Get Payment Mode and Fees
         */
        $payment_mode_serv = CorporateServicePaymentModeExtendedServiceFactory::build();
        $paymentFilter = new CorporateServicePaymentMode();
        $paymentFilter->setCorporateServiceId($rconfig->getInCorporateService()->getId());
        $paymentFilter->setPaymentCode($payment_mode);
        $paymentFilter->setDirection(PaymentDirection::IN);
        if (!$payment_mode_info = $payment_mode_serv->getCorpServPaymentModeAndFee($paymentFilter, false, $self_service))
        {
            Logger::debug("[Remittance Calculator]: Failed to get Payment Info [" . $paymentFilter->getPaymentCode() . "]");
            return false;
        }

        $paymentFilter->setCorporateServiceId($rconfig->getOutCorporateService()->getId());
        $paymentFilter->setPaymentCode($collection_mode);
        $paymentFilter->setDirection(PaymentDirection::OUT);
        if( !$collection_mode_info = $payment_mode_serv->getCorpServPaymentModeAndFee($paymentFilter, false, $self_service) )
        {
            Logger::debug("[Remittance Calculator]: Failed to get Collection Info [" . $paymentFilter->getPaymentCode() . "]");
            return false;
        }

        /*
         * Get Profit Sharing Info
         */
        $profitSharingServ = RemittanceCorpServProfitSharingServiceFactory::build();
        if( !$inProfitSharing = $profitSharingServ->getActiveProfitSharingByCorporateService($rconfig->getInCorporateService()->getId()) )
        {
            Logger::debug("[Remittance Calculator]: Failed to get Profit Sharing Info [" . $rconfig->getInCorporateService()->getId() . "]");
            return false;
        }

        if( !$outProfitSharing = $profitSharingServ->getActiveProfitSharingByCorporateService($rconfig->getOutCorporateService()->getId()) )
        {
            Logger::debug("[Remittance Calculator]: Failed to get Profit Sharing Info [" . $rconfig->getOutCorporateService()->getId() . "]");
            return false;
        }

        /*
         * Get Payment Cost
         */
        $collection_mode_info->rewind();
        $payment_mode_info->rewind();
        $costServ = PaymentModeCostGroupServiceFactory::build();
        if( !$paymentCost = $costServ->getListByCorporrateServicePaymentModeIds(
            MAX_VALUE, 1,
            array($collection_mode_info->current()->getCorporateServicePaymentModeId(),
                  $payment_mode_info->current()->getCorporateServicePaymentModeId()), NULL,
            true
        ))
        {
            Logger::debug("[Remittance Calculator]: Failed to get Payment Cost [" . $collection_mode_info->current()->getCorporateServicePaymentModeId() .
                "," .
                $payment_mode_info->current()->getCorporateServicePaymentModeId() . "]");
            return false;
        }


        $calculator = new RemittanceFeeCalculator();
        $calculator->setCalcDirection($calc_dir);
        $calculator->setRemittanceConfig($rconfig);
        $calculator->setPaymentMode($payment_mode);
        $calculator->setCollectionMode($collection_mode);
        $calculator->setInCurrency($from_cc);
        $calculator->setOutCurrency($to_cc);
        if( $calculator->getCalcDirection() == RemittanceCalculationDirection::DIR_FROM )
        {
            $calculator->setInAmount($amount);
            $calculator->_computeOutAmount();
        }
        else
        {
            $calculator->setOutAmount($amount);
            $calculator->_computeInAmount();
        }

        $calculator->setInProfitSharing($inProfitSharing);
        $calculator->setOutProfitSharing($outProfitSharing);
        $calculator->setPaymentCostCollection($paymentCost->result);

        if( $calculator->_generateCorporateServiceItem() AND
            $calculator->_computeServiceFee($collection_mode_info->current()) AND
            $calculator->_computePaymentFee($payment_mode_info->current()) AND
            $calculator->_computeProfit() AND
            $calculator->_computeCost() )
        {
            if( $promo )
            {
                if( !$calculator->_computePromo($promo) )
                {
                    Logger::debug('Failed to compute discount: ' . $promo->getId());
                    return false;
                }
            }

            return $calculator;
        }

        return false;
    }

    protected function _generateCorporateServiceItem()
    {
        $item = new RemittanceTransactionItem();

        $item->getItemType()->setCode(ItemType::CORPORATE_SERVICE);
        $item->setItemId($this->getRemittanceConfig()->getInCorporateService()->getId());
        $item->setItemInfo($this->getRemittanceConfig()->getInCorporateService());
        $item->setName($this->getRemittanceConfig()->getInCorporateService()->getName());
        $item->setDescription($this->getDescription()->toJson());
        $item->setUnitPrice($this->getInAmount());

        $this->getTransactionItems()->addData($item);

        return $item;
    }

    protected function _computeServiceFee(PaymentModeFeeGroup $fees)
    {
        if( $fees->getFeeType()->getCode() == FeeType::SERVICE_FEE )
        {
            $applicableFee = $fees->getPaymentModeFeeItems()->getApplicableFee($this->getInAmount());

            $item = new RemittanceTransactionItem();
            $item->setId(GuidGenerator::generate());

            $item->getItemType()->setCode(ItemType::CORPORATE_SERVICE_FEE);
            $item->setItemInfo($applicableFee);
            $item->setItemId($applicableFee->getId());
            $item->setName($fees->getName());
            $item->setDescription($fees->getName());

            if( $applicableFee->getIsPercentage() == 1 )
                $item->setUnitPrice(round($applicableFee->getFee()*$this->getInAmount()/100,2));
            else
                $item->setUnitPrice(round($applicableFee->getFee(),2));

            $this->getTransactionItems()->addData($item);
            return $item;
        }

        Logger::debug("[Remittance Calculator]: Failed to compute service fee [" . $this->getRemittanceConfig()->getId() . "]" );
        return false;
    }

    protected function _computePaymentFee(PaymentModeFeeGroup $fees)
    {
        if( $fees->getFeeType()->getCode() == FeeType::PAYMENT_MODE_FEE )
        {
            if( $applicableFee = $fees->getPaymentModeFeeItems()->getApplicableFee($this->getInAmount()) )
            {
                $item = new RemittanceTransactionItem();
                $item->setId(GuidGenerator::generate());

                $item->getItemType()->setCode(ItemType::PAYMENT_FEE);
                $item->setItemInfo($applicableFee);
                $item->setItemId($applicableFee->getId());
                $item->setName($fees->getName());
                $item->setDescription($fees->getName());


                if( $applicableFee->getIsPercentage() == 1 )
                {
                    $totalAmount = $this->getTransactionItems()->getTotalAmount();
                    $item->setUnitPrice(round($applicableFee->getFee()*$totalAmount/100,2));
                }
                else
                    $item->setUnitPrice(round($applicableFee->getFee(),2));

                $this->getTransactionItems()->addData($item);
                return $item;
            }
        }

        Logger::debug("[Remittance Calculator]: Failed to compute payment fee [" . $this->getRemittanceConfig()->getId() . "]" );
        return false;
    }

    protected function _computeProfit()
    {
        $inProfit = $this->getRemittanceConfig()->getInProfit($this->getOutAmount());
        if( $inProfit !== false )
        {
            $this->getInProfitSharing()->calculateProfitSharing($inProfit);

            foreach($this->getInProfitSharing()->getParties() AS $party)
            {
                $profitCost = new TransactionProfitCost();
                $profitCost->setId(GuidGenerator::generate());
                $profitCost->setType(ProfitCostType::PROFIT);
                $profitCost->setItemId($party->getId());
                $profitCost->setBeneficiaryPartyId($party->getCorporateId());
                //profit in in country currency
                $profitCost->setCountryCurrencyCode($this->getRemittanceConfig()->getRemittanceService()->getFromCountryCurrencyCode());
                $profitCost->setAmount($party->getProfitInAmount());

                $this->getProfitCostItems()->addData($profitCost);
            }
        }
        else
        {
            Logger::debug("[Remittance Calculator]: Failed to compute in profit [" . $this->getRemittanceConfig()->getId() . "]" );
            return false;
        }


        $outProfit = $this->getRemittanceConfig()->getOutProfit($this->getOutAmount());
        if( $outProfit !== false )
        {
            $this->getOutProfitSharing()->calculateProfitSharing($outProfit);

            foreach($this->getOutProfitSharing()->getParties() AS $party)
            {
                $profitCost = new TransactionProfitCost();
                $profitCost->setId(GuidGenerator::generate());
                $profitCost->setType(ProfitCostType::PROFIT);
                $profitCost->setItemId($party->getId());
                $profitCost->setBeneficiaryPartyId($party->getCorporateId());
                //profit in in country currency
                $profitCost->setCountryCurrencyCode($this->getRemittanceConfig()->getRemittanceService()->getToCountryCurrencyCode());
                $profitCost->setAmount($party->getProfitInAmount());

                $this->getProfitCostItems()->addData($profitCost);
            }
        }
        else
        {
            Logger::debug("[Remittance Calculator]: Failed to compute out profit [" . $this->getRemittanceConfig()->getId() . "]" );
            return false;
        }

        return true;
    }

    protected function _computeCost()
    {
        foreach( $this->getPaymentCostCollection() AS $costGroup )
        {
            foreach( $costGroup->getPaymentModeCostItems() AS $cost )
            {
                $profitCost = new TransactionProfitCost();
                $profitCost->setId(GuidGenerator::generate());
                $profitCost->setType(ProfitCostType::COST);
                $profitCost->setItemId($cost->getId());
                if( $cost->getServiceProviderId() )
                    $profitCost->setBeneficiaryPartyId($cost->getServiceProviderId());

                if( !$cost->getIsPercentage() )
                {
                    $profitCost->setCountryCurrencyCode($cost->getCountryCurrencyCode());
                    $profitCost->setAmount($cost->getCost());
                }
                else
                {
                    $profitCost->setCountryCurrencyCode($this->getRemittanceConfig()->getRemittanceService()->getFromCountryCurrencyCode());
                    $profitCost->setAmount(round($this->getInAmount()*$cost->getCost()/100,4));
                }

                $this->getProfitCostItems()->addData($profitCost);
            }
        }

        return true;
    }

    protected function _computePromo(UserPromoReward $promo)
    {
        if( $this->getInCurrency()->getCode() == $promo->getCountryCurrencyCode() AND
            $promo->isType(PromoTransactionType::REMITTANCE) )
        {
            //add promo item
            $promoItem = new RemittanceTransactionItem();

            $promoItem->getItemType()->setCode(ItemType::DISCOUNT);
            $promoItem->setItemId($promo->getId());
            $promoItem->setItemInfo($promo);
            $promoItem->setName("Promotion");
            $promoItem->setDescription(strtoupper(str_replace('#', '', $promo->getPromoCode())));

            $totalFee = 0;
            foreach($this->getTransactionItems() AS $item)
            {
                if( $item->isServiceFee() or $item->isPaymentFee() )
                    $totalFee += $item->getNetAmount();
            }

            if( $totalFee < $promo->getAmount() )
                $amount = $totalFee;
            else
                $amount = $promo->getAmount();

            $promoItem->setUnitPrice(-1*$amount);
            $promoItem->setCostCountryCurrencyCode($this->getInCurrency()->getCode());
            $promoItem->setCost(-1*$amount);

            $this->getTransactionItems()->addData($promoItem);
            return true;
        }

        return false;
    }
}