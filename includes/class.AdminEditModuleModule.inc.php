<?php

// v1: FEATURE COMPLETE

class AdminEditModuleModule extends BasicModule {

	// database operations
	private $moduleOperations;
	private $fieldGroupOperations;
	private $fieldOperations;

	// UI state
	private $state;
	private $message;
	private $field;
	private $fieldGroupName;
	private $newFieldGroup;

	// member variables
	private $module; // module information from database
	private $moduleInfo; // translated name and description
	private $moduleDefinition; // instance of RichModule

	public function __construct($moduleOperations, $fieldGroupOperations, $fieldOperations,
			$parameters = null) {
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
					$('.addFieldGroup.onePager').click(function() {
						window.open('<?php echo $config->getPublicRoot(); ?>/admin/new-field-group/<?php
							echo $this->module['mid']; ?>/' + $(this).val(), '_self');
					});
					$('.addFieldGroup.noOnePager').click(function() {
						var form = $(this).parents('form');
						form.submit();
					});
					$('.editFieldGroup').click(function() {
						var form = $(this).parents('form');
						form.find('[name="operation"]').val('edit');
						form.submit();
					});
					$('.upFieldGroup').click(function() {
						var form = $(this).parents('form');
						form.find('[name="operation"]').val('move');
						form.find('[name="operationTarget"]').val($(this).val());
						form.submit();
					});
					$('.downFieldGroup').click(function() {
						var form = $(this).parents('form');
						form.find('[name="operation"]').val('move');
						form.find('[name="operationTarget"]').val($(this).val());
						form.submit();
					});
					$('.moveFieldGroup').click(function() {
						var form = $(this).parents('form');
						openButtonSetDialog($(this),
							'<?php $this->text('SELECT_MOVE_TARGET'); ?>',
							'.fieldGroupTarget, .moveConfirm');
					});
					$('.copyFieldGroup').click(function() {
						var form = $(this).parents('form');
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
						openButtonSetDialog($(this),
							'<?php $this->text('DELETE_QUESTION'); ?>',
							'.deleteConfirm');
					});				
					$('.moveConfirm').click(function() {
						var form = $(this).parents('form');
						form.find('[name="operation"]').val('move');
						enableList($(this));
						form.submit();
					});
					$('.copyConfirm').click(function() {
						var form = $(this).parents('form');
						form.find('[name="operation"]').val('copy');
						enableList($(this));
						form.submit();
					});
					$('.deleteConfirm').click(function() {
						var form = $(this).parents('form');
						form.find('[name="operation"]').val('delete');
						enableList($(this));
						form.submit();
					});
				});
			</script>
		<?php endif; ?>
		<?php if (isset($this->state)) : ?>
			<?php if ($this->state === true && !isset($this->fieldGroupName)) : ?>
				<div class="dialog-success-message">
					<?php $this->text($this->message); ?>
				</div>
			<?php elseif ($this->state === false && !isset($this->moduleDefinition)) : ?>
				<div class="dialog-error-message">
					<?php $this->text($this->message); ?>
				</div>
			<?php elseif ($this->state === false) : ?>
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
			<?php elseif ($this->state === true && isset($this->fieldGroupName)) : ?>
				<div class="dialog-success-message">
					<?php $this->text($this->message,
						Utils::escapeString(
							$this->moduleDefinition->textString($this->fieldGroupName)
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
			$fieldGroups = false;
			if ($fieldGroupInfo->isOnePagePerGroup()) {
				$fieldGroups = $this->fieldGroupOperations->getFieldGroupsWithTitle(
					$this->module['mid'], $fieldGroupInfo->getKey());
			}
			else {
				$fieldGroups = $this->fieldGroupOperations->getFieldGroups(
					$this->module['mid'], $fieldGroupInfo->getKey());
			}
			if ($fieldGroups === false) {
				continue;
			}

			// print one pager list
			if ($fieldGroupInfo->isOnePagePerGroup()) {
				$this->printFieldGroupSection($fieldGroupInfo, $fieldGroups, null, $config);
			}
			else {
				$count = 0;
				// print available field groups
				foreach ($fieldGroups as $fieldGroup) {
					$count++;
					// load fields content
					$fieldsContent = $this->fieldOperations->getFields($fieldGroup['fgid']);
					if ($fieldsContent === false) {
						continue;
					}
					$this->printFieldGroupSection($fieldGroupInfo, $fieldGroup,
						$fieldsContent, $config);
				}

				// print remaining required field groups
				for (;$count < $fieldGroupInfo->getMinNumberOfGroups(); $count++) {
					$this->printFieldGroupSection($fieldGroupInfo, null, null, $config);
				}

				// print new field group
				if (isset($this->newFieldGroup) && $this->newFieldGroup === $fieldGroupInfo->getKey()) {
					$count++;
					$this->printFieldGroupSection($fieldGroupInfo, null, null, $config);
				}

				// print add button
				if ($fieldGroupInfo->getMaxNumberOfGroups() === null
						|| $count < $fieldGroupInfo->getMaxNumberOfGroups()) {
					?>
					<form method="post">
						<input type="hidden" name="operationSpace" value="fieldGroup" />
						<input type="hidden" name="fieldGroupInfo"
							value="<?php echo $fieldGroupInfo->getKey(); ?>" />
						<input type="hidden" name="operation" value="new" />
						<section>
							<h1><?php $this->moduleDefinition->text($fieldGroupInfo->getNamePlural()); ?></h1>
							<div class="buttonSet general">
								<button class="addFieldGroup noOnePager"
											value="<?php echo $fieldGroupInfo->getKey(); ?>">
									<?php $this->text('ADD_FIELD_GROUP',
										Utils::escapeString(
											$this->moduleDefinition->textString($fieldGroupInfo->getName())
										)); ?>
								</button>
							</div>
						</section>
					<?php
				}
			}
		}
		echo '</div>';
	}

