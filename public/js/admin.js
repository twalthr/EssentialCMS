$(document).ready(function(){
	$('.propagateChecked').change(function() {
		var el = $(this);
		el.parent().find('input[type="checkbox"]').prop('checked', el.prop('checked'));
	});
});