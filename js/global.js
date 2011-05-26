$(function(){
	function log($msg) {
		try {
			console.log($msg);
		} catch(e) {
		}
	}
	
	//show translations
	$('a.show_translations').click(function(e){
		if ($('div.translation:visible').size()) {
			$(this).html('Show Translations');
			$('div.translation').slideUp();
		} else {
			$(this).html('Hide Translations');
			$('div.translation').slideDown();
		}
	});
	
	$('a.translate').click(function(e){
		$('div.translation input').each(function(){
			if (!$(this).val().length) {
				var field = $(this)
				var fname = field.attr('name');
				var lang = fname.substr(-2);
				var src = $('div input[name=' + fname.substr(0, fname.length - 3) + ']').val();
				if (src.length) {
					$.ajax({  
					    url: 'https://ajax.googleapis.com/ajax/services/language/translate',  
					    dataType: 'jsonp',
					    data: { q: src,  // text to translate
					            v: '1.0',
					            langpair: 'en|' + lang },   // '|es' for auto-detect
					    success: function(result) {
					        field.val(result.responseData.translatedText);
					    },  
					    error: function(XMLHttpRequest, errorMsg, errorThrown) {
					        console.log(errorMsg);
					    }  
					});
				}
			}
		});
	});
	
	//lorem ipsum for rich textareas
	$('a.lorem_ipsum').click(function(e){
		e.preventDefault();
		$(this).closest('div.field').find('textarea').tinymce().setContent(LoremIpsum.paragraphs((2 + Math.floor(Math.random()*2)), "<p>%s</p>"));
	});
	
	//duplicate object button on settings page
	$('a.object_duplicate').click(function(e){
		e.preventDefault();
		var title = prompt('What should the new object be called?', $('form input#title').val() + ' New');
		if (title) location.href = './?' + $.param({ id: url_query('id'), action:'duplicate', title:title });
	});
	
	//update tinymce file and image references
	$('a.tinymce_update').click(function(e){
		if (old_server = prompt('What was the HTTP_HOST of the old server?')) {
			$.ajax({
				url : '/login/ajax/tinymce_update.php',
				type : 'POST',
				data : { old_server : old_server },
				success : function(data) {
					alert(data);
				}
			});
		}
	});
	
	//show sql button on object page
	$('li.sql a').click(function(e){
		e.preventDefault();
		$(this).html(($('#sql').is(':visible') ? 'Show' : 'Hide') + ' SQL');
		$('#sql').slideToggle();
	});
	
	//object value delete
	$('a.delete').click(function(e) {
		e.preventDefault();
		tr = $(this).closest('tr');
		parts = $(this).attr('rel').split('-');
		$.ajax({
			url : '/login/ajax/object_value_delete.php',
			type : 'POST',
			data : { object_id : parts[0], id : parts[1] },
			success : function(data) {
				if ($('ul.nav li').size() == 5) $('ul.nav li.option3 a').html(data); //todo genericize this with classes
				if (tr.hasClass('deleted')) {
					tr.removeClass('deleted');
				} else {
					if (tr.parent().find('tr.deleted').size()) {
						tr.addClass('deleted');
					} else {
						tr.fadeOut().slideUp();
					}
				}
			}
		});
	});
	
	//setup for sortable
	$('ul.nested li').each(function(){ $(this).attr('id', 'list_' + $(this).attr('data-id')); });
	
	//init sortable
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
					$('#panel').html(data);
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
	
	//clear images from object/edit forms
	$('a.clear_img').click(function(e){
		e.preventDefault();
		var title = $(this).attr('data-title');
		var table = $(this).attr('data-table');
		var column = $(this).attr('data-column');
		var id = $(this).attr('data-id');
		if (confirm("Are you sure you want to clear the " + title.toLowerCase() + " field?  It will happen right away (before saving).")) {
			ajax_set(table, column, id);
			$('div.field.' + column + ' img.preview').slideUp();
			$('div.field.' + column + ' a.clear_img').fadeOut();
		}
	});
	
	//adjust the css on the rows because the indentation has likely changed
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