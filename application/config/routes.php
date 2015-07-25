<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	http://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There area two reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router what URI segments to use if those provided
| in the URL cannot be matched to a valid route.
|
*/

$route['default_controller'] = 'main';
$route['404_override'] = '';

/* helpdesk knowledgebase */
$route['knowledgebase/folder/(:num)'] = "knowledgebase/folder/index/$1";
$route['knowledgebase/article'] = "knowledgebase/article/item";
$route['knowledgebase/article/(:num)/attachment/(:num)'] = "knowledgebase/article/attachment/$1/$2";
$route['knowledgebase/article/(:num)'] = "knowledgebase/article/item/$1";
$route['knowledgebase/articles'] = "knowledgebase/article/list";
$route['knowledgebase/articles/(:any)'] = "knowledgebase/article/list/$1";

/* helpdesk ticket */
$route['helpdesk/tickets/(:num)'] = "helpdesk/ticket/tickets/$1";
$route['helpdesk/tickets/(:num)/(:num)'] = "helpdesk/ticket/item/$1/$2";
$route['helpdesk/tickets/(:num)/(:num)/updates'] = "helpdesk/ticket/updates/$1/$2";
$route['helpdesk/tickets/(:num)/(:num)/comments'] = "helpdesk/ticket/comments/$1/$2";
$route['helpdesk/tickets/(:num)/(:num)/comments/(:num)'] = "helpdesk/ticket/comment_item/$1/$2/$3";
$route['helpdesk/tickets/(:num)/counts'] = "helpdesk/ticket/counts/$1";
$route['helpdesk/tickets/(:num)/statuses'] = "helpdesk/ticket/statuses/$1";
$route['helpdesk/tickets/(:num)/departments'] = "helpdesk/ticket/departments/$1";
$route['helpdesk/tickets/(:num)/priorities'] = "helpdesk/ticket/priorities/$1";
$route['helpdesk/tickets/(:num)/labels'] = "helpdesk/ticket/labels/$1";
$route['helpdesk/tickets/(:num)/labels/(:any)'] = "helpdesk/ticket/label_item/$1/$2";

/* helpdesk user */
$route['helpdesk/users/(:num)'] = "helpdesk/ticketuser/users/$1";
$route['helpdesk/users/(:num)/(:num)'] = "helpdesk/ticketuser/item/$1/$2";

/* activity */
$route['activity/search/(:num)'] = "activity/search/records/$1";
$route['activity/call/(:num)'] = "activity/call/create/$1/0";
$route['activity/call/(:num)/(:num)'] = "activity/call/record/$1/$2";
$route['activity/meeting/(:num)'] = "activity/meeting/create/$1";
$route['activity/meeting/(:num)/(:num)'] = "activity/meeting/record/$1/$2";
$route['activity/task/(:num)'] = "activity/task/create/$1";
$route['activity/task/(:num)/(:num)'] = "activity/task/record/$1/$2";

/* feed */
$route['feed'] = "history/feed/records";
$route['feed/(:any)'] = "history/feed/record/$1";
/* Location: ./application/config/routes.php */
