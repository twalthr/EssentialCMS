<script>
	var fileManager = {
		_MODES: {
			LIST: 1,
			SMALL_THUMBNAILS: 2,
			LARGE_THUMBNAILS: 4
		},
		_mediaArea: null,
		_viewMode: 0,
		// persisted content that will be shown
		_persistedContent: [],
		// current directory path
		_currentPath: '/',
		// offset of current file path in persistet content
		_currentPathOffset: 0,
		// current attachment parent
		_currentAttachmentContent: null,
		// current attachment path
		_currentAttachmentPath: '/',
		// offset of current  attachment file path in persistet content
		_currentAttachmentPathOffset: 0,
		// content of current directory path
		_currentContent: [],
		// flag if we are in edit mode
		_editMode: false,
		// collect currently selected content
		_currentSelectedContent: [],

		// public functions
		init: function (persistedContent, mediaArea) {
			this._persistedContent = persistedContent;
			this._mediaArea = mediaArea;
			this._viewMode = this._MODES.LIST;

			this._initUserInterface();
		},
		enableEditing: function () {
			this._editMode = true;
		},
		// private functions
		// all functions are more or less ordered according to the order they get called

		// insert and prepare UI elements
		_initUserInterface: function () {
			// add tabs for selecting the view
			var viewselection = $('<ul class="viewselection tabs">');
			viewselection.append(
				$('<li class="current">').append($('<a>').text("<?php $this->text('VIEW_LIST'); ?>"))
			);
			viewselection.append(
				$('<li>').append($('<a>').text("<?php $this->text('VIEW_SMALL_THUMBNAILS'); ?>"))
			);
			viewselection.append(
				$('<li>').append($('<a>').text("<?php $this->text('VIEW_LARGE_THUMBNAILS'); ?>"))
			);
			this._mediaArea.append(viewselection);

			var tab = $('<div class="tab">');

			// add locator
			tab.append($('<ul class="locator">'));

			// add view area
			tab.append($('<div class="viewarea">'));

			this._mediaArea.append($('<div class="tabContent">').append(tab));

			this._refresh();
		},

		_getViewArea: function () {
			return $('.viewarea', this._mediaArea);
		},

		_getViewSelection: function () {
			return $('.viewselection', this._mediaArea);
		},

		_getLocator: function () {
			return $('.locator', this._mediaArea);
		},

		_refresh: function () {
			this._getLocator().empty();
			this._getViewArea().empty();

			this._updateCurrentContent();
			if (this._currentContent.length === 0) {
				this._showEmptyContent();
			} else {
				this._showNonEmptyContent();
			}
		},

		_showList: function () {
			var that = this;

			// add list
			var table = $('<ul class="tableLike enableButtonsIfChecked">');
			for (var i = 0; i < this._currentContent.length; i++) {
				var content = this._currentContent[i];
				var item = this._extractItemFromPath(this._currentPath, content.internalName);
				var element = $('<li class="rowLike">');

				// add selection of sub content
				var checkbox = $('<input type="checkbox" id="item' + i + '">');
				(function (content, item) {
					checkbox.change(function (e) {
						that._selectSubContent(item, content, $(this).is(':checked'));
					});
				})(content, item); // force object copy
				element.append(checkbox);
				var label = $('<label for="item' + i + '" class="checkbox hidden showInEditMode">')
					.text(item.name);
				if (this._editMode) {
					label.removeClass('hidden');
				}
				element.append(label);
				if (item.isFile) {
					var link = "<?php echo $config->getPublicRoot(); ?>/admin/medium/" + content.mid;
					var size = this._humanFileSize(content.size);
					var modified = (content.originalModified === null) ?
						content.lastChanged : content.originalModified;
					element.append($('<a class="componentLink" href="' + link + '">').text(item.name));
					var attachments = this._getAttachment(content.mid);
					if (attachments.length > 0) {
						(function (content) {
							element.append(
								$('<a>')
									.text("<?php $this->text('WITH_ATTACHMENT'); ?>")
									.click(function (e) {
										e.preventDefault();
										that._openSubAttachment(content, '/');
									})
							);
						})(content); // force object copy
					}
					element.append($('<span class="rowAdditionalInfo">').
						text(size + ' / ' + modified));
				} else {
					(function (directory) {
						element.append($('<a class="componentLink directory">')
							.text(item.name)
							.click(function (e) {
								e.preventDefault();
								that._openSubDirectory(directory);
							})
						);
					})(that._currentPath + item.name + '/'); // force string copy
				}
				table.append(element);
			}
			this._getViewArea().append(table);

			// add buttons
			var buttons = $('<div class="buttonSet hidden showInEditMode">');
			if (this._editMode) {
				buttons.removeClass('hidden');
			}
			buttons.append(
				$('<button class="disableListIfClicked" disabled>')
					.text("<?php $this->text('RENAME'); ?>")
					.click(function() {
						$('.mediaOperation').val('move');
						// show first name in field
						$('.newName').val(
							that._extractItemFromPath(
								that._currentPath,
								that._currentSelectedContent[0].internalName).name
						);
						openButtonSetDialog($(this),
							'<?php $this->text('RENAME_QUESTION'); ?>',
							'.newName, .renameConfirm');
					})
			);
			buttons.append($('<button class="disableListIfClicked" disabled>')
				.text("<?php $this->text('ATTACH'); ?>")
				.click(function() {
					$('.mediaOperation').val('attach');
					that._generateAttachSelect();
					openButtonSetDialog($(this),
						'<?php $this->text('ATTACH_QUESTION'); ?>',
						'.attachSelect, .attachConfirm');
				})
			);
			buttons.append($('<button class="disableListIfClicked" disabled>')
				.text("<?php $this->text('DETACH'); ?>"));
			buttons.append($('<button class="disableListIfClicked" disabled>')
				.text("<?php $this->text('MOVE'); ?>"));
			buttons.append($('<button class="disableListIfClicked" disabled>')
				.text("<?php $this->text('COPY'); ?>"));
			buttons.append($('<button class="disableListIfClicked" disabled>')
				.text("<?php $this->text('EXPORT'); ?>"));
			buttons.append(
				$('<button class="disableListIfClicked" disabled>')
					.text("<?php $this->text('DELETE'); ?>")
					.click(function() {
						$('.mediaOperation').val('delete');
						openButtonSetDialog($(this),
							'<?php $this->text('DELETE_QUESTION'); ?>',
							'.deleteConfirm');
					})
			);
			this._getViewArea().append(buttons);

			// add dialog
			var dialog = $('<div class="dialog-box hidden">');
			dialog.append($('<div class="dialog-message">'));
			var dialogFields = $('<div class="fields">');
			dialogFields.append($('<input type="text" class="newName hidden" maxlength="512" minlength="1">'));

			dialogFields.append($('<select name="attachTarget" class="attachSelect hidden">'));
			dialog.append(dialogFields);
			var dialogOptions = $('<div class="options">');
			dialogOptions.append(
				$('<button class="hidden renameConfirm">')
					.text("<?php $this->text('RENAME'); ?>")
					.click(function() {
						var result = that._generatedRenamePaths($(this).closest('form'));
						if (result) {
							that._submitSelectedContent($(this).closest('form'));
						}
					})
			);
			dialogOptions.append(
				$('<button class="hidden deleteConfirm">')
					.text("<?php $this->text('DELETE'); ?>")
					.click(function() {
						that._submitSelectedContent($(this).closest('form'));
					})
			);
			dialogOptions.append(
				$('<button class="hidden attachConfirm">')
					.text("<?php $this->text('ATTACH'); ?>")
					.click(function() {
						that._submitSelectedContent($(this).closest('form'));
					})
			);
			dialogOptions.append($('<button class="hidden cancel">').text("<?php $this->text('CANCEL'); ?>"));
			dialog.append(dialogOptions);
			this._getViewArea().append(dialog);
		},
		_generateAttachSelect: function () {
			var basePath = this._currentPath;
			var select = $('.attachSelect');
			select.empty();
			contentLoop:
			for (var i = 0; i < this._currentContent.length; i++) {
				var content = this._currentContent[i];
				var item = this._extractItemFromPath(basePath, content.internalName);
				if (item.isFile) {
					// item must not be selected
					for (var j = 0; j < this._currentSelectedContent.length; j++) {
						if (content.mid === this._currentSelectedContent[j].mid) {
							continue contentLoop;
						}
					}
					select.append($('<option>').val(content.mid).text(item.name));
				}
			}
			return select;
		},
		_generatedRenamePaths: function (form) {
			var name = $('.newName').val();
			if (name.length === 0 || name.indexOf('/') !== -1) {
				return false;
			}
			var basePath = this._currentPath;
			for (var i = 0; i < this._currentSelectedContent.length; i++) {
				var content = this._currentSelectedContent[i];
				var item = this._extractItemFromPath(basePath, content.internalName);
				var oldPartOfPath = basePath + item.name;
				var newName = basePath + name + content.internalName.substring(oldPartOfPath.length);
				form.append($('<input type="hidden" name="path[]">').val(newName));
			}
			return true;
		},
		_getAttachment: function (mid) {
			var attachment = [];
			// attachments start at root
			for (var i = 0; i < this._persistedContent.length; i++) {
				var content = this._persistedContent[i];
				if (content.parent === null) {
					break;
				} else if (content.parent === mid) {
					attachment.push(content);
				}
			}
			return attachment;
		},
		_submitSelectedContent: function (form) {
			for (var i = 0; i < this._currentSelectedContent.length; i++) {
				var content = this._currentSelectedContent[i];
				form.append($('<input type="hidden" name="media[]">').val(content.mid));
			}
			form.submit();
		},
		_selectSubContent: function (item, content, checked) {
			if (item.isFile) {
				if (checked) {
					this._currentSelectedContent.push(content);
				} else {
					var index = this._currentSelectedContent.indexOf(content);
					this._currentSelectedContent.splice(index, 1);
				}
			} else {
				for (var i = this._currentPathOffset; i < this._persistedContent.length; i++) {
					var subcontent = this._persistedContent[i];
					if (subcontent.internalName.startsWith(item.basePath + item.name + '/')) {
						if (checked) {
							this._currentSelectedContent.push(subcontent);
						} else {
							var index = this._currentSelectedContent.indexOf(subcontent);
							this._currentSelectedContent.splice(index, 1);
						}
					}
				}
			}
		},
		// Source:
		// http://stackoverflow.com/questions/10420352/converting-file-size-in-bytes-to-human-readable
		_humanFileSize: function (bytes, si) {
			var thresh = si ? 1000 : 1024;
			if(Math.abs(bytes) < thresh) {
				return bytes + ' B';
			}
			var units = si
				? ['kB','MB','GB','TB','PB','EB','ZB','YB']
				: ['KiB','MiB','GiB','TiB','PiB','EiB','ZiB','YiB'];
			var u = -1;
			do {
				bytes /= thresh;
				++u;
			} while(Math.abs(bytes) >= thresh && u < units.length - 1);
			return bytes.toFixed(1) + ' ' + units[u];
		},

		_showSmallThumbnails: function () {

		},

		_showLargeThumbnails: function () {

		},

		_showNonEmptyContent: function () {
			this._getViewSelection().removeClass('hidden');
			this._getLocator().removeClass('hidden');

			this._showLocator();

			switch (this._viewMode) {
				case this._MODES.LIST:
					this._showList();
					break;

				case this._MODES.SMALL_THUMBNAILS:
					this._showSmallThumbnails();
					break;

				case this._MODES.LARGE_THUMBNAILS:
					this._showLargeThumbnails();
					break;
			}
		},

		_showEmptyContent: function () {
			this._getViewSelection().addClass('hidden');
			this._getLocator().addClass('hidden');
			this._getViewArea().append($('<p class="empty">').text("<?php $this->text('NO_MEDIA'); ?>"));
		},

		_showLocator: function () {
			// show locator for regular directory
			var splitPath = this._currentPath.split('/');
			var fullPath = '';
			var that = this;
			for (var i = 0; i < splitPath.length - 1; i++) {
				var directory = splitPath[i];
				fullPath += directory + '/';
				// root directory
				if (directory === '') {
					directory = "<?php $this->text('MEDIA_ROOT'); ?>";
				}

				if (i > 0) {
					this._getLocator().append(
						$('<li>').append($('<span class="pathseperator">'))
					);
				}

				if (i === splitPath.length - 2) {
					this._getLocator().append(
					$('<li>').append(
						$('<button class="selected">').text(directory))
					);
				} else {
					(function (path) {
						that._getLocator().append(
						$('<li>').append(
							$('<button>').text(directory).click(function () {
								that._openSuperDirectory(path);
							}))
						);
					})(fullPath); // force string copy
				}
			}

			// show locator for attachment directory
			if (this._currentAttachmentContent !== null) {
				var splitPath = this._currentAttachmentPath.split('/');
				var fullPath = '';
				var that = this;
				for (var i = 0; i < splitPath.length - 1; i++) {
					var directory = splitPath[i];
					fullPath += directory + '/';
					// root directory
					if (directory === '') {
						var item = this._extractItemFromPath(this._currentPath, this._currentAttachmentContent.internalName);
						directory = item.name;
					}

					this._getLocator().append(
						$('<li>').append($('<span class="pathseperator">'))
					);
					if (i === splitPath.length - 2) {
						this._getLocator().append(
						$('<li>').append(
							$('<button class="selected">').text(directory))
						);
					} else {
						(function (path) {
							that._getLocator().append(
							$('<li>').append(
								$('<button>').text(directory).click(function () {
									that._openSuperDirectory(path);
								}))
							);
						})(fullPath); // force string copy
					}
				}
			}
		},

		_openSubAttachment: function (parent, directory) {
			this._currentAttachmentContent = parent;
			this._currentAttachmentPath = directory;
			for (var i = this._currentAttachmentPathOffset; i < this._persistedContent.length; i++) {
				var content = this._persistedContent[i];
				if (content.parent === parent.mid && content.internalName.startsWith(directory)) {
					this._currentAttachmentPathOffset = i;
					break;
				}
			}
			this._refresh();
		},

		_openSuperDirectory: function (directory) {
			this._currentPath = directory;
			var oldOffset = this._currentPathOffset;
			this._currentPathOffset = 0;
			for (var i = oldOffset; i >= 0; i--) {
				var content = this._persistedContent[i];
				if (!content.internalName.startsWith(directory)) {
					this._currentPathOffset = i + 1;
					break;
				}
			}
			this._refresh();
		},

		_openSubDirectory: function (directory) {
			this._currentPath = directory;
			for (var i = this._currentPathOffset; i < this._persistedContent.length; i++) {
				var content = this._persistedContent[i];
				if (content.internalName.startsWith(directory)) {
					this._currentPathOffset = i;
					break;
				}
			}
			this._refresh();
		},

		_updateCurrentRegularContent: function () {
			// we sort by type first
			var currentDirs = [];
			var currentFiles = [];
			var directory = this._currentPath;
			var lastSubdirectory = null;
			for (var i = this._currentPathOffset; i < this._persistedContent.length; i++) {
				var content = this._persistedContent[i];
				var contentName = content.internalName;
				// only show non-attached  items
				if (content.parent === null) {
					if (contentName.startsWith(directory)) {
						var item = this._extractItemFromPath(directory, contentName);
						// file
						if (item.isFile) {
							currentFiles.push(content);
						}
						// subdirectory
						else {
							if (lastSubdirectory !== item.name) {
								lastSubdirectory = item.name;
								currentDirs.push(content);
							}
						}
					} else {
						break;
					}
				}
			}
			this._currentContent = $.merge(currentDirs, currentFiles);
		},

		_updateCurrentAttachmentContent: function () {
			// we sort by type first
			var currentDirs = [];
			var currentFiles = [];
			var directory = this._currentAttachmentPath;
			var lastSubdirectory = null;
			for (var i = this._currentAttachmentPathOffset; i < this._persistedContent.length; i++) {
				var content = this._persistedContent[i];
				var contentName = content.internalName;
				// no attachments left
				if (content.parent === null) {
					break;
				}
				if (content.parent === this._currentAttachmentContent.mid) {
					if (contentName.startsWith(directory)) {
						var item = this._extractItemFromPath(directory, contentName);
						// file
						if (item.isFile) {
							currentFiles.push(content);
						}
						// subdirectory
						else {
							if (lastSubdirectory !== item.name) {
								lastSubdirectory = item.name;
								currentDirs.push(content);
							}
						}
					} else {
						break;
					}
				}
			}
			this._currentContent = $.merge(currentDirs, currentFiles);
		},

		_updateCurrentContent: function () {
			this._currentContent = [];
			this._currentSelectedContent = [];
			// update from directory content
			if (this._currentAttachmentContent === null) {
				this._updateCurrentRegularContent();
			}
			// update from attachment content
			else {
				this._updateCurrentAttachmentContent();
			}
		},

		_extractItemFromPath: function (basePath, path) {
			var relativePath = path.substring(basePath.length);
			var index = relativePath.indexOf('/');
			// file
			if (index === -1) {
				return {
					'isFile': true,
					'name': relativePath,
					'basePath': basePath
				};
			}
			// subdirectory
			else {
				var subdirectory = relativePath.substring(0, index);
				return {
					'isFile': false,
					'name': subdirectory,
					'basePath': basePath
				};
			}
		},

		// removes the dialog
		_removeDialog: function () {
			$('.dialog-box', this._getViewArea()).remove();
		},

		// show dialog
		_showDialog: function (message, buttons, checkboxText, callback) {
			var dialog = $('<div class="dialog-box">');
			dialog.append($('<div class="dialog-message">').text(message))
			var options = $('<div class="options">');

			if (checkboxText !== null) {
				var wrapper = $('<div class="checkboxWrapper">');
				var checkbox = $('<input type="checkbox" id="remember">');
				wrapper.append(checkbox);
				wrapper.append($('<label for="remember" class="checkbox">')
					.text(checkboxText));
				wrapper.append(checkboxText);
				options.append(wrapper);
			}
			
			for (var i = 0; i < buttons.length; i++) {
				options.append(
					$('<button>')
						.text(buttons[i])
						.click(function () {
							if (checkboxText !== null) {
								callback($(this).index() - 1, checkbox.is(':checked'));
							} else {
								callback($(this).index(), false);
							}
						}
					)
				);
			};
			options.append($('<button class="cancel">').text("<?php $this->text('CANCEL'); ?>"));
			dialog.append(options);
			this._getViewArea().append(dialog);
		},

		// general function for handlung and output a error message
		_handleError: function (message) {
			var that = this;
			this._showDialog(message, [ "<?php $this->text('RESTART'); ?>" ], null, function () {
				that._redirect = true;
				window.location.reload(); 
			});
		}
	};
</script>
