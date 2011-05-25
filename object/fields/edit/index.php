<?php
include('../../../include.php');

if ($posting) {
	$table = db_grab('SELECT table_name FROM app_objects WHERE id = ' . $_GET['object_id']);
	$datatype  = $_POST['type'];
	if (!$editing) {
		$field = $_POST['title'];
		if ($_POST['type'] == 'select') {
			$field .= '_id';
			$datatype = 'int';
		} elseif (($_POST['type'] == 'checkboxes') || ($_POST['type'] == 'object')) {
			//if it's checkboxes, create a new linking table
			$rel_table = substr(db_grab('SELECT table_name FROM app_objects WHERE id = ' . $_POST['related_object_id']), 5);
			$_POST['field_name'] = getNewObjectName(format_text_code($table . '_to_' . $rel_table));
			db_table_create($_POST['field_name'], array($rel_table . '_id'=>'int', substr($table, 5) . '_id'=>'int'));
		} else {
			//add field to table
			$_POST['field_name'] = getNewObjectName($table, $field);
			db_column_add($table, $_POST['field_name'], $datatype);
		}
	}
	
	//check to make sure columns for translations exist
	if ($languages && isset($_POST['is_translated'])) foreach ($languages as $l) db_column_add($table, $_POST['field_name'], $datatype);
	$id = db_save('app_fields');
	
	url_change('../?id=' . $_GET['object_id']);
}

$r = db_grab('SELECT title, table_name FROM app_objects WHERE id = ' . $_GET['object_id']);

echo drawFirst(draw_link('../../?id=' . $_GET['object_id'], $r['title']) . ' &gt; ' . draw_link('../?id=' . $_GET['object_id'], 'Fields') . ' &gt; Edit Field');

$f = new form('app_fields', @$_GET['id']);
$f->set_field(array('name'=>'type', 'type'=>'select', 'options'=>$_josh['field_types'], 'default'=>'text', 'required'=>true, 'allow_changes'=>!$editing));
$f->set_field(array('name'=>'object_id', 'type'=>'hidden', 'value'=>$_GET['object_id']));
$f->set_field(array('name'=>'visibility', 'type'=>'select', 'options'=>$visibilty_levels, 'default'=>'normal', 'required'=>true));
$f->set_field(array('name'=>'additional', 'label'=>'Additional Instructions', 'type'=>'textarea'));
$f->set_field(array('name'=>'related_field_id', 'type'=>'select', 'sql'=>'SELECT id, title FROM app_fields WHERE object_id = ' . $_GET['object_id'] . ' AND is_active = 1 ORDER BY title'));
$f->set_field(array('name'=>'related_object_id', 'type'=>'select', 'sql'=>'SELECT id, title FROM app_objects WHERE is_active = 1 ORDER BY title'));
$f->set_field(array('name'=>'width', 'type'=>'text'));
$f->set_field(array('name'=>'height', 'type'=>'text'));
$f->set_hidden('field_name');
$f->unset_fields('external_table');
if (!$languages) $f->unset_fields('is_translated');
echo $f->draw();

echo drawLast();