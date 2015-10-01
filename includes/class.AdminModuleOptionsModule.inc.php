<?php

class AdminModuleOptionsModule extends BasicModule {

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
	private $moduleConfig; // config stored in database

	public function __construct(
			$moduleOperations, $fieldGroupOperations, $fieldOperations, $parameters = null) {
		parent::__construct(1, 'admin-module-options');
		$this->moduleOperations = $moduleOperations;
		$this->fieldGroupOperations = $fieldGroupOperations;
		$this->fieldOperations = $fieldOperations;

		// module id is present
		if (isset($parameters)
			&& count($parameters) > 0) {
			$this->loadModule($parameters[0]);
		}
		// if module is present, load module info
		if (!isset($this->module)) {
			return;
		}
		$this->loadModuleInfo();
		// if module info is present, load module definition
		// and the corresponding config
		if (!isset($this->moduleInfo)) {
			return;
		}
		$this->loadModuleDefinition();
		$this->loadModuleConfig();

		// if module config has been loaded
		if (!isset($this->moduleDefinition) || !isset($this->moduleConfig)) {
			return;
		}

		// handle user input
		if (Utils::getUnmodifiedStringOrEmpty('operationSpace') === 'fields') {
			$this->handleEditConfig();
			// refresh
			$this->loadModuleConfig();
		}
	}

	public function printContent($config) {
		?>
		<script type="text/javascript">
			$(document).ready(function() {
				
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
		<?php if (isset($this->moduleDefinition)) : ?>
			<form method="post">
				<input type="hidden" name="operationSpace" value="fields" />
				<section>
					<h1>
						<?php echo Utils::escapeString($this->moduleInfo['name']); ?>
					</h1>

					<?php $this->printFields($this->moduleDefinition->getConfigFieldInfo()); ?>
					<div class="buttonSet">
						<input type="submit" value="<?php $this->text('SAVE'); ?>" />
					</div>
				</section>
			</form>
		<?php endif; ?>
		<?php
	}

	// --------------------------------------------------------------------------------------------
	// Printing methods
	// --------------------------------------------------------------------------------------------

	private function printFields($fields) {
		echo '<div class="fields">';
		foreach ($fields as $field) {
			$configValue = $this->getConfigValue($field->getKey());
			if (isset($configValue)) {
				$field->printFieldWithLabel($this->moduleDefinition, $configValue['type'], $configValue['content']);
			}
			else {
				$field->printFieldWithLabel($this->moduleDefinition);
			}
		}
		echo '</div>';
	}

	// --------------------------------------------------------------------------------------------
	// User input handling methods
	// --------------------------------------------------------------------------------------------

	private function handleEditConfig() {
		$fields = $this->moduleDefinition->getConfigFieldInfo();
		foreach ($fields as $field) {
			$result = true;
			// check content
			$result = $field->isValidContentInput('typeof_' . $field->getKey(), 'valueof_' . $field->getKey());

			// validation was not successful
			if ($result !== true) {
				$this->state = false;
				$this->message = $result;
				return;
			}

			echo 'SUCCESS!!!!';
		}
	}


	// --------------------------------------------------------------------------------------------
	// Helper methods
	// --------------------------------------------------------------------------------------------

	private function getConfigValue($key) {
		if (!isset($this->moduleConfig)) {
			return null;
		}
		$value = Utils::getColumnWithValue($this->moduleConfig, 'key', $key);
		if ($value === false) {
			return null;
		}
		return $value;
	}

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

	private function loadModuleConfig() {
		$moduleConfig = $this->fieldGroupOperations->getConfigFieldGroupId($this->module['mid']);
		if ($moduleConfig === false) {
			return;
		}
		$moduleConfigFields = $this->fieldOperations->getFields($moduleConfig);
		if ($moduleConfigFields === false) {
			return;
		}
		$this->moduleConfig = $moduleConfigFields;
	}
}

?>