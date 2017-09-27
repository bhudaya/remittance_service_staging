<?php

namespace Iapps\RemittanceService\ValueObject;

use Iapps\Common\Core\EncryptedField;
use Iapps\RemittanceService\Common\Rijndael256EncryptorFactory;

class EncryptedFieldFactory {

    public static function build()
    {
        $encryptor = Rijndael256EncryptorFactory::build();
        return new EncryptedField($encryptor);
    }
}