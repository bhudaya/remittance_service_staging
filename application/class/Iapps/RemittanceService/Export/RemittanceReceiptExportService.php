<?php
/**
 * Created by PhpStorm.
 * User: Jack
 * Date: 15/7/16
 * Time: 3:53 PM
 */

namespace Iapps\RemittanceService\Export;

use Iapps\Common\Export\ReceiptExportService;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransaction;

class RemittanceReceiptExportService extends ReceiptExportService
{
    public function generateAndNotifyUser($transactionDetail , $viewPath)
    {
        if( isset($transactionDetail->transaction) )
        {
            $trx = $transactionDetail->transaction;
            if( $trx instanceof RemittanceTransaction )
            {
                if( !$trx->getUserProfileId() ) //if not slide user, no generation required
                    return true;
                else
                    return parent::generateAndNotifyUser($transactionDetail, $viewPath);
            }
        }

        //ignore if something else
        log_message('error', 'There is an unknown error when generating remittance receipt');
        return true;
    }
}