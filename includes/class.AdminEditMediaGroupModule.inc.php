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
		if (Utils::getUnmodifiedStringOrEmpty('operationSpace') === 'mediaGroup') {
			$this->handleEditMediaGroup();
			// reload media group
			if (isset($parameters) && count($parameters) > 0) {
				$this->loadMediaGroup($parameters[0]);
			}
		}
		// handle media operations
		else if (isset($this->media) &&
				Utils::getUnmodifiedStringOrEmpty('operationSpace') === 'media') {
			$this->handleEditMedia();
			// reload media
			$this->loadMedia();
		}
		// delete temp media if not media operation
		else if (isset($this->mediaGroup)) {
			$result = $this->mediaStore->deleteAllTempMedia($this->mediaGroup['mgid']);
			if ($result !== true) {
				$this->state = false;
				$this->message = $result;
			}
		}

		// show success message for newly created media group
		if (!isset($this->state) && count($parameters) > 1 && $parameters[1] === '.success') {
			$this->state = true;
			$this->message = 'MEDIA_GROUP_CREATED';
		}
	}

	public function printContent($config) {
		?>
		<!-- START OF UPLOADER -->
		<?php include __DIR__ . '/js.Uploader.inc.php'; ?>
		<!-- END OF UPLOADER -->

		<!-- START OF FILE MANAGER -->
		<?php include __DIR__ . '/js.FileManager.inc.php'; ?>
		<!-- END OF FILE MANAGER -->

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
						fileManager.enableEditing();
					});

					// ----------------------------------------------------------------------------
					// BEGIN OF MEDIA DATA
					// ----------------------------------------------------------------------------
					var currentContent = <?php echo $this->getMediaAsJson(); ?>;
					// ----------------------------------------------------------------------------
					// END OF MEDIA DATA
					// ----------------------------------------------------------------------------

					// initialize uploader
					uploader.init(currentContent, $('.uploadarea'));

					// initialize file manager
					fileManager.init(currentContent, $('.media'));
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
				</form>
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
				<div class="uploadarea hidden showInEditMode"></div>
				<?php endif; ?>
			</section>
		<?php if (isset($this->mediaGroup)) : ?>
			<form method="post" class="mediaOperations">
				<input type="hidden" name="operationSpace" value="media" />
				<input type="hidden" name="operation" class="mediaOperation" value="" />
				<section>
					<h1><?php $this->text('MEDIA'); ?></h1>
					<div class="media"></div>
				</section>
			</form>
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
			if (strlen($trimmed) > 0) {
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
			if ($updateColumns['title'] === $this->mediaGroup['title']) {
				unset($updateColumns['title']);
			}
			if ($updateColumns['description'] === $this->mediaGroup['description']) {
				unset($updateColumns['description']);
			}
			if ($updateColumns['tags'] === $this->mediaGroup['tags']) {
				unset($updateColumns['tags']);
			}
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
			// simple operation for legacy reasons (basic file input)
			case 'committedupload':
				if (!array_key_exists('file', $_FILES) || count($_FILES['file']['tmp_name']) <= 0) {
					$this->state = false;
					$this->message = 'PARAMETERS_INVALID';
					return;
				}
				$mid = $this->mediaStore->storeTempMedia(
					$this->mediaGroup['mgid'], $_FILES['file']['tmp_name']);
				if (is_string($mid)) {
					$this->state = false;
					$this->message = $mid;
					return;
				}
				$result = $this->mediaStore->commitTempMedia(
					$this->mediaGroup['mgid'],
					$mid,
					null,
					null,
					substr('/' . $_FILES['file']['name'], 0, 512),
					null);
				if ($result !== true) {
					$this->state = false;
					$this->message = $result;
					return;
				}
				break;

			case 'upload':
				$mid = null;
				if (!array_key_exists('file', $_FILES) || count($_FILES['file']['tmp_name']) <= 0) {
					$mid = 'PARAMETERS_INVALID';
				} else {
					$mid = $this->mediaStore->storeTempMedia(
						$this->mediaGroup['mgid'], $_FILES['file']['tmp_name']);
				}
				$data = null;
				// an error occured
				if (is_string($mid)) {
					$data = array('status' => 'error', 'message' => $this->textString($mid));
				} else {
					$data = array('status' => 'success', 'mid' => $mid);
				}
				die(json_encode($data));

			case 'commit':
				// add content
				$uploadedContent = Utils::getJsonFieldOrNull('uploadedContent', 3);
				if ($uploadedContent === null || !is_array($uploadedContent) ||
						!Utils::isValidFieldInt('numberNewContent')) {
					$this->state = false;
					$this->message = 'PARAMETERS_INVALID';
					return;
				}
				$numberNewContent = (int) Utils::getUnmodifiedStringOrEmpty('numberNewContent');
				if ($numberNewContent !== count($uploadedContent)) {
					$this->state = false;
					$this->message = 'UNKNOWN_ERROR';
					return;
				}
				foreach ($uploadedContent as $content) {
					// parameter checking
					if (array_key_exists('mid', $content) && Utils::isValidInt($content['mid']) &&
							array_key_exists('size', $content)  &&
							(Utils::isValidInt($content['size']) || $content['size'] === null) &&
							array_key_exists('checksum', $content)  && ((is_string($content['checksum']) &&
							strlen($content['checksum']) === 32) || $content['checksum'] === null) &&
							array_key_exists('path', $content)  && is_string($content['path']) &&
							Utils::isValidPath($content['path']) &&
							array_key_exists('modified', $content) &&
							(Utils::isValidInt($content['modified']) || $content['modified'] === null)) {
						$result = $this->mediaStore->commitTempMedia(
							$this->mediaGroup['mgid'],
							$content['mid'],
							$content['size'],
							$content['checksum'],
							$content['path'],
							$content['modified']);
						if ($result !== true) {
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
				$deletedContent = Utils::getJsonFieldOrNull('deletedContent', 2);
				if ($deletedContent === null || !is_array($deletedContent)) {
					$this->state = false;
					$this->message = 'PARAMETERS_INVALID';
					return;
				}
				foreach ($deletedContent as $mid) {
					if (is_int($mid)) {
						$result = $this->mediaStore->deleteMedia($this->mediaGroup['mgid'], $mid);
						if ($result !== true) {
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
				break;

			// list operations
			default:

				// check for media
				if (Utils::isValidFieldIntArray('media')) {
					// normalize media
					$uniqueMediumIds = array_unique(Utils::getValidFieldArray('media'));

					// foreach medium
					foreach ($uniqueMediumIds as $index => $mediumId) {
						// check if medium exists
						$medium = Utils::getColumnWithValue($this->media, 'mid', (int) $mediumId);
						if ($medium === false) {
							continue;
						}

						// do operation
						switch ($operation) {
							case 'copy':
							case 'move':
								// check if number of paths equal to number of media
								// and check if valid path
								if (!Utils::isValidFieldArray('path')) {
									return;
								}
								$paths = Utils::getValidFieldArray('path');
								if (count($paths) !== count($uniqueMediumIds) ||
										!Utils::isValidPath($paths[$index])) {
									return;
								}

								// perform move
								$result = true;
								if ($operation === 'move') {
									// check if same path
									if ($paths[$index] === $medium['internalName']) {
										continue;
									}
									$result = $this->mediaStore->moveMedia(
										$medium['mid'],
										$paths[$index]);
								}
								// perform copy
								else {
									$result = $this->mediaStore->copyMedia(
										$medium['mid'],
										$paths[$index]);
								}
								if ($result !== true) {
									$this->state = false;
									$this->message = $result;
									return;
								}
								break;
							case 'attach':
								if (!Utils::isValidFieldInt('attachTarget')) {
									return;
								}
								$target = (int) Utils::getUnmodifiedStringOrEmpty('attachTarget');
								// check if medium exists
								$targetMedium = Utils::getColumnWithValue($this->media, 'mid', $target);
								if ($targetMedium === false) {
									return;
								}
								// do not allow attachment to itself
								if ($targetMedium['mid'] === $medium['mid']) {
									continue;
								}
								// check if not attached
								if ($targetMedium['parent'] !== null) {
									return;
								}
								// check if medium is not parent
								$parent = Utils::getColumnWithValue($this->media, 'parent', $medium['mid']);
								if ($parent !== false) {
									$this->state = false;
									$this->message = 'HAS_ATTACHED_ITEMS';
									return;
								}
								// extract base path
								$basePathPos = strrpos($targetMedium['internalName'], '/');
								$basePath = substr($targetMedium['internalName'], 0, $basePathPos);

								// check if medium starts with base path
								if (substr($medium['internalName'], 0, strlen($basePath)) !== $basePath) {
									return;
								}
								$result = $this->mediaStore->attachMedia(
									$targetMedium['mid'],
									$medium['mid'],
									substr($medium['internalName'], strlen($basePath)));
								if ($result !== true) {
									$this->state = false;
									$this->message = $result;
									return;
								}
								break;
							case 'detach':
								// check if medium has parent
								if ($medium['parent'] === null) {
									return;
								}
								$parent = Utils::getColumnWithValue($this->media, 'mid', $medium['parent']);
								// extract base path
								$basePathPos = strrpos($parent['internalName'], '/');
								$basePath = substr($parent['internalName'], 0, $basePathPos);
								$result = $this->mediaStore->detachMedia(
									$medium['mid'],
									$basePath . $medium['internalName']);
								if ($result !== true) {
									$this->state = false;
									$this->message = $result;
									return;
								}
								break;
						}
					}
				}

				// check for media
				if (Utils::isValidFieldIntArray('deleteMedia')) {
					// normalize media
					$uniqueMediumIds = array_unique(Utils::getValidFieldArray('deleteMedia'));

					// foreach medium
					$result = true;
					foreach ($uniqueMediumIds as $index => $mediumId) {
						// check if medium exists
						$medium = Utils::getColumnWithValue($this->media, 'mid', (int) $mediumId);
						if ($medium === false) {
							continue;
						}
						// perform deletion
						$result = $this->mediaStore->deleteMedia($medium['mid']);
						if ($result !== true) {
							$this->state = false;
							$this->message = $result;
							return;
						}
					}
				}

				// do nothing
				break;
		}
		$this->state = true;
		$this->message = 'MEDIA_GROUP_EDITED';
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