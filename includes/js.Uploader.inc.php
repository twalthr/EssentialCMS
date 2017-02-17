<script>
	var uploader = {
		_MODES: {
			// simple one file upload
			INPUT_BASIC: 1,
			// browser supports upload of multiple files
			INPUT_MULTIPLE: 2,
			// browser supports upload of directories
			INPUT_DIRS: 4,
			// browser supports dragging files
			DRAG_FILES: 8,
			// browser supports dragging directories
			DRAG_DIRS: 16
		},
		_CHECKSUM_LEVELS: {
			// do not print errors, do not inform user if checksum calculation
			// is still in progress when closing window
			OPTIONAL: 1,
			// print errors, do not allow to close window until checksum is sent
			REQUIRED: 2,
			// do not calculate checksums
			DISABLED: 3
		},
		// need to be lower case
		_FILE_FILTER: ['thumbs.db', 'desktop.ini', '.ds_store', 'ehthumbs.db'],
		_CONFLICT_STRATEGIES: {
			NO_STRATEGY: 0,
			COEXIST: 1,
			REPLACE: 2,
			SKIP: 3
		},
		// how ofter do we check for updates in queue
		_FILE_QUEUE_MONITOR_INTERVAL: 1000,

		// configuration
		_mode: 0,
		_checksumLevel: 0,
		_uploadArea: null,
		// a web worker for creating checksums; null if not supported by the browser
		_checksumWorker: null,
		// determines if new files can be selected
		_selectionEnabled: true,
		// stratgy for file conflicts, chosen by user for all future files
		_globalFileStrategy: 0,
		// stratgy for directory conflicts, chosen by user for all future directories
		_globalDirStrategy: 0,
		// persisted content that will be modified by uploading files
		_persistedContent: [],
		// persisted content that needs to be deleted due to replacement
		_deletedContent: [],
		// content that needs to be persisted
		_uploadedContent: [],
		// files to be checksumed and uploaded
		_uploadQueue: [],
		// additional variable to validate file queue with final uploaded content
		_numberNewContent: 0,
		// flag to indicate if page unload is triggered by user or script
		_redirect: false,

		// public functions
		init: function (persistedContent, uploadArea) {
			this._detectMode();

			// only add files that are not attached to other files
			for (var i = 0; i < persistedContent.length; i++) {
				var content = persistedContent[i];
				if (content.parent === null) {
					this._persistedContent.push(content);
				}
			}
			this._uploadArea = uploadArea;

			this._checksumLevel = this._CHECKSUM_LEVELS.OPTIONAL;
			this._initChecksumWorkers();

			this._initInputElements();
		},
		// private functions
		// all functions are more or less ordered according to the order they get called

		// checks which functions are supported by the browser
		_detectMode: function () {
			this._mode = this._MODES.INPUT_BASIC;
			// check input element
			var tmpInput = document.createElement('input');
			if ('multiple' in tmpInput) {
				this._mode |= this._MODES.INPUT_MULTIPLE;
			}
			var entrySupport = 'webkitEntries' in tmpInput ||
				'mozEntries' in tmpInput ||
				'oEntries' in tmpInput ||
				'msEntries' in tmpInput ||
				'entries' in tmpInput;

			var directorySupport = 'webkitdirectory' in tmpInput ||
				'mozdirectory' in tmpInput ||
				'odirectory' in tmpInput ||
				'msdirectory' in tmpInput ||
				'directory' in tmpInput;

			if (entrySupport && directorySupport) {
				this._mode |= this._MODES.INPUT_DIRS;
			}
			// check div element
			var tmpDiv = document.createElement('div');
			if ((('draggable' in tmpDiv) || ('ondragstart' in tmpDiv && 'ondrop' in tmpDiv)) &&
					'FormData' in window &&
					'FileReader' in window) {
				this._mode |= this._MODES.DRAG_FILES;
				if (entrySupport) {
					this._mode |= this._MODES.DRAG_DIRS;
				}
			}
		},

		// insert and prepare UI elements
		_initInputElements: function () {
			// add form area
			this._uploadArea.append($('<div class="formarea hidden">'));
			// add drop area
			this._uploadArea.append($('<div class="droparea">'));
			// add button area
			this._uploadArea.append($('<div class="buttonarea">'));

			this._getDropArea().append($('<span class="status">'));

			// prepare drop area
			this._setDropAreaDefaultText();
			if (this._mode & this._MODES.DRAG_FILES) {
				this._addDragAndDropListener();
			}

			// prepare button area
			var that = this;
			this._getButtonArea().append($(
					'<button>', {
					'class': 'selectfilesbutton',
					'text': (this._mode & this._MODES.INPUT_MULTIPLE) ?
						'<?php $this->text('SELECT_FILES'); ?>' :
						'<?php $this->text('SELECT_FILE'); ?>',
					'click': function (e) {
						if (that._selectionEnabled) {
							that._openInput(false);
						}
					}
				}));

			if (this._mode & this._MODES.INPUT_DIRS) {
				this._getButtonArea().append($(
					'<button>', {
					'class': 'selectdirsbutton',
					'text': "<?php $this->text('SELECT_DIRS'); ?>",
					'click': function (e) {
						if (that._selectionEnabled) {
							that._openInput(true);
						}
					}
				}));
			}

			// add page leave message
			$(window).bind('beforeunload', function () {
				if (!that._selectionEnabled && !that._redirect) {
					return "<?php $this->text('LEAVE_PAGE'); ?>";
				}
			});
		},

		// start workers for checksum creation
		_initChecksumWorkers: function () {
			if (this._checksumLevel == this._CHECKSUM_LEVELS.DISABLED) {
				return;
			}

			// worker can be created
			if ('FileReader' in window && !!window.Worker) {
				this._checksumWorker = new Worker(
					'<?php echo $config->getPublicRoot(); ?>/admin/js/checksum-worker.js');
				var that = this;
				this._checksumWorker.onmessage = function (e) {
					that._processChecksumWorkerEvent(e);
				};
			} else {
				if (this._checksumLevel == this._CHECKSUM_LEVELS.REQUIRED) {
					this._handleError('<?php $this->text('CHECKSUM_NOT_SUPPORTED'); ?>');
				}
			}
		},

		// returns drop area element
		_getDropArea: function () {
			return $('.droparea', this._uploadArea);
		},

		// returns form area element
		_getFormArea: function () {
			return $('.formarea', this._uploadArea);
		},

		// returns button area element
		_getButtonArea: function () {
			return $('.buttonarea', this._uploadArea);
		},

		// set drop area text
		_setDropAreaText: function (text) {
			$('.status', this._getDropArea()).text(text);
		},

		// sets a cancel button to the drop area.
		// if callback is null, cancel button will be removed.
		_setDropAreaCancel: function (callback) {
			if ($('button', this._getDropArea()).length == 0 && callback != null) {
				this._getDropArea().append(
				$('<button>')
					.text("<?php $this->text('CANCEL'); ?>")
					.click(callback));
			} else if (callback == null) {
				$('button', this._getDropArea()).remove();
			} else {
				$('button', this._getDropArea()).click(callback);
			}
		},

		// sets the default text for the drop area
		_setDropAreaDefaultText: function() {
			if (this._mode & this._MODES.DRAG_FILES) {
				this._setDropAreaText('<?php $this->text('DRAG_FILES_HERE'); ?>');
			} else {
				this._setDropAreaText('<?php $this->text('SELECT_FILES_HERE'); ?>');
			}
		},

		// opens browser's input dialog for selecting one or multiple files (if supported)
		_openInput: function (dir) {
			// get the last form in form area
			var form = $('form', this._getFormArea()).first();

			// check if there is at least one form
			if (form.length == 0) {
				// create new form with empty input
				form = $('<form method="post" enctype="multipart/form-data">');
				var that = this;
				var filesSelected = function (e) {
					// multiple files supported
					if (that._mode & that._MODES.INPUT_MULTIPLE) {
						var input = e.target;
						that._processDroppedFiles(input.files);
					}
					// only basic input supported
					else {
						this._redirect = true;
						form.submit();
					}
				}
				if (this._mode & this._MODES.INPUT_MULTIPLE) {
					form.append($(
						'<input type="file" name="file" multiple>')
						.change(filesSelected));
				}
				// browser does not support multiple files
				// we create a form that submits the file immediately
				else {
					form.append($(
						'<input type="file" name="file">')
						.change(filesSelected));
					form.append(
						$('<input type="hidden" name="operationSpace" value="media">'));
					form.append(
						$('<input type="hidden" name="operation" value="committedupload">'));
				}
				// add dir input
				if (this._mode & this._MODES.INPUT_DIRS) {
					form.append($(
						'<input type="file" name="dir" ' + 
							'directory webkitdirectory msdirectory mozdirectory odirectory>')
						.change(filesSelected));
				}
				this._getFormArea().append(form);
			}
			if (dir) {
				$('input[name="dir"]', form).trigger('click');
			} else {
				$('input[name="file"]', form).trigger('click');
			}
		},

		// adds listeners to drop area
		_addDragAndDropListener: function () {
			var that = this;
			this._getDropArea().on('dragover', function(e) {
				e.preventDefault();
				if (that._selectionEnabled) {
					$(this).addClass('dragover');
				}
			});
			this._getDropArea().on('dragleave', function(e) {
				e.preventDefault();
				if (that._selectionEnabled) {
					$(this).removeClass('dragover');
				}
			});
			// no strange behavior by the browser outside the drop area
			$('body').on('dragenter dragover drop', false);

			var that = this;
			this._getDropArea().on('drop', function(e) {
				e.stopPropagation();
				e.preventDefault();

				if (!that._selectionEnabled) {
					return;
				}

				// browser does not support "items"
				if (!(that._mode & that._MODES.DRAG_DIRS)) {
					var files = e.originalEvent.dataTransfer.files;
					var identifiedFiles = [];
					for (var i = 0; i < files.length; i++) {
						var f = files[i];

						// try to find out if file is a directory
						var isFile = true;
						try {
							reader = new FileReader();
							reader.onload = function (e) {
								isFile = true;
							};
							reader.onerror = function (e) {
								isFile = false;
							};
							reader.readAsDataURL(f);
						} catch (e) {
							isFile = false;
						}

						// most likely a file
						if (isFile) {
							identifiedFiles.push(f);
						}
					}
					if (identifiedFiles.length > 0) {
						that._processDroppedFiles(identifiedFiles);
					}
				}
				// browser supports items
				else {
					if (e.originalEvent.dataTransfer.items.length > 0) {
						that._processDroppedItems(e.originalEvent.dataTransfer.items);
					}
				}
				$(this).removeClass('dragover');
			});
		},

		// process at least one item
		_processDroppedItems: function (items) {
			this._disableSelection();
			this._setDropAreaText('<?php $this->text('SEARCHING_FOR_ITEMS'); ?>');

			var functionName = ('webkitGetAsEntry' in items[0]) ? 'webkitGetAsEntry' :
				('mozGetAsEntry' in items[0]) ? 'mozGetAsEntry' :
				('oGetAsEntry' in items[0]) ? 'oGetAsEntry' :
				('msGetAsEntry' in items[0]) ? 'msGetAsEntry' :
				'getAsEntry';

			var fileList = [];

			// traverse and collect file list
			var traversingMonitor = $.Deferred();
			var traversingStatus = {
				files: 0,
				dirs: 0,
				open: 0,
				closed: 0,
				error: false,
				errorName: null,
				errorPath: null,
				stopped: false
			};

			this._monitorTraversing(traversingStatus, traversingMonitor);

			var idx = items.length;
			while (idx-- && !traversingStatus.stopped) {
				var entry = items[idx][functionName]();
				this._traverseEntry(fileList, entry, traversingStatus);
			}

			var traversingEvent = $.when(traversingMonitor);

			var that = this;

			// check for conflicts and add items if file system has been read
			traversingEvent.done(function () {
				that._preprocessFileList(fileList);
			});

			// file system reading has been stoppped by user or error
			traversingEvent.fail(function () {
				// error
				if (openClosed.error) {
					that._handleUndefinedError("<?php $this->text('COULD_NOT_OPEN_FILE',
						'" + traversingStatus.errorPath + "',
						'" + traversingStatus.errorName + "'); ?>");
				}
				// cancelled by user
				else {
					that._enableSelection();
				}
			});
		},

		// processes at least one file
		_processDroppedFiles: function (files) {
			this._disableSelection();
			this._setDropAreaText('<?php $this->text('SEARCHING_FOR_ITEMS'); ?>');
			var fileList = [];
			for (var i = 0; i < files.length; i++) {
				var file = files[i];
				if ('webkitRelativePath' in file && file.webkitRelativePath.length > 0) {
					var path = '/' + file.webkitRelativePath;
				} else {
					var path = '/' + file.name;
				}

				var descriptor = this._createFileDescriptor(
					file.name,
					path,
					file,
					file.size,
					('lastModified' in file) ? Math.floor(file.lastModified / 1000) : null);
				fileList.push(descriptor);
			}
			this._preprocessFileList(fileList);
		},

		// create a file descriptor describing a file
		_createFileDescriptor: function (name, path, file, size, modified) {
			return {
				'name': name,
				'path': path, // must start with '/'
				'file': file,
				'size': size, // can be null if browser does not support it
				'modified': modified, // can be null if browser does not support it
				'checksum': null // can be null if browser does not support it
			};
		},

		// monitors the traversing of entries
		_monitorTraversing: function (traversingStatus, traversingMonitor) {
			var that = this;
			var interval = setInterval(function() {
				that._setDropAreaText("<?php $this->text('SEARCHING_FOR_ITEMS_PROGRESS',
					'" + traversingStatus.files + "',
					'" + traversingStatus.dirs + "'); ?>");
				that._setDropAreaCancel(function() {
					traversingStatus.stopped = true;
				});

				if (traversingStatus.stopped) {
					clearInterval(interval);
					that._setDropAreaText('');
					that._setDropAreaCancel(null);
					traversingMonitor.reject();
				} else if (traversingStatus.open == traversingStatus.closed) {
					clearInterval(interval);
					// wait a last time
					setTimeout(function() {
						// still unchanged
						if (traversingStatus.open == traversingStatus.closed &&
								!traversingStatus.stopped) {
							that._setDropAreaText('');
							that._setDropAreaCancel(null);
							traversingMonitor.resolve();
						}
						// change happend again
						else {
							that._monitorTraversing(traversingStatus, traversingMonitor);
						}
					}, 500);
				}
			}, 50);
		},

		// traverse an entry (can be either a file or a directory)
		_traverseEntry: function (fileList, entry, traversingStatus) {
			traversingStatus.open++;

			var registerError = function () {
				traversingStatus.error = true;
				traversingStatus.errorName = entry.name;
				traversingStatus.errorPath = entry.fullPath;
				traversingStatus.stopped = true;
			}

			var that = this;

			// get file
			if (entry.isFile) {
				entry.file(
					// could read file successfully
					function (file) {
						if (!traversingStatus.stopped) {
							var descriptor = that._createFileDescriptor(
								file.name,
								entry.fullPath,
								file,
								file.size,
								('lastModified' in file) ? Math.floor(file.lastModified / 1000) : null);
							fileList.push(descriptor);

							traversingStatus.files++;
							traversingStatus.closed++;
						}
					},
					// error occured with file
					registerError);
			}
			// get directory
			else if (entry.isDirectory) {
				var dirReader = entry.createReader();
				dirReader.readEntries(
					// could read directory successfully
					function (entries) {
						if (!traversingStatus.stopped) {
							var idx = entries.length;
							while (idx-- && !traversingStatus.stopped) {
								that._traverseEntry(fileList, entries[idx], traversingStatus);
							}
							traversingStatus.dirs++;
							traversingStatus.closed++;
						}
					},
					// error
					registerError);
			}
		},

		// filter and handle conflicts
		_preprocessFileList: function (fileList) {
			this._setDropAreaText("<?php $this->text('SEARCHING_FOR_CONFLICTS'); ?>");

			// reject certain files
			this._filterFileList(fileList);

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
			}

			var fileConflictRoutine = function () {
				var conflict = that._checkForFileConflicts(fileList, conflictContext);
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
								that._enableSelection();
							}
						}
					);
				} else {
					// no conflicts anymore
					that._globalFileStrategy = that._CONFLICT_STRATEGIES.NO_STRATEGY;
					that._addFileListToUploadQueue(fileList, conflictContext);
				}
			};

			// check for directory conflicts
			var directoryConflictRoutine = function () {
				var conflict = that._checkForDirectoryConflicts(fileList, conflictContext);
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
								that._enableSelection();
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

		// filters files that are usually not needed
		_filterFileList: function (fileList) {
			for (var i = 0; i < fileList.length; i++) {
				var file = fileList[i];
				if ($.inArray(file.name.toLowerCase(), this._FILE_FILTER) != -1) {
					fileList.splice(i, 1);
				}
				// throw error if path is larger than 512 characters
				if (file.path.length > 512) {
					this._handleError("<?php $this->text('PATH_TOO_LONG'); ?>");
					return;
				}
			}
		},

		// checks if there are unhandled conflicts for directories
		// returns the conflict if any
		_checkForDirectoryConflicts: function (fileList, context) {
			fileLoop:
			for (var i = 0; i < fileList.length; i++) {
				var fileDescriptor = fileList[i];

				// split into directory hierarchy
				var split = fileDescriptor.path.split('/');
				var currentDirectory = '';
				var directories = []
				for (var j = 0; j < split.length - 1; j++) {
					currentDirectory += split[j] + '/'
					directories.push(currentDirectory);
				}

				// search for same directories
				directoryLoop:
				for (var k = 1; k < directories.length; k++) {
					var currentDir = directories[k]

					for (var j = 0; j < this._persistedContent.length; j++) {
						// potential conflict found
						if (this._persistedContent[j].internalName.startsWith(currentDir)) {
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
		_checkForFileConflicts: function (fileList, context) {
			fileLoop:
			for (var i = 0; i < fileList.length; i++) {
				var filePath = fileList[i].path;

				for (var j = 0; j < this._persistedContent.length; j++) {
					// potential conflict found
					if (this._persistedContent[j].internalName == filePath) {
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

		// splits up the conflictContext and fileList into content to be deleted and uploaded
		_addFileListToUploadQueue: function (fileList, conflictContext) {
			// collect content to delete
			// replaced directories
			for (var i = 0; i < conflictContext.replaceDirs.length; i++) {
				var dir = conflictContext.replaceDirs[i];
				for (var j = 0; j < this._persistedContent.length; j++) {
					var content = this._persistedContent[j];
					if (content.internalName.startsWith(dir)) {
						this._deletedContent.push(content.mid);
					}
				}
			}
			// replaced files
			for (var i = 0; i < conflictContext.replaceFiles.length; i++) {
				var replaceFile = conflictContext.replaceFiles[i];
				for (var j = 0; j < this._persistedContent.length; j++) {
					var content = this._persistedContent[j];
					if (content.internalName == replaceFile) {
						this._deletedContent.push(content.mid);
					}
				}
			}

			// add file list to process queue
			fileLoop:
			for (var i = 0; i < fileList.length; i++) {
				var file = fileList[i];
				// file must not be in directory skip list
				for (var j = 0; conflictContext.skipDirs.length; j++) {
					var dir = conflictContext.skipDirs[j];
					if (file.path.startsWith(dir)) {
						continue fileLoop;
					}
				}
				// file must not be in file skip list
				for (var j = 0; conflictContext.skipFiles.length; j++) {
					var skipFile = conflictContext.skipFiles[j];
					if (file.path == skipFile) {
						continue fileLoop;
					}
				}

				// add file
				this._uploadQueue.push(file);
			}

			this._numberNewContent = this._uploadQueue.length;

			// start upload queue
			this._checksumAndUploadFile();
		},

		// creates a checksum and/or uploads a file descriptor
		// commits content changes if all files have been processed
		_checksumAndUploadFile: function() {
			var that = this;
			// we call it with timeout to prevent stack overflow
			setTimeout(function () {
				// cancel queue processing chain
				if (that._uploadQueue.length === 0) {
					// new files added
					if (that._uploadedContent.length > 0) {
						that._commitContentChanges();
					} else {
						that._enableSelection();
					}
				} else {
					that._setDropAreaCancel(function () {
						// this is the easiest way to 
						// stop the file upload and the checksum worker
						that._redirect = true;
						window.location.reload();
					});
					var nextFile = that._uploadQueue[0];
					// checksums supported
					if (that._checksumWorker != null) {
						that._setDropAreaText("<?php $this->text('CREATING_CHECKSUM', '" +
							nextFile.name + "'); ?>");
						that._checksumWorker.postMessage({
							'type': 'start',
							'file': nextFile.file
						});
					}
					// checksums not supported but required
					else if (that._checksumLevel == that._CHECKSUM_LEVELS.REQUIRED) {
						that._handleError("<?php $this->text('CHECKSUM_UNKNOWN_ERROR'); ?>");
					}
					// checksum not supported but optional
					else {
						that._uploadFile();
					}
				}
			}, 1);
		},

		// handling event coming from the checksum worker
		_processChecksumWorkerEvent: function (e) {

			var currentFile = this._uploadQueue[0];

			// register progress
			if (e.data.type == 'status') {
				var progress = Math.round(e.data.chunk / e.data.of * 100);
				this._setDropAreaText("<?php $this->text('CREATING_CHECKSUM_PROGRESS',
					'" + currentFile.name + "',
					'" + progress + "'); ?>");
			} else if (e.data.type == 'error') {
				// inform about the error
				if (this._currentChecksumLevel != this._CHECKSUM_LEVELS.OPTIONAL) {
					if (e.data.reason == 'unsupported') {
						this._handleError("<?php $this->text('CHECKSUM_NOT_SUPPORTED'); ?>");
					} else if (e.data.reason == 'unknown') {
						this._handleError("<?php $this->text('CHECKSUM_UNKNOWN_ERROR'); ?>");
					}
				}
			} else if (e.data.type == 'done') {
				currentFile.checksum = e.data.checksum;
				this._uploadFile();
			}
		},

		// uploads a file
		_uploadFile: function () {
			var currentFileDesc = this._uploadQueue[0];
			var files = this._uploadQueue.length;
			var formdata = new FormData();
			formdata.append('operationSpace', 'media');
			formdata.append('operation', 'upload');
			formdata.append('file', currentFileDesc.file, 'file');
			var that = this;
			that._setDropAreaText(
				"<?php $this->text('UPLOADING',
				'" + currentFileDesc.name + "',
				'" + files + "'); ?>");
			$.ajax({
				'type': 'post',
				'data': formdata,
				'processData': false,
				'contentType': false,
				'dataType': 'json',
				'xhr': function (){
					// get the native XmlHttpRequest object
					var xhr = $.ajaxSettings.xhr() ;
					// set the onprogress event handler
					xhr.upload.onprogress = function (e) {
						var progress = Math.round(e.loaded / e.total * 100);
						that._setDropAreaText(
							"<?php $this->text('UPLOADING_PROGRESS',
							'" + currentFileDesc.name + "',
							'" + files + "',
							'" + progress + "'); ?>");
					};
					return xhr;
				},
				'success': function (data) {
					if (data.status === 'success') {
						that._uploadedContent.push({
							'mid': data.mid,
							'size': currentFileDesc.size,
							'checksum': currentFileDesc.checksum,
							'path': currentFileDesc.path,
							'modified': currentFileDesc.modified
						});
						that._uploadQueue.splice(0, 1);
						// start processing queue
						that._checksumAndUploadFile();
					} else if (data.status === 'error') {
						that._handleError(data.message);
					} else {
						that._handleError('<?php $this->text('NETWORK_PROBLEMS'); ?>');
					}
				},
				'error': function () {
					that._handleError('<?php $this->text('NETWORK_PROBLEMS'); ?>');
				}
			});
		},

		// commits all content changes to the server
		_commitContentChanges: function () {
			var form = $('<form class="changeSubmission" method="post">');
			form.append($('<input type="hidden" name="operationSpace" value="media">'));
			form.append($('<input type="hidden" name="operation" value="commit">'));
			form.append($('<input type="hidden" name="numberNewContent" value="' +
				this._numberNewContent + '">'));
			form.append(
				$('<input type="hidden" name="deletedContent">')
					.val(JSON.stringify(this._deletedContent))
			);
			form.append(
				$('<input type="hidden" name="uploadedContent">')
					.val(JSON.stringify(this._uploadedContent))
			);
			this._getFormArea().append(form);
			this._redirect = true;
			form.submit();
		},

		// removes the dialog
		_removeDialog: function () {
			$('.dialog-box', this._getDropArea()).remove();
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
			this._getDropArea().append(dialog);
		},

		// general function for handlung and output a error message
		_handleError: function (message) {
			var that = this;
			this._showDialog(message, [ "<?php $this->text('RESTART'); ?>" ], null, function () {
				that._redirect = true;
				window.location.reload(); 
			});
		},

		// disables the selection of files or directories with input or
		// drag&drop. enables buttons and drop area
		_disableSelection: function () {
			this._getButtonArea().children().prop('disabled', true);
			this._getDropArea().addClass('loading');
			this._setDropAreaText('');
			this._selectionEnabled = false;
		},

		// enables the selection of files or directories with input or
		// drag&drop. enables buttons and drop area
		_enableSelection: function() {
			this._getButtonArea().children().prop('disabled', false);
			this._getDropArea().removeClass('loading');
			this._setDropAreaDefaultText();
			this._uploadedContent = [];
			this._deletedContent = [];
			this._uploadQueue = [];
			this._numberNewContent = 0;
			this._globalFileStrategy = 0;
			this._globalDirStrategy = 0;
			this._selectionEnabled = true;
		}
	};
</script>
