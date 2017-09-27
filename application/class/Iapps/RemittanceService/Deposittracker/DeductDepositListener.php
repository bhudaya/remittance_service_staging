<?php

namespace Iapps\RemittanceService\DepositTracker;


use Iapps\Common\DepositTracker\DepositTracker;
use Iapps\Common\Helper\MessageBroker\BroadcastEventConsumer;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfigServiceFactory;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfigService;
use Iapps\RemittanceService\RemittanceConfig;
use Iapps\RemittanceService\RemittanceRecord\RemittanceRecordServiceFactory;
use Iapps\RemittanceService\RemittanceRecord\RemittanceRecord;
use Iapps\RemittanceService\Common\DepositTrackerServiceFactory;
use Iapps\RemittanceService\Common\DepositTrackerRequestServiceFactory;
use Iapps\Common\DepositTracker\DepositTrackerRequest;
use Exception;
use Iapps\Common\Helper\GuidGenerator;
use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\CommunicationService\CommunicationServiceProducer;
use Iapps\RemittanceService\RemittanceRecord\RemittanceStatus;
use Iapps\RemittanceService\DepositTracker\DepositTrackerConstants;
use Iapps\RemittanceService\Common\DepositTrackerEmailServiceFactory;



class DeductDepositListener extends BroadcastEventConsumer
{
    private $deposittracker;
    private $depositrequest;
    private $remittancedata;
    private $requestamount;
    private $maincurrency = 'SG-SGD';
    private $superlimit = 9999999999999999;


    protected function doTask($msg)
    {

        $data = json_decode($msg->body);
        try{
            $id = $data->remittance_id;
            $factory = RemittanceRecordServiceFactory::build();
            $record = new RemittanceRecord();
            $record->setId($id);
            $result = $factory->getByPrimaryKey($record);
            $this->remittancedata = $result;
            if($this->isRemittanceConfigExist($this->remittancedata->getRemittanceConfigurationId()))
            {
                if($this->isRemittanceCollected($this->remittancedata))
                {
                     $this->deposittracker = $this->getDepositTracker($this->remittancedata->getRemittanceConfigurationId());
                     $this->depositrequest = $this->createDepositRequestObject($this->remittancedata, $this->deposittracker[0]);
                     if($this->isBalanceSufficient($this->depositrequest,$this->deposittracker[0], $this->remittancedata))
                     { 
                        if($this->insertDeductionRequest($this->depositrequest,$this->deposittracker[0],$this->remittancedata)){
                            date_default_timezone_set('Asia/Singapore');
                            echo 'Remittance ID : '. $this->depositrequest->getTransReferenceNum()."\n";
                            echo 'Amount : '. $this->depositrequest->getAmount()."\n";
                            echo 'Has been deducted to Deposit Tracker ID : '. $this->deposittracker[0]->getId()."\n";
                            echo 'Date of deduction : ' . date('Y-m-d h:i:s a'). "\n\n";
                            $this->fireEmail($this->depositrequest,$this->deposittracker[0],$this->remittancedata);
                        }
                     }                    
                }
            }
        } catch(Exception $e){
            date_default_timezone_set('Asia/Singapore');
            echo isset($this->deposittracker[0]->id)?"Deposit Tracker Id : ".$this->deposittracker[0]->id."\n":'----';
            echo "Error : ". $e->getMessage();
            echo "Remittance ID caught on queue : " .$this->remittancedata->getRemittanceId()."\n";
            echo "Date caught : ". date('Y-m-d h:i:s a'). "\n\n";
        }
    }

    private function isRemittanceConfigExist($remittance_config_id)
    {
        $depositfactory = DepositTrackerServiceFactory::build();
        $deposit = new DepositTracker();
        $deposit->setRemittanceConfigId($remittance_config_id);
        $deposit->setDepositStatus(DepositTrackerConstants::APPROVED);
        $result = $depositfactory->getByConfigId($deposit);
        if(!empty($result)){
            return true;
        } else {
            throw new Exception(DepositTrackerConstants::ERROR_REMITTANCE_CONFIG_NOT_EXIST."\n");
        }

    }


    private function getChannelByRemittanceConfigId($remittance_config_id){

        $remittanceService = RemittanceConfigServiceFactory::build();
        $channel = $remittanceService->getRemittanceConfigById($remittance_config_id);
        if ($channel) {
            $channel = json_encode($channel);
            return $channel;
        } else {
            throw new Exception(DepositTrackerConstants::ERROR_CHANNEL_NOT_AVAILABLE);
        }

    }


