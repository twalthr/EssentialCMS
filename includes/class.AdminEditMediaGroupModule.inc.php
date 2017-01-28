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

	public function __construct(
			$createGlobal,
			$config,
			$mediaGroupOperations,
			$mediaOperations,
			$parameters = null) {
		parent::__construct(1, 'admin-edit-media-group');
		$this->createGlobal = $createGlobal;
		$this->config = $config;
		$this->mediaGroupOperations = $mediaGroupOperations;
		$this->mediaOperations = $mediaOperations;

		// media group id is present
		if (isset($parameters) && count($parameters) > 0) {
			$this->loadMediaGroup($parameters[0]);
		}
		// if media group is present, load media
		if (isset($this->mediaGroup)) {
			$this->loadMedia();
		}
		// handle media group operations
		if (Utils::getUnmodifiedStringOrEmpty('operationSpace') === 'mediaGroup') {
			$this->handleEditMediaGroup();
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
						_FILE_FILTER: ['thumbs.db', 'desktop.ini', '.ds_store', 'ehthumbs.db'], // need to be lower case

						// configuration
						_mode: 0,
						_checksumLevel: 0,
						_uploadArea: $('.uploadarea'),
						_currentContent: [],
						_checksumWorker: null,
						_selectionEnabled: true, // determines if new files can be selected

						// public functions
						init: function (currentContent) {
							this._detectMode();

							this._currentContent = currentContent;

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
							// directories should be merged, be replaced, or coexist
							// files should be replaced, or coexist

							// check for similar directories

							// check for similar files

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
								'size': size
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
							this._setDropAreaCancel(function() {
								traversingStatus.stopped = true;
							});

							var that = this;
							var interval = setInterval(function() {
								that._setDropAreaText("<?php $this->text('ITEMS_FOUND',
									'" + traversingStatus.files + "',
									'" + traversingStatus.dirs + "'); ?>");

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
								this._checksumWorker.onmessage = function (e) {
									this._processChecksumWorkerEvent(e);
								};
							} else {
								if (this._checksumLevel == this._CHECKSUM_LEVELS.REQUIRED) {
									this._handleError('<?php $this->text('CHECKSUM_NOT_SUPPORTED'); ?>');
								}
							}
						},

						_processChecksumWorkerEvent: function (e) {
							console.log("TODO");
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
			<form method="post">
				<input type="hidden" name="operationSpace" value="media" />
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