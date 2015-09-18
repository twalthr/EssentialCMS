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

function openLightboxWithHtml(html, returnCallback) {
	var dialog = $('<div>', {
		'class': 'lightbox_overlay_dialog'
	});
	dialog.append($.parseHTML(html));

	// insert overlay
	var overlay = $('<div class="lightbox_overlay"></div>');
	$(window).scroll(function() {
		overlay.css({
			// scrollbars do not hide when resizing
			'left': ($(window).scrollLeft() == 1) ? 0 : $(window).scrollLeft(),
			'top': ($(window).scrollTop() == 1) ? 0 : $(window).scrollTop()
		});
	});
	$('body').append(overlay);
	$(window).trigger('scroll');

	$('body').append(dialog);

	$('.lightbox_overlay').animate({ opacity: 0.6 }, 200, 'linear', function() {
		// adapt dialog to dimensions of the window, so that the component is centered
		dialog.css({
			'top': '50%',
			'left': '50%',
			'margin-top': -(dialog.outerHeight() / 2),
			'margin-left': -(dialog.outerWidth() / 2)
		})
		// make the dialog visible
		.animate({ opacity: 1 }, 400, 'linear');

		$(window).scroll( function() {
			dialog.css({
				'margin-top': (dialog.outerHeight() < $(window).height()) ? 
					-(dialog.outerHeight() / 2) + $(window).scrollTop() : +(dialog.outerHeight() / 2),
				'margin-left': (dialog.outerWidth() < $(window).width()) ? 
					-(dialog.outerWidth() / 2) + $(window).scrollLeft() : +(dialog.outerWidth() / 2)
			});
		});
		$(window).trigger('scroll');
	});
}

function openLightboxWithUrl(targetUrl, returnCallback) {
	$.ajax({
		type: 'GET',
		url: targetUrl,
		dataType: 'html',
		success: function(data, textStatus, jqXHR) {
			openLightboxWithHtml(data, returnCallback);
		},
		error: function(jqXHR, textStatus, errorThrown) {
			alert('Error');
		}
	});
}