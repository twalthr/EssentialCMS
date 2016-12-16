<?php

// v1: FEATURE COMPLETE

class AdminChangelogModule extends BasicModule {

	// database operations
	private $changelogOperations;

	// UI state
	private $state;
	private $message;
	private $errorReason;

	// member variables
	private $changelog;
	private $compiler;

	public function __construct($changelogOperations, $compiler) {
		parent::__construct(1, 'admin-changelog');
		$this->changelogOperations = $changelogOperations;
		$this->compiler = $compiler;

		// load changelog
		$this->loadChangelog();
		if (!isset($this->changelog)) {
			return;
		}

		// handle changelog operations
		if (Utils::getUnmodifiedStringOrEmpty('operationSpace') === 'changelog') {
			$this->handleChangelogOperations();
			// reload changelog
			$this->loadChangelog();
		}
	}

	public function printContent($config) {
		?>
		<script type="text/javascript">
			$(document).ready(function(){
				$('#compileChanges').click(function() {
					$('.buttonSet').addClass('hidden');
					$('.spinner').removeClass('hidden');
					$('.description').text('<?php $this->text('PLEASE_WAIT'); ?>');

					var refresh = function() {
						$('#changelogOperation').val('refresh');
						$('#changelogOperations').submit();
					}

					var compile = function() {
						$.ajax({
							'url': $('#changelogOperations').attr('action'),
							'method': 'POST',
							'data': $('#changelogOperations').serialize(),
							'dataType': 'json',
							'success': function(data) {
								if (data.status == 'error') {
									refresh();
								}
								else if (data.status == 'intermediate') {
									var changes = $('.changelog li');
									// only update list if it in sync with server list
									if (data.processed < changes.length) {
										changes.find(':lt(' + data.processed + ')').remove();
									}
									// continue compilation
									compile();
								}
								else if (data.status == 'success') {
									refresh();
								}
								else {
									refresh();
								}
							},
							'error': refresh,
							'timeout': <?php echo $config->getMaxRuntime(); ?> + 5
						});
					}

					// compile
					compile();
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
					<?php $this->text($this->message, $this->errorReason); ?>
				</div>
			<?php endif; ?>
		<?php endif; ?>
		<section>
			<h1><?php $this->text('CHANGELOG'); ?></h1>
			<?php if ($this->changelog === false || empty($this->changelog)) : ?>
				<p class="empty">
					<?php $this->text('NO_CHANGES'); ?>
				</p>
			<?php else : ?>
				<form method="post" action="<?php echo $config->getPublicRoot(); ?>/admin/changes"
						id="changelogOperations">
					<input type="hidden" name="operationSpace" value="changelog" />
					<input type="hidden" name="operation" id="changelogOperation" value="compile" />
					<p class="description">
						<?php $this->text('CHANGELOG_INTRODUCTION'); ?>
					</p>
					<div class="buttonSet general">
						<button id="compileChanges">
							<?php $this->text('COMPILE_CHANGES'); ?>
						</button>
					</div>
					<div class="spinner hidden">
						<div class="bounce1"></div>
						<div class="bounce2"></div>
						<div class="bounce3"></div>
					</div>
					<?php $this->printChangelog($config); ?>
				</form>
			<?php endif; ?>
		</section>
		<?php
	}

	// --------------------------------------------------------------------------------------------
	// Printing methods
	// --------------------------------------------------------------------------------------------

	private function printChangelog($config) {
		echo '<ul class="tableLike changelog">';
		foreach ($this->changelog as $change) {
			echo '<li class="rowLike">';
			switch ($change['operation']) {
				case changelogOperations::CHANGELOG_OPERATION_UPDATED:
					echo '<label class="changeOperation updated" title="';
					$this->text('UPDATED');
					echo '">';
					$this->text('UPDATED');
					echo '</label>';
					break;
				case changelogOperations::CHANGELOG_OPERATION_DELETED:
					echo '<label class="changeOperation deleted" title="';
					$this->text('DELETED');
					echo '">';
					$this->text('DELETED');
					echo '</label>';
					break;
			}
			switch ($change['type']) {
				case ChangelogOperations::CHANGELOG_TYPE_GLOBAL:
					echo '<a href="' . $config->getPublicRoot() . '/admin/overview" class="componentLink">';
					$this->text('GLOBAL_CHANGE');
					echo '</a>';
					break;
				case ChangelogOperations::CHANGELOG_TYPE_PAGE:
					echo '<a href="' . $config->getPublicRoot() . '/admin/page/' .
						$change['recordId'] . '" class="componentLink">';
					$this->text('PAGE_CHANGE');
					echo '</a>';
					break;
				case ChangelogOperations::CHANGELOG_TYPE_MODULE:
					echo '<a href="' . $config->getPublicRoot() . '/admin/module/' .
						$change['recordId'] . '" class="componentLink">';
					$this->text('MODULE_CHANGE');
					echo '</a>';
					break;
				case ChangelogOperations::CHANGELOG_TYPE_FIELD_GROUP:
					echo '<a href="' . $config->getPublicRoot() . '/admin/field-group/' .
						$change['recordId'] . '" class="componentLink">';
					$this->text('FIELD_GROUP_CHANGE');
					echo '</a>';
					break;
				case ChangelogOperations::CHANGELOG_TYPE_MEDIA_REFERENCE:
					echo 'TODO';
					break;
			}
			if (Utils::hasStringContent($change['description'])) {
				echo '<span class="rowAdditionalInfo">';
				echo Utils::escapeString($change['description']);
				echo '</span>';
			}
			echo '</li>';
		}
		echo '</ul>';
	}

	// --------------------------------------------------------------------------------------------
	// User input handling methods
	// --------------------------------------------------------------------------------------------

	private function handleChangelogOperations() {
		$operation = Utils::getUnmodifiedStringOrEmpty('operation');

		switch ($operation) {
			case 'compile':
				$this->compiler->compile($this->changelog);
				// check if compilation has finished
				if ($this->compiler->hasFinished()) {
					// success
					if ($this->compiler->wasSuccessful()) {
						$data = array('status' => 'success');
						die(json_encode($data));
					}
					// error
					else {
						$data = array('status' => 'error');
						die(json_encode($data));
					}
				}
				// compilation is not completed yet
				else {
					$processedChanges = $this->compiler->getProcessedChanges();
					$data = array('status' => 'intermediate', 'processed' => $processedChanges);
					die(json_encode($data));
				}
				break;
			default:
				$lastErrorReason = $this->compiler->getErrorReason();
				if (isset($lastErrorReason)) {
					$this->state = false;
					$this->message = 'CHANGES_ERROR';
					$this->errorReason = $lastErrorReason;
				}
				else if (empty($this->changelog)) {
					$this->state = true;
					$this->message = 'CHANGES_PUBLISHED';
				}
				else {
					$this->state = false;
					$this->message = 'UNKNOWN_ERROR';
				}
				break;
		}
	}

	// --------------------------------------------------------------------------------------------
	// Database loading methods
	// --------------------------------------------------------------------------------------------

	private function loadChangelog() {
		$changelog = $this->changelogOperations->getChanges();
		if ($changelog === false) {
			$this->state = false;
			$this->message = 'UNKNOWN_ERROR';
			return;
		}
		$this->changelog = $changelog;
	}
}

?>