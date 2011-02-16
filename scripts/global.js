$(function(){
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
	
	//show sql button on object page
	$('a[href=#sql]').click(function(e){
		e.preventDefault();
		$(this).html(($('#sql').is(':visible') ? 'Show' : 'Hide') + ' SQL');
		$('#sql').slideToggle();
	});
	
	//object value delete
	$('a.delete').click(function(e) {
		e.preventDefault();
		var tr = $(this).closest('tr');
		parts = tr.attr('id').split('-');
		$.ajax({
			url : '/login/ajax/object_value_delete.php',
			type : 'POST',
			data : { object_id : url_id(), id : parts[parts.length-1] },
			success : function(data) {
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
});

function clearImg(table, column, id, title) {
	if (confirm("Are you sure you want to clear the " + title.toLowerCase() + " field?  It will be done immediately.")) {
		ajax_set(table, column, id);
		$('div.field.' + column + ' img.preview').slideUp();
		$('div.field.' + column + ' a.clear_img').slideUp();
	}
	return false;
}