<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Help_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->db->sync_log = false;
    }

    function get_menu_tree($params) {
        if (isset($params['lang'])) {
            $crm = isset($params['crm']) ? $params['crm'] : '0';
            $query = "
                SELECT 
                    id, name, title, help_menu.order, parentid, language, help_menu.type
                FROM help_menu 
                WHERE 
                    language='{$params['lang']}' 
                    AND help_menu.type='tree' 
                    AND crm='$crm'
                ORDER BY id, help_menu.order
                ";
            $result = $this->db->query($query);
            if ($result && $result->num_rows() > 0) {
                $rows = $result->result_array();
                $result->free_result();
                $nodes = array();
                $delimiter = '||||';

                //add subname and show pro to all menus
                foreach ($rows as $key => $value) {
                    $rows[$key]['subname'] = preg_replace('/([ -.&_])/i', '', strtolower($value['name']));
                    $rows[$key]['editting'] = false;
                }
                //wraping
                foreach ($rows as $key => $value) {
                    //set link to node in tree and except fail case
                    $nodeLink = '';
                    $this->getLink($rows, $value, $nodeLink, $value['nestParent'], $delimiter);
                    if ($nodeLink !== '') {
                        $value['direct'] = preg_replace('/\|\|\|\|children\|\|\|\|/i', ' > ', $nodeLink);
                        $rows[$key]['direct'] = $value['direct'];
                        //change type of order property
                        $value['order'] = (int) $value['order'];
                        //$value['children'] = array();
                        $nodes[$nodeLink] = $value;
                    }
                }
                //explode to tree
                $tree = $this->explodeTree($nodes, $delimiter);
                //apply order to tree
                $this->orderTree($tree);

                $res['rows'] = $rows;
                $res['tree'] = $tree;
                return $res;
            }
        }
        $res['rows'] = $res['tree'] = array();
        return $res;
    }

    //get link of object in tree
    function getLink($array, $object, &$link, &$nestParent, $delimiter = '_') {
        if (!is_array($nestParent)) {
            $nestParent = array();
        }
        if ($object['parentid'] !== NULL) {
            if ($object['parentid'] === '0') {
                if ($link === '') {
                    $link = $object['name'];
                }
            } else {
                $parent = NULL;
                foreach ($array as $k => $val) {
                    if ($val['id'] === $object['parentid']) {
                        $parent = $val;
                        break;
                    }
                }
                if ($parent !== NULL) {
                    $nestParent[] = $parent['id'];
                    if ($link === '') {
                        $link = $parent['name'] . $delimiter . 'children' . $delimiter . $object['name'];
                    } else {
                        $link = $parent['name'] . $delimiter . 'children' . $delimiter . $link;
                    }
                    $this->getLink($array, $parent, $link, $nestParent, $delimiter);
                } else {
                    $link = '';
                }
            }
        }
    }

    function explodeTree($array, $delimiter = '_', $baseval = false) {
        if (!is_array($array))
            return false;
        $splitRE = '/' . preg_quote($delimiter, '/') . '/';
        $returnArr = array();
        foreach ($array as $key => $val) {
            // Get parent parts and the current leaf
            $parts = preg_split($splitRE, $key, -1, PREG_SPLIT_NO_EMPTY);
            $leafPart = array_pop($parts);

            // Build parent structure
            // Might be slow for really deep and large structures
            $parentArr = &$returnArr;
            foreach ($parts as $part) {
                if (!isset($parentArr[$part])) {
                    $parentArr[$part] = array();
                } elseif (!is_array($parentArr[$part])) {
                    if ($baseval) {
                        $parentArr[$part] = array('__base_val' => $parentArr[$part]);
                    } else {
                        $parentArr[$part] = array();
                    }
                }
                $parentArr = &$parentArr[$part];
            }

            // Add the final part to the structure
            if (empty($parentArr[$leafPart])) {
                $parentArr[$leafPart] = $val;
            } elseif ($baseval && is_array($parentArr[$leafPart])) {
                $parentArr[$leafPart]['__base_val'] = $val;
            }
        }
        return $returnArr;
    }

    function orderTree(&$tree) {
        usort($tree, function ($a, $b) {
            return $a['order'] >= $b['order'];
        });
        foreach ($tree as $k => $val) {
            if (isset($val['children'])) {
                $this->orderTree($tree[$k]['children']);
            }
        }
    }

    function edit_menu_content($params) {
        if (isset($params['id']) && isset($params['content'])) {
            $this->db->where('id', $params['id']);
            $result = $this->db->get('help_content');
            //check menuid for update or insert?
            if ($result && $result->num_rows() > 0) {
                $rows = $result->result_array();
                $result->free_result();
                //convert images which src is base64 encoded string to image files
                $params['content'] = $this->convert_images_base64($params['content']);
                //update new content to menu
                $content = $rows[0];
                $content['content'] = $params['content'];
                $content['update'] = date('Y-m-d');
                $content_id = $content['id'];
                unset($content['id']);
                $this->db->where('id', $content_id);
                $this->db->update('help_content', $content);
            }
            return array('success' => TRUE);
        }
        return array('success' => FALSE);
    }

    function list_contents($params) {
        $res['success'] = FALSE;
        if (isset($params['menuid']) && isset($params['lang'])) {
            $this->db->where('menuid', $params['menuid']);
            $this->db->where('language', $params['lang']);
            $result = $this->db->get('help_content');
            if ($result && $result->num_rows() > 0) {
                $res['row'] = $result->result_array();
                foreach ($res['row'] as $key => $val) {
                    $res['row'][$key]['editting'] = false;
                }
                $result->free_result();
                $res['success'] = TRUE;
                return $res;
            }
            return $res;
        }
        return $res;
    }

    function send_msg($params) {
        if (isset($params['menuid']) && isset($params['helpful'])) {
            $data['menuid'] = $params['menuid'];
            $data['helpful'] = $params['helpful'];
            if (isset($params['accurate']))
                $data['accurate'] = ($params['accurate'] == 'true') ? 'Y' : 'N';
            if (isset($params['depth']))
                $data['depth'] = ($params['depth'] == 'true') ? 'Y' : 'N';
            if (isset($params['more_examples']))
                $data['more_examples'] = ($params['more_examples'] == 'true') ? 'Y' : 'N';
            $data['message'] = isset($params['message']) ? (string) $params['message'] : '';
            $data['crm'] = isset($params['crm']) ? $params['crm'] : '0';
            $data['regdate'] = date('Y-m-d H:i:s');

            $this->db->insert('help_opinion', $data);
            $res['success'] = TRUE;
        } else {
            $res['success'] = FALSE;
        }
        return $res;
    }

    function add_menu($params) {
        if (isset($params['name']) && isset($params['parentid']) && isset($params['lang'])) {
            $crm = isset($params['crm']) ? $params['crm'] : '0';
            $name = $params['name'];
            preg_replace('/([ ])/i', '', $name);
            if ($name != "") {
                //check: duplicate menu name in menu parent
                $tname = $this->trim_all(strtolower($params['name']), " ");
                $query = "
                    SELECT 
                        id 
                    FROM help_menu 
                    WHERE 
                        TRIM(LCASE(name))='$tname' 
                        AND parentid='{$params['parentid']}' 
                        AND language='{$params['lang']}' 
                        AND help_menu.type='tree'
                        AND crm='$crm'
                    LIMIT 1
                    ";
                $result = $this->db->query($query);
                if ($result && $result->num_rows() > 0) {
                    $res['success'] = FALSE;
                    $res['message'] = lang('archive_duplicate_data');
                } else {
                    //find orderid for new menu
                    $query = "
                        SELECT 
                            MAX(help_menu.order) as maxorder 
                        FROM help_menu 
                        WHERE 
                            parentid='{$params['parentid']}' 
                            AND language='{$params['lang']}' 
                            AND help_menu.type='tree'
                            AND crm='$crm'
                        ";
                    $result = $this->db->query($query);
                    if ($result && $result->num_rows() > 0) {
                        $order = $result->result_array();
                        $data['order'] = (int) $order[0]['maxorder'] + 1;
                        $data['name'] = $params['name'];
                        $data['parentid'] = $params['parentid'];
                        $data['regdate'] = date('Y-m-d H:i:s');
                        $data['language'] = $params['lang'];
                        $data['type'] = 'tree';
                        $data['crm'] = $crm;
                        $this->db->insert('help_menu', $data);
                        $data['id'] = $this->db->insert_id();
                        $data['editting'] = false;

                        $res['menuinfo'] = $data;
                        $res['success'] = TRUE;
                        $result->free_result();
                    } else {
                        $res['success'] = FALSE;
                        $res['message'] = lang('alert_update_query_error');
                    }
                }
            } else {
                $res['success'] = FALSE;
                $res['message'] = lang('hp_alert_menu_name');
            }
        } else {
            $res['success'] = FALSE;
            $res['message'] = lang('hp_sth_wrong');
        }
        return $res;
    }

    function remove_menu($params) {
        if (isset($params['menuid'])) {
            $menuIds = array();
            $menuIds[] = $params['menuid'];
            $this->select_children_menu($params['menuid'], $menuIds);
            $this->db->where_in('id', $menuIds);
            $this->db->delete('help_menu');
            $this->db->where_in('menuid', $menuIds);
            $this->db->delete('help_content');
            $this->db->where_in('menuid', $menuIds);
            $this->db->delete('help_opinion');
            $res['delete_id'] = $params['menuid'];
            $res['success'] = TRUE;
        } else {
            $res['success'] = FALSE;
        }
        return $res;
    }

    function select_children_menu($menuId, &$menuIds) {
        $this->db->select('id');
        $this->db->where('parentid', $menuId);
        $result = $this->db->get('help_menu');
        if ($result && $result->num_rows() > 0) {
            foreach ($result->result_array() as $key => $val) {
                $menuIds[] = $val['id'];
                $this->select_children_menu($val['id'], $menuIds);
            }
            $result->free_result();
        }
    }

    function trim_all($str, $what = NULL, $with = ' ') {
        if ($what === NULL) {
            //  Character      Decimal      Use
            //  "\0"            0           Null Character
            //  "\t"            9           Tab
            //  "\n"           10           New line
            //  "\x0B"         11           Vertical Tab
            //  "\r"           13           New Line in Mac
            //  " "            32           Space

            $what = "\\x00-\\x20";    //all white-spaces and control chars
        }
        return trim(preg_replace("/[" . $what . "]+/", $with, $str), $what);
    }

    function add_article($params) {
        if (isset($params['menuid']) && isset($params['title']) && isset($params['lang'])) {
            $data['menuid'] = $params['menuid'];
            $data['title'] = $params['title'];
            $data['regdate'] = date('Y-m-d H:i:s');
            $data['content'] = 'Article content';
            $data['language'] = $params['lang'];
            $data['crm'] = isset($params['crm']) ? $params['crm'] : '0';
            $this->db->insert('help_content', $data);
            $data['id'] = $this->db->insert_id();
            $data['editting'] = false;
            $res['data'] = $data;
            $res['success'] = TRUE;
        } else {
            $res['success'] = FALSE;
        }
        return $res;
    }

    function remove_article($params) {
        if (isset($params['id'])) {
            $this->db->where('id', $params['id']);
            $this->db->delete('help_content');
            $res['success'] = TRUE;
        } else {
            $res['success'] = FALSE;
        }
        return $res;
    }

    function change_name($params) {
        if (isset($params['id']) && isset($params['name'])) {
            $data['name'] = $params['name'];
            $this->db->where('id', $params['id']);
            $this->db->update('help_menu', $data);
            return array('success' => TRUE);
        }
        return array('success' => FALSE);
    }

    function menu_title($params) {
        if (isset($params['lang'])) {
            $crm = isset($params['crm']) ? $params['crm'] : '0';
            $this->db->where('language', $params['lang']);
            $this->db->where('type', 'title');
            $this->db->where('crm', $crm);
            $result = $this->db->get('help_menu');
            if ($result && $result->num_rows() > 0) {
                $row = $result->result_array();
                $res = $row[0];
                $res['subname'] = preg_replace('/([ -.&_])/i', '', strtolower($res['name']));
            } else {
                $res = array();
            }
        } else {
            $res = array();
        }
        return $res;
    }

    function change_title_name($params) {
        if (isset($params['id']) && isset($params['title'])) {
            $data['title'] = $params['title'];
            $this->db->where('id', $params['id']);
            $this->db->update('help_content', $data);
            return array('success' => TRUE);
        }
        return array('success' => FALSE);
    }

    function get_opinion($params) {
        if (isset($params['lang']) && isset($params['pagination'])) {
            $crm = isset($params['crm']) ? $params['crm'] : '0';
            $query = "
                SELECT ho.menuid
                FROM help_opinion as ho 
                LEFT JOIN help_menu as hm ON ho.menuid=hm.id 
                WHERE 
                    hm.language='{$params['lang']}'
                    AND ho.crm='$crm'
            ";
            $result = $this->db->query($query);
            if ($result && $result->num_rows() > 0) {
                $total = $result->num_rows();
                $limit = isset($params['pagination']['limit']) ? $params['pagination']['limit'] : '10';
                $curPage = isset($params['pagination']['curPage']) ? $params['pagination']['curPage'] : '1';
                $offset = ((int) $curPage - 1) * (int) $limit;
                $res['maxPage'] = ceil($total / (int) $limit);
                $order = isset($params['pagination']['order']) ? $params['pagination']['order'] : 'ho.regdate';
                $orderType = isset($params['pagination']['orderType']) ? $params['pagination']['orderType'] : 'DESC';

                $query = "
                        SELECT 
                            hm.name as menuname,
                            ho.menuid,
                            ho.helpful, 
                            ho.accurate, 
                            ho.depth, 
                            ho.more_examples, 
                            ho.message, 
                            hm.language, 
                            ho.regdate
                        FROM help_opinion as ho 
                        LEFT JOIN help_menu as hm ON ho.menuid=hm.id 
                        WHERE 
                            hm.language='{$params['lang']}'
                            AND ho.crm='$crm'
                        ORDER BY $order $orderType
                        LIMIT $offset, $limit
                    ";

                $result = $this->db->query($query);
                $rows = $result->result_array();
                $result->free_result();
                foreach ($rows as $key => $val) {
                    if ($val['helpful']) {
                        $rows[$key]['helpful'] = $val['helpful'] == 'Y' ? TRUE : FALSE;
                    }
                    if ($val['accurate']) {
                        $rows[$key]['accurate'] = $val['accurate'] == 'Y' ? TRUE : FALSE;
                    }
                    if ($val['depth']) {
                        $rows[$key]['depth'] = $val['depth'] == 'Y' ? TRUE : FALSE;
                    }
                    if ($val['more_examples']) {
                        $rows[$key]['more_examples'] = $val['more_examples'] == 'Y' ? TRUE : FALSE;
                    }
                }
                $res['rows'] = $rows;
                $res['success'] = TRUE;
            } else {
                $res['rows'] = array();
                $res['success'] = TRUE;
            }
        } else {
            $res['success'] = FALSE;
        }
        return $res;
    }

    function search_data($params) {
        if (isset($params['lang']) && isset($params['keyword'])) {
            $crm = isset($params['crm']) ? $params['crm'] : '0';
            $menuIds = array();
            //search in menu and head title
            $query = "
                SELECT hp.id
                FROM help_menu as hp
                WHERE 
                    language = '{$params['lang']}'
                    AND name LIKE '%{$params['keyword']}%'
                    AND ( 
                        SELECT COUNT(id)
                        FROM help_menu
                        WHERE 
                            language = '{$params['lang']}'
                            AND parentid = hp.id
                            AND hp.crm='$crm'
                        ) = '0'
                    AND hp.type='tree'
                    AND hp.crm='$crm'
                ";
            $result = $this->db->query($query);
            if ($result && $result->num_rows() > 0) {
                $rows = $result->result_array();
                $result->free_result();
                foreach ($rows as $key => $val) {
                    $menuIds[] = $val['id'];
                }
            }

            //search in article
            $query = "
                SELECT menuid 
                FROM help_content 
                WHERE 
                    language='{$params['lang']}' 
                    AND MATCH (title, content) AGAINST ('{$params['keyword']}')
                    AND ( 
                        SELECT COUNT(id)
                        FROM help_menu
                        WHERE 
                            language = '{$params['lang']}'
                            AND parentid = help_content.menuid
                            AND help_menu.crm='$crm'
                    ) = '0'
                    AND crm='$crm'
                ";
            $result = $this->db->query($query);
            if ($result && $result->num_rows() > 0) {
                $rows = $result->result_array();
                $result->free_result();
                foreach ($rows as $key => $val) {
                    $menuIds[] = $val['menuid'];
                }
            }

            //get information of menu in menuIds
            $res['rows'] = array();
            if (sizeof($menuIds) > 0) {
                $menu_sql = implode(",", $menuIds);
                $query = "
                    SELECT *
                    FROM help_menu
                    WHERE id IN ($menu_sql)
                    ";
                $result = $this->db->query($query);
                if ($result && $result->num_rows() > 0) {
                    $limit = isset($params['limit']) ? $params['limit'] : '10';
                    $curPage = isset($params['curPage']) ? $params['curPage'] : '1';
                    $offset = ((int) $curPage - 1) * (int) $limit;
                    $res['total'] = $result->num_rows();
                    $order = isset($params['order']) ? $params['order'] : 'help_menu.name';
                    $orderType = isset($params['orderType']) ? $params['orderType'] : 'ASC';

                    $query = "
                        SELECT *
                        FROM help_menu
                        WHERE id IN ($menu_sql)
                        ORDER BY $order $orderType
                        LIMIT $offset, $limit
                    ";
                    $result = $this->db->query($query);
                    $res['rows'] = $result->result_array();
                    $result->free_result();
                }
            }
            $res['success'] = TRUE;
        } else {
            $res['success'] = FALSE;
        }
        return $res;
    }

    function order_menu($params) {
        if (isset($params['menuorder'])) {
            $this->db->update_batch('help_menu', $params['menuorder'], 'id');
            $res['success'] = TRUE;
        } else {
            $res['success'] = FALSE;
        }
        return $res;
    }

    function register($params) {
        if (isset($params['username'])) {
            //check duplicate username
            $result = $this->db->query("SELECT id FROM help_users WHERE username='{$params['username']}' LIMIT 1");
            if (!$result || $result->num_rows() == 0) {
                if (isset($params['password']) && isset($params['password2']) && $params['password'] == $params['password2']) {
                    if (isset($params['email']) && isset($params['email2']) && $params['email'] == $params['email2']) {
                        $data['username'] = $params['username'];
                        $data['password'] = base64_encode($params['password']);
                        $data['email'] = $params['email'];
                        $data['regdate'] = date('Y:m:d H:i:s');

                        $this->db->insert('help_users', $data);
                        $res['success'] = TRUE;
                        $res['message'] = 'Please wait for accepting to use your account. Now, you can return Help page.';
                    } else {
                        $res['success'] = FALSE;
                        $res['message'] = 'Wrong confirm email.';
                    }
                } else {
                    $res['success'] = FALSE;
                    $res['message'] = 'Wrong confirm password.';
                }
            } else {
                $result->free_result();
                $res['success'] = FALSE;
                $res['message'] = 'Duplicated username.';
            }
        } else {
            $res['success'] = FALSE;
            $res['message'] = 'Please input username';
        }
        return $res;
    }

    function login($params) {
        if (isset($params['username']) && isset($params['password'])) {
            $username = $params['username'];
            $password = base64_encode($params['password']);
            $result = $this->db->query("SELECT * FROM help_users WHERE username='$username' AND password='$password' AND active='1' LIMIT 1");
            if ($result && $result->num_rows() > 0) {
                $rows = $result->result_array();
                $result->free_result();
                setcookie("HANBIRO_HP", serialize($rows[0]), time() + 3600 * 24 * 30);  /* expire in 30 days */
                $res['success'] = TRUE;
            } else {
                $res['success'] = FALSE;
                $res['message'] = 'Wrong username or password';
            }
        } else {
            $res['success'] = FALSE;
            $res['message'] = 'Please input data!';
        }
        return $res;
    }

    function user_list($params) {
        $query = "SELECT id FROM help_users WHERE username<>'postmaster'";
        $result = $this->db->query($query);
        //$result = $this->db->query("SELECT id, username, email, active, regdate FROM help_users WHERE username<>'postmaster' ORDER BY id");
        if ($result && $result->num_rows() > 0) {
            $limit = isset($params['limit']) ? $params['limit'] : '10';
            $curPage = isset($params['curPage']) ? $params['curPage'] : '1';
            $offset = ((int) $curPage - 1) * (int) $limit;
            $res['total'] = $result->num_rows();
            //query result
            $query = "
                SELECT 
                    id, 
                    username, 
                    email, 
                    active, 
                    regdate 
                FROM help_users 
                WHERE username<>'postmaster'
                ORDER BY id ASC
                LIMIT $offset, $limit
                ";
            $result = $this->db->query($query);
            $res['rows'] = $result->result_array();

            foreach ($res['rows'] as $k => $val) {
                if ($val['active'] == '1')
                    $res['rows'][$k]['active'] = 'Yes';
                else
                    $res['rows'][$k]['active'] = 'No';
            }
            $result->free_result();
            $res['success'] = TRUE;
        } else {
            $res['success'] = FALSE;
        }
        return $res;
    }

    function active_user($params) {
        if (isset($params['id'])) {
            $data = array(
                'active' => '1'
            );
            $this->db->where('id', $params['id']);
            $this->db->update('help_users', $data);
            $res['success'] = TRUE;
        } else {
            $res['success'] = FALSE;
        }
        return $res;
    }

    function disactive_user($params) {
        if (isset($params['id'])) {
            $data = array(
                'active' => '0'
            );
            $this->db->where('id', $params['id']);
            $this->db->update('help_users', $data);
            $res['success'] = TRUE;
        } else {
            $res['success'] = FALSE;
        }
        return $res;
    }

    function delete_user($params) {
        if (isset($params['id'])) {
            $this->db->delete('help_users', array('id' => $params['id']));
            $res['success'] = TRUE;
        } else {
            $res['success'] = FALSE;
        }
        return $res;
    }

    private function convert_images_base64($content) {
        //$threads = array();
        //initial threads for creating images
        //$offset = 0;
        $run = true;
        while ($run) {
            $start_pos = strpos($content, "src=\"data", 0);
            if ($start_pos !== FALSE) {
                //except keyword: src="data (length: 9)
                $start_pos += 5;
                $end_pos = strpos($content, "\"", $start_pos);
                if ($end_pos !== FALSE) {
                    //Continue to searching
                    //$offset = $end_pos + 1;
                    //except keyword: " (length: 1)
                    $end_pos -= 1;
                    //get src of image
                    $length = $end_pos - $start_pos + 1;
                    $src = substr($content, $start_pos, $length);
                    /*$thread = new CreateImageThreads($content, $start_pos, $length);
                    $threads[] = $thread;*/
                    $filename = $this->create_img_from_src($src);
                    if ($filename !== '') {
                        $run = true;
                        //apply new path to browser
                        $path = "images/$filename";
                        $content = substr_replace($content, $path, $start_pos, $length);
                    } else {
                        $run = false;
                    }
                } else {
                    //finish searching
                    //$offset = sizeof($content);
                    $run = false;
                }
            } else {
                //finish searching
                //$offset = sizeof($content);
                $run = false;
            }
        }
        /*//start threads
        foreach ($threads as $i => $thread) {
            $thread->start();
        }
        // Let the threads come back
        foreach ($threads as $i => $thread) {
            $thread->join();
        }
        //update new content
        foreach ($threads as $i => $thread) {
            $content = substr_replace($content, $thread->path, $thread->start_pos, $thread->length);
        }*/
        return $content;
    }
    
    private function create_img_from_src($src, $prefix = "upload_") {
        list($type, $data) = explode(';', $src);
        list(, $type) = explode(':', $type);
        list(, $data) = explode(',', $data);
        $data = base64_decode($data);
        $type = $this->parse_img_type($type);
        if ($type !== '') {
            //get unique filename
            $filename = $prefix . uniqid() . "." . $type;
            $full_path = dirname(__FILE__);
            $home_path = explode("application", $full_path);
            if (sizeof($home_path) > 0) {
                $home_path = $home_path[0];
            }
            $path = $home_path . "help/images/$filename";
            //create new image file
            file_put_contents($path, $data);
            return $filename;
        } else {
            return '';
        }
    }
    
    private function parse_img_type($_type) {
        $type = '';
        switch ($_type) {
            case 'image/gif':
                $type = 'gif';
                break;
            case 'image/jpeg':
                $type = 'jpg';
                break;
            case 'image/png':
                $type = 'png';
                break;
        }
        return $type;
    }
    
}

