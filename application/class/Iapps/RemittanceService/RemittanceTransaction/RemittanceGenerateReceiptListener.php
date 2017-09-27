<?php

namespace Iapps\RemittanceService\RemittanceTransaction;

use Iapps\Common\Helper\MessageBroker\BroadcastEventConsumer;
use Iapps\Common\Helper\RequestHeader;
use Iapps\Common\Microservice\AccountService\AccountService;
use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\Common\Microservice\DeliveryService\DeliveryClient;
use Iapps\Common\Microservice\PaymentService\PaymentService;
use Iapps\Common\Microservice\PaymentService\PaymentServiceFactory;
use Iapps\Common\Microservice\RemittanceService\RemittanceTransaction;
use Iapps\RemittanceService\Common\TransactionType;
use Iapps\RemittanceService\Export\RemittanceReceiptExportService;

class RemittanceGenerateReceiptListener extends BroadcastEventConsumer{

    protected $header = array();

    public function setHeader(array $header)
    {
        $this->header = $header;
        return $this;
    }

    public function getHeader()
    {
        return $this->header;
    }

    protected function doTask($msg)
    {
        $this->setForceAcknowledgement(false);
        $trx_serv = RemittanceTransactionServiceFactory::build();

        //to overwrite the headers
        $pay_serv = PaymentServiceFactory::build();
        $acc_serv = AccountServiceFactory::build();
        $trx_serv->setDeliveryClient(DeliveryClient::SYSTEM);

        $data = json_decode($msg->body);

        try{
            //wait one second for the data to be commited to DB
            sleep(2);

            $transaction = new RemittanceTransaction();
            $transaction->setId($data->transaction_id);
            $trx_serv->setPaymentService($pay_serv);
            $trx_serv->setAccountService($acc_serv);
            $trx_serv->setDeliveryClient(DeliveryClient::SYSTEM);
            $transactionDetail = $trx_serv->getTransactionDetail($transaction,1, 1);

            if (empty($transactionDetail)){
                return false;
            }

            $receiptExportService = new RemittanceReceiptExportService();


            if( $transactionDetail->transaction->getTransactionType()->getCode() == TransactionType::CODE_REFUND ) {
                $viewPath = "./application/class/Iapps/RemittanceService/Export/ViewReceiptExportRefund.php";
            }
            elseif( $transactionDetail->transaction->getTransactionType()->getCode() == TransactionType::CODE_CASH_IN OR
                    $transactionDetail->transaction->getTransactionType()->getCode() == TransactionType::CODE_CASH_OUT )
            {
                $viewPath = './application/class/Iapps/RemittanceService/Export/ViewReceiptExportRemit.php';
            }
            elseif( $transactionDetail->transaction->getTransactionType()->getCode() == TransactionType::CODE_LOCAL_CASH_IN )
            {
                $viewPath = './application/class/Iapps/RemittanceService/Export/ViewReceiptExportLocalSender.php';
            }
            elseif( $transactionDetail->transaction->getTransactionType()->getCode() == TransactionType::CODE_LOCAL_CASH_OUT )
            {
                $viewPath = './application/class/Iapps/RemittanceService/Export/ViewReceiptExportLocalRecipient.php';
            }
            else
                return false;

            return $receiptExportService->generateAndNotifyUser($transactionDetail , $viewPath);
        } catch (\Exception $e){
            return false;
        }
    }
    
    public function listenEvent()
    {
        $this->listen(RemittanceTransactionEventType::REMITTANCE_TRANSACTION_CREATED, NULL, 'remittance.queue.generateReceipt');
    }
}