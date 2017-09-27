<?php

namespace Iapps\RemittanceService\Common;

class NameMatcher{

    public static function execute($name1, $name2)
    {
        if( strlen($name1) <= 0 or strlen($name2) <= 0)
            return false;

        //split name into words by spaces
        $a = explode(" ", $name1);
        $b = explode(" ", $name2);

        foreach($a AS $key => $value)
            $a[$key] = strtolower($value);

        foreach($b AS $key => $value)
            $b[$key] = strtolower($value);

        if (count(array_diff(array_merge($a, $b), array_intersect($a, $b))) === 0) {
            return true;
        }

        //another try
        $a = strtolower(str_replace(" ","",$name1));
        $b = strtolower(str_replace(" ","",$name2));

        if( $a === $b)
            return true;

        return false;
    }
}