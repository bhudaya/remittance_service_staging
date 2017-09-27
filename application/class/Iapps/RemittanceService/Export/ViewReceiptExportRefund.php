<?php

use Iapps\Common\Helper\StringMasker;

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

    $paymentMode = ($transactionDetail->payment)? $transactionDetail->payment[0] : array();;
    $transactionType = $transactionDetail->transaction->getTransactionType()->getDisplayName();
    $countryCurrencyCode = $transactionDetail->transaction->getCountryCurrencyCode();

    if( isset($paymentMode->description1) )
        $paymentModeDescription1 = json_decode($paymentMode->description1);

    if( isset($paymentMode->description2) )
        $paymentModeDescription2 = json_decode($paymentMode->description2);

    foreach ($transactionDetail->transaction_items AS $k =>  $v){


        switch ($v->getItemType()->getCode()) {

            case 'corporate_service':
                $itemDescription = json_decode($v->getDescription());
                $paymentModeSubTotal = -1 * $v->getNetAmount();
                break;
            case 'corporate_service_fee':

                $transactionFee = -1 * $v->getNetAmount();
                break;
            case 'payment_fee':
                $paymentModeFee = -1 * $v->getNetAmount();
                break;
            case 'discount':
                $discountDescription =  json_decode($v->getDescription());
                $totalDiscount = -1 * $v->getNetAmount();
                break;
        }
    }


    $totalCharges = $paymentModeFee + $transactionFee;
    $totalPayable = abs($transactionDetail->transaction_items->getTotalAmount());
    if(isset($paymentMode->payment_mode_name))
        $paymentModeName = $paymentMode->payment_mode_name;

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
                    <span style="color:#8f8f8f; font-size: 14px">Transaction ID <?php echo $transactionID?></span>
                </p>
                <p style="margin: 0;padding: 0;margin-top: 2px;">
                    <span style="color:#8f8f8f; font-size: 14px"><?php echo $transactionDateStr?></span>
                </p>
            </div>
        </div>
    </div>
    <div style="clear:both;"></div>
    <div style="width: 94%;overflow: hidden;margin: auto;" >
        <div style="clear:both;"></div>
        <div style="width: 94%;overflow: hidden;margin: auto;" >
            <div class="wrapper" >
            </div>
            <div style="clear:both;"></div>
            <div style="padding: 0;margin:0 auto;margin-top: 24px;margin-bottom: 24px; width: 100%; height: 1px ;background:#8f8f8f;;"></div>

            <h3 style="color:#e92b83;font-size: 20px;">REFUND DESCRIPTION</h3>

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

                    <?php if($paymentModeFee != NULL) { ?>
                        <p><span style="float: right;color: #000;font-size: 14px;"> <?php echo $currencyFormatter::format($paymentModeFee, $countryCurrencyCode); ?>  </span> <span style="color:#4A4A4A"> Payment Mode Fee </span></p>
                    <?php } ?>
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

            <div style="float:left; width: 100%;" >
                <p>&nbsp;</p>
                <div style="padding: 0;margin: 0;font-size: 14px;font-weight: normal;">
                    <p><span style="float: right;color: #000;font-size: 14px;"> <?php echo $paymentModeName ?>  </span> <span style="color:#4A4A4A"> Mode of Refund </span></p>

                </div>
            </div>

            <div style="clear:both;"></div>
            <div style="padding: 0;margin:0 auto;margin-top: 24px;margin-bottom: 24px; width: 100%; height: 1px ;background:#8f8f8f;;"></div>

            <p style="text-align: right;color:#4A4A4A;"><b>Total Refund : </b><span style="color: #e92b83"><b><?php echo $currencyFormatter::format($totalPayable , $countryCurrencyCode); ?> </span></b></p>

            <div style="clear:both;"></div>
            <div style="padding: 0;margin:0 auto;margin-top: 24px;margin-bottom: 24px; width: 100%; height: 1px ;background:#8f8f8f;;"></div>

            <p style="text-align: center;color:#4A4A4A; font-size:14px">Note: Refund does not include payment mode fee. For more information, please contact our helpdesk.</p>


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
