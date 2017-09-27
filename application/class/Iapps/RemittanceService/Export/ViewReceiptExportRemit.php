<?php

use Iapps\Common\Helper\StringMasker;
use Iapps\RemittanceService\RemittanceCompany\RemittanceCompany;

    $paymentMode = 0;
    $paymentModeFee = 0;
    $transactionFee = 0;
    $totalPayable = 0;
    $itemDescription = array();
    $paymentModeSubTotal = 0;
    $discountDescription = array();
    $totalDiscount = 0;
    $totalCharges = 0;
    $paymentModeDescription1 = array();
    $paymentModeDescription2 = array();
    $transactionId = NULL;
    $transactionDate = NULL;
    $subTotal = 0;
    $paymentModeName = NULL;
    $transactionID = $transactionDetail->transaction->getTransactionID();

    $transactionDetail->transaction->getCreatedAt()->setTimeZoneFormat($timezone_format);
    $transactionDateStr = $transactionDetail->transaction->getCreatedAt()->getLocalDateTimeStr();

    $paymentMode = ($transactionDetail->payment)? $transactionDetail->payment[0] : array();
    $transactionType = $transactionDetail->transaction->getTransactionType()->getDisplayName();
    $countryCurrencyCode = $transactionDetail->transaction->getCountryCurrencyCode();
    $receipt_number  = $transactionDetail->remittance["remittanceID"] ? $transactionDetail->remittance["remittanceID"] : "-";

    if( isset($paymentMode->description1) )
        $paymentModeDescription1 = json_decode($paymentMode->description1);

    if( isset($paymentMode->description2) )
        $paymentModeDescription2 = json_decode($paymentMode->description2);

    $combinedPaymentDescription = '';
    if( is_array($paymentModeDescription1) )
    {
        foreach($paymentModeDescription1 AS $desc1)
        {
            if( strlen($combinedPaymentDescription) > 0 )
                $combinedPaymentDescription .= ', ';

            if( isset($desc1->title) AND strlen($desc1->title)>0 )
                $combinedPaymentDescription = $desc1->title . ":";

            if( isset($desc1->value) )
                $combinedPaymentDescription .= $desc1->value;
        }
    }

    if( is_array($paymentModeDescription2) )
    {
        foreach($paymentModeDescription2 AS $desc2)
        {
            if( strlen($combinedPaymentDescription) > 0 )
                $combinedPaymentDescription .= ', ';

            if( isset($desc2->title) AND strlen($desc2->title)>0 )
                $combinedPaymentDescription .= $desc2->title . ":";

            if( isset($desc2->value) )
                $combinedPaymentDescription .= $desc2->value;
        }
    }

    $recipientDescription = array();
    $recipient = $transactionDetail->remittance["recipient"];
    $recipientDescription['recipient_full_name'] = isset($recipient['full_name']) ? $recipient['full_name'] : "-";
    $recipientDescription['residential_country'] = isset($recipient['residential_country']) ? $recipient['residential_country'] : "-";

    $recipient_dialing_code = empty($recipient['recipient_dialing_code']) ? "" : $recipient['recipient_dialing_code'];
    $recipient_mobile_number = empty($recipient['recipient_mobile_number']) ? "" : $recipient['recipient_mobile_number'];

    if (!empty($recipient_dialing_code))
    {
        if (!strpos($recipient_dialing_code,"+"))
        {
            $recipient_dialing_code = "+" . $recipient_dialing_code;
        }
    }

    $recipientDescription['mobile_no'] = $recipient_dialing_code . " " . $recipient_mobile_number;
    $recipientDescription['accountID'] = array("title" => "SLIDE Account ID", "value" => isset($recipient['accountID']) ? $recipient['accountID'] : "-");
    $recipientDescription['relationship_to_sender'] = array("title" => "Relationship to Sender", "value" => isset($recipient['relationship_to_sender']) ? $recipient['relationship_to_sender'] : "-");
    $recipientDescription['confirm_collection_mode'] = array("title" => "Collection Mode", "value" => "-");

    $recipientDescription["bank_info"] = "-";
    $recipientDescription["account_holder_name"] = "-";

    if(isset($recipient['collectionInfo']))
    {
        $collectionInfo = $recipient['collectionInfo'];

        if( isset($collectionInfo->payment_mode_name) )
            $recipientDescription['confirm_collection_mode'] = array("title" => "Collection Mode", "value" => $collectionInfo->payment_mode_name);

        if (isset($collectionInfo->option))
        {
            $option = $collectionInfo->option;
            if(!empty($option))
            {
                $collectionInfoJson = json_decode($option);

                $bank_name = isset($collectionInfoJson->bank_name) ? $collectionInfoJson->bank_name : null;
                $bank_code = isset($collectionInfoJson->bank_code) ? $collectionInfoJson->bank_code : null;
                $account_no = isset($collectionInfoJson->account_no) ? StringMasker::mask($collectionInfoJson->account_no,4) : null;
                $account_holder_name = isset($collectionInfoJson->account_holder_name) ? $collectionInfoJson->account_holder_name : "-";

                $recipientDescription["bank_info"] = $bank_name . " " . $account_no;
                $recipientDescription["account_holder_name"] = $account_holder_name;
            }
        }
    }

    foreach($recipientDescription as $k => $v)
    {
        if(is_array($v))
        {
            if(empty($v['value'])) $recipientDescription[$k]['value'] = "NA";
        }else{
            if(empty($v)) $recipientDescription[$k] = "NA";
        }
    }



    $sender = $transactionDetail->remittance["sender"];

    $senderDescription = array();

    $senderDescription['name'] = isset($sender['name']) ? $sender['name'] : "-";
    $senderDescription['country'] = isset($sender['host_address']->country) ? $sender['host_address']->country : "-";
    $senderDescription['mobile_no'] = isset($sender['mobile_no']) ? $sender['mobile_no']->getCombinedNumber() : "-";
    $senderDescription['accountID'] = array("title" => "SLIDE Account ID", "value" => isset($sender['accountID']) ? $sender['accountID'] : "-");
    $senderDescription['id_type'] = array("title" => "Identification Type", "value" => isset($sender['id_type']) ? $sender['id_type'] : "-");
    $senderDescription['host_identity_card'] = array("title" => "Identification No", "value" => isset($sender['host_identity_card']) ? StringMasker::mask($sender['host_identity_card'],4) : "-");
    $senderDescription['nationality'] = isset($sender['nationality']) ? $sender['nationality'] : "-";
    $senderDescription['addresss'] = isset($sender['host_address']->address) ? $sender['host_address']->address : "-";
    $senderDescription['addresss'] .= isset($sender['host_address']->city_name) ? ", " . $sender['host_address']->city_name : null;
    $senderDescription['addresss'] .= isset($sender['host_address']->province_name) ? ", " . $sender['host_address']->province_name : null;
    $senderDescription['remittance_purpose'] = array("title" => "Purpose", "value" => isset($recipient['remittance_purpose']) ? $recipient['remittance_purpose'] : "-");

    foreach($senderDescription as $k => $v)
    {
        if(is_array($v))
        {
            if(empty($v['value'])) $v['value'] = "NA";
        }else{
            if(empty($v)) $senderDescription[$k] = "NA";
        }
    }


    foreach ($transactionDetail->transaction_items AS $k =>  $v){


        switch ($v->getItemType()->getCode()) {

            case 'corporate_service':
                $itemDescription = json_decode($v->getDescription());
                $paymentModeSubTotal = $v->getNetAmount();
                break;
            case 'corporate_service_fee':

                $transactionFee = $v->getNetAmount();
                break;
            case 'payment_fee':
                $paymentModeFee = $v->getNetAmount();
                break;
            case 'discount':
                $discountDescription =  json_decode($v->getDescription());
                $totalDiscount = $v->getNetAmount();
                break;
        }
    }


    $totalCharges = $paymentModeFee + $transactionFee;
    $totalPayable = abs($transactionDetail->transaction_items->getTotalAmount());
    $paymentModeName = $paymentMode->payment_mode_name;

    $delivery = $transactionDetail->remittance["home_collection"];
    $delivery_info = array();
    $delivery_info['delivery_mode_name'] = "-";
    $delivery_info['deliveryID'] = "-";
    $delivery_info['delivery_time'] = "-";

    if($delivery != NULL) {
        $delivery_info['delivery_mode_name'] = isset($delivery['delivery_mode_name']) ? $delivery['delivery_mode_name'] : "-";
        $delivery_info['deliveryID'] = isset($delivery['deliveryID']) ? $delivery['deliveryID'] : "-";
        $delivery_time = isset($delivery['updated_at']) ? $delivery['updated_at'] : isset($delivery['scheduled_at']) ? $delivery['scheduled_at'] : NULL;

        if ($delivery_time) {
            $deliveryTime = \Iapps\Common\Core\IappsDateTime::fromString($delivery_time);
            $deliveryTime->setTimeZoneFormat($timezone_format);
            $delivery_info['delivery_time'] = $deliveryTime->getLocalDateTimeStr();
        }
    }

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/html">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .wrapper {
            overflow:hidden;
        }

        .wrapper div {
            min-height: 200px;
            padding: 10px;
        }
        #one {
            background-color: white;
            float:left;
            margin:0px;
            width:50%;
            padding:0px;
        }
        #two {
            background-color: white;
            overflow:hidden;
            margin:0px;
            padding:0px;
        }

        @media screen and (max-width: 400px) {
            #one {
                float: none;
                margin-right:0;
                width:auto;
                border:0;
                border-bottom:2px solid #000;
            }
        }
    </style>
