$(function(){
	$('a.lorem_ipsum').click(function(e){
		e.preventDefault();
		$(this).closest('div.field').find('textarea').tinymce().setContent(LoremIpsum.paragraphs((2 + Math.floor(Math.random()*2)), "<p>%s</p>"));
	});
});

function showSQL() {
	$("#sql").slideToggle();
	
	if ($("a.option_1_3").html() == "Show SQL") {
		$("a.option_1_3").html("Hide SQL");
	} else {
		$("a.option_1_3").html("Show SQL");
	}
}

function clearImg(table, column, id, title) {
	if (confirm("Are you sure you want to clear the " + title.toLowerCase() + " field?  It will be done immediately.")) {
		ajax_set(table, column, id);
		$('div.field.' + column + ' img.preview').slideUp();
		$('div.field.' + column + ' a.clear_img').slideUp();
	}
	return false;
}