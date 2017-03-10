$(document).ready(function(){
	var rootUrl = $('head script[src$="/js/admin.js"]').prop('src').slice(0,-12);
	// --------------------------------------------------------------------------------------------
	// General functionality for administration
	// --------------------------------------------------------------------------------------------

	$(document).on('change', '.propagateChecked', function() {
		var el = $(this);
		el.parent().find('input[type="checkbox"]').prop('checked', el.prop('checked'));
	});
	$(document).on('change', '.enableButtonsIfChecked input[type="checkbox"],' + 
			'.enableButtonsIfChecked input[type="radio"]', function() {
		var list = $(this).closest('.enableButtonsIfChecked');
		var disabled = list.find('input[type="checkbox"]:checked, input[type="radio"]:checked')
			.length == 0;
		list.siblings('.buttonSet').find('button').prop('disabled', disabled);
	});
	$(document).on('click', '.disableListIfClicked', function() {
		var list = $(this).closest('.buttonSet').prev();
		list.find('input[type="checkbox"], input[type="radio"]').prop('disabled', true);
		// hide add button
		list.parent().find('.addButton').addClass('hidden');
		// hide button of buttonSet
		list.siblings('.buttonSet').find('button').addClass('hidden');
	});
	$(document).on('click', 'button', function(e) {
		e.preventDefault();
	});

	// --------------------------------------------------------------------------------------------
	// Visualize fields for administration
	// --------------------------------------------------------------------------------------------

	$(document).on('click', '.tabBox .tabs a', function() {
		var tab = $(this).parent();
		tab.siblings().removeClass('current');
		tab.addClass('current');
		var tabContent = tab.closest('.tabBox').find('.tabContent .tab');
		tabContent.addClass('hidden');
		tabContent.find(':input').prop('disabled', true);
		var openedTab = $(tabContent.get(tab.index()));
		openedTab.removeClass('hidden');
		openedTab.find(':input:not(.neverEnable)').prop('disabled', false);
	});
	$(document).on('click', '.arrayElementOptions .remove', function() {
		var button = $(this);
		button.closest('.arrayElement').remove();
	});
	$(document).on('click', '.arrayOptions .add', function() {
		var button = $(this);
		var newArrayElement = button
				.siblings('.template')
				.clone(true);
		newArrayElement.find(':input:not(.neverEnable)').prop('disabled', false);
		newArrayElement.find('.hidden :input').prop('disabled', true);
		newArrayElement.removeClass('hidden');
		button.parent().before(newArrayElement);
	});
	$(document).on('click', '.pageSelectionButton', function() {
		var button = $(this);
		var idInput = button.siblings('.pageSelectionId');
		var lightboxOpened = function() {
			$('input:checked').trigger('change');
			$('.dialog-window .selectPage').click(function() {
				var page = $('.dialog-window input[name="page"]:checked');
				var label = page.siblings('label');
				idInput.val(page.val());
				button.siblings('.pageSelectionName').val(
					label.text().trim() + ' / ' + page.val());
				closeLightbox();
			});
		};
		openLightboxWithUrl(rootUrl + '/select-page-dialog/' + idInput.val(),
			true,
			lightboxOpened);
	});
});

function enableList(button) {
	var list = button.closest('.dialog-box').siblings('.enableButtonsIfChecked');
	list.find('input[type="checkbox"]').prop('disabled', false);
}

function openButtonSetDialog(button, message, showElements) {
	var dialog = button.parent().next();
	dialog.removeClass('hidden');
	dialog.find('.dialog-message').text(message);

	var elements = dialog.find(showElements);
	elements.removeClass('hidden');
	// elements.filter(':input').prop('disabled', false);

	var cancelButton = dialog.find('.cancel');
	cancelButton.removeClass('hidden');
	cancelButton.off('click');
	cancelButton.click(function(e) {
		e.preventDefault();
		// hide dialog
		dialog.addClass('hidden');
		elements.addClass('hidden');
		// elements.filter(':input').prop('disabled', true);

		// enable list if it was disabled
		var buttonSet = dialog.parent().find('.buttonSet');
		buttonSet.find('button').removeClass('hidden');
		buttonSet.prev().find('input[type="checkbox"]').prop('disabled', false);
		buttonSet.parent().find('.addButton').removeClass('hidden');
	});
}

function generateIdentifierFromString(str) {
	return str
		.toLowerCase()
		.replace(/\s/g,'-')
		.replace(/([^.:0-9a-zA-Z+_-]|[^0-9a-zA-Z]$)/g,'')
		.replace(/-+/g, '-')
		.replace(/-+$/g, '');
}

function generateDate() {
	var date = new Date();
	date.setHours(date.getHours() - (date.getTimezoneOffset() / 60));
	return date.toISOString().slice(0, 19).replace('T', ' ');
}

function openLightboxWithHtml(html, allowClosing, lightboxOpened) {
	var dialog = $('<div>', {
		'class': 'lightbox-overlay-dialog'
	});
	dialog.append($.parseHTML(html, null, true));

	// insert overlay
	var overlay = $('<div>', {
		'class': 'lightbox-overlay',
		'click': function() {
			if (allowClosing) {
				closeLightbox();
			}
		}
	});
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

	overlay.animate({ opacity: 0.6 }, 200, 'linear', function() {
		// adapt dialog to dimensions of the window, so that the component is centered
		dialog.css({
			'top': '50%',
			'left': '50%',
			'margin-top': -(dialog.outerHeight() / 2),
			'margin-left': -(dialog.outerWidth() / 2),
			'max-height': $(window).height() - 40,
			'max-width': Math.min($(window).width() - 40, 978)
		})
		// make the dialog visible
		.animate({ opacity: 1 }, 400, 'linear', lightboxOpened);

		// allow closing with ESC
		if (allowClosing) {
			$(document).keyup(lightboxEscapeCallback);
			dialog.find('.lightbox-close').click(closeLightbox);
		}

		// add refresh callbacks
		$(window).scroll(lightboxRefresh);
		$(window).resize(lightboxRefresh);
		lightboxRefresh();
	});
}

function lightboxEscapeCallback(e) {
	if (e.keyCode == 27) {
		closeLightbox();
	}
}

function lightboxRefresh() {
	var dialog = $('.lightbox-overlay-dialog');
	dialog.css({
		'margin-top': (dialog.outerHeight() < $(window).height()) ? 
			-(dialog.outerHeight() / 2) + $(window).scrollTop() : +(dialog.outerHeight() / 2),
		'margin-left': (dialog.outerWidth() < $(window).width()) ? 
			-(dialog.outerWidth() / 2) + $(window).scrollLeft() : +(dialog.outerWidth() / 2),
		'max-height': $(window).height() - 40,
		'max-width': Math.min($(window).width() - 40, 978)
	});
}

function openLightboxWithUrl(targetUrl, allowClosing, lightboxOpened) {
	$.ajax({
		type: 'GET',
		url: targetUrl,
		dataType: 'html',
		success: function(data, textStatus, jqXHR) {
			openLightboxWithHtml(data, allowClosing, lightboxOpened);
		},
		error: function(jqXHR, textStatus, errorThrown) {
			alert('Error');
		}
	});
}

function closeLightbox() {
	$('.lightbox-overlay-dialog').animate({ opacity: 0 }, 200, 'linear', function() {
		$(this).remove();
		// remove overall overlay
		$('.lightbox-overlay').animate({ opacity: 0 }, 400, 'linear', function() {
				$(this).remove();
				$(window).off('scroll', lightboxRefresh);
				$(window).off('resize', lightboxRefresh);
				$(document).off('keyup', lightboxEscapeCallback);
		});
	});
}