</head>
<body>
<div style="width: 100%; margin: 0 auto; padding: 0;font-family:Arial,'Times New Roman','Microsoft YaHei',SimHei;">
    <div style="width: 100%;border:0px;background: #f5f5f5;height: auto;display: block;overflow: hidden;">
        <div style="width: 50%; min-width: 300px; padding: 0;text-align: left; float: left">
            <div style="margin: 0;padding: 0;padding-top: 15px;padding-right: 15px; padding-left: 15px; width: auto;text-align: left; float: left">
                <img src="https://s3-ap-southeast-1.amazonaws.com/slideproduction/public/images/logo.gif" style="">
            </div>
            <div style="width: 60%; text-align: left; float: left">
                <p style="margin: 0;padding: 0;margin-top: 10px;">
                    <span style="color:#e92b83;">Thank you for choosing SLIDE</span>
                </p>
                <p style="margin: 0;padding: 0;margin-top: 10px;">
                    <span style="color:#4A4A4A;font-size: 40px; font-weight: bold "><?php echo $currencyFormatter::format($totalPayable , $countryCurrencyCode); ?></span>
                </p>
            </div>
        </div>
        <div style="width: 50%; min-width: 300px; padding: 0;text-align: right; float: right">
            <div style="width: 100%; float: right;  text-align: right; padding-right: 20px">
                <p style="margin: 0;padding: 0;margin-top: 10px;">
                  <span style="color:#4A4A4A; font-size: 20px">
                          <b><?php echo $transactionType?></b>
                  </span>
                </p>

                <p style="margin: 0;padding: 0;margin-top: 10px;">
                    <span style="color:#8f8f8f; font-size: 14px">Receipt No.: <?php echo $receipt_number?></span>
                </p>

                <p style="margin: 0;padding: 0;margin-top: 10px;">
                    <span style="color:#8f8f8f; font-size: 14px">Transaction ID: <?php echo $transactionID?></span>
                </p>

                <p style="margin: 0;padding: 0;margin-top: 2px;">
                    <span style="color:#8f8f8f; font-size: 14px"><?php echo $transactionDateStr ?></span>
                </p>
            </div>
        </div>
    </div>
    <div style="clear:both;"></div>
    <div style="width: 94%;overflow: hidden;margin: auto;" >
        <div style="clear:both;"></div>
        <div style="width: 94%;overflow: hidden;margin: auto;" >
            <div class="wrapper" >
                <div id="one">
                    <h3 style="color:#e92b83;font-size: 20px;">SENDER</h3>
                    <div style="padding: 0;margin: 0;font-size: 14px;font-weight: normal;">
                        <p style="font-size: 14px">
                            <?php
                            foreach ($senderDescription as $k => $v)
                            {
                                echo "<p>";
                                if(is_array($v))
                                {
                                    echo "<span style=\"\">".$v['title'].":&nbsp;</span>";
                                    echo "<span style=\"\">" . $v['value'] . "</span>";
                                }else{
                                    echo "<span style=\"\">".$v."</span>";
                                }
                                echo "</p>";
                            }
                            ?>
                    </div>
                </div>
                <div id="two">
                    <h3 style="color:#e92b83;font-size: 20px;">RECIPIENT</h3>
                    <div style="padding: 0;margin: 0;font-size: 14px;font-weight: normal;">
                        <?php
                        foreach ($recipientDescription as $k => $v)
                        {
                            echo "<p>";
                            if(is_array($v))
                            {
                                echo "<span style=\"\">".$v['title'].":&nbsp;</span>";
                                echo "<span style=\"\">" . $v['value'] . "</span>";
                            }else{
                                echo "<span style=\"\">".$v."</span>";
                            }
                            echo "</p>";
                        }
                        ?>
                    </div>
                </div>
            </div>
            <div style="clear:both;"></div>
            <div style="padding: 0;margin:0 auto;margin-top: 24px;margin-bottom: 24px; width: 100%; height: 1px ;background:#8f8f8f;;"></div>

            <div style="padding: 0;margin: 0;font-size: 14px;font-weight: normal;">
                <?php foreach ($itemDescription AS $k => $v):?>
                    <p style="font-size: 14px"><span style="float: right;color:#000;"><?php echo is_numeric($v->value) ? number_format(floatval($v->value),2) : $v->value ?></span><span style="color:#000"><?php echo $v->title ?></span></p>
                    <div style="clear:both;"></div>
                <?php endforeach;?>
            </div>

            <p style="font-size: 14px;"><span style="float: right;color: #000;"><b> <?php echo $currencyFormatter::format(abs($paymentModeSubTotal), $countryCurrencyCode); ?>  </b></span> <span style="color:#4A4A4A;"><b>Sub Total</b></span></p>


            <div style="float:left; width: 100%;" >
                <u style="color:#666;">Charges</u>
                <div style="padding: 0;margin: 0;font-size: 14px;font-weight: normal;">
                    <p><span style="float: right;color: #000;font-size: 14px;"> <?php echo $paymentModeName ?>  </span> <span style="color:#4A4A4A"> Payment Mode </span></p>

                    <p><span style="float: right;color: #000;font-size: 14px;"> <?php echo $currencyFormatter::format($paymentModeFee, $countryCurrencyCode); ?>  </span> <span style="color:#4A4A4A"> Payment Mode Fee </span></p>
                    <p><span style="float: right;color: #000;font-size: 14px;"> <?php echo $currencyFormatter::format($transactionFee, $countryCurrencyCode); ?>  </span> <span style="color:#4A4A4A"> Service Fee </span></p>

                    <p><span style="float: right"> <b><?php echo $currencyFormatter::format($totalCharges, $countryCurrencyCode); ?></b>  </span> <span style="color:#4A4A4A"><b>Total Charges</b> </span></p>

                </div>
            </div>

            <?php if($totalDiscount){?>
                <div style="float:left; width: 100%;margin-top: 24px;" >
                    <u style="color:#4A4A4A;">Discount</u>
                    <div style="padding: 0;margin: 0;font-size: 14px;font-weight: normal;">
                        <p><span style="float: right;color:#000""><b><?php echo $currencyFormatter::format($totalDiscount, $countryCurrencyCode); ?> </b> </span><span style="color:#4A4A4A"><b>Total Discount</b></span></p>
                    </div>

                </div>

            <?php }?>

            <?php
            if(isset($transactionDetail->remittance['is_home_collection'])) {
                if($transactionDetail->remittance['is_home_collection'] == true) {
            ?>
            <div style="float:left; width: 100%;">
                <u style="color:#666;">Delivery</u>
                <div style="padding: 0;margin: 0;font-size: 14px;font-weight: normal;">
                    <p><span
                            style="float: right;color: #000;font-size: 14px;"> <?php echo $delivery_info['delivery_mode_name']; ?>  </span>
                        <span style="color:#4A4A4A"> Delivery Mode </span></p>
                    <p><span
                            style="float: right;color: #000;font-size: 14px;"> <?php echo $delivery_info['deliveryID']; ?>  </span>
                        <span style="color:#4A4A4A"> Delivery ID </span></p>
                    <p><span
                            style="float: right;color: #000;font-size: 14px;"> <?php echo $delivery_info['delivery_time']; ?>  </span>
                        <span style="color:#4A4A4A"> Time </span></p>

                </div>
            </div>
            <?php
                }
            }
            ?>


            <div style="clear:both;"></div>
            <div style="padding: 0;margin:0 auto;margin-top: 24px;margin-bottom: 24px; width: 100%; height: 1px ;background:#8f8f8f;;"></div>

            <p style="text-align: right;color:#4A4A4A;"><b>Total Payable : </b><span style="color: #e92b83"><b><?php echo $currencyFormatter::format($totalPayable , $countryCurrencyCode); ?> </span></b></p>

            <div style="clear:both;"></div>
            <div style="padding: 0;margin:0 auto;margin-top: 24px;margin-bottom: 24px; width: 100%; height: 1px ;background:#8f8f8f;;"></div>


            <?php
            if(isset($transactionDetail->remittance['remittance_company']) AND
                $transactionDetail->remittance['remittance_company'] instanceof RemittanceCompany)
            {
                echo $transactionDetail->remittance['remittance_company']->getReceiptFooter();
            }
            ?>


            <?php if(isset($agentId) AND strlen($combinedPaymentDescription)>0 ){?>
                <div style="clear:both;"></div>
                <div style="padding: 0;margin:0 auto;margin-top: 24px;margin-bottom: 24px; width: 100%; height: 1px ;background:#8f8f8f;;"></div>

                <?php if ($agentProfileImageUrl){?>
                    <div style="float: left;">
                        <img src="<?php echo $agentProfileImageUrl; ?>" alt="" style="width:100px; height:100px; border-radius:50%; overflow:hidden;">
                    </div>
                <?php } ?>
                <div style="text-align: center;padding-top: 5px;padding-bottom: 5px; padding-left: 30px;color:#4A4A4A">
                    <?php echo $combinedPaymentDescription;?>
                </div>
            <?php }?>

            <div style="clear:both;"></div>
            <div style="padding: 0;margin:0 auto;margin-top: 24px;margin-bottom: 24px; width: 100%; height: 1px ;background:#8f8f8f;;"></div>

            <p style="text-align: center;">
                <span style="color: #e92b83"><b>NEED HELP?</b></span> Contact us at helpme.id@slide.club
            </p>

            <div  style="width: 100%;border:0px;background: #e6086f;height:96px;display: block;overflow: hidden; color: #FFF;text-align: center;">
                <p>Thank you for using SLIDE and have a nice day.</p>
                <p>Terms and conditions apply.</p>
            </div>
        </div>

    </div>

</body>
</html>
