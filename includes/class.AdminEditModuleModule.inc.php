<?php

class AdminEditModuleModule extends BasicModule {

	// database operations
	private $moduleOperations;
	private $fieldGroupOperations;
	private $fieldOperations;

	// UI state
	private $state;
	private $message;
	private $fieldGroupNamePlural;

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
		// handle field group operations
		if (isset($this->moduleDefinition)
			&& Utils::getUnmodifiedStringOrEmpty('operationSpace') === 'fieldGroup') {
			$this->handleEditFieldGroup();
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
					$('.addFieldGroup').click(function() {
						window.open('<?php echo $config->getPublicRoot(); ?>/admin/new-field-group/<?php
							echo $this->module['mid']; ?>/' + $(this).val(), '_self');
					});
					$('.moveFieldGroup').click(function() {
						var form = $(this).parents('form');
						form.find('[name="operation"]').val('move');
						openButtonSetDialog($(this),
							'<?php $this->text('SELECT_MOVE_TARGET'); ?>',
							'.fieldGroupTarget, .moveConfirm');
					});
					$('.copyFieldGroup').click(function() {
						var form = $(this).parents('form');
						form.find('[name="operation"]').val('copy');
						openButtonSetDialog($(this),
							'<?php $this->text('SELECT_COPY_TARGET'); ?>',
							'.fieldGroupTarget, .copyConfirm');
					});
					$('.exportFieldGroup').click(function() {
						var form = $(this).parents('form');
						var lightboxOpened = function() {
							$('.dialog-box #exportConfirm').click(function() {
								form.find('[name="operation"]').val('export');
								form.find('[name="operationParameter"]')
									.val($('.dialog-box #exportTargetModule').val());
								form.submit();
							});
						};
						openLightboxWithUrl(
							'<?php echo $config->getPublicRoot(); ?>/admin/export-field-group-dialog/' +
								$(this).val(),
							true,
							lightboxOpened);
					});
					$('.deleteFieldGroup').click(function() {
						var form = $(this).parents('form');
						form.find('[name="operation"]').val('delete');
						openButtonSetDialog($(this),
							'<?php $this->text('DELETE_QUESTION'); ?>',
							'.deleteConfirm');
					});				
					$('.moveConfirm').click(function() {
						var form = $(this).parents('form');
						enableList($(this));
						form.submit();
					});
					$('.copyConfirm').click(function() {
						var form = $(this).parents('form');
						enableList($(this));
						form.submit();
					});
					$('.deleteConfirm').click(function() {
						var form = $(this).parents('form');
						enableList($(this));
						form.submit();
					});
				});
			</script>
		<?php endif; ?>
		<?php if (isset($this->state)) : ?>
			<?php if ($this->state === true && !isset($this->fieldGroupNamePlural)) : ?>
				<div class="dialog-success-message">
					<?php $this->text($this->message); ?>
				</div>
			<?php elseif ($this->state === false) : ?>
				<div class="dialog-error-message">
					<?php $this->text($this->message); ?>
				</div>
			<?php elseif ($this->state === true && isset($this->fieldGroupNamePlural)) : ?>
				<div class="dialog-success-message">
					<?php $this->text($this->message,
						Utils::escapeString(
							$this->moduleDefinition->textString($this->fieldGroupNamePlural)
						)); ?>
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
			<?php $this->printFieldGroups($config); ?>
		<?php endif; ?>
		<?php
	}

	// --------------------------------------------------------------------------------------------
	// Printing methods
	// --------------------------------------------------------------------------------------------

	private function printFieldGroups($config) {
		$fieldGroupInfos = $this->moduleDefinition->getFieldGroupInfo();
		echo '<div class="fieldGroups">';
		foreach ($fieldGroupInfos as $fieldGroupInfo) {
			$fieldGroupContent = false;
			if ($fieldGroupInfo->isOnePagePerGroup()) {
				$fieldGroupContent = $this->fieldGroupOperations->getFieldGroupsWithTitle(
					$this->module['mid'], $fieldGroupInfo->getKey());
			}
			else {
				$fieldGroupContent = $this->fieldGroupOperations->getFieldGroups(
					$this->module['mid'], $fieldGroupInfo->getKey());
			}
			if ($fieldGroupContent === false) {
				continue;
			}
			$this->printFieldGroupSection($fieldGroupInfo, $fieldGroupContent, $config);
		}
		echo '</div>';
	}

	private function printFieldGroupSection($fieldGroupInfo, $fieldGroupContent, $config) {
		?>
		<form method="post">
			<input type="hidden" name="operationSpace" value="fieldGroup" />
			<input type="hidden" name="fieldGroupInfo" value="<?php echo $fieldGroupInfo->getKey(); ?>" />
			<input type="hidden" name="operation" />
			<input type="hidden" name="operationParameter" />
			<section>
				<h1>
					<?php if ($fieldGroupInfo->getMaxNumberOfGroups() === 1) : ?>
						<?php $this->moduleDefinition->text($fieldGroupInfo->getName()); ?>
					<?php else : ?>
						<?php $this->moduleDefinition->text($fieldGroupInfo->getNamePlural()); ?>
					<?php endif; ?>
				</h1>
				<?php if ($fieldGroupInfo->getMaxNumberOfGroups() === null
						|| count($fieldGroupContent) < $fieldGroupInfo->getMaxNumberOfGroups()) : ?>
					<div class="buttonSet general">
						<button class="addFieldGroup" value="<?php echo $fieldGroupInfo->getKey(); ?>">
							<?php $this->text('ADD_FIELDGROUP',
								Utils::escapeString(
									$this->moduleDefinition->textString($fieldGroupInfo->getNamePlural())
								)); ?>
						</button>
					</div>
				<?php endif; ?>
				<?php if (count($fieldGroupContent) === 0) : ?>
					<p class="empty">
						<?php $this->text('NO_FIELDGROUP',
							Utils::escapeString(
								$this->moduleDefinition->textString($fieldGroupInfo->getNamePlural())
							)); ?>
					</p>;
				<?php endif; ?>
				<?php if ($fieldGroupInfo->isOnePagePerGroup()) : ?>
					<ul class="tableLike enableButtonsIfChecked">
						<?php foreach ($fieldGroupContent as $content) : ?>
							<li class="rowLike">
								<input type="checkbox" id="fieldGroup<?php echo $content['fgid']; ?>"
										name="fieldGroups[]"
										value="<?php echo $content['fgid']; ?>" />
								<label for="fieldGroup<?php echo $content['fgid']; ?>"
										class="checkbox">
									<?php echo Utils::escapeString($content['title']); ?>
								</label>
								<a href="<?php echo $config->getPublicRoot(); ?>/admin/field-group/<?php 
										echo $content['fgid']; ?>"
										<?php if (isset($content['private']) 
												&& $content['private'] === '1') : ?>
											class="private componentLink"
										<?php else : ?>
											class="componentLink"
										<?php endif; ?>
										>
									<?php echo Utils::escapeString($content['title']); ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
					<div class="buttonSet">
						<?php if ($fieldGroupInfo->hasOrder()) : ?>
							<button class="moveFieldGroup disableListIfClicked"
								value="<?php echo $fieldGroupInfo->getKey(); ?>" disabled>
								<?php $this->text('MOVE'); ?>
							</button>
						<?php endif; ?>
						<button class="copyFieldGroup disableListIfClicked"
							value="<?php echo $fieldGroupInfo->getKey(); ?>" disabled>
							<?php $this->text('COPY'); ?>
						</button>
						<button class="exportFieldGroup"
							value="<?php echo $this->moduleDefinition->getName() . '/'
								 . $fieldGroupInfo->getKey(); ?>" disabled>
							<?php $this->text('EXPORT'); ?>
						</button>
						<button class="deleteFieldGroup disableListIfClicked"
							value="<?php echo $fieldGroupInfo->getKey(); ?>" disabled>
							<?php $this->text('DELETE'); ?>
						</button>
					</div>
					<div class="dialog-box hidden">
						<div class="dialog-message"></div>
						<div class="fields">
							<?php $this->printFieldGroupsAsSelect($fieldGroupContent); ?>
						</div>
						<div class="options">
							<button class="hidden copyConfirm"><?php $this->text('COPY'); ?></button>
							<button class="hidden moveConfirm"><?php $this->text('MOVE'); ?></button>
							<button class="hidden deleteConfirm"><?php $this->text('DELETE'); ?></button>
							<button class="hidden cancel"><?php $this->text('CANCEL'); ?></button>
						</div>
					</div>
				<?php else : ?>
					<?php $fieldGroupInfo->printFieldsWithLabel(); ?>
				<?php endif; ?>
			</section>
		</form>
		<?php
	}

	private function printFieldGroupsAsSelect($fieldGroupContent) {
		echo '<select name="operationTarget" class="hidden fieldGroupTarget">';
		$i = 0;
		foreach ($fieldGroupContent as $content) {
			echo '<option value="' . $i . '">';
			echo Utils::escapeString($content['title']);
			echo '</option>';
			$i++;
		}
		echo '</select>';
	}

	// --------------------------------------------------------------------------------------------
	// User input handling methods
	// --------------------------------------------------------------------------------------------

	private function handleEditFieldGroup() {
		$operation = Utils::getUnmodifiedStringOrEmpty('operation');

		// check fieldGroupInfo
		$fieldGroupInfoKey = Utils::getUnmodifiedStringOrEmpty('fieldGroupInfo');
		$fieldGroupInfo = $this->moduleDefinition->getFieldGroupInfoOfKey($fieldGroupInfoKey);
		if ($fieldGroupInfo === false) {
			return;
		}
		// load corresponding content
		$fieldGroupContent = $this->fieldGroupOperations->getFieldGroups(
			$this->module['mid'], $fieldGroupInfo->getKey());
		if ($fieldGroupContent === false) {
			$this->state = false;
			$this->message = 'UNKNOWN_ERROR';
			return;
		}

		// do operation
		switch ($operation) {
			case 'add':

				break;
			case 'move':
			case 'copy':
			case 'delete':
				// check selected field groups and target
				if (!Utils::isValidFieldIntArray('fieldGroups')
						|| !Utils::isValidFieldInt('operationTarget')) {
					return;
				}
				// normalize selected field groups
				$fieldGroupIds = array_unique(Utils::getValidFieldArray('fieldGroups'));
				$operationTarget = (int) Utils::getUnmodifiedStringOrEmpty('operationTarget');

				// check target position
				if ($operationTarget < 0 || $operationTarget >= count($fieldGroupContent)) {
					return;
				}

				// foreach field group
				$result = true;
				foreach ($fieldGroupIds as $fieldGroupId) {
					// check if field group exists
					$fieldGroup = Utils::getColumnWithValue($fieldGroupContent, 'fgid', (int) $fieldGroupId);
					if ($fieldGroup === false) {
						return;
					}

					if ($operation === 'move') {
						// perform move
						$result = $result
							&& $this->fieldGroupOperations->moveFieldGroupWithinModule(
								$fieldGroup['fgid'],
								$operationTarget);
					}
					else if ($operation === 'copy') {
						// perform copy
						$result = $result
							&& $this->fieldGroupOperations->copyFieldGroupWithinModule(
								$fieldGroup['fgid'],
								$operationTarget);
					}
					else if ($operation === 'delete') {
						// perform delete
						$result = $result
							&& $this->fieldGroupOperations->deleteFieldGroup($fieldGroup['fgid']);
					}
				}
				if ($result === false) {
					$this->state = false;
					$this->message = 'UNKNOWN_ERROR';
				}
				else if ($operation === 'move') {
					$this->state = true;
					$this->message = 'FIELD_GROUP_MOVE_SUCCESSFUL';
					$this->fieldGroupNamePlural = $fieldGroupInfo->getNamePlural();
				}
				else if ($operation === 'copy') {
					$this->state = true;
					$this->message = 'FIELD_GROUP_COPY_SUCCESSFUL';
					$this->fieldGroupNamePlural = $fieldGroupInfo->getNamePlural();
				}
				else if ($operation === 'delete') {
					$this->state = true;
					$this->message = 'FIELD_GROUP_DELETE_SUCCESSFUL';
					$this->fieldGroupNamePlural = $fieldGroupInfo->getNamePlural();
				}
				break;
			case 'export':
				// check selected field groups
				if (!Utils::isValidFieldIntArray('fieldGroups')) {
					return;
				}
				// normalize selected field groups
				$fieldGroupIds = array_unique(Utils::getValidFieldArray('fieldGroups'));
				// check operationParameter1
				if (!Utils::isValidFieldInt('operationParameter')) {
					return;
				}
				$targetMid = (int) Utils::getUnmodifiedStringOrEmpty('operationParameter');
				$targetModule = $this->moduleOperations->getModule($targetMid);

				// check for module equality
				if ($targetModule['definitionId'] != $this->module['definitionId']) {
					return;
				}

				// foreach field group
				$result = true;
				foreach ($fieldGroupIds as $fieldGroupId) {
					// check if field group exists
					$fieldGroup = Utils::getColumnWithValue($fieldGroupContent, 'fgid', (int) $fieldGroupId);
					if ($fieldGroup === false) {
						return;
					}

					// perform export
					$result = $result
							&& $this->fieldGroupOperations->moveFieldGroupWithinModules(
								$fieldGroup['fgid'],
								$targetModule['mid']);
				}
				if ($result === false) {
					$this->state = false;
					$this->message = 'UNKNOWN_ERROR';
				}
				else {
					$this->state = true;
					$this->message = 'MODULE_EXPORT_SUCCESSFUL';
				}
				break;
		}
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
}

?>