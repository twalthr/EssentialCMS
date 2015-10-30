<?php

class AdminExportFieldGroupModule extends BasicModule {

	// database operations
	private $moduleOperations;

	// UI state
	private $state;
	private $message;

	// member variables
	private $moduleDefinition;
	private $fieldGroupInfo;
	private $similarModules;

	public function __construct($moduleOperations, $parameters = null) {
		parent::__construct(1, 'admin-export-field-group-module');
		$this->moduleOperations = $moduleOperations;

		// module definition id and field group key are defined
		if (isset($parameters)
			&& count($parameters) > 1) {
			$this->loadModuleAndFieldGroupInfo($parameters[0], $parameters[1]);
		}
		// field group valid -> load similar modules
		if (isset($fieldGroupInfo)) {
			$this->loadSimilarModules();
		}

	}

	public function printContent($config) {
		?>
		<?php if (!isset($this->state)) : ?>
		<script type="text/javascript">
			$(document).ready(function() {
				$('#exportTargetSection').change(function() {
					var select = $(this).val();
					if (select === 'empty' || select.indexOf('global') === 0) {
						$('#exportTargetSectionField').addClass('hidden');
					}
					else {
						$('#exportTargetSectionField').removeClass('hidden');
					}
				});
			});
		</script>
		<?php endif; ?>
		<div class="dialog-box">
			<?php if (isset($this->state)) : ?>
				<div class="dialog-error-message">
					<?php $this->text($this->message); ?>
				</div>
			<?php else: ?>
				<h1>
					<?php $this->text('FIELD_GROUP_EXPORT',
						Utils::escapeString(
							$this->moduleDefinition->textString($this->fieldGroupInfo->getNamePlural())
						)); ?></h1>
				<div class="dialog-message">
					<?php $this->text('EXPORT_FIELD_GROUP',
						Utils::escapeString(
							$this->moduleDefinition->textString($this->fieldGroupInfo->getNamePlural())
						)); ?>
				</div>
				<div class="fields">
					<div class="field">
						<label for="exportTargetSection"><?php $this->text('TARGET_SECTION'); ?></label>
						<?php $this->printSectionListAsSelect(); ?>
					</div>
					<div class="field hidden" id="exportTargetSectionField">
						<label for="exportTargetPage"><?php $this->text('TARGET_PAGE'); ?></label>
						<?php $this->printPageListAsSelect(); ?>
					</div>
				</div>
				<div class="options">
					<button id="exportConfirm"><?php $this->text('EXPORT'); ?></button>
					<button class="lightbox-close"><?php $this->text('CANCEL'); ?></button>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	// --------------------------------------------------------------------------------------------
	// Printing methods
	// --------------------------------------------------------------------------------------------


	// --------------------------------------------------------------------------------------------
	// Database loading methods
	// --------------------------------------------------------------------------------------------

	private function loadModuleAndFieldGroupInfo($moduleDefId, $fieldGroupKey) {
		// check module definition id
		if (!RichModule::isValidModuleDefinitionId($moduleDefId)) {
			$this->state = false;
			$this->message = 'MODULE_DEFINITION_INVALID';
			return;
		}
		// load module definition
		$moduleDefinition = RichModule::loadModuleDefinition($moduleDefId);
		if ($moduleDefinition === false) {
			$this->state = false;
			$this->message = 'MODULE_DEFINITION_INVALID';
			return;
		}
		$this->moduleDefinition = $moduleDefinition;
		$fieldGroupInfo = $moduleDefinition->getFieldGroupInfoOfKey($fieldGroupKey);
		if ($fieldGroupInfo === false) {
			$this->state = false;
			$this->message = 'FIELD_GROUP_NOT_FOUND';
			return;
		}
		$this->fieldGroupInfo = $fieldGroupInfo;
	}

	private function loadSimilarModules() {
		$similarModules = $this->moduleOperations->getSimilarModulesWithPage($moduleDefinition->getName());
		echo var_dump($similarModules);
	}
}

?>