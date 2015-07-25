<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class QueryLogHook 
{
    function log_queries() 
    {    
        $CI =& get_instance();

        $times = $CI->db->query_times;
        $output = NULL;     
        $queries = $CI->db->queries;

        if (count($queries) == 0)
        {
            $output .= "no queries\n";
        }
        else
        {
            foreach ($queries as $key => $query)
            {
                $output .= sprintf("[%s]-%s %s\n\n", date('Y/m/d H:i:s', time()), $times[$key], $query);
            }
            $took = round(doubleval($times[$key]), 3);
            $output .= "===[took:{$took}]\n\n";
        }

        $CI->load->helper('file');
        if ( ! write_file(APPPATH  . "/logs/DB-Query.log", $output, 'a+'))
        {
             log_message('debug','Unable to write query the file');
        }   
    }
}

/* End of file log_query.php */
/* Location: ./application/hooks/log_query.php */