    private function isBalanceSufficient($request, $deposit, $remittancedata)
    {

        $emaildata = array();
        $result = $this->getChannelByRemittanceConfigId($deposit->getRemittanceConfigId());
        $channel = json_decode($result);

        if($deposit->getDepositHolder() == $channel->from_country_partner_id){
            $depositholder = $channel->from_country_partner_name;
        }
        if($deposit->getDepositHolder() == $channel->to_country_partner_id){
            $depositholder = $channel->to_country_partner_name;
        }
        $balance = $deposit->getAmount();
        
        $emaildata['channelid'] = $channel->channel_id;
        $emaildata['fromcountrycurrencycode'] = $channel->from_country_currency_code;
        $emaildata['tocountrycurrencycode'] = $channel->to_country_currency_code;
        $emaildata['fromcountrypartnername'] = $channel->from_country_partner_name;
        $emaildata['tocountrypartnername'] = $channel->to_country_partner_name;
        $emaildata['depositholder'] = $depositholder;
        $emaildata['balance'] = $balance;
        $currency = $deposit->getCountryCurrencyCode();
        $depositamount = $deposit->getAmount();
        $displayrate = $remittancedata->getDisplayRate();
        $fromamount = $remittancedata->getFromAmount();
        $toamount = $remittancedata->getToAmount();
        if($currency != $this->maincurrency){
            $difference = $depositamount - $toamount;
            if($difference < $deposit->getThresholdAmount()){
                $emaildata['balance'] = $difference;
                $emails = $this->getDepositTrackerEmails($deposit->getId());
                $this->deductDepositForNegativeBalance($request, $deposit, $remittancedata);
                $this->fireEmailNotification($emails,$deposit,$emaildata);
                throw new Exception(DepositTrackerConstants::ERROR_DEPOSIT_INSUFFICIENT_BALANCE."\n");
            }
            if($difference < $deposit->getThresholdAmount() && $difference < 0){
                $emaildata['balance'] = $difference;
                $emails = $this->getDepositTrackerEmails($deposit->getId());
                $this->deductDepositForNegativeBalance($request, $deposit, $remittancedata);
                $this->fireEmailNotification($emails,$deposit,$emaildata);
                throw new Exception(DepositTrackerConstants::ERROR_DEPOSIT_INSUFFICIENT_BALANCE."\n");
            }
        }
        if($currency == $this->maincurrency){
            $difference = $depositamount - $fromamount;
            if($difference < $deposit->getThresholdAmount() && $difference > 0){
                $emaildata['balance'] = $difference;
                $emails = $this->getDepositTrackerEmails($deposit->getId());
                $this->deductDepositForNegativeBalance($request, $deposit, $remittancedata);
                $this->fireEmailNotification($emails,$deposit,$emaildata);
                throw new Exception(DepositTrackerConstants::ERROR_DEPOSIT_INSUFFICIENT_BALANCE."\n");
            }
            if($difference < $deposit->getThresholdAmount() && $difference < 0){
                $emaildata['balance'] = $difference;
                $emails = $this->getDepositTrackerEmails($deposit->getId());
                $this->deductDepositForNegativeBalance($request, $deposit, $remittancedata);
                $this->fireEmailNotification($emails,$deposit,$emaildata);
                throw new Exception(DepositTrackerConstants::ERROR_DEPOSIT_INSUFFICIENT_BALANCE."\n");
            }
        } 
        return true;
    }

    private function getDepositTracker($remittance_config_id)
    {
        $depositfactory = DepositTrackerServiceFactory::build();
        $deposit = new DepositTracker();
        $deposit->setRemittanceConfigId($remittance_config_id);
        $deposit->setDepositStatus(DepositTrackerConstants::APPROVED);
        $result = $depositfactory->getByConfigId($deposit);
        if($result){
           return $result;
        } else {
            throw new Exception(DepositTrackerConstants::ERROR_REMITTANCE_CONFIG_NOT_EXIST."\n");
        }
    }


    private function isRemittanceCollected($remittancedata)
    {
        if((!empty($remittancedata->getCollectedAt()->getString()) || !is_null($remittancedata->getCollectedAt()->getUnix())) && ($remittancedata->getApprovalStatus() == RemittanceStatus::APPROVED)){
            return true;
        } else {
            throw new Exception(DepositTrackerConstants::ERROR_REMITTANCE_NOT_YET_COLLECTED."\n");
        }
    }


