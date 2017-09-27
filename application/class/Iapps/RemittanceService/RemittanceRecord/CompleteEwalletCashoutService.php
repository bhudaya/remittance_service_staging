<?php

namespace Iapps\RemittanceService\RemittanceRecord;

use Iapps\Common\Core\IappsBasicBaseService;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransaction;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransactionServiceFactory;

class CompleteEwalletCashoutService extends IappsBasicBaseService{

    public function process($transaction_id)
    {
        $tranServ = RemittanceTransactionServiceFactory::build();
        $tranServ->setUpdatedBy($this->getUpdatedBy());
        $tranServ->setIpAddress($this->getIpAddress());

        $_ci = get_instance();
        $_ci->load->model('remittancerecord/Remittance_model');
        $repo = new RemittanceRecordRepository($_ci->Remittance_model);
        $systemPayment = new SystemRemittancePayment();
        $remittanceServ = new RemittanceRecordService($repo, $this->getIpAddress()->getString(), $this->getUpdatedBy(), $systemPayment);

        //get transaction
        if( $transaction = $tranServ->findById($transaction_id) )
        {
            if( $transaction instanceof RemittanceTransaction )
            {
                if( $transaction->isCashOut() )
                {
                    //find remittance
                    if( $remittance = $remittanceServ->getByTransactionId($transaction->getId(), false) )
                    {
                        if( $remittance instanceof RemittanceRecord )
                        {
                            return $remittanceServ->completeCashOut($remittance->getId());
                        }
                    }
                }

                //nothing to process
                return true;
            }
        }

        return false;
    }
}