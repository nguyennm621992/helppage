<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if ( ! function_exists('parseIniFile'))
{
    function parseIniFile($iIniFile) {
        $a  = array();
        $aMatches = array();

        $s = '\s*([[:alnum:]_\- \*]+?)\s*';

        preg_match_all('#^\s*((\['.$s.'\])|(("?)'.$s.'\\5\s*=\s*("?)(.*?)\\7))\s*(;[^\n]*?)?$#ms', file_get_contents($iIniFile), $aMatches, PREG_SET_ORDER);

        foreach ($aMatches as $aMatch) {
            if (empty($aMatch[2]))
                $a[$aMatch[6]] = $aMatch[8];
        }
        return $a;
    }
}

$lang =  parseIniFile(sprintf("%s/helppage.ini", dirname(__FILE__)));

//$lang = parse_ini_file('groupware.ini');


// --------------------------------------------------------------------
/* End of file groupware_lang.php */
/* Location: ./application/language/vi/groupware_lang.php */