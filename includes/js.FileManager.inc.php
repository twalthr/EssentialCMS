<script>
	var fileManager = {
		_MODES: {
			LIST: 1,
			SMALL_THUMBNAILS: 2,
			LARGE_THUMBNAILS: 4
		},
		_CONFLICT_STRATEGIES: {
			NO_STRATEGY: 0,
			COEXIST: 1,
			REPLACE: 2,
			SKIP: 3
		},
		_mediaArea: null,
		_viewMode: 0,
		// persisted content that will be shown
		_persistedContent: [],
		// current directory path
		_currentRegularPath: '/',
		// offset of current file path in persistet content
		_currentRegularPathOffset: 0,
		// current attachment parent
		_currentAttachmentContent: null,
		// current attachment path
		_currentAttachmentPath: '/',
		// offset of current  attachment file path in persistet content
		_currentAttachmentPathOffset: 0,
		// content of current directory path
		_currentContent: [],
		// selected content including subcontent
		_selectedContent: [],
		// flag if we are in edit mode
		_editMode: false,
		// content to be renamed, moved, copied, attached etc.
		_contentToBeModified: [],
		// paths for renaming, moving, copying
		_pathsForModification: [],
		// content to be deleted due to replacement or deletion
		_contentToBeDeleted: [],
		// stratgy for file conflicts, chosen by user for all future files
		_globalFileStrategy: 0,
		// stratgy for directory conflicts, chosen by user for all future directories
		_globalDirStrategy: 0,

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

		_inAttachment: function () {
			return this._currentAttachmentContent !== null;
		},

		_getCurrentPath: function () {
			if (this._inAttachment()) {
				return this._currentAttachmentPath;
			} else {
				return this._currentRegularPath;
			}
		},

		_getCurrentOffset: function () {
			if (this._inAttachment()) {
				return this._currentAttachmentPathOffset;
			} else {
				return this._currentRegularPathOffset;
			}
		},

		_showList: function () {
			var that = this;

			// add list
			var table = $('<ul class="tableLike enableButtonsIfChecked">');
			for (var i = 0; i < this._currentContent.length; i++) {
				var content = this._currentContent[i];
				var item = this._extractItemFromPath(this._getCurrentPath(), content.internalName);
				var element = $('<li class="rowLike">');

				// add checkbox for selection
				var checkbox = $('<input type="checkbox" id="item' + i + '">');
				(function (content, item) {
					checkbox.change(function (e) {
						that._selectContent(item, content, $(this).is(':checked'));
					});
				})(content, item); // force object copy
				element.append(checkbox);
				var label = $('<label for="item' + i + '" class="checkbox hidden showInEditMode">')
					.text(item.name);
				if (this._editMode) {
					label.removeClass('hidden');
				}
				element.append(label);

				// add name
				if (item.isFile) {
					var link = "<?php echo $config->getPublicRoot(); ?>/admin/medium/" + content.mid;
					var size = this._humanFileSize(content.size);
					var modified = (content.originalModified === null) ?
						content.lastChanged : content.originalModified;
					element.append($('<a class="componentLink" href="' + link + '">').text(item.name));
					if (this._hasAttachment(content.mid)) {
						(function (content) {
							element.append(
								$('<a>')
									.text("<?php $this->text('WITH_ATTACHMENT'); ?>")
									.click(function (e) {
										e.preventDefault();
										that._openAttachment(content);
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
					})(that._getCurrentPath() + item.name + '/'); // force string copy
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
								that._getCurrentPath(),
								that._selectedContent[0].internalName).name
						);
						openButtonSetDialog($(this),
							'<?php $this->text('RENAME_QUESTION'); ?>',
							'.newName, .renameConfirm');
					})
			);
			if (!this._inAttachment()) {
				buttons.append(
					$('<button class="disableListIfClicked" disabled>')
						.text("<?php $this->text('MOVE'); ?>")
						.click(function () {
							$('.mediaOperation').val('move');
							that._generateCopyMoveSelect(false);
							openButtonSetDialog($(this),
								'<?php $this->text('MOVE_COPY_QUESTION'); ?>',
								'.copyMoveSelect, .newName, .moveConfirm');
						})
				);
				buttons.append(
					$('<button class="disableListIfClicked" disabled>')
						.text("<?php $this->text('COPY'); ?>")
						.click(function () {
							$('.mediaOperation').val('copy');
							that._generateCopyMoveSelect(true);
							openButtonSetDialog($(this),
								'<?php $this->text('MOVE_COPY_QUESTION'); ?>',
								'.copyMoveSelect, .newName, .copyConfirm');
						})
				);
				buttons.append($('<button class="disableListIfClicked" disabled>')
					.text("<?php $this->text('EXPORT'); ?>"));
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
			} else {
				buttons.append(
					$('<button class="disableListIfClicked" disabled>')
						.text("<?php $this->text('DETACH'); ?>")
						.click(function() {
							$('.mediaOperation').val('detach');
							openButtonSetDialog($(this),
								'<?php $this->text('DETACH_QUESTION'); ?>',
								'.detachConfirm');
						})
				);
			}
			
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
			dialogFields.append($('<select name="attachTarget" class="attachSelect hidden">'));
			dialogFields.append($('<select name="copyMoveTarget" class="copyMoveSelect hidden">'));
			dialogFields.append(
				$('<input type="text" class="newName hidden" maxlength="512" minlength="1">'));
			dialog.append(dialogFields);
			var dialogOptions = $('<div class="options">');
			dialogOptions.append(
				$('<button class="hidden renameConfirm">')
					.text("<?php $this->text('RENAME'); ?>")
					.click(function() {
						var result = that._generateRenamePaths($(this).closest('form'));
						if (result) {
							that._submitModifications();
						}
					})
			);
			dialogOptions.append(
				$('<button class="hidden deleteConfirm">')
					.text("<?php $this->text('DELETE'); ?>")
					.click(function() {
						that._contentToBeDeleted = that._selectedContent;
						that._submitModifications();
					})
			);
			dialogOptions.append(
				$('<button class="hidden attachConfirm">')
					.text("<?php $this->text('ATTACH'); ?>")
					.click(function() {
						that._contentToBeModified = that._selectedContent;
						that._submitModifications();
					})
			);
			dialogOptions.append(
				$('<button class="hidden detachConfirm">')
					.text("<?php $this->text('DETACH'); ?>")
					.click(function() {
						that._contentToBeModified = that._selectedContent;
						that._submitModifications();
					})
			);
			var copyMoveAction = function () {
				var target = $('.copyMoveSelect').val();
				if  (target === 'new') {
					var newName = $('.newName').val();
					if (newName.length < 1 || newName.indexOf('/') !== -1) {
						return;
					}
					target = that._getCurrentPath() + newName + '/';
				}
				that._removeDialog();
				that._preprocessCopyMove(target);
			};

			dialogOptions.append(
				$('<button class="hidden moveConfirm">')
					.text("<?php $this->text('MOVE'); ?>")
					.click(function() {
						copyMoveAction();
					})
			);
			dialogOptions.append(
				$('<button class="hidden copyConfirm">')
					.text("<?php $this->text('COPY'); ?>")
					.click(function() {
						copyMoveAction();
					})
			);
			dialogOptions.append($('<button class="hidden cancel">').text("<?php $this->text('CANCEL'); ?>"));
			dialog.append(dialogOptions);
			this._getViewArea().append(dialog);
		},
		_generateCopyMoveSelect: function (allowSameDirectory) {
			var select = $('.copyMoveSelect');
			var newDirInput = $('.newName');
			select.empty();

			// add new directory
			select.append(
				$('<option>')
					.val('new')
					.text("<?php $this->text('NEW_DIRECTORY'); ?>")
			);

			select.change(function () {
				if (select.val() === 'new') {
					newDirInput.removeClass('hidden');
				} else {
					newDirInput.addClass('hidden');
				}
			});

			var lastDirectory = null;
			var lastSplit = [];
			var tmpElement = $("<div>");
			for (var i = 0; i < this._persistedContent.length; i++) {
				var content = this._persistedContent[i];
				// skip attachments
				if (content.parent !== null) {
					continue;
				}

				var pos = content.internalName.lastIndexOf('/');
				var basePath = content.internalName.substring(0, pos);
				if (basePath !== lastDirectory) {
					var split = basePath.split('/');
					var diffLevel = 0;
					// find difference to previous directory
					for (; diffLevel < split.length && diffLevel < lastSplit.length; diffLevel++) {
						if (split[diffLevel] !== lastSplit[diffLevel]) {
							break;
						}
					}
					// add differences
					for (var j = diffLevel; j < split.length; j++) {
						// compute full path
						var fullPath = '';
						for (var k = 0; k <= j; k++) {
							fullPath += split[k] + '/';
						}
						// indention
						var indention = '';
						for (var k = 0; k < j; k++) {
							indention += '&nbsp;&nbsp;&nbsp;&nbsp;';
						}
						var escaped = tmpElement
							.text(split[j] === '' ? "<?php $this->text('MEDIA_ROOT'); ?>" : split[j])
							.html();
						select.append(
							$('<option>')
								.val(fullPath)
								.html(indention + escaped)
								.prop('disabled', fullPath === this._getCurrentPath() && !allowSameDirectory)
						);
					}
					lastDirectory = basePath;
					lastSplit = split;
				}
			}
		},
		_generateAttachSelect: function () {
			var basePath = this._currentRegularPath;
			var select = $('.attachSelect');
			select.empty();
			contentLoop:
			for (var i = 0; i < this._currentContent.length; i++) {
				var content = this._currentContent[i];
				var item = this._extractItemFromPath(basePath, content.internalName);
				if (item.isFile) {
					// item must not be selected
					for (var j = 0; j < this._selectedContent.length; j++) {
						if (content.mid === this._selectedContent[j].mid) {
							continue contentLoop;
						}
					}
					select.append($('<option>').val(content.mid).text(item.name));
				}
			}
		},
		_generateRenamePaths: function (form) {
			var name = $('.newName').val();
			if (name.length === 0 || name.indexOf('/') !== -1) {
				return false;
			}
			var basePath = this._getCurrentPath();
			var fileCounter = 1;
			for (var i = 0; i < this._selectedContent.length; i++) {
				var content = this._selectedContent[i];
				var item = this._extractItemFromPath(basePath, content.internalName);
				var oldPartOfPath = basePath + item.name;
				var remainingPath = content.internalName.substring(oldPartOfPath.length);
				// add numbering if more than one file is renamed
				var numbering = '';
				if (this._selectedContent.length > 1 && remainingPath.length === 0) {
					numbering = ' ' + (fileCounter++);
				}
				var newName = basePath + name + numbering + remainingPath;
				this._contentToBeModified.push(content);
				this._pathsForModification.push(newName);
			}
			return true;
		},
		_hasAttachment: function (mid) {
			// attachments start at root
			for (var i = 0; i < this._persistedContent.length; i++) {
				var content = this._persistedContent[i];
				if (content.parent === null) {
					break;
				} else if (content.parent === mid) {
					return true;
				}
			}
			return false;
		},
		_submitModifications: function () {
			var form = $('.mediaOperations');
			for (var i = 0; i < this._contentToBeModified.length; i++) {
				var content = this._contentToBeModified[i];
				form.append($('<input type="hidden" name="media[]">').val(content.mid));
			}
			for (var i = 0; i < this._pathsForModification.length; i++) {
				var path = this._pathsForModification[i];
				form.append($('<input type="hidden" name="path[]">').val(path));
			}
			for (var i = 0; i < this._contentToBeDeleted.length; i++) {
				var content = this._contentToBeDeleted[i];
				form.append($('<input type="hidden" name="deleteMedia[]">').val(content.mid));
			}
			form.submit();
		},
		_selectContent: function (item, content, checked) {
			if (this._inAttachment()) {
				this._selectAttachmentContent(item, content, checked);
			} else {
				this._selectRegularContent(item, content, checked);
			}
		},
		_selectAttachmentContent: function (item, content, checked) {
			if (item.isFile) {
				if (checked) {
					this._selectedContent.push(content);
				} else {
					var index = this._selectedContent.indexOf(content);
					this._selectedContent.splice(index, 1);
				}
			} else {
				for (var i = 0; i < this._persistedContent.length; i++) {
					var subcontent = this._persistedContent[i];
					// skip regular content
					if (subcontent.parent === null) {
						continue;
					}
					if (subcontent.internalName.startsWith(item.basePath + item.name + '/')) {
						if (checked) {
							this._selectedContent.push(subcontent);
						} else {
							var index = this._selectedContent.indexOf(subcontent);
							this._selectedContent.splice(index, 1);
						}
					}
				}
			}
		},
		_selectRegularContent: function (item, content, checked) {
			if (item.isFile) {
				if (checked) {
					this._selectedContent.push(content);
				} else {
					var index = this._selectedContent.indexOf(content);
					this._selectedContent.splice(index, 1);
				}
			} else {
				for (var i = this._currentRegularPathOffset; i < this._persistedContent.length; i++) {
					var subcontent = this._persistedContent[i];
					// skip attachments
					if (subcontent.parent !== null) {
						continue;
					}
					if (subcontent.internalName.startsWith(item.basePath + item.name + '/')) {
						if (checked) {
							this._selectedContent.push(subcontent);
						} else {
							var index = this._selectedContent.indexOf(subcontent);
							this._selectedContent.splice(index, 1);
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
			var splitPath = this._currentRegularPath.split('/');
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
						$('<li>').append($('<span class="pathseparator">'))
					);
				}

				if (i === splitPath.length - 2 && !this._inAttachment()) {
					this._getLocator().append(
					$('<li>').append(
						$('<button class="selected">').text(directory))
					);
				} else {
					(function (path) {
						that._getLocator().append(
						$('<li>').append(
							$('<button>').text(directory).click(function () {
								that._openSuperRegularDirectory(path);
							}))
						);
					})(fullPath); // force string copy
				}
			}

			// show locator for attachment directory
			if (this._inAttachment()) {
				var splitPath = this._currentAttachmentPath.split('/');
				var fullPath = '';
				var that = this;
				for (var i = 0; i < splitPath.length - 1; i++) {
					var directory = splitPath[i];
					fullPath += directory + '/';
					// root directory
					if (directory === '') {
						var item = this._extractItemFromPath(
							this._currentRegularPath,
							this._currentAttachmentContent.internalName);
						directory = item.name;
					}

					this._getLocator().append(
						$('<li>').append($('<span class="pathseparator">'))
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
									that._openSuperAttachmentDirectory(path);
								}))
							);
						})(fullPath); // force string copy
					}
				}
			}
		},

		_openAttachment: function (parent) {
			this._currentAttachmentContent = parent;

			for (var i = 0; i < this._persistedContent.length; i++) {
				var content = this._persistedContent[i];
				if (content.parent === null) {
					break;
				}
				if (content.parent === parent.mid) {
					this._currentAttachmentPathOffset = i;
					break;
				}
			}
			this._refresh();
		},

		_openSuperRegularDirectory: function (directory) {
			// reset attachment
			this._currentAttachmentContent = null;
			this._currentAttachmentPath = '/';
			this._currentAttachmentPathOffset = 0;

			this._currentRegularPath = directory;
			var oldOffset = this._currentRegularPathOffset;
			this._currentRegularPathOffset = 0;
			for (var i = oldOffset; i >= 0; i--) {
				var content = this._persistedContent[i];
				if (content.parent === null && !content.internalName.startsWith(directory)) {
					this._currentRegularPathOffset = i + 1;
					break;
				}
			}
			this._refresh();
		},

		_openSuperAttachmentDirectory: function (directory) {
			this._currentAttachmentPath = directory;
			var oldOffset = this._currentAttachmentPathOffset;
			this._currentAttachmentPathOffset = 0;
			for (var i = oldOffset; i >= 0; i--) {
				var content = this._persistedContent[i];
				if (content.parent === this._currentAttachmentContent.mid &&
						!content.internalName.startsWith(directory)) {
					this._currentAttachmentPathOffset = i + 1;
					break;
				}
			}
			this._refresh();
		},

		_openSubDirectory: function (directory) {
			if (this._currentAttachmentContent !== null) {
				this._openAttachmentDirectory(directory);
			} else {
				this._openSubRegularDirectory(directory);
			}
		},

		_openSubRegularDirectory: function (directory) {
			this._currentRegularPath = directory;
			for (var i = this._currentRegularPathOffset; i < this._persistedContent.length; i++) {
				var content = this._persistedContent[i];
				if (content.parent === null && content.internalName.startsWith(directory)) {
					this._currentRegularPathOffset = i;
					break;
				}
			}
			this._refresh();
		},

		_openAttachmentDirectory: function (directory) {
			this._currentAttachmentPath = directory;
			for (var i = this._currentAttachmentPathOffset; i < this._persistedContent.length; i++) {
				var content = this._persistedContent[i];
				// no attachements left
				if (content.parent === null) {
					break;
				}
				if (content.parent === this._currentAttachmentContent.mid &&
						content.internalName.startsWith(directory)) {
					this._currentAttachmentPathOffset = i;
					break;
				}
			}
			this._refresh();
		},

		_updateCurrentRegularContent: function () {
			// we sort by type first
			var currentDirs = [];
			var currentFiles = [];
			var directory = this._currentRegularPath;
			var lastSubdirectory = null;
			for (var i = this._currentRegularPathOffset; i < this._persistedContent.length; i++) {
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
			this._selectedContent = [];
			this._contentToBeModified = [];
			this._pathsForModification = [];
			this._contentToBeDeleted = [];
			// update from regular content
			if (this._inAttachment()) {
				this._updateCurrentAttachmentContent();
			}
			// update from attachment content
			else {
				this._updateCurrentRegularContent();
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

		_preprocessCopyMove: function (target) {
			var contentList = this._selectedContent;
			this._globalFileStrategy = this._CONFLICT_STRATEGIES.NO_STRATEGY;
			this._globalDirStrategy = this._CONFLICT_STRATEGIES.NO_STRATEGY;

			// check for conflicts

			// we need a decision if
			// directories should be merged, replaced, or skipped
			// files should be replaced, coexist, or be skipped

			var that = this;

			// list of directories that are allowed to coexist
			var conflictContext = {
				coexistDirs: [],
				replaceDirs: [],
				skipDirs: [],
				coexistFiles: [],
				replaceFiles: [],
				skipFiles: []
			};

			var fileConflictRoutine = function () {
				var conflict = that._checkForFileConflicts(contentList, target, conflictContext);
				if (conflict !== null) {
					that._showDialog(
						"<?php $this->text('FILE_CONFLICT', '" + conflict + "'); ?>",
						["<?php $this->text('KEEP_BOTH'); ?>",
							"<?php $this->text('REPLACE'); ?>",
							"<?php $this->text('SKIP'); ?>",
							"<?php $this->text('CANCEL'); ?>"],
						"<?php $this->text('REMEMBER_DECISION'); ?>",
						function (decision, remember) {
							that._removeDialog();
							if (decision == 0) {
								if (remember) {
									that._globalFileStrategy =
										that._CONFLICT_STRATEGIES.COEXIST;
								}
								conflictContext.coexistFiles.push(conflict);
								fileConflictRoutine();
							} else if (decision == 1) {
								if (remember) {
									that._globalFileStrategy =
										that._CONFLICT_STRATEGIES.REPLACE;
								}
								conflictContext.replaceFiles.push(conflict);
								fileConflictRoutine();
							} else if (decision == 2) {
								if (remember) {
									that._globalFileStrategy =
										that._CONFLICT_STRATEGIES.SKIP;
								}
								conflictContext.skipFiles.push(conflict);
								fileConflictRoutine();
							} else if (decision == 3) {
								that._refresh();
							}
						}
					);
				} else {
					// no conflicts anymore
					that._globalFileStrategy = that._CONFLICT_STRATEGIES.NO_STRATEGY;
					that._processCopyMove(contentList, target, conflictContext);
				}
			};

			// check for directory conflicts
			var directoryConflictRoutine = function () {
				var conflict = that._checkForDirectoryConflicts(contentList, target, conflictContext);
				if (conflict !== null) {
					that._showDialog(
						"<?php $this->text('DIRECTORY_CONFLICT', '" + conflict + "'); ?>",
						["<?php $this->text('MERGE'); ?>",
							"<?php $this->text('REPLACE'); ?>",
							"<?php $this->text('SKIP'); ?>",
							"<?php $this->text('CANCEL'); ?>"],
						"<?php $this->text('REMEMBER_DECISION'); ?>",
						function (decision, remember) {
							that._removeDialog();
							if (decision == 0) {
								if (remember) {
									that._globalDirStrategy =
										that._CONFLICT_STRATEGIES.COEXIST;
								}
								conflictContext.coexistDirs.push(conflict);
								directoryConflictRoutine();
							} else if (decision == 1) {
								if (remember) {
									that._globalDirStrategy =
										that._CONFLICT_STRATEGIES.REPLACE;
								}
								conflictContext.replaceDirs.push(conflict);
								directoryConflictRoutine();
							} else if (decision == 2) {
								if (remember) {
									that._globalDirStrategy =
										that._CONFLICT_STRATEGIES.SKIP;
								}
								conflictContext.skipDirs.push(conflict);
								directoryConflictRoutine();
							} else if (decision == 3) {
								that._refresh();
							}
						}
					);
				} else {
					that._globalDirStrategy = that._CONFLICT_STRATEGIES.NO_STRATEGY;
					fileConflictRoutine();
				}
			};
			directoryConflictRoutine();
		},

		// checks if there are unhandled conflicts for directories
		// returns the conflict if any
		_checkForDirectoryConflicts: function (contentList, target, context) {
			fileLoop:
			for (var i = 0; i < contentList.length; i++) {
				var content = contentList[i];
				// skip attachments
				if (content.parent !== null) {
					continue;
				}
				// the new location of the file
				var relativeInternalName = content.internalName.substring(this._currentRegularPath.length);

				// split into directory hierarchy
				var split = relativeInternalName.split('/');
				var currentDirectory = '';
				var directories = [];
				for (var j = 0; j < split.length - 1; j++) {
					currentDirectory += split[j] + '/';
					// add target directory to make it absolute again
					directories.push(target + currentDirectory);
				}

				// search for same directories
				directoryLoop:
				for (var k = 1; k < directories.length; k++) {
					var currentDir = directories[k];

					for (var j = 0; j < this._persistedContent.length; j++) {
						var persistedContent = this._persistedContent[j];
						// skip attachments
						if (persistedContent.parent !== null) {
							continue;
						}
						// potential conflict found
						if (persistedContent.internalName.startsWith(currentDir)) {
							// check if strategy already given
							if ($.inArray(currentDir, context.coexistDirs) != -1) {
								continue directoryLoop; // only skip this directory
							}
							else if ($.inArray(currentDir, context.replaceDirs)  != -1 ||
									$.inArray(currentDir, context.skipDirs)  != -1) {
								continue fileLoop; // skip all sub directories
							}
							// check for global strategy coexist
							else if (this._globalDirStrategy ==
									this._CONFLICT_STRATEGIES.COEXIST) {
								context.coexistDirs.push(currentDir);
							}
							// check for global strategy replace
							else if (this._globalDirStrategy ==
									this._CONFLICT_STRATEGIES.REPLACE) {
								context.replaceDirs.push(currentDir);
							}
							// check for global strategy skip
							else if (this._globalDirStrategy ==
									this._CONFLICT_STRATEGIES.SKIP) {
								context.skipDirs.push(currentDir);
							} else {
								// no strategy could be applied
								return currentDir;
							}
						}
					}
				}
			}
			return null;
		},

		// checks if there are unhandled conflicts for files
		// returns the conflict if any
		_checkForFileConflicts: function (contentList, target, context) {
			fileLoop:
			for (var i = 0; i < contentList.length; i++) {
				var content = contentList[i];
				// skip attachments
				if (content.parent !== null) {
					continue;
				}
				// the new location of the file
				var filePath = target + content.internalName.substring(this._currentRegularPath.length);

				for (var j = 0; j < this._persistedContent.length; j++) {
					var persistedContent = this._persistedContent[j];
					// skip attachments
					if (persistedContent.parent !== null) {
						continue;
					}
					// potential conflict found
					if (persistedContent.internalName == filePath) {
						// check if strategy already given
						if ($.inArray(filePath, context.coexistFiles) != -1 ||
									$.inArray(filePath, context.replaceFiles)  != -1 ||
									$.inArray(filePath, context.skipFiles)  != -1) {
							continue fileLoop;
						}
						// check for global strategy coexist
						else if (this._globalFileStrategy ==
								this._CONFLICT_STRATEGIES.COEXIST) {
							context.coexistFiles.push(filePath);
						}
						// check for global strategy replace
						else if (this._globalFileStrategy ==
								this._CONFLICT_STRATEGIES.REPLACE) {
							context.replaceFiles.push(filePath);
						}
						// check for global strategy skip
						else if (this._globalFileStrategy ==
								this._CONFLICT_STRATEGIES.SKIP) {
							context.skipFiles.push(filePath);
						} else {
							// check if entire directory is replaced anyway
							for (var k = 0; k < context.replaceDirs.length; k++) {
								if (filePath.startsWith(context.replaceDirs[k])) {
									continue fileLoop;
								}
							}
							// check if entire directory is skipped anyway
							for (var k = 0; k < context.skipDirs.length; k++) {
								if (filePath.startsWith(context.skipDirs[k])) {
									continue fileLoop;
								}
							}
							// no strategy could be applied
							return filePath;
						}
					}
				}
			}
			return null;
		},

		// splits up the conflictContext and contentList into content to be deleted and uploaded
		_processCopyMove: function (contentList, target, conflictContext) {
			// collect content to delete
			// replaced directories
			for (var i = 0; i < conflictContext.replaceDirs.length; i++) {
				var dir = conflictContext.replaceDirs[i];
				for (var j = 0; j < this._persistedContent.length; j++) {
					var content = this._persistedContent[j];
					// skip attachments
					if (content.parent !== null) {
						continue;
					}
					if (content.internalName.startsWith(dir)) {
						this._contentToBeDeleted.push(content);
					}
				}
			}
			// replaced files
			for (var i = 0; i < conflictContext.replaceFiles.length; i++) {
				var replaceFile = conflictContext.replaceFiles[i];
				for (var j = 0; j < this._persistedContent.length; j++) {
					var content = this._persistedContent[j];
					// skip attachments
					if (content.parent !== null) {
						continue;
					}
					if (content.internalName == replaceFile) {
						this._contentToBeDeleted.push(content);
					}
				}
			}

			// add file list
			fileLoop:
			for (var i = 0; i < contentList.length; i++) {
				var content = contentList[i];
				// the new location of the file
				var filePath = target + content.internalName.substring(this._currentRegularPath.length);

				// file must not be in directory skip list
				for (var j = 0; conflictContext.skipDirs.length; j++) {
					var dir = conflictContext.skipDirs[j];
					if (filePath.startsWith(dir)) {
						continue fileLoop;
					}
				}
				// file must not be in file skip list
				for (var j = 0; conflictContext.skipFiles.length; j++) {
					var skipFile = conflictContext.skipFiles[j];
					if (filePath == skipFile) {
						continue fileLoop;
					}
				}

				// add file
				this._contentToBeModified.push(content);
				this._pathsForModification.push(filePath);
			}

			// submit
			this._submitModifications();
		},

		// removes the dialog
		_removeDialog: function () {
			$('.dialog-box', this._getViewArea()).last().remove();
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
		},
	};
</script>
