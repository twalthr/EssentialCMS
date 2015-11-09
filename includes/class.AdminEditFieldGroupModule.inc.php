<?php

// v1: FEATURE COMPLETE

class AdminEditFieldGroupModule extends BasicModule {

	// database operations
	private $moduleOperations;
	private $fieldGroupOperations;
	private $fieldOperations;

	// UI state
	private $state;
	private $message;
	private $field;
	private $fieldGroupName;

	// member variables
	private $config; // config for page redirect after field group creation
	private $fieldGroup; // field group stored in database
	private $module; // module stored in database
	private $moduleDefinition; // instance of RichModule
	private $fieldGroupInfo; // field group defined by module definition
	private $fieldsContent; // fields content stored in database

	public function __construct($config, $moduleOperations, $fieldGroupOperations,
			$fieldOperations, $parameters = null) {
		parent::__construct(1, 'admin-edit-field-group');
		$this->config = $config;
		$this->moduleOperations = $moduleOperations;
		$this->fieldGroupOperations = $fieldGroupOperations;
		$this->fieldOperations = $fieldOperations;

		// module id and field group key is present
		// for new fieldgroup
		if (isset($parameters) && count($parameters) > 1 && $parameters[1] !== '.success') {
			$this->loadModuleAndFieldGroupInfo($parameters[0], $parameters[1]);
		}
		// field group id is present
		// for editing existing field group
		else if (isset($parameters) && count($parameters) > 0) {
			$this->loadFieldGroup($parameters[0]);
			if (!isset($this->fieldGroup)) {
				return;
			}

			// load module and field group info
			$this->loadModuleAndFieldGroupInfo($this->fieldGroup['module'], $this->fieldGroup['key']);
			if (!isset($this->fieldGroupInfo)) {
				return;
			}

			// show success message for newly created field group
			if (!isset($this->state) && count($parameters) > 1 && $parameters[1] === '.success') {
				$this->state = true;
				$this->message = 'FIELD_GROUP_CREATED';
				$this->fieldGroupName = $this->fieldGroupInfo->getName();
			}
		}
		// parameters invalid
		else {
			$this->state = false;
			$this->message = 'PARAMETERS_INVALID';
		}

		if (!isset($this->fieldGroupInfo)) {
			return;
		}

		// load field content
		$this->loadFieldsContent();

		// handle user input
		if (Utils::getUnmodifiedStringOrEmpty('operationSpace') === 'fields') {
			$this->handleEditFieldGroup();
			// refresh
			$this->loadFieldsContent();
		}
	}

