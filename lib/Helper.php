<?php

namespace WHMCS\Modules\Registrar\Metaname;

class Helper {
    public static function str_rpartition($string, $delimiter)
    {
        $i = strrpos($string, $delimiter);
        if (false === $i) {
            return array($string, NULL);
        } else {
            return array(substr($string, 0, $i), substr($string, ($i+1)));
        }
    }


    function str_endswith($string, $suffix)
    {
        $i = strrpos($string, $suffix);
        if (false === $i) {
            return false;
        } else {
            return ($i + strlen($suffix)) == strlen($string);
        }
    }
}