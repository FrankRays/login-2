<?php
include('include.php');

echo drawFirst();

$objects = db_table('SELECT 
	o.id, 
	o.title object, 
	o.updated_date,
	o.table_name,
	(SELECT COUNT(*) FROM app_users_to_objects u2o WHERE u2o.object_id = o.id AND u2o.user_id = ' . user() . ') permission,
	o.list_grouping
FROM app_objects o
WHERE o.is_active = 1
ORDER BY o.list_grouping, o.title');

$nav = array();
if (isProgrammer()) {
	$nav[] = array('url'=>'site/', 'icon'=>'cogs', 'title'=>'Site Settings');
	$nav[] = array('url'=>'users/', 'icon'=>'group', 'title'=>'Users');
	$nav[] = array('url'=>'edit/', 'icon'=>'pencil', 'title'=>'Add New Object');
	//echo draw_nav(array('site/'=>'<i class="icon-cogs"></i> Site Settings', 'users/'=>'<i class="icon-group"></i> Users', 'edit/'=>'<i class="icon-pencil"></i> Add New Object'));
	echo drawNav($nav);
} elseif (isAdmin()) {
	$nav[] = array('url'=>'users/', 'icon'=>'group', 'title'=>'Users');
	//echo draw_nav(array('users/'=>'<i class="icon-users"></i> Users'));
	echo drawNav($nav);
}

$t = new table;
$t->set_column('object', 'l', 'Object', 200);
$t->set_column('count_active', 'c', '# Active');
$t->set_column('updated_user', 'r', 'Last Update By');
$t->set_column('updated', 'r');

foreach ($objects as &$o) {
	if (!$object = db_grab('SELECT 
			' . db_updated('a') . ',
			(SELECT CONCAT_WS(" ", u.firstname, u.lastname) FROM app_users u WHERE u.id = a.created_user) created_user,
			(SELECT CONCAT_WS(" ", u.firstname, u.lastname) FROM app_users u WHERE u.id = a.updated_user) updated_user,
			(SELECT COUNT(*) FROM ' . $o['table_name'] . ' a WHERE a.is_active = 1) count_active
		FROM ' . $o['table_name'] . ' a
		WHERE a.is_active = 1
		ORDER BY a.updated_date DESC, a.created_date DESC')) $object = array('updated'=>false, 'created_user'=>false, 'updated_user'=>false, 'count_active'=>0);
	$o = array_merge($o, $object);
	$o['group'] = $o['list_grouping'];
	if (isAdmin() || $o['permission']) {
		$o['object'] = draw_link('object/?id=' . $o['id'], $o['object']);
	} else {
		$o['object'] = draw_container('span', $o['object'], array('class'=>'g'));
	}
	if (empty($o['updated_user'])) $o['updated_user'] = $o['created_user'];
	if (!empty($o['updated'])) $o['updated'] = format_date($o['updated']);
}
echo $t->draw($objects, 'No objects have been added yet!');

$panel = draw_p('This is the main directory of website &#8216;objects.&#8217;  Those that are linked you have permission to edit.') . draw_p('You\'re logged in as ' . draw_strong($_SESSION[SESSION_USER_NAME]) . '.' . BR . 'Click ' . draw_link(url_action_add('logout'), 'here') . ' to log out.');

echo drawLast($panel);