	public function printContent($config) {
		?>
		<?php if (isset($this->fieldGroupInfo)) : ?>
			<script type="text/javascript">
				$(document).ready(function() {
					$('#cancel').click(function() {
						window.open('<?php echo $config->getPublicRoot(); ?>/admin/module/<?php 
							echo $this->module['mid']; ?>', '_self');
					});
				});
			</script>
		<?php endif; ?>
		<?php if (isset($this->state)) : ?>
			<?php if ($this->state === true) : ?>
				<div class="dialog-success-message">
					<?php $this->text($this->message,
						Utils::escapeString(
							$this->moduleDefinition->textString(
								$this->fieldGroupName)
						)); ?>
				</div>
			<?php elseif ($this->state === false && !isset($this->moduleDefinition)) : ?>
				<div class="dialog-error-message">
					<?php $this->text($this->message); ?>
				</div>
			<?php else: ?>
				<div class="dialog-error-message">
					<?php if (isset($this->field)) : ?>
						<?php $this->moduleDefinition->text($this->field); ?>:
					<?php endif; ?>
					<?php $this->text($this->message,
						Utils::escapeString(
							$this->moduleDefinition->textString(
								$this->fieldGroupName)
						)); ?>
				</div>
			<?php endif; ?>
		<?php endif; ?>
		<?php if (isset($this->fieldGroupInfo)) : ?>
			<form method="post"
				<?php if (isset($this->fieldGroup)) : ?>
					action="<?php echo $config->getPublicRoot(); ?>/admin/field-group/<?php 
						echo $this->fieldGroup['fgid']; ?>"
				<?php endif; ?>>
				<input type="hidden" name="operationSpace" value="fields" />
				<section>
					<h1>
						<?php if (isset($this->fieldGroup)) : ?>
							<?php $this->text('EDIT_FIELD_GROUP',
									Utils::escapeString(
										$this->moduleDefinition->textString(
											$this->fieldGroupInfo->getName())
									)); ?>
							<?php else: ?>
							<?php $this->text('ADD_FIELD_GROUP',
									Utils::escapeString(
										$this->moduleDefinition->textString(
											$this->fieldGroupInfo->getName())
									)); ?>
						<?php endif; ?>
					</h1>
					<div class="buttonSet general">
						<?php if (isset($this->fieldGroup)) : ?>
							<input type="submit" value="<?php $this->text('SAVE'); ?>" />
						<?php else: ?>
							<input type="submit" value="<?php $this->text('CREATE'); ?>" />
						<?php endif; ?>
						<button id="cancel"><?php $this->text('CANCEL'); ?></button>
					</div>
					<div class="fields">
						<?php $this->fieldGroupInfo->printFields($this->moduleDefinition,
								$this->fieldsContent); ?>
					</div>
				</section>
			</form>
		<?php endif; ?>
		<?php
	}

	// --------------------------------------------------------------------------------------------
	// User input handling methods
	// --------------------------------------------------------------------------------------------

	private function handleEditFieldGroup() {
		// validate fields
		$result = $this->fieldGroupInfo->validateFields();
		// validation was not successful
		if ($result !== true) {
			$this->state = false;
			$this->message = $result[0];
			$this->field = $result[1];
			$this->fieldGroupName = $result[2];
			return;
		}

		// field group does not exist yet, create new fieldgroup
		$newFieldGroupId = null;
		if (!isset($this->fieldGroup)) {
			$numberOfFieldGroups = $this->fieldGroupOperations->getNumberOfFieldGroups($this->module['mid'],
				$this->fieldGroupInfo->getKey());
			if ($numberOfFieldGroups === false) {
				$this->state = false;
				$this->message = 'UNKNOWN_ERROR';
				return;
			}
			// do not allow new field group if maximum number is reached
			if ($this->fieldGroupInfo->getMaxNumberOfGroups() !== null
					&& $numberOfFieldGroups >= $this->fieldGroupInfo->getMaxNumberOfGroups()) {
				$this->state = false;
				$this->message = 'FIELD_GROUP_MAXIMUM_REACHED';
				$this->fieldGroupName = $this->fieldGroupInfo->getNamePlural();
				return;
			}
			// create new field group
			$fieldGroupId = $this->fieldGroupOperations->addFieldGroup($this->module['mid'],
				$this->fieldGroupInfo->getKey());
			if ($fieldGroupId === false) {
				$this->state = false;
				$this->message = 'UNKNOWN_ERROR';
				return;
			}
			$this->loadFieldGroup($fieldGroupId);
			if (!isset($this->fieldGroup)) {
				$this->state = false;
				$this->message = 'UNKNOWN_ERROR';
				return;
			}
			$newFieldGroupId = $fieldGroupId;
		}

		// handle edit
		$result = $this->fieldGroupInfo->handleEditFieldGroup($this->fieldGroup['fgid'], $this->fieldsContent,
			$this->fieldOperations);
		if ($result === true) {
			$this->state = true;
			$this->message = 'FIELD_GROUP_CHANGED';
			$this->fieldGroupName = $this->fieldGroupInfo->getName();

			// redirect
			if (isset($newFieldGroupId)) {
				Utils::redirect($this->config->getPublicRoot() . '/admin/field-group/' .
					$newFieldGroupId . '/.success');
			}
		}
		else {
			$this->state = false;
			$this->message = $result;
		}
	}

	// --------------------------------------------------------------------------------------------
	// Database loading methods
	// --------------------------------------------------------------------------------------------

	private function loadFieldGroup($fgid) {
		if (!Utils::isValidInt($fgid)) {
			$this->state = false;
			$this->message = 'FIELD_GROUP_NOT_FOUND';
			return;
		}
		$fieldGroupId = (int) $fgid;
		$fieldGroup = $this->fieldGroupOperations->getFieldGroup($fieldGroupId);
		if ($fieldGroup === false) {
			$this->state = false;
			$this->message = 'FIELD_GROUP_NOT_FOUND';
			return;
		}
		$this->fieldGroup = $fieldGroup;
	}

	private function loadModuleAndFieldGroupInfo($mid, $fieldGroupKey) {
		// check module id
		if (!Utils::isValidInt($mid)) {
			$this->state = false;
			$this->message = 'MODULE_DEFINITION_INVALID';
			return;
		}
		$moduleId = (int) $mid;
		$module = $this->moduleOperations->getModule($moduleId);
		if ($module === false) {
			$this->state = false;
			$this->message = 'MODULE_NOT_FOUND';
			return;
		}
		$this->module = $module;
		// load module definition
		$moduleDefinition = RichModule::loadModuleDefinition($module['definitionId']);
		if ($moduleDefinition === false) {
			$this->state = false;
			$this->message = 'MODULE_DEFINITION_INVALID';
			return;
		}
		$this->moduleDefinition = $moduleDefinition;
		// load field info
		$fieldGroupInfo = $moduleDefinition->getFieldGroupInfoOfKey($fieldGroupKey);
		if ($fieldGroupInfo === false) {
			$this->state = false;
			$this->message = 'FIELD_GROUP_NOT_FOUND';
			return;
		}
		$this->fieldGroupInfo = $fieldGroupInfo;
	}

	private function loadFieldsContent() {
		$fieldsContent = $this->fieldOperations->getFields($this->fieldGroup['fgid']);
		if ($fieldsContent === false) {
			$this->state = false;
			$this->message = 'UNKNOWN_ERROR';
			return;
		}
		$this->fieldsContent = $fieldsContent;
	}
}

?>