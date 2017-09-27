<?php

namespace Iapps\RemittanceService\ExchangeRate;

use Iapps\Common\Validator\IappsValidator;
use Iapps\RemittanceService\RemittanceConfig\ConversionType;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfig;

class ExchangeRatePairValidator extends IappsValidator{

    protected $collection;
    protected $config;
    protected $firstRate;
    protected $secondRate;

    public static function make(ExchangeRateCollection $collection, RemittanceConfig $config)
    {
        $v = new ExchangeRatePairValidator();
        $v->collection = $collection;
        $v->config = $config;

        $v->validate();

        return $v;
    }

    public function getFirstRate()
    {
        return $this->firstRate;
    }

    public function getSecondRate()
    {
        return $this->secondRate;
    }

    public function validate()
    {
        $this->isFailed = true;

        if( $this->collection instanceof ExchangeRateCollection AND
            $this->config instanceof RemittanceConfig )
        {
            if( count($this->collection) != 2 )
                return false;

            $this->collection->rewind();
            $rate1 = $this->collection->current();
            $this->collection->next();
            $rate2 = $this->collection->current();

            if( !$corp1 = $this->config->rateBelongsTo($rate1) OR
                !$corp2 = $this->config->rateBelongsTo($rate2) )
                return false;

            if( $corp1->getId() == $corp2->getId() )
                return false;

            if( $this->config->getConversionType() == ConversionType::DIRECT )
            {
                if( $this->config->isRider($corp1) )
                {
                    $this->firstRate = $rate1;
                    $this->secondRate = $rate2;
                    $this->isFailed = false;
                    return true;
                }
                elseif( $this->config->isRider($corp2) )
                {
                    $this->firstRate = $rate2;
                    $this->secondRate = $rate1;
                    $this->isFailed = false;
                    return true;
                }
            }
            elseif( $this->config->getConversionType() == ConversionType::INTERMEDIARY )
            {
                $this->firstRate = $rate1;
                $this->secondRate = $rate2;
                $this->isFailed = false;
                return true;
            }
        }

        return false;
    }
}