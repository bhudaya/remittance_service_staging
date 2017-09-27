<?php

namespace Iapps\RemittanceService\Attribute;

use Iapps\Common\Core\IappsBaseDataMapper;

interface IAttributeDataMapper extends IappsBaseDataMapper{
    public function findAll();
    public function findByCode($code);
    public function insert(Attribute $attribute);
    public function update(Attribute $attribute);
}