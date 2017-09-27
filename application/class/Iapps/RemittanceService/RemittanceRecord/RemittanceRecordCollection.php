<?php

namespace Iapps\RemittanceService\RemittanceRecord;

use Iapps\Common\Core\IappsBaseEntityCollection;

class RemittanceRecordCollection extends IappsBaseEntityCollection{

    public function joinSender(IappsBaseEntityCollection $users)
    {
        foreach($this AS $remittanceRecord)
        {
            if( $user = $users->getById($remittanceRecord->getSender()->getId()) )
            {
                $remittanceRecord->setSender($user);
            }
        }
        
        return $this;
    }
    
    public function joinApprovedRejectedBy(IappsBaseEntityCollection $users) 
    {
        foreach($this AS $remittanceRecord)
        {
            if( $user = $users->getById($remittanceRecord->getApprovedRejectedByUser()->getId()) )
            {
                $remittanceRecord->setApprovedRejectedByUser($user);
            }
        }
        
        return $this;
    }
    
    public function joinRecipient(IappsBaseEntityCollection $recipients)
    {
        foreach($this AS $remittanceRecord)
        {
            if( $recipient = $recipients->getById($remittanceRecord->getRecipient()->getId()) )
            {
                $remittanceRecord->setRecipient($recipient);
            }
        }
        
        return $this;
    }
    
    public function joinTransaction(IappsBaseEntityCollection $transactions)
    {
        foreach($this AS $remittanceRecord)
        {
            if( $transaction = $transactions->getById($remittanceRecord->getInTransaction()->getId()) )
            {
                $remittanceRecord->setInTransaction($transaction);
            }
            
            if( $transaction = $transactions->getById($remittanceRecord->getOutTransaction()->getId()) )
            {
                $remittanceRecord->setOutTransaction($transaction);
            }
        }
        
        return $this;
    }
}