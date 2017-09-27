<?php

namespace Iapps\RemittanceService\Recipient;

use Iapps\Common\Core\IappsBaseDataMapper;

interface IRecipientDataMapper extends IappsBaseDataMapper{

    public function findByUserProfileId($user_profile_id);
    public function findByMobileNumber($user_profile_id, $dialing_code, $mobile_number);
    public function insert(Recipient $recipient);
    public function update(Recipient $recipient);
    public function findByParam(Recipient $recipient, array $recipient_id_arr = NULL, $limit, $page);
    public function findByHashedMobileNumber($hashed_dialing_code, $hashed_mobile_number);
}