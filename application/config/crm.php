<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$config['crm_config']['permission_field']     = array('1'=>'manager', '2'=>'customer', '3'=>'product', '4'=>'sales', '0'=>'none');
$config['crm_config']['permission_level']     = array('1'=>'public', '2'=>'staff', '3'=>'admin', '4'=>'manager', '0'=>'closed');

/*
 |--------------------------------------------------------------------------
| Template_ Directory
|--------------------------------------------------------------------------
|
| Template Directory & Compile Directory set
|
*/
$config['_template_dir'] = APPPATH.'views/_template';
$config['_compile_dir'] = APPPATH.'views/_compile';
$config['template_dir'] = UI_PATH.'template/';


// ------------------------------------------------------------------------
/* End of file crm.php */
/* Location: ./application/config/crm.php */