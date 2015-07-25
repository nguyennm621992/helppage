<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

require APPPATH . '/libraries/Starter_Controller.php';
require_once(APPPATH . '/third_party/dompdf/dompdf_config.inc.php');

class Help_Page extends Starter_Controller {

    public function __construct() {
        parent::__construct();
        
        $this->db->sync_log = false;
        $this->load->model(array('help_page/help_model'));
    }

    public function menu_tree_post() {
        $params = $this->get_param();
        //get list
        $result = $this->help_model->get_menu_tree($params);
        //response client
        $this->response($result);
    }

    public function edit_content_post() {
        $params = $this->get_param();
        //get list
        $result = $this->help_model->edit_menu_content($params);
        //response client
        $this->response($result);
    }

    public function list_contents_post() {
        $params = $this->get_param();
        //get list
        $result = $this->help_model->list_contents($params);
        //response client
        $this->response($result);
    }

    public function send_msg_post() {
        $params = $this->get_param();
        //get list
        $result = $this->help_model->send_msg($params);
        //response client
        $this->response($result);
    }

    public function add_menu_post() {
        $params = $this->get_param();
        //get list
        $result = $this->help_model->add_menu($params);
        //response client
        $this->response($result);
    }

    public function remove_menu_post() {
        $params = $this->get_param();
        //get list
        $result = $this->help_model->remove_menu($params);
        //response client
        $this->response($result);
    }

    public function add_article_post() {
        $params = $this->get_param();
        //get list
        $result = $this->help_model->add_article($params);
        //response client
        $this->response($result);
    }

    public function remove_article_post() {
        $params = $this->get_param();
        //get list
        $result = $this->help_model->remove_article($params);
        //response client
        $this->response($result);
    }

    public function change_name_post() {
        $params = $this->get_param();
        //get list
        $result = $this->help_model->change_name($params);
        //response client
        $this->response($result);
    }

    public function menu_title_post() {
        $params = $this->get_param();
        //get list
        $result = $this->help_model->menu_title($params);
        //response client
        $this->response($result);
    }

    public function change_title_name_post() {
        $params = $this->get_param();
        //get list
        $result = $this->help_model->change_title_name($params);
        //response client
        $this->response($result);
    }

    public function get_opinion_post() {
        $params = $this->get_param();
        //get list
        $result = $this->help_model->get_opinion($params);
        //response client
        $this->response($result);
    }

    public function search_data_post() {
        $params = $this->get_param();
        //get list
        $result = $this->help_model->search_data($params);
        //response client
        $this->response($result);
    }

    public function order_menu_post() {
        $params = $this->get_param();
        //get list
        $result = $this->help_model->order_menu($params);
        //response client
        $this->response($result);
    }

    public function export_pdf_post() {
        $params = $this->get_param();
        if (isset($params['content'])) {
            // page info here, db calls, etc.
            $params['content'] = str_replace('&lt;', '<', $params['content']);
            $params['content'] = str_replace('&gt;', '>', $params['content']);
            $temp_file = tempnam(sys_get_temp_dir(), '');
            file_put_contents($temp_file, $params['content']);
            //test read file
            /* $handle = fopen($temp_file, "r");
              $contents = fread($handle, filesize($temp_file));
              fclose($handle); */
        }
        $this->response(array(
            'file_name' => $temp_file,
            'test' => ROOT_PATH
        ));
    }

    public function export_all_pdf_post() {
        $params = $this->get_param();
        if (isset($params['isAll']) && isset($params['lang']) && isset($params['url'])) {
            $query = "SELECT title, content FROM help_content WHERE language='{$params['lang']}' LIMIT 0, 10";
            $result = $this->db->query($query);
            if ($result && $result->num_rows() > 0) {
                $rows = $result->result_array();
                $exportPdf = "";
                foreach ($rows as $k => $val) {
                    $exportPdf .= '<div class="hpanel hgreen">
                                        <div class="panel-heading hbuilt">
                                            <a draggable="false" class="article-title">
                                                <span>' . $val['title'] . '&nbsp;</span>
                                            </a>
                                        </div>
                                        <div class="panel-body">
                                            <div>' . $val['content'] . '</div>
                                        </div>
                                    </div>';
                }
                $result->free_result();
            }
            $content = preg_replace('/(".\/images)|("images)/i', '"' . $params['url'], $exportPdf);
            // page info here, db calls, etc.
            $content = str_replace('&lt;', '<', $content);
            $content = str_replace('&gt;', '>', $content);
            /*$temp_file = tempnam(sys_get_temp_dir(), '');
            file_put_contents($temp_file, $content);*/
            $this->response(array(
                'file_name' => 'dompdf.pdf',
                'content' => $content
            ));
            $dompdf = new DOMPDF();
            $dompdf->load_html($content);
            $dompdf->render();

            $output = $dompdf->output();
            file_put_contents(APPPATH . '/third_party/dompdf/dompdf.pdf', $output);
            $temp_file = 'dompdf.pdf'; 
        }
    }

