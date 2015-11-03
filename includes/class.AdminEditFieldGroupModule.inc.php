<?php

class AdminEditFieldGroupModule extends BasicModule {

	// database operations
	private $moduleOperations;
	private $fieldGroupOperations;
	private $fieldOperations;

	// UI state
	private $state;
	private $message;
	private $field;

	// member variables
	private $config;
	private $fieldGroup; // field group stored in database
	private $module; // module stored in database
	private $moduleDefinition; // instance of RichModule
	private $fieldGroupInfo; // field group defined by module definition
	private $fieldContent; // field content stored in database

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
			// load module and field group info
			if (isset($this->fieldGroup)) {
				$this->loadModuleAndFieldGroupInfo($this->fieldGroup['module'], $this->fieldGroup['key']);
				// show success message for newly created field group
				if (!isset($this->state) && count($parameters) > 1 && $parameters[1] === '.success') {
					$this->state = true;
					$this->message = 'FIELD_GROUP_CREATED';
				}
			}
		}
		// parameters invalid
		else {
			$this->state = false;
			$this->message = 'PARAMETERS_INVALID';
		}

		// load field content
		if (isset($this->fieldGroupInfo)) {
			$this->loadFieldContent();
		}
		else {
			return;
		}

		// handle user input
		if (Utils::getUnmodifiedStringOrEmpty('operationSpace') === 'fields') {
			$this->handleEditFieldGroup();
			// refresh
			$this->loadFieldContent();
		}
	}

	public function printContent($config) {
		?>
		<?php if (isset($this->moduleDefinition)) : ?>
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
								$this->fieldGroupInfo->getName())
						)); ?>
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
						<button id="cancel"><?php $this->text('CANCEL'); ?></button>
					</div>
					<?php $this->printFields(); ?>
					<div class="buttonSet">
						<?php if (isset($this->fieldGroup)) : ?>
							<input type="submit" value="<?php $this->text('SAVE'); ?>" />
						<?php else: ?>
							<input type="submit" value="<?php $this->text('CREATE'); ?>" />
						<?php endif; ?>
					</div>
				</section>
			</form>
		<?php endif; ?>
		<?php
	}

	// --------------------------------------------------------------------------------------------
	// Printing methods
	// --------------------------------------------------------------------------------------------

	private function printFields() {
		echo '<div class="fields">';
		foreach ($this->fieldGroupInfo->getFieldInfos() as $field) {
			$field->printFieldWithLabel(
				$this->moduleDefinition,
				$this->getFieldContent($field->getKey()));
		}
		echo '</div>';
	}

	// --------------------------------------------------------------------------------------------
	// User input handling methods
	// --------------------------------------------------------------------------------------------

	private function handleEditFieldGroup() {
		// validate fields
		foreach ($this->fieldGroupInfo->getFieldInfos() as $field) {
			// check input
			$result = $field->isValidTypeAndContentInput();
			// validation was not successful
			if ($result !== true) {
				$this->state = false;
				$this->message = $result;
				$this->field = $field->getName();
				return;
			}
		}

		// create new fieldgroup
		$newFieldGroupId = null;
		if (!isset($this->fieldGroup)) {
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

		foreach ($this->fieldGroupInfo->getFieldInfos() as $field) {
			// save fields if not equal
			$content = $field->getValidTypeAndContentInput();
			$currentContent = $this->getFieldContent($field->getKey());

			if (!Utils::arrayEqual($content, $currentContent, 'type', 'content')) {
				// field not in database yet
				if ($currentContent === null) {
					// check if not default value
					$currentContent = $field->getDefaultContent();
					if (!Utils::arrayEqual($content, $currentContent, 'type', 'content')) {
						foreach ($content as $value) {
							$result = $result && $this->fieldOperations->addField(
								$this->fieldGroup['fgid'],
								$field->getKey(),
								$value['type'],
								$value['content']);
						}
					}
				}
				// field already in database
				else {
					$result = $this->fieldOperations->deleteField(
						$this->fieldGroup['fgid'],
						$field->getKey());
					foreach ($content as $value) {
						$result = $result && $this->fieldOperations->addField(
							$this->fieldGroup['fgid'],
							$field->getKey(),
							$value['type'],
							$value['content']);
					}
				}

				if ($result !== true) {
					$this->state = false;
					$this->message = 'UNKNOWN_ERROR';
					return;
				}
			}
		}

		if (isset($newFieldGroupId)) {
			Utils::redirect($this->config->getPublicRoot() . '/admin/field-group/' .
				$newFieldGroupId . '/.success');
		}
		else {
			$this->state = true;
			$this->message = 'FIELD_GROUP_CHANGED';
		}
	}

	// --------------------------------------------------------------------------------------------
	// Helper methods
	// --------------------------------------------------------------------------------------------

	private function getFieldContent($key) {
		if (!isset($this->fieldContent)) {
			return null;
		}
		$value = Utils::getColumnWithValues($this->fieldContent, 'key', $key);
		if ($value === false) {
			return null;
		}
		return $value;
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

	private function loadFieldContent() {
		$fieldContent = $this->fieldOperations->getFields($this->fieldGroup['fgid']);
		if ($fieldContent === false) {
			return;
		}
		$this->fieldContent = $fieldContent;
	}
}

?>