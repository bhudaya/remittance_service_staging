<?php

namespace Iapps\RemittanceService\RemittanceRecord;

interface RemittancePaymentModeOptionInterface {
    function getOption(array $paymentInfo);
    function saveResponse();
    function getFormattedResponseMessage($response);
}