<?php

namespace Iapps\RemittanceService\Common;

use Iapps\Common\Microservice\AccountService\PartnerAccountServiceFactory;
use Iapps\Common\Validator\IappsValidator;

/*
 * To validate is the user/admin is employee of the corporate
 */
class EmployerValidator extends IappsValidator{

    protected $user_id;
    protected $corporate_id;

    public static function make($user_id, $corporate_id)
    {
        $v = new EmployerValidator();
        $v->user_id = $user_id;
        $v->corporate_id = $corporate_id;

        $v->validate();

        return $v;
    }

    public function validate()
    {
        $this->isFailed = true;

        $accServ = PartnerAccountServiceFactory::build();
        if( $upline = $accServ->getAgentUplineStructure($this->user_id) )
        {
            if( $upline->first_upline->getUser()->getId() == $this->corporate_id )
            {
                $this->isFailed = false;
                return true;
            }
        }

        return false;
    }
}