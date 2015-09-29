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
	private $moduleInfo;
	private $moduleDefinition;
	private $moduleConfig;

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
		if (isset($this->module)) {
			$this->loadModuleInfo();
		}
		// if module info is present, load module definition
		if (isset($this->moduleInfo)) {
			$this->loadModuleDefinition();
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
				<section>
					<h1>
						<?php echo Utils::escapeString($this->moduleInfo['name']); ?>
					</h1>

					<?php $this->printFields($this->moduleDefinition->getConfigFieldInfo()); ?>
				</section>
		<?php endif; ?>
		<?php
	}

	// --------------------------------------------------------------------------------------------
	// Printing methods
	// --------------------------------------------------------------------------------------------
	private function printFields($fields) {
		echo '<div class="fields">';
		foreach ($fields as $field) {
			echo '<div class="richField">';
			echo '	<label for="title">';
			echo $this->moduleDefinition->text($field->getName());
			echo '	</label>';
			// check if multi type field
			if (FieldInfo::isMultiTypeField($field->getAllowedContentTypes())) {
				$types = FieldInfo::getTypesOfField($field->getAllowedContentTypes());
				// print type selection
				echo '<ul class="tabs typeSelection">';
				foreach ($types as $type) {
					echo '	<li>';
					echo '		<a class="tab">';
					echo $this->text(FieldInfo::translateTypeToString($type));
					echo '		</a>';
					echo '	</li>';
				}
				echo '</ul>';
			}
			/*$field->printField(
				$field->getKey(),
				, $large, $minLength, $maxLength);*/
			echo '</div>';
		}
		echo '</div>';
	}

	// --------------------------------------------------------------------------------------------
	// User input handling methods
	// --------------------------------------------------------------------------------------------


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