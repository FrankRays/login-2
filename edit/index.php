<?php 
//add a new object to the CMS or edit its settings
include('../include.php');

if (!admin()) url_change(DIRECTORY_BASE);

if ($posting) {
	if (!$editing) {
		//create new table
		$_POST['table_name'] = getNewObjectName('user ' . $_POST['title']);
		db_table_create($_POST['table_name']);
	}
	$id = db_save('app_objects');
	db_checkboxes('permissions', 'app_users_to_objects', 'object_id', 'user_id', $id);
	if ($editing) {
		db_checkboxes('object_links', 'app_objects_links', 'object_id', 'linked_id', $_GET['id']);
		url_change_post('../');
	} else {
		//add new title column because we nearly always need it
		db_column_add($_POST['table_name'], 'title', 'text');
		db_save('app_fields', false, array('object_id'=>$id, 'type'=>'text', 'title'=>'Title', 'field_name'=>'title', 'visibility'=>'list', 'required'=>true));
		url_change('../object/?id=' . $id);
	}
} elseif (url_action('delete')) {
	//ok you're going to delete this object
	$table = db_grab('SELECT table_name FROM app_objects WHERE id = ' . $_GET['id']);
	if (db_table_drop($table)) {
		db_table_drop($table . '_to_words');
		db_query('DELETE FROM app_fields WHERE object_id = ' . $_GET['id']);
		db_query('DELETE FROM app_objects_links WHERE object_id = ' . $_GET['id']);
		db_query('DELETE FROM app_users_to_objects WHERE object_id = ' . $_GET['id']);
		db_query('DELETE FROM app_objects WHERE id = ' . $_GET['id']);
	}
	url_change(DIRECTORY_BASE);
} elseif (url_action('duplicate')) {
	//duplicate an object and all its meta and values
	//todo fix app_objects precedence
	$table_name = getNewObjectName('user ' . $_GET['title']);
	$object_id = db_query('INSERT INTO app_objects ( title, table_name, order_by, direction, group_by_field, list_help, form_help, show_published, web_page, created_date, created_user, is_active ) SELECT "' . $_GET['title'] . '", "' . $table_name . '", order_by, direction, group_by_field, list_help, form_help, show_published, web_page, ' . db_date() . ', ' . user() . ', 1 FROM app_objects WHERE id = ' . $_GET['id']);
	db_table_duplicate(db_grab('SELECT table_name FROM app_objects WHERE id = ' . $_GET['id']), $table_name);
	//going to skip copying permissions
	db_query('INSERT INTO app_objects_links ( object_id, linked_id ) SELECT ' . $object_id . ', linked_id FROM app_objects_links WHERE object_id = ' . $_GET['id']);
	db_query('INSERT INTO app_fields ( object_id, type, title, field_name, visibility, required, related_field_id, related_object_id, width, height, additional, created_date, created_user, is_active ) SELECT ' . $object_id . ', type, title, field_name, visibility, required, related_field_id, related_object_id, width, height, additional, ' . db_date() . ', ' . user() . ', 1 FROM app_fields WHERE object_id = ' . $_GET['id']);
	
	//fix app_objects.group_by_field
	if ($field_name = db_grab('SELECT f.field_name FROM app_fields f JOIN app_objects o ON f.id = o.group_by_field WHERE o.id = ' . $object_id)) {
		$field_id = db_grab('SELECT id FROM app_fields WHERE field_name = "' . $field_name . '" AND object_id = ' . $object_id);
		db_query('UPDATE app_objects SET group_by_field = ' . $field_id . ' WHERE id = ' . $object_id);
	}
	
	//fix app_fields.related_field_id
	if ($field_names = db_table('SELECT f1.id, f2.field_name FROM app_fields f1 JOIN app_fields f2 ON f1.related_field_id = f2.id WHERE f1.object_id = ' . $object_id)) {
		foreach ($field_names as $field) {
			$field_id = db_grab('SELECT id FROM app_fields WHERE field_name = "' . $field['field_name'] . '" AND object_id = ' . $object_id);
			db_query('UPDATE app_fields SET related_field_id = ' . $field_id . ' WHERE id = ' . $field['id']);
		}
	}
	
	url_change(DIRECTORY_BASE . 'object/?id=' . $object_id);
} elseif (url_action('resize')) {
	//resize all images in object according to new field rules
	//todo move this to field edit?
	$table = db_grab('SELECT table_name FROM app_objects WHERE id = ' . $_GET['id']);
	$cols = db_table('SELECT field_name, width, height FROM app_fields WHERE object_id = ' . $_GET['id'] . ' AND (type = "image" OR type = "image-alt") AND (width IS NOT NULL OR height IS NOT NULL)');
	$rows = db_table('SELECT id, ' . implode(', ', array_key_values($cols, 'field_name')) . ' FROM ' . $table);
	foreach ($rows as $r) {
		$updates = array();
		foreach ($cols as $c) {
			if ($r[$c['field_name']]) $updates[] = $c['field_name'] . ' = ' . format_binary(format_image_resize($r[$c['field_name']], $c['width'], $c['height']));
		}
		if (count($updates)) db_query('UPDATE ' . $table . ' SET ' . implode(', ', $updates) . ', updated_date = NOW(), updated_user = ' . user() . ' WHERE id = ' . $r['id']);
	}
	url_drop('action');
} elseif ($editing) {
	$title = db_grab('SELECT title FROM app_objects WHERE id = ' . $_GET['id']);
	$action = 'Edit Settings';
	echo drawFirst(draw_link('../object/?id=' . $_GET['id'], $title) . ' &gt; ' . $action);
} else { //adding
	$action = 'Add New Object';
	echo drawFirst($action);
}

