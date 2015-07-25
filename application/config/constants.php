<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| File and Directory Modes
|--------------------------------------------------------------------------
|
| These prefs are used when checking and setting modes when working
| with the file system.  The defaults are fine on servers with proper
| security, but you may wish (or even need) to change the values in
| certain environments (Apache running a separate process for each
| user, PHP under CGI with Apache suEXEC, etc.).  Octal values should
| always be used to set the mode correctly.
|
*/
define('FILE_READ_MODE', 0644);
define('FILE_WRITE_MODE', 0666);
define('DIR_READ_MODE', 0755);
define('DIR_WRITE_MODE', 0777);

/*
|--------------------------------------------------------------------------
| File Stream Modes
|--------------------------------------------------------------------------
|
| These modes are used when working with fopen()/popen()
|
*/

define('FOPEN_READ',							'rb');
define('FOPEN_READ_WRITE',						'r+b');
define('FOPEN_WRITE_CREATE_DESTRUCTIVE',		'wb'); // truncates existing file data, use with care
define('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE',	'w+b'); // truncates existing file data, use with care
define('FOPEN_WRITE_CREATE',					'ab');
define('FOPEN_READ_WRITE_CREATE',				'a+b');
define('FOPEN_WRITE_CREATE_STRICT',				'xb');
define('FOPEN_READ_WRITE_CREATE_STRICT',		'x+b');

/*
|--------------------------------------------------------------------------
| Directory Path Infomation
|--------------------------------------------------------------------------
*/
define('GW_VERSION',       '5.0.1');
define('HTTP_HOST',        $_SERVER['HTTP_HOST']);
define('HMAIL_HOST',       $_SERVER['HMAIL_HOST']);
define('SERVER_ADDR',      $_SERVER["SERVER_ADDR"]);
define('HTTP_PROTOCOL',    (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? "https" : "http");
define('CORE_PATH',        "/home/HanbiroMailcore/");
define('HOME_PATH',        CORE_PATH . 'docs/');
define('SYSTEM_PATH',      CORE_PATH . 'help');
define('LOG_PATH',         CORE_PATH . 'log/');
define('GWDATA_PATH',      CORE_PATH . 'GWDATA/');
define('DATA_PATH',        GWDATA_PATH . HTTP_HOST . '/');
define('CONF_PATH',        DATA_PATH . 'config/');
define('QUEUE_PATH',       DATA_PATH . 'queue/');
define('CONFIG_PATH',      dirname(__FILE__));
define('ROOT_PATH', 	   strtr(preg_replace("/(.*)[\/]application\/config/i", "\\1", CONFIG_PATH), "\\", "/"));
define('UI_PATH', 	       ROOT_PATH . '/app/');
define('TEMP_PATH', 	   ROOT_PATH . '/temp');
define('MAIN_DIR',		   str_replace(basename($_SERVER['SCRIPT_NAME']), "", $_SERVER['SCRIPT_NAME']));
define('MAIN_INDEX',	   $_SERVER['SCRIPT_NAME'].'/');
define('BIN_DIR',          CORE_PATH .'bin/');
define('VPOPMAIL_BIN',     "/home/vpopmail/bin/");
define('HMAILADMIN',       BIN_DIR . 'hmailadmin');
define('APPROVAL_BIN_DIR', CORE_PATH . 'approval/bin/');
define('WEBDISK_DATA_PATH', "/home/HanbiroMailcore/cloud/data/link/".HTTP_HOST.'/');
define('API_MODE', TRUE);

/* End of file constants.php */
/* Location: ./application/config/constants.php */
