<?php

// v1: FEATURE COMPLETE

class AdminExportFieldGroupModule extends BasicModule {

	// database operations
	private $moduleOperations;

	// UI state
	private $errorMessage;

	// member variables
	private $moduleInfo;
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
		// parameters invalid
		else {
			$this->errorMessage = 'FIELD_GROUP_NOT_FOUND';
		}
		// field group valid -> load similar modules
		if (isset($this->fieldGroupInfo)) {
			$this->loadSimilarModules();
		}
	}

	public function printContent($config) {
		?>
		<div class="dialog-box">
			<?php if (isset($this->errorMessage)) : ?>
				<div class="dialog-error-message">
					<?php $this->text($this->errorMessage); ?>
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
						<label for="exportTargetModule"><?php $this->text('TARGET_MODULE'); ?></label>
						<?php $this->printSimilarModuleSelect(); ?>
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

	private function printSimilarModuleSelect() {
		echo '<select id="exportTargetModule">';
		echo '	<option value="empty" selected>';
		$this->text('PLEASE_SELECT');
		echo '	</option>';
		// for each page
		$lastPid = null;
		$lastSection = null;
		foreach ($this->similarModules as $similarModule) {
			if ($lastPid != $similarModule['pid']) {
				if (isset($lastPid)) {
					echo '</optgroup>';
				}
				echo '<optgroup label="' . $similarModule['title'] . '">';
				$lastPid = $similarModule['pid'];
				$lastSection = null;
			}
			if ($lastSection != $similarModule['section']) {
				if (isset($lastSection)) {
					echo '</optgroup>';
				}
				echo '<optgroup label="&nbsp;&nbsp;';
				$this->text(ModuleOperations::translateSectionToLocale($similarModule['section']));
				echo '">';
				$lastSection = $similarModule['section'];
			}
			echo '<option value="' . $similarModule['mid'] . '">' . $this->moduleInfo['name'] . '</option>';
		}
		if (isset($lastSection)) {
			echo '</optgroup>';
		}
		if (isset($lastPid)) {
			echo '</optgroup>';
		}
		echo '</select>';
	}

	// --------------------------------------------------------------------------------------------
	// Database loading methods
	// --------------------------------------------------------------------------------------------

	private function loadModuleAndFieldGroupInfo($moduleDefId, $fieldGroupKey) {
		// check module definition id
		if (!RichModule::isValidModuleDefinitionId($moduleDefId)) {
			$this->errorMessage = 'MODULE_DEFINITION_INVALID';
			return;
		}
		// load module definition
		$moduleDefinition = RichModule::loadModuleDefinition($moduleDefId);
		if ($moduleDefinition === false) {
			$this->errorMessage = 'MODULE_DEFINITION_INVALID';
			return;
		}
		$this->moduleDefinition = $moduleDefinition;
		// load module info
		$this->moduleInfo = RichModule::getLocalizedModuleInfo($moduleDefId);
		// load field info
		$fieldGroupInfo = $moduleDefinition->getFieldGroupInfoOfKey($fieldGroupKey);
		if ($fieldGroupInfo === false) {
			$this->errorMessage = 'FIELD_GROUP_NOT_FOUND';
			return;
		}
		$this->fieldGroupInfo = $fieldGroupInfo;
	}

	private function loadSimilarModules() {
		$similarModules = $this->moduleOperations->getSimilarModulesWithPage(
			$this->moduleDefinition->getName());
		if ($similarModules === false) {
			$this->errorMessage = 'UNKNOWN_ERROR';
			return;
		}
		$this->similarModules = $similarModules;
	}
}

?>