/*class CreateImageThreads extends Thread {
    
    private $src;
    private $prefix;
    private $start_pos;
    private $length;
    private $path;

    public function __construct($content, $start_pos, $length, $prefix="upload_") {
        $this->src = substr($content, $start_pos, $length);
        $this->prefix = $prefix;
        $this->start_pos = $start_pos;
        $this->length = $length;
    }

    public function run() {
        $filename = $this->create_img_from_src($this->src, $this->prefix);
        if ($filename !== '') {
            //apply new path to browser
            $path = "images/$filename";
            $this->path = $path;
        }
    }
    
    private function create_img_from_src($src, $prefix = "upload_") {
        list($type, $data) = explode(';', $src);
        list(, $type) = explode(':', $type);
        list(, $data) = explode(',', $data);
        $data = base64_decode($data);
        $type = $this->parse_img_type($type);
        if ($type !== '') {
            //get unique filename
            $filename = $prefix . uniqid() . "." . $type;
            $full_path = dirname(__FILE__);
            $home_path = explode("application", $full_path);
            if (sizeof($home_path) > 0) {
                $home_path = $home_path[0];
            }
            $path = $home_path . "help/images/$filename";
            //create new image file
            file_put_contents($path, $data);
            return $filename;
        } else {
            return '';
        }
    }
    
    private function parse_img_type($_type) {
        $type = '';
        switch ($_type) {
            case 'image/gif':
                $type = 'gif';
                break;
            case 'image/jpeg':
                $type = 'jpg';
                break;
            case 'image/png':
                $type = 'png';
                break;
        }
        return $type;
    }

}*/

/* End of file employee_model.php */
/* Location: ./application/models/user_model.php */
