<?php

namespace Iapps\RemittanceService\SearchRemittanceRecord;

use Iapps\Common\Core\IappsBaseService;
use Iapps\RemittanceService\RemittanceRecord\RemittanceRecordCollection;
use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\RemittanceService\Recipient\RecipientServiceFactory;
use Iapps\RemittanceService\Recipient\Recipient;
use Iapps\RemittanceService\RemittanceTransaction\RemittanceTransactionServiceFactory;
use Iapps\RemittanceService\RemittanceCompanyUser\RemittanceCompanyUserServiceFactory;

class SearchRemittanceRecordService extends IappsBaseService{
    
    public function findByFilters(RemittanceRecordCollection $filters, IappsDateTime $from_date, IappsDateTime $to_date, $limit = NULL, $page = NULL)
    {
        $this->getRepository()->setFromCreatedAt($from_date);
        $this->getRepository()->setToCreatedAt($to_date);
        
        if( $info = $this->getRepository()->findByFilters($filters, $limit, $page) )
        {                        
            $this->_extractRelatedInformation($info->getResult());
            return $info;
        }
        
        return false;
    }
    
    protected function _extractRelatedInformation(RemittanceRecordCollection $collection)
    {
        //sender/approval user information
        $userIds = $collection->getFieldValues('sender_user_profile_id');
        $approvalIds = $collection->getFieldValues('approved_rejected_by');
        $userIds = array_merge($userIds, $approvalIds);
        $userIds = array_unique($userIds);
        
        $accServ = AccountServiceFactory::build();
        if( $users = $accServ->getUsers($userIds) )
        {
            $collection->joinSender($users);
            $collection->joinApprovedRejectedBy($users);
        }
        
        //recipient information
        $recipient_ids = $collection->getFieldValues('recipient_id');
        $recipient_ids = array_unique($recipient_ids);
        $recipientServ = RecipientServiceFactory::build();
        if( $recipients = $recipientServ->getRecipientByParam(new Recipient(), $recipient_ids, MAX_VALUE, 1) )
        {  
            $collection->joinRecipient($recipients->getResult());
        }                
        
        //transaction/items
        $trx_ids = $collection->getFieldValues('in_transaction_id');
        $trx_ids = array_merge($trx_ids, $collection->getFieldValues('out_transaction_id'));
        $trxServ = RemittanceTransactionServiceFactory::build();
        if( $trxs = $trxServ->findByIdArr($trx_ids) )
        {
            $collection->joinTransaction($trxs->getResult());
        }
    }
}