    public function help_lang_post() {
        $params = $this->get_param();
        //choose language for params requested
        if (isset($params['lang'])) {
            $language = $params['lang'];
        } else {
            $language = $this->language;
        }
        $segment = array_slice($this->uri->rsegment_array(), 2);
        if (empty($segment)) {
            return;
        }

        $this->lang->is_loaded = array();
        $result = array();

        foreach ($segment as $item) {
            if ($lang_data = $this->lang->load($item, $language, TRUE)) {
                foreach ($lang_data as $key => $data) {
                    $result[$key] = is_string($data) ? nl2br($data) : $data;
                }
            }
        }

        $output['success'] = !empty($result);
        $output['rows'] = $result;
        $this->response($output, 200);
    }

    public function config_get() {
        // language list
        $lang = array();
        if ($this->_hp_config['languages']) {
            foreach ($this->_hp_config['languages'] as $key => $value) {
                if ($key == 'ru')
                    continue;
                $lang[] = array("name" => $key, "title" => $value);
            }
        }

		/*
        // get logo
        if (HMAIL_HOST == 'sc-eng.com') {
            $logo_path = "/groupware/_template/_login_/sc-eng.com/sceng_mainimage.gif";
        } else if (HMAIL_HOST == 'swcell.com') {
            $logo_path = "/groupware/_template/_login_/swcell.com/sewon_mainimage.gif";
        } else if (HMAIL_HOST == 'bmwdongsung.co.kr') {
            $logo_path = "/groupware/_template/_login_/bmwdongsung.co.kr/main_banner.jpg";
        } else {
            $logo_path = sprintf("/winapp/%s/images/mainlogo.gif", HTTP_HOST);
            if (!is_file(HOME_PATH . $logo_path)) {
                $logo_path = "";
            }
        }
       */ 
        //check cookie
        $is_master = FALSE;
        $_CRYPT_USER = unserialize($this->check_cookie());
        if ($_CRYPT_USER) {
            $is_viewer = FALSE;
            if ($_CRYPT_USER['username'] == 'postmaster') {
                $is_master = TRUE;
            }
        } else {
            $is_viewer = TRUE;
            
        }

        $result = array(
            'lang' => $lang,
        //    'logo' => $logo_path,
            'is_viewer' => $is_viewer,
            'is_master' => $is_master
        );
        $this->response(array('success' => TRUE, 'rows' => $result), 200);
    }

    public function register_user_post() {
        $params = $this->get_param();
        //get list
        $result = $this->help_model->register($params);
        //response client
        $this->response($result);
    }

    public function login_user_post() {
        $params = $this->get_param();
        //get list
        $result = $this->help_model->login($params);
        //response client
        $this->response($result);
    }
    
    public function logout_user_get() {
        $this->_delete_cookie();
        $this->response(array("success" => TRUE, "msg" => lang('alert_logout')), 200);
    }
    
    public function user_list_post() {
        $params = $this->get_param();
        //get list
        $result = $this->help_model->user_list($params);
        //response client
        $this->response($result);
    }
    
    public function active_user_post() {
        $params = $this->get_param();
        //get list
        $result = $this->help_model->active_user($params);
        //response client
        $this->response($result);
    }
    
    public function disactive_user_post() {
        $params = $this->get_param();
        //get list
        $result = $this->help_model->disactive_user($params);
        //response client
        $this->response($result);
    }
    
    public function delete_user_post() {
        $params = $this->get_param();
        //get list
        $result = $this->help_model->delete_user($params);
        //response client
        $this->response($result);
    }

}
