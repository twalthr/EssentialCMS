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
					id="mediaOperations">
				<button id="createMediaGlobalGroup" class="addButton">
					<?php $this->text('CREATE_MEDIA_GROUP'); ?>
				</button>
				<input type="hidden" name="operationSpace" value="global" />
				<input type="hidden" name="operation" id="mediaGlobalOperation" />
				<?php $this->printMediaGroups($config, true); ?>
				<div class="buttonSet">
					<button id="mediumDelete"  class="disableListIfClicked" disabled>
						<?php $this->text('DELETE'); ?></button>
				</div>
				<div class="dialog-box hidden">
					<div class="dialog-message"></div>
					<div class="options">
						<button id="mediumDeleteConfirm" class="hidden"><?php $this->text('DELETE'); ?></button>
						<button class="hidden cancel"><?php $this->text('CANCEL'); ?></button>
					</div>
				</div>
			</form>
		</section>
		<section>
			<h1><?php $this->text('LOCAL_MEDIA_GROUPS'); ?></h1>
			<form method="post" action="<?php echo $config->getPublicRoot(); ?>/admin/media"
					id="mediaOperations">
				<button id="createMediaLocalGroup" class="addButton">
					<?php $this->text('CREATE_MEDIA_GROUP'); ?>
				</button>
				<input type="hidden" name="operationSpace" value="local" />
				<input type="hidden" name="operation" id="mediaLocalOperation" />
				<?php $this->printMediaGroups($config, false); ?>
				<div class="buttonSet">
					<button id="mediumDelete"  class="disableListIfClicked" disabled>
						<?php $this->text('DELETE'); ?></button>
				</div>
				<div class="dialog-box hidden">
					<div class="dialog-message"></div>
					<div class="options">
						<button id="mediumDeleteConfirm" class="hidden"><?php $this->text('DELETE'); ?></button>
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