    private function createDepositRequestObject($remittancedata,$deposit)
    {  
        $currency = $deposit->getCountryCurrencyCode();
        $displayrate = $remittancedata->getDisplayRate();
        $fromamount = $remittancedata->getFromAmount();
        $toamount = $remittancedata->getToAmount();

        
        if($currency != $this->maincurrency){
            $this->requestamount = $toamount;
        }
        if($currency == $this->maincurrency){
            $this->requestamount = $fromamount;
        }
        
        $this->requestamount = $fromamount;
        $request = new DepositTrackerRequest();
        $request->setid(GuidGenerator::generate());
        $request->setAmount($this->requestamount);
        $request->setDepositTrackerId($deposit->getId());
        $request->setType(DepositTrackerConstants::DEDUCTION);
        $request->setStatus(DepositTrackerConstants::APPROVED);
        $request->setPurpose(DepositTrackerConstants::TRANSACTION);
        $collectioninfo = json_decode($remittancedata->getCollectionInfo());
        if(isset($collectioninfo->option->bank_code)) {
            $request->setBank($collectioninfo->option->bank_code);
        }
        $request->setTransReferenceNum($remittancedata->getRemittanceID());
        $request->setCreatedBy($remittancedata->getCreatedBy());
        return $request;

    }

    private function insertDeductionRequest($request, $deposit, $remittancedata)
    {
        $requestfactory = DepositTrackerRequestServiceFactory::build();

        $currency = $deposit->getCountryCurrencyCode();
        $depositamount = $deposit->getAmount();
        $displayrate = $remittancedata->getDisplayRate();
        $fromamount = $remittancedata->getFromAmount();
        $toamount = $remittancedata->getToAmount();
        $thresholdamount = $deposit->getThresholdAmount();
        $newbalance = '';
        
        if($currency != $this->maincurrency){
            if(($depositamount - $toamount) < 0){
                throw new Exception(DepositTrackerConstants::ERROR_DEPOSIT_INSUFFICIENT_BALANCE."\n");
            }
            $request->setAmount($toamount);
            $newbalance = $depositamount - $toamount;
        } else
        if($currency == $this->maincurrency){
            if(($depositamount - $fromamount) < 0){
                throw new Exception(DepositTrackerConstants::ERROR_DEPOSIT_INSUFFICIENT_BALANCE."\n");
            }
            $newbalance = $depositamount - $fromamount;
        }


        $depositdata = new DepositTracker();
        $depositdata->setId($deposit->getId());
        $depositdata->setAmount($newbalance);
        if($newbalance < $thresholdamount){
            $depositdata->setThresholdStatus(DepositTrackerConstants::LOWTHRESHOLDSTATUS);
        } else {
            $depositdata->setThresholdStatus(DepositTrackerConstants::NORMALTHRESHOLDSTATUS);
        }
        $depositdata->setUpdatedBy($deposit->getUpdatedBy());  
        if($requestfactory->insertDeductionRequest($request,$depositdata)){
            return true;
        } else {
            return false;
        }

    }


    //returns array object
    private function getDepositTrackerEmails($depositid)
    {
        $depositfactory = DepositTrackerEmailServiceFactory::build();
        $deposit = new DepositTracker();
        $deposit->setId($depositid);
        $deposit->setDepositStatus(DepositTrackerConstants::APPROVED);
        $result = $depositfactory->getDepositTrackerEmails($deposit);
        return $result;
    }

