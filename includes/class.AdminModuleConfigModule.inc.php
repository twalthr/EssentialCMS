<?php

// v1: FEATURE COMPLETE

class AdminModuleConfigModule extends BasicModule {

	// database operations
	private $moduleOperations;
	private $fieldGroupOperations;
	private $fieldOperations;

	// UI state
	private $state;
	private $message;
	private $field;

	// member variables
	private $module;
	private $moduleInfo; // translated name and description
	private $moduleDefinition; // instance of RichModule
	private $moduleConfigFieldGroupInfo; // instance of FieldGroupInfo
	private $moduleConfigFieldGroup; // config field group stored in database
	private $moduleConfigFields; // config fields content stored in database

	public function __construct($moduleOperations, $fieldGroupOperations, $fieldOperations,
			$parameters = null) {
		parent::__construct(1, 'admin-module-config');
		$this->moduleOperations = $moduleOperations;
		$this->fieldGroupOperations = $fieldGroupOperations;
		$this->fieldOperations = $fieldOperations;

		// module id is present
		if (isset($parameters) && count($parameters) > 0) {
			$this->loadModule($parameters[0]);
		}
		// parameters invalid
		else {
			$this->state = false;
			$this->message = 'PARAMETERS_INVALID';
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
		if (!isset($this->moduleDefinition) || !isset($this->moduleConfigFields)) {
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
		<?php if (isset($this->moduleDefinition)) : ?>
		<script type="text/javascript">
			$(document).ready(function() {
				$('#cancelConfig').click(function() {
					window.open('<?php echo $config->getPublicRoot(); ?>/admin/module/<?php 
						echo $this->module['mid']; ?>', '_self');
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
					<?php if (isset($this->field)) : ?>
						<?php $this->moduleDefinition->text($this->field); ?>:
					<?php endif; ?>
					<?php $this->text($this->message); ?>
				</div>
			<?php endif; ?>
		<?php endif; ?>
		<?php if (isset($this->moduleDefinition)) : ?>
			<form method="post">
				<input type="hidden" name="operationSpace" value="fields" />
				<section>
					<h1>
						<?php $this->text('MODULE_CONFIG'); ?>: 
							<?php echo Utils::escapeString($this->moduleInfo['name']); ?>
					</h1>
					<div class="buttonSet general">
						<input type="submit" value="<?php $this->text('SAVE'); ?>" />
						<button id="cancelConfig"><?php $this->text('CANCEL'); ?></button>
					</div>
					<div class="fields">
						<?php $this->moduleConfigFieldGroupInfo->printFields($this->moduleConfigFields); ?>
					</div>
				</section>
			</form>
		<?php endif; ?>
		<?php
	}

	// --------------------------------------------------------------------------------------------
	// User input handling methods
	// --------------------------------------------------------------------------------------------

	private function handleEditConfig() {
		// validate fields
		$result = $this->moduleConfigFieldGroupInfo->validateFields();
		// validation was not successful
		if ($result !== true) {
			$this->state = false;
			$this->message = $result[0];
			$this->field = $result[1];
			return;
		}

		// handle edit
		$result = $this->moduleConfigFieldGroupInfo->handleEditFieldGroup(
			$this->moduleConfigFieldGroup,
			$this->moduleConfigFields,
			$this->fieldOperations);
		if ($result === true) {
			$this->state = true;
			$this->message = 'MODULE_CONFIG_CHANGED';
		}
		else {
			$this->state = false;
			$this->message = $result;
		}
	}

	// --------------------------------------------------------------------------------------------
	// Helper methods
	// --------------------------------------------------------------------------------------------

	private function getConfigContent($key) {
		if (!isset($this->moduleConfigFields)) {
			return null;
		}
		$value = Utils::getColumnWithValues($this->moduleConfigFields, 'key', $key);
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
		$this->moduleConfigFieldGroupInfo = $moduleDefinition->getConfigAsFieldGroupInfo();
	}

	private function loadModuleConfig() {
		$moduleConfigFieldGroup = $this->fieldGroupOperations->getConfigFieldGroupId($this->module['mid']);
		if ($moduleConfigFieldGroup === false) {
			$this->state = false;
			$this->message = 'UNKNOWN_ERROR';
			return;
		}
		$this->moduleConfigFieldGroup = $moduleConfigFieldGroup;
		$moduleConfigFields = $this->fieldOperations->getFields($moduleConfigFieldGroup);
		if ($moduleConfigFields === false) {
			$this->state = false;
			$this->message = 'UNKNOWN_ERROR';
			return;
		}
		$this->moduleConfigFields = $moduleConfigFields;
	}
}

?>