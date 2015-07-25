<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');
/**
 * Starter_Controller Class
 *
 * 기본 베이스 컨트롤러
 * 1. 설정 파일 로드
 * 2. SSO 인증 처리
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Libraries
 * @author		Software Dev. Team
 * @copyright	Copyright (c) 2013, Hanbiro, Inc.
 * @license		http://hanbiro.com
 * @link		http://hanbiro.com
 * @since		Version 1.0
 */
require APPPATH . '/libraries/REST_Controller.php';

set_time_limit(0);
ignore_user_abort(true);

class Starter_Controller extends REST_Controller {

    public $language = 'en';
    protected $_user_config = array();

    public function __construct() {
        parent::__construct();

        $this->config->load('helppage');
        $this->_hp_config = $this->config->item('hp_config');
        $this->_auth_check();
    }

    private function _auth_check() {
        $auth_check = TRUE;
        $non_check_uri = array('help/help_page/help_lang/helppage', 'help/help_page/config', 'help/help_page/menu_tree', 
            'help/help_page/menu_title', 'help/help_page/list_contents', 'help/help_page/search_data', 'help/help_page/export_pdf', 
            'help/help_page/send_msg', 'help/help_page/register_user', 'help/help_page/login_user');

        $path_info = $this->input->server('PATH_INFO');
        if (isset($path_info)) {
            foreach ($non_check_uri as $uri) {
                if (strpos($path_info, $uri) === 1) {
                    $auth_check = FALSE;
                    break;
                }
            }
        }

        if ($auth_check) {
            $_CRYPT_USER_ = $this->check_cookie();
            if (empty_check($_CRYPT_USER_)) {
                $this->_access_deny(lang("alert_connect_time_expired"), 200);
            }

            $this->_check_user_config($_CRYPT_USER_);
        }
    }

    protected function check_cookie() {
        $_cookie_hp = $this->input->cookie('HANBIRO_HP');
        if (empty_check($_cookie_hp))
            return FALSE;
        else
            return $_cookie_hp;
    }

    protected function _delete_cookie() {
        if (!empty($_COOKIE)) {
            foreach ($_COOKIE as $skey => $sval) {
                setcookie($skey, "", time() - 3600);
            }
        }
    }

    public function _access_deny($msg = '', $code = 100, $status = 401) {
        $this->_delete_cookie();
        $this->response(array("success" => FALSE, "code" => $code, "msg" => $msg), $status);
    }
    
    private function _check_user_config($_CRYPT_USER_) {
        $this->_user_config = unserialize($_CRYPT_USER_);
    }
}

// --------------------------------------------------------------------
/* End of file Starter_Controller.php */
/* Location: ./application/libraries/Starter_Controller.php */
