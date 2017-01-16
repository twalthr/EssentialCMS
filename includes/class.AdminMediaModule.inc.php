<?php

class AdminMediaModule extends BasicModule {

	// database operations
	private $mediaGroupOperations;

	// UI state
	private $state;
	private $message;

	// member variables
	private $globalMediaGroups;
	private $localMediaGroups;

	public function __construct($mediaGroupOperations) {
		parent::__construct(1, 'admin-media');
		$this->mediaGroupOperations = $mediaGroupOperations;

		// load global media groups
		$this->loadMediaGroups(true);
		if (!isset($this->globalMediaGroups)) {
			return;
		}

		// load local media groups
		$this->loadMediaGroups(false);
		if (!isset($this->localMediaGroups)) {
			return;
		}

		// handle global media group operations
		if (Utils::getUnmodifiedStringOrEmpty('operationSpace') === 'global') {
			$this->handleMediaGroupOperations(true);
			// reload media groups
			$this->loadMediaGroups(true);
			$this->loadMediaGroups(false);
		}
		// handle global media group operations
		else if (Utils::getUnmodifiedStringOrEmpty('operationSpace') === 'local') {
			$this->handleMediaGroupOperations(false);
			// reload media groups
			$this->loadMediaGroups(true);
			$this->loadMediaGroups(false);
		}
	}

