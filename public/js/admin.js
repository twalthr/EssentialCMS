$(document).ready(function(){
	$('.propagateChecked').change(function() {
		var el = $(this);
		el.parent().find('input[type="checkbox"]').prop('checked', el.prop('checked'));
	});
});

function generateIdentifierFromString(str) {
	return str
		.toLowerCase()
		.replace(/\s/g,'-')
		.replace(/([^.:0-9a-zA-Z+_-]|[^0-9a-zA-Z]$)/g,'');
}

function generateDate() {
	var date = new Date();
	date.setHours(date.getHours() - (date.getTimezoneOffset() / 60));
	return date.toISOString().slice(0, 19).replace('T', ' ');
}