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

	$('.pageSelectionWrapper').each(function() {
		var wrapper = $(this);
		var selectButton = wrapper.find('.pageSelectionButton');
		var deleteButton = wrapper.find('.deleteButton');
		var openButton = wrapper.find('.openButton');
		var selectedId = wrapper.find('.pageSelectionId');
		var selectedName = wrapper.find('.pageSelectionName');

		if (selectedId.val().length == 0) {
			deleteButton.addClass('hidden');
			openButton.addClass('hidden');
		}

		selectButton.on('click', function() {
			var lightboxOpened = function() {
				$('input:checked').trigger('change');
				$('.dialog-window .selectPage').click(function() {
					var page = $('.dialog-window input[name="page"]:checked');
					selectedId.val(page.val());
					selectedName.val(page.val());
					deleteButton.removeClass('hidden');
					openButton.removeClass('hidden');
					closeLightbox();
				});
			};
			openLightboxWithUrl(
				rootUrl + '/select-page-dialog/' + selectedId.val(),
				true,
				lightboxOpened);
		});

		deleteButton.on('click', function() {
			selectedId.val('');
			selectedName.val('');
			deleteButton.addClass('hidden');
			openButton.addClass('hidden');
		});

		openButton.on('click', function() {
			window.open(rootUrl + '/page/' + selectedId.val(), '_self');
		});
	});

	$(document).on('change', '.rangeWrapper input[type="range"]', function() {
		var el = $(this);
		el.parent().find('.rangeValue').val(el.val());
	});
	$('.rangeWrapper input[type="range"]').trigger('change');

	$(document).on('click', '.idWrapper button', function() {
		var el = $(this);
		var id = el.parent().find('input');
		var form = el.closest('form');
		var title = form.find('input[type="text"][name$="_title"]');
		var str;
		// use title
		if (title.length === 1 && title.val().length > 0) {
			str = title.val();
		}
		// use ID field
		else {
			str = id.val();
		}
		id.val(generateIdentifierFromString(str));
	});

	$('.encryptionWrapper').each(function() {
		var wrapper = $(this);
		var value = wrapper.find('input[type=hidden]');
		var text = wrapper.find('input[type=text], textarea');
		var password1 = wrapper.find('input[type=password]').first();
		var password2 = wrapper.find('input[type=password]').last();
		var encryptButton = wrapper.find('.encryptButton');
		var decryptButton = wrapper.find('.decryptButton');
		var deleteButton = wrapper.find('.deleteButton');
		var shortPasswordError = wrapper.find('.shortPassword');
		var unequalPasswordsError = wrapper.find('.unequalPasswords');
		var wrongPasswordError = wrapper.find('.wrongPassword');
		var unsupportedBrowserError = wrapper.find('.unsupportedBrowser');

		var resetInterface = function() {
			text.prop('disabled', true);
			decryptButton.addClass('hidden');
			encryptButton.addClass('hidden');
			deleteButton.addClass('hidden');
			password1.addClass('hidden');
			password1.val('');
			password2.addClass('hidden');
			password2.val('');
		}

		// reset the content to allow deleting invalid entries
		var resetContent = function() {
			resetInterface();
			value.val('');
			text.val('');
			text.prop('disabled', false);
			encryptButton.removeClass('hidden');
		}

		// copy value
		text.val(value.val());

		// test support for Web Crypto API
		if (window.crypto === undefined || window.crypto.subtle === undefined ||
				window.TextEncoder === undefined || window.TextDecoder === undefined ||
				window.atob === undefined || window.btoa === undefined) {
			unsupportedBrowserError.removeClass('hidden');
			resetInterface();
			return;
		}

		// disable input if there is encrypted content
		if (text.val().length > 0) {
			text.prop('disabled', true);
			encryptButton.addClass('hidden');
		} else {
			decryptButton.addClass('hidden');
			deleteButton.addClass('hidden');
		}

		// delete action
		deleteButton.on('click', function() {
			wrongPasswordError.addClass('hidden');
			resetContent();
		});

		// encryption action
		encryptButton.on('click', function() {
			shortPasswordError.addClass('hidden');
			unequalPasswordsError.addClass('hidden');
			// text enter state
			if (!text.prop('disabled')) {
				text.prop('disabled', true);
				password1.removeClass('hidden');
				password2.removeClass('hidden');
			}
			// password enter state
			else {
				// verify password
				if (password1.val().length < 8) {
					shortPasswordError.removeClass('hidden');
				} else if (password1.val() !== password2.val()) {
					unequalPasswordsError.removeClass('hidden');
				}
				// encrypt
				else {
					// encode text
					const plainText = new TextEncoder().encode(text.val());

					// encode password
					const encodedPassword = new TextEncoder().encode(password1.val());

					// generate salt
					const salt = new Uint8Array(64);
					crypto.getRandomValues(salt);
					const saltString = bufferToBase64(salt);

					// add salt to password
					const saltedPassword = new Uint8Array(encodedPassword.length + salt.length);
					saltedPassword.set(encodedPassword, 0);
					saltedPassword.set(salt, encodedPassword.length);

					// generate init vector
					const iv = new Uint8Array(12);
					crypto.getRandomValues(iv);
					const ivString = bufferToBase64(iv);

					// perform encryption
					const alg = {name: 'AES-GCM', iv: iv};
					crypto.subtle
						// hash
						.digest('SHA-256', saltedPassword)
						// import key
						.then(function(hashedPassword) {
							return crypto.subtle.importKey(
								'raw',
								hashedPassword,
								alg,
								false,
								['encrypt']);
						})
						// encrypt
						.then(function(key) {
							return crypto.subtle.encrypt(alg, key, plainText);
						})
						// save
						.then(function(encrypted) {
							const encryptedString = bufferToBase64(new Uint8Array(encrypted));
							text.val(
								'AES-GCM|SHA-256|' + saltString + '|' + ivString + '|' + encryptedString + '|'
							);
							value.val(text.val());
							// reset
							resetInterface();
							// enable decrypt mode
							decryptButton.removeClass('hidden');
							deleteButton.removeClass('hidden');
						})
						// error
						.catch(function() {
							unsupportedBrowserError.removeClass('hidden');
							resetInterface();
						});
				}
			}
		});

		// decryption action
		decryptButton.on('click', function() {
			shortPasswordError.addClass('hidden');
			wrongPasswordError.addClass('hidden');
			// encrypted state
			if (password1.hasClass('hidden')) {
				password1.removeClass('hidden');
				deleteButton.addClass('hidden');
			}
			// password enter state
			else {
				// verify password
				if (password1.val().length < 8) {
					shortPasswordError.removeClass('hidden');
				}
				// decrypt
				else {
					// decode components
					const components = text.val().split('|');

					// check components
					if (components.length != 6 || components[0] !== 'AES-GCM' || components[1] !== 'SHA-256') {
						resetContent();
						return;
					}

					try {
						const salt = base64ToBuffer(components[2]);
						const iv = base64ToBuffer(components[3]);
						const encrypted = base64ToBuffer(components[4]);
					} catch (e) {
						resetContent();
						return;
					}

					// encode password
					const encodedPassword = new TextEncoder().encode(password1.val());

					// add salt to password
					const saltedPassword = new Uint8Array(encodedPassword.length + salt.length);
					saltedPassword.set(encodedPassword, 0);
					saltedPassword.set(salt, encodedPassword.length);

					// perform decryption
					const alg = {name: 'AES-GCM', iv: iv};
					crypto.subtle
						// hash
						.digest('SHA-256', saltedPassword)
						// import key
						.then(function(hashedPassword) {
							return crypto.subtle.importKey(
								'raw',
								hashedPassword,
								alg,
								false,
								['decrypt']);
						})
						// decrypt
						.then(function(key) {
							return crypto.subtle.decrypt(alg, key, encrypted);
						})
						// save
						.then(function(decrypted) {
							const plainText = new TextDecoder().decode(new Uint8Array(decrypted));
							text.val(plainText);
							value.val(''); // only the empty string is a valid plain value
							// reset
							resetInterface();
							// enable encrypt mode
							text.prop('disabled', false);
							encryptButton.removeClass('hidden');
						})
						// error
						.catch(function() {
							wrongPasswordError.removeClass('hidden');
							resetInterface();
							decryptButton.removeClass('hidden');
							deleteButton.removeClass('hidden');
						});
				}
			}
		});
	});
});

// --------------------------------------------------------------------------------------------
// Helper functions
// --------------------------------------------------------------------------------------------

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

function bufferToBase64(buffer) {
	var binary = '';
	var bytes = new Uint8Array(buffer);
	var len = bytes.byteLength;
	for (var i = 0; i < len; i++) {
		binary += String.fromCharCode(bytes[i]);
	}
	return window.btoa(binary);
}

function base64ToBuffer(base64) {
	var binary =  window.atob(base64);
	var len = binary.length;
	var bytes = new Uint8Array(len);
	for (var i = 0; i < len; i++) {
		bytes[i] = binary.charCodeAt(i);
	}
	return bytes;
}