	public function printContent($config) {
		?>
		<script type="text/javascript">
			$(document).ready(function(){
				$('#globalMediaGroupOperations .addButton').click(function() {
					window.open(
						'<?php echo $config->getPublicRoot(); ?>/admin/new-global-media-group',
						'_self');
				});
				$('#localMediaGroupOperations .addButton').click(function() {
					window.open(
						'<?php echo $config->getPublicRoot(); ?>/admin/new-local-media-group',
						'_self');
				});
				$('.delete').click(function() {
					$(this).closest('form').find('input[name=operation]').val('delete');
					openButtonSetDialog($(this),
						'<?php $this->text('DELETE_QUESTION'); ?>',
						'.deleteConfirm');
				});
				$('.deleteConfirm').click(function() {
					enableList($(this));
					$(this).closest('form').submit();
				});
				$('.lock').click(function() {
					var form = $(this).closest('form');
					form.find('input[name=operation]').val('lock');
					form.submit();
				});
				$('.unlock').click(function() {
					$(this).closest('form').find('input[name=operation]').val('unlock');
					openButtonSetDialog($(this),
						'<?php $this->text('UNLOCK_QUESTION'); ?>',
						'.unlockConfirm');
				});
				$('.unlockConfirm').click(function() {
					enableList($(this));
					$(this).closest('form').submit();
				});
				$('.move').click(function() {
					var form = $(this).closest('form');
					form.find('input[name=operation]').val('move');
					form.submit();
				});
				$('.copy').click(function() {
					var form = $(this).closest('form');
					form.find('input[name=operation]').val('copy');
					form.submit();
				});
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
		<section>
			<h1><?php $this->text('GLOBAL_MEDIA_GROUPS'); ?></h1>
			<form method="post" action="<?php echo $config->getPublicRoot(); ?>/admin/media"
					id="globalMediaGroupOperations">
				<button class="addButton">
					<?php $this->text('CREATE_MEDIA_GROUP'); ?>
				</button>
				<input type="hidden" name="operationSpace" value="global" />
				<input type="hidden" name="operation" />
				<?php $this->printMediaGroups($config, true); ?>
				<div class="buttonSet">
					<button class="disableListIfClicked delete" disabled>
						<?php $this->text('DELETE'); ?>
					</button>
					<button class="disableListIfClicked lock" disabled>
						<?php $this->text('LOCK'); ?>
					</button>
					<button class="disableListIfClicked unlock" disabled>
						<?php $this->text('UNLOCK'); ?>
					</button>
					<button class="disableListIfClicked move" disabled>
						<?php $this->text('MOVE'); ?>
					</button>
					<button class="disableListIfClicked copy" disabled>
						<?php $this->text('COPY'); ?>
					</button>
				</div>
				<div class="dialog-box hidden">
					<div class="dialog-message"></div>
					<div class="options">
						<button class="hidden deleteConfirm">
							<?php $this->text('DELETE'); ?></button>
						<button class="hidden unlockConfirm">
							<?php $this->text('UNLOCK'); ?></button>
						<button class="hidden cancel"><?php $this->text('CANCEL'); ?></button>
					</div>
				</div>
			</form>
		</section>
		<section>
			<h1><?php $this->text('LOCAL_MEDIA_GROUPS'); ?></h1>
			<form method="post" action="<?php echo $config->getPublicRoot(); ?>/admin/media"
					id="localMediaGroupOperations">
				<button class="addButton">
					<?php $this->text('CREATE_MEDIA_GROUP'); ?>
				</button>
				<input type="hidden" name="operationSpace" value="local" />
				<input type="hidden" name="operation" />
				<?php $this->printMediaGroups($config, false); ?>
				<div class="buttonSet">
					<button class="disableListIfClicked delete" disabled>
						<?php $this->text('DELETE'); ?>
					</button>
					<button class="disableListIfClicked lock" disabled>
						<?php $this->text('LOCK'); ?>
					</button>
					<button class="disableListIfClicked unlock" disabled>
						<?php $this->text('UNLOCK'); ?>
					</button>
					<button class="disableListIfClicked move" disabled>
						<?php $this->text('MOVE'); ?>
					</button>
					<button class="disableListIfClicked copy" disabled>
						<?php $this->text('COPY'); ?>
					</button>
				</div>
				<div class="dialog-box hidden">
					<div class="dialog-message"></div>
					<div class="options">
						<button class="hidden deleteConfirm">
							<?php $this->text('DELETE'); ?></button>
						<button class="hidden unlockConfirm">
							<?php $this->text('UNLOCK'); ?></button>
						<button class="hidden cancel"><?php $this->text('CANCEL'); ?></button>
					</div>
				</div>
			</form>
		</section>
		<?php
	}

	// --------------------------------------------------------------------------------------------
	// Printing methods
	// --------------------------------------------------------------------------------------------

	private function printMediaGroups($config, $isGlobal) {
		if ($isGlobal) {
			$mediaGroups = $this->globalMediaGroups;
		} else {
			$mediaGroups = $this->localMediaGroups;
		}
		if ($mediaGroups === false || count($mediaGroups) === 0) {
			echo '<p class="empty">';
			echo $this->text('NO_MEDIA_GROUP');
			echo '</p>';
			return;
		}
		echo '<ul class="tableLike enableButtonsIfChecked">';
		foreach ($mediaGroups as $mediaGroup) {
			echo '<li class="rowLike">';
			echo '<input type="checkbox" id="mediaGroup' . $mediaGroup['mgid'] . '" name="mediaGroup[]"';
			echo ' value="' . $mediaGroup['mgid'] . '" />';
			echo '<label for="mediaGroup' . $mediaGroup['mgid'] . '" class="checkbox">';
			echo Utils::escapeString($mediaGroup['title']) .' </label>';
			echo '<a href="' . $config->getPublicRoot() . '/admin/media-group/' . $mediaGroup['mgid'] . '"';
			if (Utils::hasStringContent($mediaGroup['description'])) {
				echo ' title="' . Utils::escapeString($mediaGroup['description']) . '"';
			}
			if (Utils::isFlagged($mediaGroup['options'], MediaGroupOperations::LOCKED_OPTION)) {
				echo ' class="locked componentLink"';
			}
			else {
				echo ' class="componentLink"';
			}
			echo '>' . Utils::escapeString($mediaGroup['title']) . '</a>';
			if (Utils::hasStringContent($mediaGroup['tags'])) {
				echo '<span class="rowAdditionalInfo">';
				echo Utils::escapeString($mediaGroup['tags']);
				echo '</span>';
			}
			echo '</li>';
		}
		echo '</ul>';
	}

	// --------------------------------------------------------------------------------------------
	// User input handling methods
	// --------------------------------------------------------------------------------------------

	private function handleMediaGroupOperations($isGlobal) {
		if ($isGlobal) {
			$mediaGroups = $this->globalMediaGroups;
		} else {
			$mediaGroups = $this->localMediaGroups;
		}
		$operation = Utils::getUnmodifiedStringOrEmpty('operation');

		// check for media groups
		if (!Utils::isValidFieldIntArray('mediaGroup')) {
			return;
		}

		// normalize media groups
		$uniqueMediaGroups = array_unique(Utils::getValidFieldArray('mediaGroup'));

		// foreach media group
		$result = true;
		foreach ($uniqueMediaGroups as $mediaGroupId) {
			// check if media group exists
			$mediaGroup = Utils::getColumnWithValue($mediaGroups, 'mgid', (int) $mediaGroupId);
			if ($mediaGroup === false) {
				continue;
			}

			// do operation
			switch ($operation) {
				case 'delete':
					// check for lock
					if (Utils::isFlagged($mediaGroup['options'], MediaGroupOperations::LOCKED_OPTION)) {
						$result = false;
						$this->message = 'LOCKED';
					} else {
						$result = $result &&
							$this->mediaGroupOperations->deleteMediaGroup($mediaGroup['mgid']);
					}
					break;
				case 'lock':
					$result = $result && $this->mediaGroupOperations->lockMediaGroup($mediaGroup['mgid']);
					break;
				case 'unlock':
					$result = $result && $this->mediaGroupOperations->unlockMediaGroup($mediaGroup['mgid']);
					break;
				case 'move':
					// check for lock
					if (Utils::isFlagged($mediaGroup['options'], MediaGroupOperations::LOCKED_OPTION)) {
						$result = false;
						$this->message = 'LOCKED';
					} else {
						$result = $result && $this->mediaGroupOperations->moveMediaGroup($mediaGroup['mgid']);
					}
					break;
				case 'copy':
					$result = $result && $this->mediaGroupOperations->copyMediaGroup($mediaGroup['mgid']);
					// TODO COPY FILES!!!!!!!!!
					break;
				default:
					$result = false;
				break;
			}
		}

		// set state
		$this->state = $result;
		if ($result === true) {
			switch ($operation) {
				case 'delete':
					$this->message = 'MEDIA_GROUPS_DELETED';
					break;
				case 'lock':
					$this->message = 'MEDIA_GROUPS_LOCKED';
					break;
				case 'unlock':
					$this->message = 'MEDIA_GROUPS_UNLOCKED';
					break;
				case 'move':
					$this->message = 'MEDIA_GROUPS_MOVED';
					break;
				case 'copy':
					$this->message = 'MEDIA_GROUPS_COPIED';
					break;
			}
		}
		else if (!isset($this->message)) {
			$this->message = 'UNKNOWN_ERROR';
		}
	}

	// --------------------------------------------------------------------------------------------
	// Helper methods
	// --------------------------------------------------------------------------------------------

	

	// --------------------------------------------------------------------------------------------
	// Database loading methods
	// --------------------------------------------------------------------------------------------

	private function loadMediaGroups($isGlobal) {
		if ($isGlobal) {
			$globalMediaGroups = $this->mediaGroupOperations->getGlobalMediaGroups();
			if ($globalMediaGroups === false) {
				$this->state = false;
				$this->message = 'UNKNOWN_ERROR';
			}
			$this->globalMediaGroups = $globalMediaGroups;
		} else {
			$localMediaGroups = $this->mediaGroupOperations->getLocalMediaGroups();
			if ($localMediaGroups === false) {
				$this->state = false;
				$this->message = 'UNKNOWN_ERROR';
			}
			$this->localMediaGroups = $localMediaGroups;
		}
	}
}

?>