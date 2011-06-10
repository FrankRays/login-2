<?php
include('../include.php');

if (!admin()) url_change(DIRECTORY_BASE);

if ($posting) {
	if ($uploading) $_POST['banner_image'] = format_image_resize(file_get_uploaded('banner_image'), false, 105);
	db_save('app', 1);

	//process changes to languages
	$languages_checked = array_checkboxes('languages');
	db_query('UPDATE app_languages SET checked = 0');
	foreach ($languages_checked as $l) db_query('UPDATE app_languages SET checked = 1 WHERE id = ' . $l['id']);
	url_change_post('../');
}
echo drawFirst('Site Settings');

$t = new form('app', 1, 'Edit Site Settings');
$t->set_field(array('type'=>'checkboxes', 'name'=>'languages', 'sql'=>'SELECT id, title, checked FROM app_languages ORDER BY title'));
echo $t->draw();

echo draw_div('panel', 
	draw_p('The banner image will be resized to 920px x 105px.') . 
	draw_p('If you have just migrated servers, you may wish to <a class="tinymce_update">update the TinyMCE file and image references</a>.') .
	draw_p('You can also <a href="cleanup/">clean up</a> deleted or unused columns (irreversibly).') .
	draw_p('You can also <a href="cleanup/?test=true">run the cleanup in test mode</a> to see what operations would be performed.')
);

echo drawLast();