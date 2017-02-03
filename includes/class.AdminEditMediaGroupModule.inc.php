<?php

class AdminEditMediaGroupModule extends BasicModule {

	// database operations
	private $mediaGroupOperations;
	private $mediaOperations;

	// UI state
	private $state;
	private $message;

	// member variables
	private $createGlobal; // flag if we are in global or local media group creation mode
	private $config; // config for page redirect after media group creation
	private $mediaGroup; // media group information from database
	private $media; // media information from database
	private $mediaStore; // manages media files

	public function __construct(
			$createGlobal,
			$config,
			$mediaGroupOperations,
			$mediaOperations,
			$mediaStore,
			$parameters = null) {
		parent::__construct(1, 'admin-edit-media-group');
		$this->createGlobal = $createGlobal;
		$this->config = $config;
		$this->mediaGroupOperations = $mediaGroupOperations;
		$this->mediaOperations = $mediaOperations;
		$this->mediaStore = $mediaStore;

		// media group id is present
		if (isset($parameters) && count($parameters) > 0) {
			$this->loadMediaGroup($parameters[0]);
		}
		// if media group is present, load media
		if (isset($this->mediaGroup)) {
			$this->loadMedia();
		}
		// handle media group operations
		if (isset($this->mediaGroup) &&
				Utils::getUnmodifiedStringOrEmpty('operationSpace') === 'mediaGroup') {
			$this->handleEditMediaGroup();
		}
		// handle media operations
		else if (isset($this->media) &&
				Utils::getUnmodifiedStringOrEmpty('operationSpace') === 'media') {
			$this->handleEditMedia();
		}

		// show success message for newly created media group
		if (!isset($this->state) && count($parameters) > 1 && $parameters[1] === '.success') {
			$this->state = true;
			$this->message = 'MEDIA_GROUP_CREATED';
		}
	}

