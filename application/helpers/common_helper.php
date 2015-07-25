<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');
if (!function_exists('empty_check')) {

    function empty_check($string) {
        if (empty($string) && $string !== '0') {
            return TRUE;
        }
        if (!is_string($string)) {
            return FALSE;
        } else if (is_array($string) && sizeof($string) == 0) {
            return FALSE;
        }

        $string = trim($string);

        for ($i = 0; $i < strlen($string); $i++) {
            $c = substr($string, $i, 1);
            if (($c != "\r" ) && ($c != " ") && ($c != "\n") && ($c != "\t")) {
                return FALSE;
            }
        }
        return TRUE;
    }

}
