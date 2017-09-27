<?php

namespace Iapps\RemittanceService\RemittanceRecord;

/*
* This is responsible to check if the collection can be request
*/
use Iapps\Common\Microservice\AccountService\User;
use Iapps\Common\Microservice\PaymentService\PaymentServiceFactory;
use Iapps\RemittanceService\Recipient\Recipient;

class CollectionChecker{

    protected $requestable = false;

    public function setRequestable($requestable)
    {
        $this->requestable = $requestable;
        return $this;
    }

    public function isRequestable()
    {
        return $this->requestable;
    }

    public static function check(Recipient $recipient, $collection_mode)
    {
        $c = new CollectionChecker();

        $payment_service = PaymentServiceFactory::build();
        if( $paymentMode = $payment_service->getPaymentModeInfo($collection_mode) )
        {
            if( (bool) $paymentMode->getSelfService() )
            {
                //for ewallet --> not sure where to put this logic?
                if( $collection_mode == 'EWA' )
                {
                    if( $recipient->isSlideUser() )
                        $c->setRequestable(true);
                    else
                        $c->setRequestable(false);
                }
                else
                    $c->setRequestable(true);
            }
            else
                $c->setRequestable(false);

            return $c;
        }

        return false;
    }


}