	public function printContent($config) {
		?>
		<script type="text/javascript">
			$(document).ready(function() {
				$('#editMediaGroupCancel').click(function() {
					window.open('<?php echo $config->getPublicRoot(); ?>/admin/media', '_self');
				});
				<?php if (isset($this->mediaGroup) && isset($this->state)) : ?>
					$('#editMediaGroup').trigger('click');
				<?php endif; ?>
				<?php if (isset($this->mediaGroup)) : ?>
					$('#editMediaGroup').click(function() {
						$('.showInEditMode').removeClass('hidden');
						$('.hiddenInEditMode').remove();
					});
					// ----------------------------------------------------------------------------
					// BEGIN OF UPLOAD UTIL
					// ----------------------------------------------------------------------------
					var uploadUtil = {
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
						_uploadArea: $('.uploadarea'),
						// a web worker for creating checksums; null if not supported by the browser
						_checksumWorker: null,
						// determines if new files can be selected
						_selectionEnabled: true,
						// stratgy for file conflicts, chosen by user for all future files
						_globalFileStrategy: 0,
						// stratgy for directory conflicts, chosen by user for all future directories
						_globalDirStrategy: 0,
						// persisted content that will be modified by uploading files
						_overallContent: [],
						// persisted content that needs to be deleted due to replacement
						_deletedContent: [],
						// content that needs to be persisted
						_uploadedContent: [],
						// files to be checksumed and uploaded
						_fileQueue: [],
						// monitor that watches the progress of files in the queue
						_fileQueueMonitor: null,

						// public functions
						init: function (overallContent) {
							this._detectMode();

							this._overallContent = overallContent;

							this._checksumLevel = this._CHECKSUM_LEVELS.REQUIRED;
							this._initChecksumWorkers();

							this._initInputElements();
						},
						// private functions

						// insert and prepare UI elements
						_initInputElements: function () {
							// add form area
							this._uploadArea.append($(
								'<div>', {
								'class': ['formarea', 'hidden']
							}));
							// add frame area
							this._uploadArea.append($(
								'<div>', {
								'class': ['framearea', 'hidden']
							}));
							// add drop area
							this._uploadArea.append($(
								'<div>', {
								'class': 'droparea'
							}));
							// prepare drop area
							this._setDropAreaDefaultText();
							if (this._mode & this._MODES.DRAG_FILES) {
								this._addDragAndDropListener();
							}

							// add button area
							this._uploadArea.append($(
								'<div>', {
								'class': 'buttonarea'
							}));

							// prepare button area
							var that = this
							$('.buttonarea', this._uploadArea)
								.append($(
									'<button>', {
									'class': 'selectbutton',
									text: (this._mode & this._MODES.INPUT_MULTIPLE) ?
										'<?php $this->text('SELECT_FILES'); ?>' :
										'<?php $this->text('SELECT_FILE'); ?>',
									click: function (e) {
										if (that._selectionEnabled) {
											that._openInput();
										}
									}
								}));
						},

						// opens browser's input dialog for selecting one or multiple files (if supported)
						_openInput: function () {
							// get the last form in form area
							var newestForm = $('.formarea form', this._uploadArea).last();

							// check if there is at least one form
							if (newestForm.length != 0) {
								// if the input value is empty it can reused
								var inputField = $('input', newestForm);
								if (inputField.val() == '') {
									inputField.trigger('click');
								}
							} else {
								// create new form with empty input
								var fileUpload = $('<form>');
								if (this._mode & this._MODES.INPUT_MULTIPLE) {
									fileUpload.append($(
										'<input type="file" name="file" multiple>')
										.change(this._filesSelected));
								} else {
									fileUpload.append($(
										'<input type="file" name="file">')
										.change(this._filesSelected));
								}
								$('.formarea form', this._uploadArea).append(fileUpload);
								$('input', fileUpload).trigger('click');
							}
						},

						_filesSelected: function () {
							console.log("TODO");
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
								that._setDropAreaText("<?php $this->text('SEARCHING_FOR_CONFLICTS'); ?>");
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

						_preprocessFileList: function (fileList) {
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
											that._hideDialog();
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
									that._addFileListToProcessQueue(fileList, conflictContext);
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
											that._hideDialog();
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

						// checks if there are unhandled conflicts
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

									for (var j = 0; j < this._overallContent.length; j++) {
										// potential conflict found
										if (this._overallContent[j].internalName.startsWith(currentDir)) {
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

						_checkForFileConflicts: function (fileList, context) {
							fileLoop:
							for (var i = 0; i < fileList.length; i++) {
								var filePath = fileList[i].path;

								for (var j = 0; j < this._overallContent.length; j++) {
									// potential conflict found
									if (this._overallContent[j].internalName == filePath) {
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

						_addFileListToProcessQueue: function (fileList, conflictContext) {
							// collect content to delete
							// replaced directories
							for (var i = 0; i < conflictContext.replaceDirs.length; i++) {
								var dir = conflictContext.replaceDirs[i];
								for (var j = 0; j < this._overallContent.length; j++) {
									var content = this._overallContent[j];
									if (content.internalName.startsWith(dir)) {
										this._deletedContent.push(content.mid);
									}
								}
							}
							// replaced files
							for (var i = 0; i < conflictContext.replaceFiles.length; i++) {
								var replaceFile = conflictContext.replaceFiles[i];
								for (var j = 0; j < this._overallContent.length; j++) {
									var content = this._overallContent[j];
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
								this._fileQueue.push(file);
							}

							// start processing queue
							this._checksumAndUploadFileDesc();
						},
						// creates a checksum and/or uploads a file descriptor
						// commits content changes if all files have been processed
						_checksumAndUploadFileDesc: function() {
							var that = this;
							// we call it with timeout to prevent stack overflow
							setTimeout(function () {
								// cancel queue processing chain
								if (that._fileQueue.length === 0) {
									// new files added
									if (that._uploadedContent.length > 0) {
										that._commitContentChanges();
									}
								} else {
									var nextFile = that._fileQueue[0];
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
										that._uploadFileDesc();
									}
								}
							}, 1);
						},
						// commits all content changes to the server
						_commitContentChanges: function () {
							var form = $('<form class="changeSubmission" method="post">');
							form.append($('<input type="hidden" name="operationSpace" value="media">'));
							form.append($('<input type="hidden" name="operation" value="commit">'));
							form.append(
								$('<input type="hidden" name="deletedContent">')
									.val(JSON.stringify(this._deletedContent))
							);
							form.append(
								$('<input type="hidden" name="uploadedContent">')
									.val(JSON.stringify(this._uploadedContent))
							);
							this._getFormArea().append(form);
							form.submit();
						},
						// handling event coming from the checksum worker
						_processChecksumWorkerEvent: function (e) {

							var currentFile = this._fileQueue[0];

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
										this._handleError('<?php $this->text('CHECKSUM_NOT_SUPPORTED'); ?>');
									} else if (e.data.reason == 'unknown') {
										this._handleError('<?php $this->text('CHECKSUM_UNKNOWN_ERROR'); ?>');
										// TODO add button for restart page
									}
								}
							} else if (e.data.type == 'done') {
								currentFile.checksum = e.data.checksum;
								this._uploadFileDesc();
							}
						},
						// uploads a file
						_uploadFileDesc: function () {
							var currentFileDesc = this._fileQueue[0];
							var formdata = new FormData();
							formdata.append('operationSpace', 'media');
							formdata.append('operation', 'upload');
							formdata.append('file', currentFileDesc.file, 'file');
							var that = this;
							$.ajax({
								'type': 'post',
								'data': formdata,
								'processData': false,
								'contentType': false,
								'dataType': 'json',
								'success': function (data) {
									if (data.status === 'success') {
										that._uploadedContent.push({
											'mid': data.mid,
											'size': currentFileDesc.size,
											'checksum': currentFileDesc.checksum,
											'path': currentFileDesc.path
										});
										that._fileQueue.splice(0, 1);
										// start processing queue
										that._checksumAndUploadFileDesc();
									} else {

									}
								}
							});
						},

						_hideDialog: function () {
							$('.dialog-box', this._getDropArea()).remove();
						},

						_showDialog: function (message, buttons, checkboxText, callback) {
							var dialog = $('<div class="dialog-box">');
							dialog.append($('<div class="dialog-message">').text(message))
							var options = $('<div class="options">');
							var wrapper = $('<div class="checkboxWrapper">');
							var checkbox = $('<input type="checkbox" id="remember">');
							wrapper.append(checkbox);
							wrapper.append($('<label for="remember" class="checkbox">')
								.text(checkboxText));
							wrapper.append(checkboxText);
							options.append(wrapper);
							for (var i = 0; i < buttons.length; i++) {
								options.append(
									$('<button>')
										.text(buttons[i])
										.click(function () {
											callback($(this).index() - 1, checkbox.is(':checked'));
										}
									)
								);
							};
							dialog.append(options);
							this._getDropArea().append(dialog);
						},

						// filters files that are usually not needed
						_filterFileList: function (fileList) {
							for (var i = 0; i < fileList.length; i++) {
								if ($.inArray(name.toLowerCase(), this._FILE_FILTER) != -1) {
									fileList.splice(i, 1);
								}
							}
						},

						// create a file descriptor describing a file
						_createFileDescriptor: function (name, path, file, size) {
							return {
								'name': name,
								'path': path,
								'file': file,
								'size': size, // can be null if browser does not support it
								'checksum': null
							};
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
												file.size);
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

						// general function for handlung and output a error message
						_handleUndefinedError: function (message) {
							t2u._enableSelection();
							console.log("TODO");
						},

						// monitors the traversing of entries
						_monitorTraversing: function (traversingStatus, traversingMonitor) {
							var that = this;
							var interval = setInterval(function() {
								that._setDropAreaText("<?php $this->text('SEARCHING_FOR_ITEMS',
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

						_getDropArea: function () {
							return $('.droparea', this._uploadArea);
						},

						_getFormArea: function () {
							return $('.formarea', this._uploadArea);
						},

						_setDropAreaText: function (text) {
							this._getDropArea().text(text);
						},

						// sets a cancel button to the drop area.
						// if callback is null, cancel button will be removed.
						_setDropAreaCancel: function (callback) {
							if ($('.droparea button', this._uploadArea).length == 0 && callback != null) {
								this._getDropArea().append(
								$('<button>')
									.text('<?php $this->text('CANCEL'); ?>')
									.click(callback));
							} else if (callback == null) {
								$('.droparea button', this._uploadArea).remove();
							} else {
								$('.droparea button', this._uploadArea).click(cancelCallback);
							}
						},

						// processes at least one file
						_processDroppedFiles: function (files) {
							this._disableSelection();
							console.log("TODO");
						},

						// disables the selection of files or directories with input or
						// drag&drop. shows loading on buttons and drop area
						_disableSelection: function () {
							$('.buttonarea', this._uploadArea).children().addClass('loading');
							this._getDropArea().addClass('loading');
							this._getDropArea().text('');
							this._selectionEnabled = false;
						},

						// enables the selection of files or directories with input or
						// drag&drop. removes loading on buttons and drop area
						_enableSelection: function() {
							$('.buttonarea', this._uploadArea).children().removeClass('loading');
							this._getDropArea().removeClass('loading');
							this._setDropAreaDefaultText();
							this._selectionEnabled = true;
						},

						// sets the default text for the drop area
						_setDropAreaDefaultText: function() {
							if (this._mode & this._MODES.DRAG_FILES) {
								this._setDropAreaText('<?php $this->text('DRAG_FILES_HERE'); ?>');
							} else {
								this._setDropAreaText('<?php $this->text('SELECT_FILES_HERE'); ?>');
							}
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

						// checks which functions are supported by the browser
						_detectMode: function () {
							this._mode = this._MODES.INPUT_BASIC;
							// check input element
							var tmpInput = document.createElement('input');
							if ('multiple' in tmpInput) {
								this._mode |= this._MODES.INPUT_MULTIPLE;
							}
							if ('webkitEntries' in tmpInput ||
									'mozEntries' in tmpInput ||
									'oEntries' in tmpInput ||
									'msEntries' in tmpInput ||
									'entries' in tmpInput) {
								this._mode |= this._MODES.INPUT_DIRS;
							}
							// check div element
							var tmpDiv = document.createElement('div');
							if ((('draggable' in tmpDiv) || ('ondragstart' in tmpDiv && 'ondrop' in tmpDiv)) &&
									'FormData' in window && 'FileReader' in window) {
								this._mode |= this._MODES.DRAG_FILES;
								if ('webkitEntries' in tmpInput ||
										'mozEntries' in tmpInput ||
										'oEntries' in tmpInput ||
										'msEntries' in tmpInput ||
										'entries' in tmpInput) {
									this._mode |= this._MODES.DRAG_DIRS;
								}
							}
						}
					};
					// ----------------------------------------------------------------------------
					// END OF UPLOAD UTIL
					// ----------------------------------------------------------------------------

					// ----------------------------------------------------------------------------
					// BEGIN OF MEDIA DATA
					// ----------------------------------------------------------------------------
					var currentContent = <?php echo $this->getMediaAsJson(); ?>;
					// ----------------------------------------------------------------------------
					// END OF MEDIA DATA
					// ----------------------------------------------------------------------------

					// start upload util
					uploadUtil.init(currentContent);
				<?php endif; ?>
			});
		</script>
		<?php if (isset($this->state)) : ?>
			<?php if ($this->state === true) : ?>
				<div class="dialog-success-message">
					<?php $this->text($this->message); ?>
				</div>
			<?php else: ?>
				<div class="dialog-error-message">
					<?php $this->text($this->message); ?>
				</div>
			<?php endif; ?>
		<?php endif; ?>
		<?php if (isset($this->mediaGroup)) : ?>
			<form method="post">
		<?php else : ?>
			<form method="post" action="<?php echo $config->getPublicRoot(); ?>/admin/new-<?php 
			echo $this->createGlobal ? 'global' : 'local'; ?>-media-group">
		<?php endif; ?>
				<input type="hidden" name="operationSpace" value="mediaGroup" />
				<section>
					<?php if (isset($this->mediaGroup)) : ?>
						<h1 class="hiddenInEditMode">
							<?php echo Utils::escapeString($this->mediaGroup['title']); ?>
						</h1>
						<h1 class="hidden showInEditMode"><?php $this->text('MEDIA_GROUP_PROPERTIES'); ?></h1>
						<div class="buttonSet general">
							<?php if (!Utils::isFlagged(
								$this->mediaGroup['options'], MediaGroupOperations::LOCKED_OPTION)) : ?>
							<button id="editMediaGroup" class="hiddenInEditMode">
								<?php $this->text('EDIT_MEDIA_GROUP'); ?>
							</button>
							<input type="submit" class="hidden showInEditMode"
								value="<?php $this->text('SAVE'); ?>" />
							<?php endif; ?>
							<button id="editMediaGroupCancel">
								<?php $this->text('CANCEL'); ?>
							</button>
						</div>
					<?php else : ?>
						<h1><?php $this->text('NEW_MEDIA_GROUP'); ?></h1>
						<div class="buttonSet general">
							<input type="submit" value="<?php $this->text('CREATE'); ?>" />
							<button id="editMediaGroupCancel">
								<?php $this->text('CANCEL'); ?>
							</button>
						</div>
					<?php endif; ?>
					<div 
						<?php if (isset($this->mediaGroup)) : ?>
							class="hidden showInEditMode"
						<?php endif; ?>>
						<div class="fields">
							<div class="field">
								<label for="title"><?php $this->text('MEDIA_GROUP_TITLE'); ?></label>
								<input type="text" name="title" id="title" class="large" maxlength="256"
									value="<?php echo Utils::getEscapedFieldOrVariable('title',
										$this->mediaGroup['title']); ?>"
									required />
							</div>
							<div class="field">
								<label for="description"><?php $this->text('DESCRIPTION'); ?></label>
								<textarea name="description" id="description" class="large" maxlength="1024"
									rows="3"><?php echo Utils::getEscapedFieldOrVariable('description',
										$this->mediaGroup['description']); ?></textarea>
							</div>
							<div class="field">
								<label for="tags"><?php $this->text('TAGS'); ?></label>
								<input type="text" name="tags" id="tags" class="large" maxlength="256"
									value="<?php echo Utils::getEscapedFieldOrVariable('tags',
										$this->mediaGroup['tags']); ?>"
									/>
								<span class="hint"><?php $this->text('TAGS_HINT'); ?></span>
							</div>
						</div>
						<div class="fieldsRequired">
							<?php $this->text('REQUIRED'); ?>
						</div>
					</div>
					<?php if (isset($this->mediaGroup)) : ?>
					<div class="hiddenInEditMode">
						<?php if (Utils::hasStringContent($this->mediaGroup['description'])) : ?>
							<div><span class="labelLike"><?php $this->text('DESCRIPTION'); ?></span>
									<?php echo Utils::escapeString($this->mediaGroup['description']); ?></div>
						<?php endif; ?>
						<?php if (Utils::hasStringContent($this->mediaGroup['tags'])) : ?>
							<div><span class="labelLike"><?php $this->text('TAGS'); ?></span>
									<?php echo Utils::escapeString($this->mediaGroup['tags']); ?></div>
						<?php endif; ?>
					</div>
					<?php endif; ?>
				</section>
			</form>
		<?php if (isset($this->mediaGroup)) : ?>
			<section>
				<h1><?php $this->text('MEDIA'); ?></h1>
				<div class="uploadarea">
				</div>
				<div class="media">
				</div>
			</section>
		<?php endif; ?>
	<?php
	}

	// --------------------------------------------------------------------------------------------
	// Printing methods
	// --------------------------------------------------------------------------------------------

	private function getMediaAsJson() {
		return json_encode($this->media);
	}


	// --------------------------------------------------------------------------------------------
	// User input handling methods
	// --------------------------------------------------------------------------------------------

	private function handleEditMediaGroup() {
		// check if locked
		if (isset($this->mediaGroup)
				&& Utils::isFlagged($this->mediaGroup['options'], MediaGroupOperations::LOCKED_OPTION)) {
			$this->state = false;
			$this->message = 'MEDIA_GROUP_LOCKED';
			return;
		}
		$updateColumns = [];
		// check fields
		if (!Utils::isValidFieldWithContentNoLinebreak('title', 256)) {
			$this->state = false;
			$this->message = 'INVALID_MEDIA_GROUP_TITLE';
			return;
		}
		$updateColumns['title'] = Utils::getValidFieldString('title');
		if (!Utils::isValidFieldWithMaxLength('description', 1024)) {
			$this->state = false;
			$this->message = 'INVALID_MEDIA_GROUP_DESCRIPTION';
			return;
		}
		$updateColumns['description'] = Utils::getValidFieldStringOrNull('description');
		if (!Utils::isValidFieldWithTags('tags', 256)) {
			$this->state = false;
			$this->message = 'INVALID_MEDIA_GROUP_TAGS';
			return;
		}

		// normalize tags
		$split = preg_split("/[,#]+/", Utils::getValidFieldString('tags'));
		$normalized = [];
		foreach ($split as $value) {
			$trimmed = trim($value);
			if (count($trimmed) > 0) {
				$normalized[] = $trimmed;
			}
		}
		$imploded = implode(', ', array_unique($normalized));
		$updateColumns['tags'] = $imploded;

		$result = false;
		// create media group
		if (!isset($this->mediaGroup)) {
			$result = $this->mediaGroupOperations->addMediaGroup(
				$updateColumns['title'],
				$updateColumns['description'],
				$updateColumns['tags'],
				$this->createGlobal ? MediaGroupOperations::GLOBAL_GROUP_OPTION : 0);
			if ($result === false) {
				$this->state = false;
				$this->message = 'UNKNOWN_ERROR';
				return;
			} else {
				// redirect
				Utils::redirect($this->config->getPublicRoot() . '/admin/media-group/' .
					$result . '/.success');
			}
		}
		// update existing media group
		else {
			$result = $this->mediaGroupOperations->updateMediaGroup($this->mediaGroup['mgid'], $updateColumns);
			if ($result === false) {
				$this->state = false;
				$this->message = 'UNKNOWN_ERROR';
				return;
			} else {
				$this->state = true;
				$this->message = 'MEDIA_GROUP_EDITED';
			}
		}
	}

	private function handleEditMedia() {
		$operation = Utils::getUnmodifiedStringOrEmpty('operation');
		// check if locked
		if (isset($this->mediaGroup)
				&& Utils::isFlagged($this->mediaGroup['options'], MediaGroupOperations::LOCKED_OPTION)) {
			$this->state = false;
			$this->message = 'MEDIA_GROUP_LOCKED';
			return;
		}

		switch ($operation) {
			case 'upload':
				$mid = $this->mediaStore->storeTempMedia(
					$this->mediaGroup['mgid'], $_FILES['file']['tmp_name']);
				$data = null;
				// an error occured
				if (is_string($mid)) {
					$data = array('status' => 'error', 'message' => $this->config->text($mid));
				} else {
					$data = array('status' => 'success', 'mid' => $mid);
				}
				die(json_encode($data));
			case 'commit':
				// add content
				$uploadedContent = Utils::getJsonFieldOrNull('uploadedContent', 3);
				if ($uploadedContent === null || !is_array($uploadedContent)) {
					$this->state = false;
					$this->message = 'PARAMETERS_INVALID';
					return;
				}
				foreach ($uploadedContent as $content) {
					if (array_key_exists('mid', $content) && is_int($content['mid']) &&
							array_key_exists('size', $content)  &&
							(is_int($content['size']) || $content['size'] === null) &&
							array_key_exists('checksum', $content)  && ((is_string($content['checksum']) &&
							strlen($content['checksum']) === 32) || $content['checksum'] === null) &&
							array_key_exists('path', $content)  && is_string($content['path']) &&
							strlen($content['path']) < 512 && strlen($content['path']) > 0) {
						$result = $this->mediaStore->commitTempMedia(
							$this->mediaGroup['mgid'],
							$content['mid'],
							$content['size'],
							$content['checksum'],
							$content['path']);
						if ($result === true) {
							continue;
						} else {
							$this->state = false;
							$this->message = $result;
							return;
						}
					}
					// invalid parameters
					else {
						$this->state = false;
						$this->message = 'PARAMETERS_INVALID';
						return;
					}
				}

				// delete content
				break;
			default:
				# code...
				break;
		}
		
	}

	// --------------------------------------------------------------------------------------------
	// Database loading methods
	// --------------------------------------------------------------------------------------------

	private function loadMediaGroup($mediaGroupId) {
		if (!Utils::isValidInt($mediaGroupId)) {
			$this->state = false;
			$this->message = 'MEDIA_GROUP_NOT_FOUND';
			return;
		}
		$mediaGroup = $this->mediaGroupOperations->getMediaGroup($mediaGroupId);
		if ($mediaGroup === false) {
			$this->state = false;
			$this->message = 'MEDIA_GROUP_NOT_FOUND';
			return;
		}
		$this->mediaGroup = $mediaGroup;
	}

	private function loadMedia() {
		if (!isset($this->mediaGroup)) {
			return;
		}
		$media = $this->mediaOperations->getMediaSummary($this->mediaGroup['mgid']);
		if ($media === false) {
			$this->state = false;
			$this->message = 'MEDIA_NOT_FOUND';
			return;
		}
		$this->media = $media;
	}
}

?>