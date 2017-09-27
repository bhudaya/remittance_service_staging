<?php

namespace Iapps\RemittanceService\ExchangeRate;

use Iapps\Common\Microservice\AccountService\User;
use Iapps\Common\Core\IappsBaseEntity;
use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\SystemCode\SystemCode;

class ExchangeRate extends IappsBaseEntity{

    protected $corporate_service_id;
    protected $exchange_rate;
    protected $margin;
    protected $ref_exchange_rate;
    protected $status = ExchangeRateStatus::PENDING;
    protected $approve_reject_remark;
    protected $approve_reject_at;
    protected $approve_reject_by;
    protected $approve_reject_by_name;
    protected $approve_rate;
    protected $is_active;
    protected $channel;

    protected $updatedByCorporate;
    
    function __construct()
    {
        parent::__construct();

        $this->approve_reject_at = new IappsDateTime();
        $this->channel = new SystemCode();
    }
    
    public function setCorporateServiceId($corporate_service_id)
    {
        $this->corporate_service_id = $corporate_service_id;
        return $this;
    }

    public function getCorporateServiceId()
    {
        return $this->corporate_service_id;
    }

    public function setExchangeRate($exchange_rate)
    {
        $this->exchange_rate = $exchange_rate;
        return $this;
    }

    public function getExchangeRate()
    {
        return $this->exchange_rate;
    }

    public function setMargin($margin)
    {
        $this->margin = $margin;
        return $this;
    }

    public function getMargin()
    {
        return $this->margin;
    }

    public function setRefExchangeRate(ExchangeRate $exchangeRate)
    {
        $this->ref_exchange_rate = $exchangeRate;
        return $this;
    }

    public function getRefExchangeRate()
    {
        if( $this->ref_exchange_rate == NULL )
            $this->ref_exchange_rate = new ExchangeRate();

        return $this->ref_exchange_rate;
    }

    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setApproveRejectRemark($approve_reject_remark)
    {
        $this->approve_reject_remark = $approve_reject_remark;
        return $this;
    }

    public function getApproveRejectRemark()
    {
        return $this->approve_reject_remark;
    }

    public function setApproveRejectAt(IappsDateTime $approve_reject_at)
    {
        $this->approve_reject_at = $approve_reject_at;
        return $this;
    }

    public function getApproveRejectAt()
    {
        return $this->approve_reject_at;
    }

    public function setApproveRejectBy($approve_reject_by)
    {
        $this->approve_reject_by = $approve_reject_by;
        return $this;
    }

    public function getApproveRejectBy()
    {
        return $this->approve_reject_by;
    }

    public function setApproveRejectByName($name)
    {
        $this->approve_reject_by_name = $name;
        return $this;
    }

    public function getApproveRejectByName()
    {
        return $this->approve_reject_by_name;
    }

    public function setApproveRate($rate)
    {
        $this->approve_rate = $rate;
        return $this;
    }

    public function getApproveRate()
    {
        return $this->approve_rate;
    }

    public function setIsActive($is_active)
    {
        $this->is_active = $is_active;
        return $this;
    }

    public function getIsActive()
    {
        return $this->is_active;
    }

    public function setChannel(SystemCode $channel)
    {
        $this->channel = $channel;
        return $this;
    }

    public function getChannel()
    {
        return $this->channel;
    }

    public function setUpdatedByCorporate($corporate)
    {
        $this->updatedByCorporate = $corporate;
        return $this;
    }

    public function getUpdatedByCorporate()
    {
        return $this->updatedByCorporate;
    }

    public function jsonSerialize()
    {
        $json = parent::jsonSerialize();

        $json['corporate_service_id'] = $this->getCorporateServiceId();
        $json['exchange_rate'] = $this->getExchangeRate();
        $json['margin'] = $this->getMargin();
        $json['ref_exchange_rate_id'] = $this->getRefExchangeRate()->getId();
        $json['status'] = $this->getStatus();
        $json['approve_reject_remark'] = $this->getApproveRejectRemark();
        $json['approve_reject_at'] = $this->getApproveRejectAt()->getString() ? $this->getApproveRejectAt()->getString() : NULL;
        $json['approve_reject_by'] = $this->getApproveRejectBy();
        $json['approve_reject_by_name'] = $this->getApproveRejectByName();
        $json['approve_rate'] = $this->getApproveRate();
        $json['is_active'] = $this->getIsActive();
        $json['channel'] = $this->getChannel()->getCode();

        return $json;
    }

    public function approveRejectRate($status, $remark, User $admin)
    {
        if( $this->getStatus() == ExchangeRateStatus::PENDING )
        {
            if( $status == ExchangeRateStatus::APPROVED )
            {
                $this->setIsActive(1);
            }
            elseif( $status == ExchangeRateStatus::REJECTED )
            {
                $this->setIsActive(0);
            }
            else
                return false;

            $this->setStatus($status);
            $this->setApproveRejectBy($admin->getId());
            $this->setApproveRejectAt(IappsDateTime::now());
            $this->setApproveRejectRemark($remark);
            return true;
        }

        return false;
    }

    public function getBuyingPrice()
    {
        return $this->getExchangeRate() - $this->getMargin();
    }

    public function getPrices()
    {
        $prices = array();
        if( $this->getRefExchangeRate()->getExchangeRate() )
            $prices['selling_price'] = round($this->getRefExchangeRate()->getBuyingPrice(),4);
        else
            $prices['selling_price'] = round($this->getExchangeRate(),4);

        $prices['margin'] = round($this->getMargin(),4);

        if( $prices['selling_price'] !== NULL)
            $prices['buying_price'] = round($prices['selling_price'] - $prices['margin'], 4);
        else
            $prices['buying_price'] = NULL;

        return $prices;
    }
}