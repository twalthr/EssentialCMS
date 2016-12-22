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
		}
		// handle global media group operations
		else if (Utils::getUnmodifiedStringOrEmpty('operationSpace') === 'local') {
			$this->handleMediaGroupOperations(false);
			// reload media groups
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
					<button id="delete" class="disableListIfClicked" disabled>
						<?php $this->text('DELETE'); ?>
					</button>
					<button id="lock" class="disableListIfClicked" disabled>
						<?php $this->text('LOCK'); ?>
					</button>
					<button id="unlock" class="disableListIfClicked" disabled>
						<?php $this->text('UNLOCK'); ?>
					</button>
					<button id="move" class="disableListIfClicked" disabled>
						<?php $this->text('MOVE'); ?>
					</button>
				</div>
				<div class="dialog-box hidden">
					<div class="dialog-message"></div>
					<div class="options">
						<button id="mediumDeleteConfirm" class="hidden">
							<?php $this->text('DELETE'); ?></button>
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
					<button id="delete" class="disableListIfClicked" disabled>
						<?php $this->text('DELETE'); ?>
					</button>
					<button id="lock" class="disableListIfClicked" disabled>
						<?php $this->text('LOCK'); ?>
					</button>
					<button id="unlock" class="disableListIfClicked" disabled>
						<?php $this->text('UNLOCK'); ?>
					</button>
					<button id="move" class="disableListIfClicked" disabled>
						<?php $this->text('MOVE'); ?>
					</button>
				</div>
				<div class="dialog-box hidden">
					<div class="dialog-message"></div>
					<div class="options">
						<button id="mediumDeleteConfirm" class="hidden">
							<?php $this->text('DELETE'); ?></button>
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
		foreach ($this->mediaGroups as $mediaGroup) {
			echo '<li class="rowLike">';
			echo '<input type="checkbox" id="mediaGroup' . $mediaGroup['mgid'] . '" name="mediaGroup[]"';
			echo ' value="' . $mediaGroup['mgid'] . '" />';
			echo '<label for="mediaGroup' . $mediaGroup['mgid'] . '" class="checkbox">';
			echo Utils::escapeString($mediaGroup['title']) .' </label>';
			echo '<a href="' . $config->getPublicRoot() . '/admin/media-group/' . $mediaGroup['pid'] . '"';
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
		$operation = Utils::getUnmodifiedStringOrEmpty('operation');

		
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