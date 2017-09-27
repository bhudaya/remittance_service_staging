<?php

namespace Iapps\RemittanceService\Common;

use Iapps\Common\Microservice\AccountService\User;
use Iapps\Common\Microservice\CountryService\CountryServiceFactory;

class AddressNameExtractor {

    public static function extract(User $user)
    {
        $address = $user->getHostAddress();
        $address->province_name = null;
        $address->city_name = null;

        $countryService = CountryServiceFactory::build();
        if( isset($address->province) )
        {
            $code = $address->province;

            if($province = $countryService->getProvinceInfo($code) )
                $address->province_name = $province->getName();
        }

        if( isset($address->city ) )
        {
            $code = $address->city;

            if( $city = $countryService->getCityInfo($code) )
                $address->city_name = $city->getName();
        }

        if( isset($address->country ) )
        {
            $code = $address->country;
            if( $country = $countryService->getCountryInfo($code) )
                $address->country_name = $country->getName();
        }

        $user->setHostAddress($address);
        return $user;
    }
}