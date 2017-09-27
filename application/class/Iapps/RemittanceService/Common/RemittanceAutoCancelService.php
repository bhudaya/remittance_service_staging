<?php

namespace Iapps\RemittanceService\Common;

use Iapps\Common\Core\IappsBaseService;
use Iapps\Common\Core\IappsBasicBaseService;
use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\Microservice\PromoCode\PromoCodeClientFactory;
use Iapps\Common\Transaction\Transaction;
use Iapps\Common\Transaction\TransactionStatus;
use Iapps\RemittanceService\RefundRequest\RefundRequestServiceFactory;
use Iapps\RemittanceService\RemittanceRecord\RemittanceRecord;
use Iapps\RemittanceService\RemittanceRecord\RemittanceRecordServiceFactory;
use Iapps\RemittanceService\RemittanceRecord\SystemRemittancePayment;
use Iapps\RemittanceService\RemittanceTransaction\ItemType;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransaction;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransactionEventBroadcastWithKeyProducer;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransactionRepository;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransactionServiceFactory;

class RemittanceAutoCancelService extends IappsBaseService{

    protected $transactionServ;
    protected $refundServ;
    protected $remittanceServ;

    function __construct(RemittanceTransactionRepository $rp, $ipAddress='127.0.0.1', $updatedBy=NULL)
    {
        parent::__construct($rp, $ipAddress, $updatedBy);

        $this->remittanceServ = RemittanceRecordServiceFactory::build();
        $this->transactionServ = RemittanceTransactionServiceFactory::build();
        $this->refundServ = RefundRequestServiceFactory::build();
        $this->remittanceServ->setUpdatedBy($this->getUpdatedBy());
        $this->remittanceServ->setIpAddress($this->getIpAddress());
        $this->transactionServ->setUpdatedBy($this->getUpdatedBy());
        $this->transactionServ->setIpAddress($this->getIpAddress());
        $this->refundServ->setUpdatedBy($this->getUpdatedBy());
        $this->refundServ->setIpAddress($this->getIpAddress());
    }

    public function process()
    {
        $now = IappsDateTime::now();
        //filter
        $filter = new Transaction();
        $filter->setExpiredDate($now);

        /*
         * local cash out trx
         */
        $filter->getTransactionType()->setCode(TransactionType::CODE_LOCAL_CASH_OUT);
        $filter->getStatus()->setCode(TransactionStatus::CONFIRMED);
        if( $info = $this->getRepository()->findByParam($filter, 100, 1) )
        {
            $expiredTrxs = $info->result;
            foreach($expiredTrxs AS $expiredTrx)
            {
                if( $expiredTrx instanceof RemittanceTransaction )
                {
                    $this->_expireCashOutTransaction($expiredTrx);
                }
            }
        }

        /*
         * repeat for international cash out trx - not required
         */
        /*
        $filter->getTransactionType()->setCode(TransactionType::CODE_CASH_OUT);
        $filter->getStatus()->setCode(TransactionStatus::CONFIRMED);
        if( $info = $this->getRepository()->findByParam($filter, 100, 1) )
        {
            $expiredTrxs = $info->result;
            foreach($expiredTrxs AS $expiredTrx)
            {
                if( $expiredTrx instanceof RemittanceTransaction )
                {
                    $this->_expireCashOutTransaction($expiredTrx);
                }
            }
        }
        */


        /*
         * international cash in pending_payment remittance trx
         */
        $filter->getTransactionType()->setCode(TransactionType::CODE_CASH_IN);
        $filter->getStatus()->setCode(TransactionStatus::CONFIRMED);
        if( $info = $this->getRepository()->findByParam($filter, 100, 1) )
        {
            $expiredTrxs = $info->result;
            foreach($expiredTrxs AS $expiredTrx)
            {
                if( $expiredTrx instanceof RemittanceTransaction )
                {
                    $this->_expireCashInTransaction($expiredTrx);
                }
            }
        }


        //no expired trx
        $this->setResponseCode(MessageCode::CODE_JOB_PROCESS_PASSED);
        return true;
    }

