$(function(){
	$('ul.nested').nestedSortable({
		disableNesting: 'no-nest',
		items : "li:not(.disabled)",
		listType: 'ul',
		forcePlaceholderSize: true,
		handle: 'div',
		helper:	'clone',
		items: 'li',
		opacity: 0.8,
		tabSize: 25,
		delay: 300,
		distance: 15,
		placeholder: 'placeholder',
		tolerance: 'pointer',
		toleranceElement: '> div',
		update: function(event, ui) {
			var id				= ui.item.attr('data-id');
			var arrayed			= $('ul.nested').nestedSortable('toArray', {startDepthCount: 0});
			var list			= new Array();
			var parent_id		= false;
			var table_name		= $('#table_name').val();
			var nesting_column	= $('#nesting_column').val();
			for (var i = 0; i < arrayed.length; i++) {
				if (arrayed[i].item_id != 'root') list[list.length] = arrayed[i].item_id;
				if (arrayed[i].item_id == id) parent_id = arrayed[i].parent_id;
			}
			$.ajax({
				url : '/login/ajax/nested_reorder.php',
				type : 'POST',
				data : { 
					id : id,
					table_name : table_name,
					nesting_column : nesting_column,
					parent_id : parent_id, 
					list : list.join(',')
				},
				success : function(data) {
					//$('#panel').html(data);
				}
			});
			fix_depths($('ul.nested'));
		}	
	});
	
	//delete items out of a nested list
	$('ul.nested div.delete a').click(function(){
		var item_id = $(this).closest('div.row').attr('id').replace('item_', '');
		var item = $('li.list_' + item_id);
		var children = $('li.list_' + item_id + ' > ul').children();
		if (confirm('Are you sure?')) {
			$.ajax({
				url : '/login/ajax/nested_delete.php',
				type : 'POST',
				data : { item_id : item_id, table : $('#table_name').val(), nesting_column : $('#nesting_column').val() },
				success : function(data) {
					$('#panel').html(data);
					if (children.size()) {
						//console.log('has ' + children.size() + ' children');
						item.before(children);
						fix_depths($('ul.nested'));
					}
					item.slideUp();
				}
			});
		}
	});
	
	function fix_depths(ul, level) {
		if (!level) level = 1;
		var needle = 'level_';
		var strlen = needle.length;
		$(ul).children().each(function(){
			var row = $(this).find('div.row');
			var classes = row.attr('class').split(' ');
			for (var j = 0; j < classes.length; j++) {
				if (classes[j].substr(0, strlen) == needle) row.removeClass(classes[j]).addClass(needle + level);
			}
			fix_depths($(this).children('ul'), (level + 1))
		});
	}
});
