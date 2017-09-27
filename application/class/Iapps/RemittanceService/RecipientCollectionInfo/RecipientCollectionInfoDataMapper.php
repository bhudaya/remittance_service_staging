<?php

namespace Iapps\RemittanceService\RecipientCollectionInfo;

use Iapps\Common\Core\IappsBaseDataMapper;

interface RecipientCollectionInfoDataMapper extends IappsBaseDataMapper{

    public function findByRecipientId($recipient_id);
    public function findByRecipientIds(array $recipient_ids);
    public function insert(RecipientCollectionInfo $info);
    public function update(RecipientCollectionInfo $info);
    public function findbyParm(RecipientCollectionInfo $info);
}