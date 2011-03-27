<?php
include('../include.php');
$array = array_ajax();

echo draw_array($array);
/*
	expecting from POST: 
	
	item_id
	parent_id
	depth
	right
	left
	table
	
	..describing the new position of the item that was dragged
*/

if ($array['parent_id'] == 'root') $array['parent_id'] = 'NULL';

$item = db_array('SELECT parent_id, precedence, subsequence FROM ' . $array['table'] . ' WHERE id = ' . $array['item_id']);

//maybe we need to know which direction we are moving?
$diff = $item['precedence'] - $array['left'];
$what = "diff: " . $diff;

if ($item['parent_id'] != $array['parent_id']) {
	$diff = $item['parent_id'] - $array['parent_id'];
	$what = "parent diff: " . $diff;
}

//change the item that was dragged
db_query('UPDATE ' . $array['table'] . ' SET parent_id = ' . $array['parent_id'] . ', precedence = ' . $array['left'] . ', subsequence = ' . ($array['left'] + 1) .' WHERE id = ' . $array['item_id']);

if($diff >= 0) {
	//nudge everything right (except the element that was moved)
	db_query('UPDATE ' . $array['table'] . ' SET subsequence = subsequence + 2 WHERE id <> ' . $array['item_id']);
	db_query('UPDATE ' . $array['table'] . ' SET precedence = precedence + 2 WHERE id <> ' . $array['item_id']);
	$what .= '/nudge right';
} else {
	//nudge everything left (except the element that was moved)
	db_query('UPDATE ' . $array['table'] . ' SET subsequence=subsequence - 2 WHERE id <> ' . $array['item_id']);
	db_query('UPDATE ' . $array['table'] . ' SET precedence=precedence - 2 WHERE id <> ' . $array['item_id']);
	$what .= '/nudge left';
}

nestedTreeRebuild($array['table']);

echo $what;