<?php

class AdminEditModuleModule extends BasicModule {

	// database operations
	private $moduleOperations;
	private $fieldGroupOperations;
	private $fieldOperations;

	// UI state
	private $state;
	private $message;

	// member variables
	private $module;
	private $moduleInfo; // translated name and description
	private $moduleDefinition; // instance of RichModule

	public function __construct(
			$moduleOperations, $fieldGroupOperations, $fieldOperations, $parameters = null) {
		parent::__construct(1, 'admin-edit-module');
		$this->moduleOperations = $moduleOperations;
		$this->fieldGroupOperations = $fieldGroupOperations;
		$this->fieldOperations = $fieldOperations;

		// module id is present
		if (isset($parameters)
			&& count($parameters) > 0) {
			$this->loadModule($parameters[0]);
		}
		// if module is present, load module info
		if (isset($this->module)) {
			$this->loadModuleInfo();
		}
		// if module info is present, load module definition
		if (isset($this->moduleInfo)) {
			$this->loadModuleDefinition();
		}
	}

	public function printContent($config) {
		?>
		<?php if (isset($this->moduleDefinition)) : ?>
			<script type="text/javascript">
				$(document).ready(function() {
					$('#editModuleOptions').click(function() {
						window.open('<?php echo $config->getPublicRoot(); ?>/admin/module-options/<?php
							echo $this->module['mid']; ?>', '_self');
					});
					$('#editModuleCancel').click(function() {
						window.open('<?php echo $config->getPublicRoot(); ?>/admin/page/<?php
							echo $this->module['page']; ?>', '_self');
					});
				});
			</script>
		<?php endif; ?>
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
		<?php if (isset($this->moduleDefinition)) : ?>
			<section>
					<h1>
						<?php $this->text('MODULE'); ?>
						<?php echo Utils::escapeString($this->moduleInfo['name']); ?>
					</h1>
					<div class="buttonSet general">
						<button id="editModuleOptions">
							<?php $this->text('EDIT_MODULE_CONFIG'); ?>
						</button>
						<button id="editModuleCancel">
							<?php $this->text('CANCEL'); ?>
						</button>
					</div>
			</section>
			<?php $this->printFieldGroups(); ?>
		<?php endif; ?>
		<?php
	}

	// --------------------------------------------------------------------------------------------
	// Printing methods
	// --------------------------------------------------------------------------------------------

	private function printFieldGroups() {
		$fieldGroups = $this->moduleDefinition->getFieldGroupInfo();
		echo '<div class="fieldGroups">';
		foreach ($fieldGroups as &$fieldGroup) {
			$fieldGroups = false;
			if ($fieldGroup->isOnePagePerGroup) {
				$fieldGroups = $fieldOperations->getFieldGroupsWithTitle(
					$this->module['mid'], $fieldGroup->getKey());
			}
			else {
				$fieldGroups = $fieldOperations->getFieldGroups(
					$this->module['mid'], $fieldGroup->getKey());
			}
			if ($fieldGroups === false) {
				continue;
			}
			$fieldGroup->printFieldGroupSection($this->moduleDefinition, $fieldGroups, $this->fieldOperations);
		}
		echo '</div>';
	}

	// --------------------------------------------------------------------------------------------
	// User input handling methods
	// --------------------------------------------------------------------------------------------

	

	// --------------------------------------------------------------------------------------------
	// Database loading methods
	// --------------------------------------------------------------------------------------------

	private function loadModule($moduleId) {
		if (!Utils::isValidInt($moduleId)) {
			$this->state = false;
			$this->message = 'MODULE_NOT_FOUND';
			return;
		}
		$module = $this->moduleOperations->getModule($moduleId);
		if ($module === false) {
			$this->state = false;
			$this->message = 'MODULE_NOT_FOUND';
			return;
		}
		$this->module = $module;
	}

	private function loadModuleInfo() {
		if (!RichModule::isValidModuleDefinitionId($this->module['definitionId'])) {
			$this->state = false;
			$this->message = 'MODULE_DEFINITION_INVALID';
			return;
		}
		$this->moduleInfo = RichModule::getLocalizedModuleInfo($this->module['definitionId']);
	}

	private function loadModuleDefinition() {
		$moduleDefinition = RichModule::loadModuleDefinition($this->module['definitionId']);
		if ($moduleDefinition === false) {
			$this->state = false;
			$this->message = 'MODULE_DEFINITION_INVALID';
			return;
		}
		$this->moduleDefinition = $moduleDefinition;
	}
}

?>