	// $fieldGroups contains either a list of one pagers or field group for non-one pages
	private function printFieldGroupSection($fieldGroupInfo, $fieldGroups, $fieldsContent, $config) {
		?>
		<form method="post">
			<input type="hidden" name="operationSpace" value="fieldGroup" />
			<input type="hidden" name="fieldGroupInfo" value="<?php echo $fieldGroupInfo->getKey(); ?>" />
			<input type="hidden" name="operation" />
			<input type="hidden" name="operationParameter" />
			<section>
				<h1>
					<?php if ($fieldGroupInfo->getMaxNumberOfGroups() === 1 
							|| !$fieldGroupInfo->isOnePagePerGroup()) : ?>
						<?php $this->moduleDefinition->text($fieldGroupInfo->getName()); ?>
					<?php else : ?>
						<?php $this->moduleDefinition->text($fieldGroupInfo->getNamePlural()); ?>
					<?php endif; ?>
				</h1>
				
				<?php if ($fieldGroupInfo->isOnePagePerGroup()) : ?>
					<?php if ($fieldGroupInfo->getMaxNumberOfGroups() === null
							|| count($fieldGroups) < $fieldGroupInfo->getMaxNumberOfGroups()) : ?>
						<div class="buttonSet general">
							<button class="addFieldGroup onePager"
									value="<?php echo $fieldGroupInfo->getKey(); ?>">
								<?php $this->text('ADD_FIELD_GROUP',
									Utils::escapeString(
										$this->moduleDefinition->textString($fieldGroupInfo->getName())
									)); ?>
							</button>
						</div>
					<?php endif; ?>
					<?php if (count($fieldGroups) === 0) : ?>
						<p class="empty">
							<?php $this->text('NO_FIELD_GROUP',
								Utils::escapeString(
									$this->moduleDefinition->textString($fieldGroupInfo->getNamePlural())
								)); ?>
						</p>
					<?php endif; ?>
					<ul class="tableLike enableButtonsIfChecked">
						<?php foreach ($fieldGroups as $fieldGroup) : ?>
							<li class="rowLike">
								<input type="checkbox" id="fieldGroup<?php echo $fieldGroup['fgid']; ?>"
										name="fieldGroups[]"
										value="<?php echo $fieldGroup['fgid']; ?>" />
								<label for="fieldGroup<?php echo $fieldGroup['fgid']; ?>"
										class="checkbox">
									<?php echo Utils::escapeString($fieldGroup['title']); ?>
								</label>
								<a href="<?php echo $config->getPublicRoot(); ?>/admin/field-group/<?php 
										echo $fieldGroup['fgid']; ?>"
										<?php if (isset($fieldGroup['private']) 
												&& $fieldGroup['private'] === '1') : ?>
											class="private componentLink"
										<?php else : ?>
											class="componentLink"
										<?php endif; ?>
										>
									<?php echo Utils::escapeString($fieldGroup['title']); ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
					<div class="buttonSet">
						<?php if ($fieldGroupInfo->hasOrder()) : ?>
							<button class="moveFieldGroup disableListIfClicked" disabled>
								<?php $this->text('MOVE'); ?>
							</button>
						<?php endif; ?>
						<?php if ($fieldGroupInfo->getMaxNumberOfGroups() === null
								|| count($fieldGroups) < $fieldGroupInfo->getMaxNumberOfGroups()) : ?>
							<?php if ($fieldGroupInfo->hasOrder()) : ?>
								<button class="copyFieldGroup disableListIfClicked" disabled>
							<?php else : ?>
								<button class="copyConfirm" disabled>
							<?php endif; ?>
								<?php $this->text('COPY'); ?>
							</button>
						<?php endif; ?>
						<button class="exportFieldGroup"
								value="<?php echo $this->moduleDefinition->getName() . '/'
								 . $fieldGroupInfo->getKey(); ?>" disabled>
							<?php $this->text('EXPORT'); ?>
						</button>
						<button class="deleteFieldGroup disableListIfClicked" disabled>
							<?php $this->text('DELETE'); ?>
						</button>
					</div>
					<div class="dialog-box hidden">
						<div class="dialog-message"></div>
						<div class="fields">
							<?php $this->printFieldGroupsAsSelect($fieldGroups); ?>
						</div>
						<div class="options">
							<button class="hidden copyConfirm"><?php $this->text('COPY'); ?></button>
							<button class="hidden moveConfirm"><?php $this->text('MOVE'); ?></button>
							<button class="hidden deleteConfirm"><?php $this->text('DELETE'); ?></button>
							<button class="hidden cancel"><?php $this->text('CANCEL'); ?></button>
						</div>
					</div>
				<?php else : ?>
					<?php if (isset($fieldGroups)) : ?>
						<input type="hidden" name="fieldGroup" value="<?php echo $fieldGroups['fgid']; ?>" />
						<input type="hidden" name="operationTarget"
							value="<?php echo $fieldGroups['order']; ?>" />
					<?php endif; ?>
					<div class="fields">
						<?php $fieldGroupInfo->printFields($this->moduleDefinition, $fieldsContent,
							$fieldGroups['fgid']); ?>
					</div>
					<div class="buttonSet">
						<button class="editFieldGroup">
						<?php if (isset($fieldGroups)) : ?>
							<?php $this->text('SAVE'); ?>
						<?php else: ?>
							<?php $this->text('CREATE'); ?>
						<?php endif; ?>
						</button>
						<?php if (isset($fieldGroups)) : ?>
							<?php if ($fieldGroupInfo->hasOrder()) : ?>
								<button class="upFieldGroup"
										value="<?php echo $fieldGroups['order'] - 1; ?>">
									<?php $this->text('UP'); ?>
								</button>
								<button class="downFieldGroup"
										value="<?php echo $fieldGroups['order'] + 1; ?>">
									<?php $this->text('DOWN'); ?>
								</button>
							<?php endif; ?>
							<?php if ($fieldGroupInfo->getMaxNumberOfGroups() === null
									|| count($fieldGroups) < 
										$fieldGroupInfo->getMaxNumberOfGroups()) : ?>
								<button class="copyConfirm">
									<?php $this->text('COPY'); ?>
								</button>
							<?php endif; ?>
							<button class="exportFieldGroup"
									value="<?php echo $this->moduleDefinition->getName() . '/'
										 . $fieldGroupInfo->getKey(); ?>">
								<?php $this->text('EXPORT'); ?>
							</button>
							<button class="deleteFieldGroup disableListIfClicked">
								<?php $this->text('DELETE'); ?>
							</button>
						<?php endif; ?>	
					</div>
					<div class="dialog-box hidden">
						<div class="dialog-message"></div>
						<div class="options">
							<button class="hidden deleteConfirm"><?php $this->text('DELETE'); ?></button>
							<button class="hidden cancel"><?php $this->text('CANCEL'); ?></button>
						</div>
					</div>
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
		// default field group name for success and error messages
		$this->fieldGroupName = $fieldGroupInfo->getNamePlural();
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
			// show a new mask for adding a new field group
			case 'new':
				// check if maximimum is reached
				if ($fieldGroupInfo->getMaxNumberOfGroups() === null
						|| count($fieldGroupContent) < $fieldGroupInfo->getMaxNumberOfGroups()) {
					$this->newFieldGroup = $fieldGroupInfo->getKey();
				}
				else {
					$this->state = false;
					$this->message = 'FIELD_GROUP_MAXIMUM_REACHED';
					return;
				}
				break;
			case 'edit':
				$fieldGroupId = null;
				// check if field group exists
				if (Utils::isValidFieldInt('fieldGroup')) {
					$fieldGroupIdString = Utils::getUnmodifiedStringOrEmpty('fieldGroup');
					$fieldGroup = Utils::getColumnWithValue($fieldGroupContent, 'fgid',
						(int) $fieldGroupIdString);
					if ($fieldGroup === false) {
						$this->state = false;
						$this->message = 'FIELD_GROUP_NOT_FOUND';
						return;
					}
					$fieldGroupId = $fieldGroup['fgid'];
				}
				// check if maximimum is reached
				else if ($fieldGroupInfo->getMaxNumberOfGroups() === null
						|| count($fieldGroupContent) < $fieldGroupInfo->getMaxNumberOfGroups()) {
					$this->newFieldGroup = $fieldGroupInfo->getKey();
				}
				else {
					$this->state = false;
					$this->message = 'FIELD_GROUP_MAXIMUM_REACHED';
					return;
				}

				// validate fields
				$result = $fieldGroupInfo->validateFields($fieldGroupId);
				// validation was not successful
				if ($result !== true) {
					$this->state = false;
					$this->message = $result[0];
					$this->field = $result[1];
					$this->fieldGroupName = $result[2];
					return;
				}

				// create field group
				$created = false;
				if (!isset($fieldGroupId)) {
					// create new field group
					$newFieldGroupId = $this->fieldGroupOperations->addFieldGroup($this->module['mid'],
						$fieldGroupInfo->getKey());
					if ($newFieldGroupId === false) {
						$this->state = false;
						$this->message = 'UNKNOWN_ERROR';
						return;
					}
					$fieldGroupId = $newFieldGroupId;
					$created = true;
				}

				// load fields content
				$fieldsContent = $this->fieldOperations->getFields($fieldGroupId);
				if ($fieldsContent === false) {
					$this->state = false;
					$this->message = 'UNKNOWN_ERROR';
					return;
				}

				// handle edit
				$result = $fieldGroupInfo->handleEditFieldGroup($fieldGroupId, $fieldsContent,
					$this->fieldOperations, !$created);
				if ($result === true) {
					// no new field group anymore, since it is now an official field group
					$this->newFieldGroup = null;
					$this->state = true;
					if ($created) {
						$this->message = 'FIELD_GROUP_CREATED';

					}
					else {
						$this->message = 'FIELD_GROUP_CHANGED';
					}
					$this->fieldGroupName = $fieldGroupInfo->getName();
				}
				else {
					$this->state = false;
					$this->message = $result;
				}
				break;
			case 'copy':
				// check if maximimum is reached
				if ($fieldGroupInfo->getMaxNumberOfGroups() !== null
						&& count($fieldGroupContent) >= $fieldGroupInfo->getMaxNumberOfGroups()) {
					$this->state = false;
					$this->message = 'FIELD_GROUP_MAXIMUM_REACHED';
					return;
				}
			case 'move':
			case 'delete':
				$fieldGroupIds = [];
				if (Utils::isValidFieldIntArray('fieldGroups')) {
					// normalize selected field groups
					$fieldGroupIds = array_unique(Utils::getValidFieldArray('fieldGroups'));
				}
				else if (Utils::isValidFieldInt('fieldGroup')) {
					$fieldGroupIds = [Utils::getUnmodifiedStringOrEmpty('fieldGroup')];
				}
				else {
					return;
				}
				
				// check target
				if (!Utils::isValidFieldInt('operationTarget')) {
					return;
				}
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

					if ($operation === 'move' && $fieldGroupInfo->hasOrder()) {
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
				}
				else if ($operation === 'copy') {
					$this->state = true;
					$this->message = 'FIELD_GROUP_COPY_SUCCESSFUL';
				}
				else if ($operation === 'delete') {
					$this->state = true;
					$this->message = 'FIELD_GROUP_DELETE_SUCCESSFUL';
				}
				break;
			case 'export':
				// check selected field groups
				$fieldGroupIds = [];
				if (Utils::isValidFieldIntArray('fieldGroups')) {
					// normalize selected field groups
					$fieldGroupIds = array_unique(Utils::getValidFieldArray('fieldGroups'));
				}
				else if (Utils::isValidFieldInt('fieldGroup')) {
					$fieldGroupIds = [Utils::getUnmodifiedStringOrEmpty('fieldGroup')];
				}
				else {
					return;
				}

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

				// check for export to itself
				if ($targetModule['mid'] === $this->module['mid']) {
					return;
				}

				// count field groups in target
				$count = $this->fieldGroupOperations->getNumberOfFieldGroups($targetModule['mid'],
					$fieldGroupInfo->getKey());
				if ($count === false) {
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

					// check if maximimum is reached
					if ($fieldGroupInfo->getMaxNumberOfGroups() !== null
							&& $count++ >= $fieldGroupInfo->getMaxNumberOfGroups()) {
						$this->state = false;
						$this->message = 'FIELD_GROUP_MAXIMUM_REACHED';
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
					$this->message = 'FIELD_GROUP_EXPORT_SUCCESSFUL';
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