    private function fireEmailNotification($emails, $deposit, $emaildata)
    {

      if(!empty($emails)) {

          $formattedbalance =  substr($deposit->getCountryCurrencyCode(),strpos($deposit->getCountryCurrencyCode(),'-') + 1)." ".number_format($emaildata['balance'],2,".",",");

          $subject = 'Low SLIDE Deposit - [' . $emaildata['channelid'] .' , '. $emaildata['depositholder'].' ]';

          $textcontent = "<p>[AB]Channel ID[CD]: ".$emaildata['channelid']."</p>";
          $textcontent .= "<p>[AB]From Country Currency[CD]: ".$emaildata['fromcountrycurrencycode']."</p>";
          $textcontent .= "<p>[AB]To Country Currency[CD] ".$emaildata['tocountrycurrencycode']."</p>";
          $textcontent .= "<p>[AB]From Country Partner[CD]: ".$emaildata['fromcountrypartnername']."</p>";
          $textcontent .= "<p>[AB]To Country Partner[CD]: ".$emaildata['tocountrypartnername']."</p><br/>";
          $textcontent .= "<p>[AB]Deposit Holder[CD]: ".$emaildata['depositholder']."</p>";
          $textcontent .= "<p>[AB]Balance[CD]: ".$formattedbalance."</p>";

          $textcontent = str_replace("[AB]","<div style='width:25%; font-weight: bold; float:left;margin-right:3px;margin-left:3px'>",$textcontent);
          $textcontent = str_replace("[CD]","</div>",$textcontent);

          $emailArray = array();
          for ($i = 0; $i < sizeof($emails); $i++) {
              $emailArray[$i] = $emails[$i]->getEmail();
          }
          $communication = new CommunicationServiceProducer();
          $communication->sendEmail(getenv("ICS_PROJECT_ID"),$subject ,$textcontent, $textcontent, $emailArray);
      }
    }


    private function fireEmail($depositrequest,$deposittracker,$remittancedata)
    {
        $to = array('marvin@iappsasia.com');
        $subject  = 'Deduction Successful';
        $content  = 'Remittance ID : '. $depositrequest->getTransReferenceNum()."\n";
        $content .= 'Amount : '. $depositrequest->getAmount()."\n";
        $content .=  'Has been deducted to Deposit Tracker ID : '. $deposittracker->getId()."\n";
        $content .= 'Date of deduction : ' . date('Y-m-d h:i:s a'). "\n\n";
        $communication = new CommunicationServiceProducer();
        $communication->sendEmail(getenv("ICS_PROJECT_ID"),$subject ,$content, $content,$to );
    }

    public function getProtectedValue($obj,$name)
    {
        $array = (array)$obj;
        $prefix = chr(0).'*'.chr(0);
        return $array[$prefix.$name];
    }

    public function listenEvent()
    {
        $this->listen('remittance.completed', NULL, 'remittance.queue.deductDeposit');
    }

    /*
     * @access private
     * @usage the Product Owner
     * requested to have negative balance
     * to be displayed. This function
     * does the job, it gets called
     * right after the validation "if" conditions
     * fails in the isBalanceSufficient function.
     * Initially we only display negative amount
     * in the email that was being fired in this
     * listener whenever the balance is lower than the
     * threshold amount. Now the balance will
     * also get negative amount.
     */
    private function deductDepositForNegativeBalance($request, $deposit , $remittancedata)
    {
        $requestfactory = DepositTrackerRequestServiceFactory::build();

        $currency = $deposit->getCountryCurrencyCode();
        $depositamount = $deposit->getAmount();
        $displayrate = $remittancedata->getDisplayRate();
        $fromamount = $remittancedata->getFromAmount();
        $toamount = $remittancedata->getToAmount();
        $thresholdamount = $deposit->getThresholdAmount();
        $newbalance = '';

        if($currency != $this->maincurrency){
//            if(($depositamount - $toamount) < 0){
//                throw new Exception(DepositTrackerConstants::ERROR_DEPOSIT_INSUFFICIENT_BALANCE."\n");
//            }
            $request->setAmount($toamount);
            $newbalance = $depositamount - $toamount;
        } else
            if($currency == $this->maincurrency){
//                if(($depositamount - $fromamount) < 0){
//                    throw new Exception(DepositTrackerConstants::ERROR_DEPOSIT_INSUFFICIENT_BALANCE."\n");
//                }
                $newbalance = $depositamount - $fromamount;
            }


        $depositdata = new DepositTracker();
        $depositdata->setId($deposit->getId());
        $depositdata->setAmount($newbalance);
        if($newbalance < $thresholdamount){
            $depositdata->setThresholdStatus(DepositTrackerConstants::LOWTHRESHOLDSTATUS);
        } else {
            $depositdata->setThresholdStatus(DepositTrackerConstants::NORMALTHRESHOLDSTATUS);
        }
        $depositdata->setUpdatedBy($deposit->getUpdatedBy());
        if($requestfactory->insertDeductionRequest($request,$depositdata)){
            return true;
        } else {
            return false;
        }

    }


}