    protected function _expireCashOutTransaction(RemittanceTransaction $expiredTrx)
    {//get remittance info
        if( $remittance = $this->remittanceServ->getByTransactionId($expiredTrx->getId(), false) )
        {
            if( $remittance instanceof RemittanceRecord )
            {
                //$this->getRepository()->startDBTransaction();
                $this->getRepository()->beginDBTransaction();

                //update remittance status to expired
                if( !$this->remittanceServ->expire($remittance) )
                {
                    $this->getRepository()->rollbackDBTransaction();
                    return false;
                }

                //set status to expired
                if( !$this->transactionServ->expireTransaction($expiredTrx) )
                {
                    $this->getRepository()->rollbackDBTransaction();
                    return false;
                }

                //cancel payment request if any
                if( $request_id = $remittance->getCollectionRequestId() )
                {//cancel payment
                    $systemPayment = new SystemRemittancePayment();
                    $systemPayment->paymentCancel($remittance->getOutTransaction(), $request_id);
                }

                //trigger refund
                if( $reason = $this->refundServ->getRefundReason('Expired Transaction') )
                {
                    if( $this->refundServ->initiateFullRefundRequest($remittance->getInTransaction()->getTransactionID(), $reason, null, false, false) )
                    {
                        //$this->getRepository()->completeDBTransaction();
                        if ($this->getRepository()->statusDBTransaction() === FALSE){
                            $this->getRepository()->rollbackDBTransaction();
                        }else {
                            $this->getRepository()->commitDBTransaction();
                        }


                        return true;  //its all done
                    }
                }

                $this->getRepository()->rollbackDBTransaction();
            }
        }

        return false;
    }

    protected function _expireCashInTransaction(RemittanceTransaction $expiredTrx)
    {//get remittance info
        if( $remittance = $this->remittanceServ->getByTransactionId($expiredTrx->getId(), true) )
        {
            if( $remittance instanceof RemittanceRecord )
            {
                //$this->getRepository()->startDBTransaction();
                $this->getRepository()->beginDBTransaction();

                //update remittance status to expired
                if( !$this->remittanceServ->expire($remittance) )
                {
                    $this->getRepository()->rollbackDBTransaction();
                    return false;
                }

                //set status to expired
                if( !$this->transactionServ->expireTransaction($expiredTrx) )
                {
                    $this->getRepository()->rollbackDBTransaction();
                    return false;
                }

                foreach($remittance->getInTransaction()->getItems() AS $item)
                {
                    if( $item->getItemType()->getCode() == ItemType::DISCOUNT )
                    {
                        if($remittance->getInTransaction()->getTransactionType()->getCode() != TransactionType::CODE_REFUND) {
                            $promoServ = PromoCodeClientFactory::build(2);
                            if (!$promoServ->cancelReservedPromoCode($item->getItemId())) {
                                Logger::debug('Failed to cancel reserved promo code' . $remittance->getInTransaction()->getId());
                            }
                        }
                    }
                }


                //publish remittance transaction expired queue if remittance is home collection
                if ((bool)$remittance->getIsHomeCollection()) {
                    RemittanceTransactionEventBroadcastWithKeyProducer::publishTransactionStatusChanged($expiredTrx->getTransactionID(), $expiredTrx->getStatus()->getCode());
                }

                //cancel payment request if any
                if( $request_id = $remittance->getPayMentRequestId() )
                {//cancel payment
                    $systemPayment = new SystemRemittancePayment();
                    $systemPayment->paymentCancel($remittance->getInTransaction(), $request_id);
                }

                //cancel collection request if any
                if( $request_id = $remittance->getCollectionRequestId() )
                {//cancel payment
                    $systemPayment = new SystemRemittancePayment();
                    $systemPayment->paymentCancel($remittance->getOutTransaction(), $request_id);
                }

                //$this->getRepository()->completeDBTransaction();
                if ($this->getRepository()->statusDBTransaction() === FALSE){
                    $this->getRepository()->rollbackDBTransaction();
                }else {
                    $this->getRepository()->commitDBTransaction();
                }

                return true;
            }
        }

        return false;
    }
}