$f = new form('app_objects', @$_GET['id'], $action);

if (url_id()) {
	//if editings present more options
	$order_by = db_table('SELECT field_name, title FROM app_fields WHERE object_id = ' . $_GET['id'] . ' AND is_active = 1 ORDER BY precedence');
	$order_by['precedence'] = 'Precedence';
	$order_by['created_date'] = 'Created';
	$order_by['updated_date'] = 'Updated';
	$f->set_field(array('name'=>'order_by', 'type'=>'select', 'options'=>$order_by));
	$f->set_field(array('name'=>'table_name', 'type'=>'text', 'allow_changes'=>false));
	$f->set_field(array('name'=>'direction', 'type'=>'select', 'options'=>array_2d(array('ASC', 'DESC')), 'default'=>'ASC', 'required'=>true));
	if ($options = db_table('SELECT id, title FROM app_fields WHERE type = "select" AND is_active = 1 AND object_id = ' . $_GET['id'])) {
		$f->set_field(array('name'=>'group_by_field', 'label'=>'Group By', 'type'=>'select', 'options'=>$options));
	}
	if ($options = db_table('SELECT o.id, o.title, (SELECT COUNT(*) FROM app_objects_links l WHERE l.object_id = ' . $_GET['id'] . ' AND l.linked_id = o.id) checked FROM app_objects o JOIN app_fields f ON o.id = f.object_id WHERE f.related_object_id = ' . $_GET['id'])) {
		$f->set_field(array('name'=>'object_links', 'type'=>'checkboxes', 'label'=>'Linked Objects', 'linking_table'=>'app_objects_links', 'options_table'=>'app_objects', 'option_id'=>'object_id', 'option_title'=>'title', 'options'=>$options));
	}
} else {
	$f->unset_fields('table_name,group_by_field,order_by,web_page,show_published');
	$f->set_field(array('name'=>'direction', 'type'=>'hidden', 'value'=>'ASC'));
}

//permissions
if (db_grab('SELECT COUNT(*) FROM app_users WHERE is_active = 1 AND is_admin <> 1 AND id <> ' . user())) {
	$sql = 'SELECT u.id, CONCAT(u.firstname, " ", u.lastname) title, ' . (url_id() ? '(SELECT COUNT(*) FROM app_users_to_objects u2o WHERE u2o.user_id = u.id AND u2o.object_id = ' . $_GET['id'] . ')' : 1) . ' checked FROM app_users u WHERE u.is_active = 1 and u.is_admin <> 1 ORDER BY title';
	$f->set_field(array('name'=>'permissions', 'type'=>'checkboxes', 'sql'=>$sql));
}

//table name handled automatically, help handled with in-place editor
$f->unset_fields('list_help,form_help');
echo $f->draw();

if (url_id()) {
	$images = false;
	if (db_grab('SELECT COUNT(*) FROM app_fields WHERE object_id = ' . $_GET['id'] . ' AND (type = "image" OR type = "image-alt") AND (width IS NOT NULL OR height IS NOT NULL)')) {
		$images = draw_p('You can also ' . draw_link(url_action_add('resize'), 'resize all images') . '.');
	}
	echo draw_div('panel', 
		draw_p('You can drop this object and all its associated fields and values by ' . draw_link(url_action_add('delete'), 'clicking here') . '.') . 
		$images . 
		draw_p('You can also ' . draw_link(false, 'duplicate this object', false, array('class'=>'object_duplicate')) . ' and all of its values.')
	);
}

echo drawLast();
?>