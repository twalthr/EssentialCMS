<?php

// v1: FEATURE COMPLETE

class AdminEditPageModule extends BasicModule {

	// database operations
	private $pageOperations;
	private $moduleOperations;
	private $menuItemOperations;

	// UI state
	private $state;
	private $message;
	private $createdPageId;

	// member variables
	private $page;
	private $modulesBySection;

	public function __construct(
			$pageOperations, $moduleOperations, $menuItemOperations, $parameters = null) {
		parent::__construct(1, 'admin-edit-page');
		$this->pageOperations = $pageOperations;
		$this->moduleOperations = $moduleOperations;
		$this->menuItemOperations = $menuItemOperations;

		// page id is present
		if (isset($parameters)
			&& count($parameters) > 0) {
			$this->loadPage($parameters[0]);
		}
		// if page is present, load modules
		if (isset($this->page)) {
			$this->loadModulesBySection();
		}

		// handle new page
		if (!isset($this->page)
			&& Utils::hasFields()) {
			$this->handleNewPage();
		}
		// handle edit modules
		else if (isset($this->page)
			&& Utils::getUnmodifiedStringOrEmpty('operationSpace') === 'module') {
			$this->handleEditModules();
			// refresh
			$this->loadModulesBySection();
		}
		// handle edit page
		else if (isset($this->page)
			&& Utils::getUnmodifiedStringOrEmpty('operationSpace') === 'page') {
			$this->handleEditPage();
			// refresh
			$this->loadPage($this->page['pid']);
		}
	}

	public function printContent($config) {
		?>
		<?php if (!empty($this->state) && isset($this->createdPageId)) : ?>
			<div class="dialog-box">
				<div class="dialog-success-message">
				<?php $this->text('PAGE_CREATED'); ?>
			</div>
			<a href="<?php echo $config->getPublicRoot(); ?>/admin/page/<?php echo $this->createdPageId; ?>"
				class="goto"><?php $this->text('GOTO_PAGE'); ?></a>
			</div>
		<?php return; ?>
		<?php else: ?>
			<script type="text/javascript">
				$(document).ready(function() {
					$('#editPageCancel').click(function() {
						window.open('<?php echo $config->getPublicRoot(); ?>/admin/pages', '_self');
					});
					$('#pageDirectAccess').change(function() {
						var externalId = $('#externalId');
						externalId.prop('disabled', !$(this).prop('checked'));
						if (!externalId.prop('disabled') && externalId.val().length == 0)  {
							externalId.val(generateIdentifierFromString($('#title').val()));
						}
					});
					$('#pageDirectAccess').trigger('change');
					$('#pageCustomLastChange').change(function() {
						var externalLastChanged = $('#externalLastChanged');
						externalLastChanged.prop('disabled', !$(this).prop('checked'));
						if (!externalLastChanged.prop('disabled') && externalLastChanged.val().length == 0) {
							externalLastChanged.val(generateDate());
						}
					});
					$('#pageCustomLastChange').trigger('change');

					<?php if (isset($this->page)) : ?>
					$('#editPage').click(function() {
						$('.showInEditMode').removeClass('hidden');
						$('.hiddenInEditMode').remove();
					});
					$('.addButton').click(function(e) {
						var form = $(this).parents('form');
						var lightboxOpened = function() {
							$('.dialog-window .selectModule').click(function() {
								form.find('[name="operation"]').val('add');
								form.find('[name="operationParameter1"]').val($(this).val());
								form.submit();
							});
						};
						openLightboxWithUrl(
							'<?php echo $config->getPublicRoot(); ?>/admin/select-module-dialog',
							true,
							lightboxOpened);
					});				
					$('.moveModule').click(function() {
						var form = $(this).parents('form');
						form.find('[name="operation"]').val('move');
						openButtonSetDialog($(this),
							'<?php $this->text('SELECT_MOVE_TARGET'); ?>',
							'.moduleTarget, .moveConfirm');
					});
					$('.copyModule').click(function() {
						var form = $(this).parents('form');
						form.find('[name="operation"]').val('copy');
						openButtonSetDialog($(this),
							'<?php $this->text('SELECT_COPY_TARGET'); ?>',
							'.moduleTarget, .copyConfirm');
					});
					$('.exportModule').click(function() {
						var form = $(this).parents('form');
						var lightboxOpened = function() {
							$('.dialog-box #exportConfirm').click(function() {
								form.find('[name="operation"]').val('export');
								form.find('[name="operationParameter1"]')
									.val($('.dialog-box #exportTargetSection').val());
								form.find('[name="operationParameter2"]')
									.val($('.dialog-box #exportTargetPage').val());
								form.submit();
							});
						};
						openLightboxWithUrl(
							'<?php echo $config->getPublicRoot(); ?>/admin/export-module-dialog',
							true,
							lightboxOpened);
					});
					$('.deleteModule').click(function() {
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
					<?php endif; ?>

					<?php if (isset($this->page) && isset($this->state)) : ?>
						$('#editPage').trigger('click');
					<?php endif; ?>
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
		<?php if (isset($this->page)) : ?>
			<form method="post">
		<?php else : ?>
			<form method="post" action="<?php echo $config->getPublicRoot(); ?>/admin/new-page">
		<?php endif; ?>
			<input type="hidden" name="operationSpace" value="page" />
				<section>
					<?php if (isset($this->page)) : ?>
						<h1 class="hiddenInEditMode">
							<?php echo Utils::escapeString($this->page['title']); ?>
						</h1>
						<h1 class="hidden showInEditMode"><?php $this->text('PAGE_PROPERTIES'); ?></h1>
						<div class="buttonSet general">
							<button id="editPage" class="hiddenInEditMode">
								<?php $this->text('EDIT_PAGE'); ?>
							</button>
							<button id="editPageCancel">
								<?php $this->text('CANCEL'); ?>
							</button>
						</div>
					<?php else : ?>
						<h1><?php $this->text('NEW_PAGE'); ?></h1>
					<?php endif; ?>
					<div 
						<?php if (isset($this->page)) : ?>
							class="hidden showInEditMode"
						<?php endif; ?>>
						<div class="fields">
							<div class="field">
								<label for="title"><?php $this->text('PAGE_TITLE'); ?></label>
								<input type="text" name="title" id="title" class="large"
									value="<?php echo Utils::getEscapedFieldOrVariable('title',
										$this->page['title']); ?>"
									required />
							</div>
							<div class="field">
								<label for="hoverTitle"><?php $this->text('PAGE_HOVERTITLE'); ?></label>
								<input type="text" name="hoverTitle" id="hoverTitle"  class="large"
									value="<?php echo Utils::getEscapedFieldOrVariable('hoverTitle',
										$this->page['hoverTitle']); ?>"
									/>
								<span class="hint"><?php $this->text('PAGE_HOVERTITLE_HINT'); ?></span>
							</div>
							<div class="field">
								<label><?php $this->text('PAGE_DIRECT_ACCESS'); ?></label>
								<div class="checkboxWrapper">
									<input type="checkbox" id="pageDirectAccess" name="pageDirectAccess"
										value="direct-access"
									 	<?php echo (Utils::getCheckedFieldOrVariable('pageDirectAccess',
									 	$this->page['externalId']))?
									 	'checked' : ''; ?> />
									<label for="pageDirectAccess" class="checkbox">
										<?php $this->text('ALLOW_PAGE_DIRECT_ACCESS'); ?>
									</label>
									<?php $this->text('ALLOW_PAGE_DIRECT_ACCESS'); ?>
								</div>
							</div>
							<div class="field">
								<label for="externalId"><?php $this->text('PAGE_EXTERNALID'); ?></label>
								<input type="text" name="externalId" id="externalId"  class="large" disabled
									value="<?php echo Utils::getEscapedFieldOrVariable('externalId',
										$this->page['externalId']); ?>"/>
								<span class="hint"><?php $this->text('PAGE_EXTERNALID_HINT'); ?></span>
							</div>
							<div class="field">
								<label><?php $this->text('CUSTOM_PAGE_LAST_CHANGE'); ?></label>
								<div class="checkboxWrapper">
									<input type="checkbox" id="pageCustomLastChange"
										name="pageCustomLastChange" value="custom-last-change"
										<?php echo (Utils::getCheckedFieldOrVariable('pageCustomLastChange',
											$this->page['externalLastChanged']))?
									 	'checked' : ''; ?> />
									<label for="pageCustomLastChange" class="checkbox">
										<?php $this->text('DO_CUSTOM_PAGE_LAST_CHANGE'); ?>
									</label>
									<?php $this->text('DO_CUSTOM_PAGE_LAST_CHANGE'); ?>
								</div>
							</div>
							<div class="field">
								<label for="externalLastChanged">
									<?php $this->text('PAGE_EXTERNAL_LAST_CHANGED'); ?></label>
								<input type="text" name="externalLastChanged" id="externalLastChanged" 
									value="<?php echo Utils::getEscapedFieldOrVariable('externalLastChanged',
										$this->page['externalLastChanged']); ?>" disabled />
							</div>
							<div class="field">
								<label><?php $this->text('PUBLICATION'); ?></label>
								<div class="checkboxWrapper">
									<input type="checkbox" id="pageDeactivated" name="pageDeactivated"
										value="deactivated"
										<?php echo (Utils::getCheckedFieldOrVariableFlag('pageDeactivated',
											$this->page['options'], PAGES_OPTION_PRIVATE))?
									 	'checked' : ''; ?> />
									<label for="pageDeactivated" class="checkbox">
										<?php $this->text('DEACTIVATE_PAGE'); ?>
									</label>
									<?php $this->text('DEACTIVATE_PAGE'); ?>
								</div>
							</div>
						</div>
						<div class="fieldsRequired">
							<?php $this->text('REQUIRED'); ?>
						</div>
						<div class="buttonSet">
							<?php if (isset($this->page)) : ?>
								<input type="submit" value="<?php $this->text('SAVE'); ?>" />
							<?php else: ?>
								<input type="submit" value="<?php $this->text('CREATE_PAGE'); ?>" />
							<?php endif; ?>
						</div>
					</div>
				</section>
			</form>
			<?php if (isset($this->page)) : ?>
				<?php $this->printMissingModules(); ?>
					<?php $this->printSection(
						$config,
						ModuleOperations::MODULES_SECTION_PRE_CONTENT); ?>
					<?php $this->printSection(
						$config,
						ModuleOperations::MODULES_SECTION_CONTENT); ?>
					<?php $this->printSection(
						$config,
						ModuleOperations::MODULES_SECTION_ASIDE_CONTENT); ?>
					<?php $this->printSection(
						$config,
						ModuleOperations::MODULES_SECTION_POST_CONTENT); ?>
			<?php endif; ?>
		<?php
	}

	// --------------------------------------------------------------------------------------------
	// Printing methods
	// --------------------------------------------------------------------------------------------

	private function printSection($config, $section) {
		?>
		<form method="post">
			<input type="hidden" name="operationSpace" value="module" />
			<input type="hidden" name="section"
				value="<?php echo ModuleOperations::translateToSectionString($section); ?>" />
			<input type="hidden" name="operation" />
			<input type="hidden" name="operationParameter1" />
			<input type="hidden" name="operationParameter2" />
			<section class="<?php echo array_key_exists($section, $this->modulesBySection) ?
				'' : 'hidden showInEditMode'; ?>">
				<h1><?php $this->text(ModuleOperations::translateSectionToLocale($section)); ?></h1>
				<div class="buttonSet general">
					<button class="hidden showInEditMode addButton"
						value="<?php echo ModuleOperations::translateToSectionString($section); ?>">
						<?php $this->text('ADD_MODULE'); ?></button>
				</div>
				<div class="moduleList enableButtonsIfChecked">
					<?php $this->printModuleListAsTable($config, $section); ?>
				</div>
				<div class="buttonSet hidden showInEditMode">
					<button class="moveModule disableListIfClicked"
						value="<?php echo ModuleOperations::translateToSectionString($section); ?>" disabled>
						<?php $this->text('MOVE'); ?>
					</button>
					<button class="copyModule disableListIfClicked"
						value="<?php echo ModuleOperations::translateToSectionString($section); ?>" disabled>
						<?php $this->text('COPY'); ?>
					</button>
					<button class="exportModule"
						value="<?php echo ModuleOperations::translateToSectionString($section); ?>" disabled>
						<?php $this->text('EXPORT'); ?>
					</button>
					<button class="deleteModule disableListIfClicked"
						value="<?php echo ModuleOperations::translateToSectionString($section); ?>" disabled>
						<?php $this->text('DELETE'); ?>
					</button>
				</div>
				<div class="dialog-box hidden">
					<div class="dialog-message"></div>
					<div class="fields">
						<?php $this->printModuleListAsSelect($section); ?>
					</div>
					<div class="options">
						<button class="hidden copyConfirm"><?php $this->text('COPY'); ?></button>
						<button class="hidden moveConfirm"><?php $this->text('MOVE'); ?></button>
						<button class="hidden deleteConfirm"><?php $this->text('DELETE'); ?></button>
						<button class="hidden cancel"><?php $this->text('CANCEL'); ?></button>
					</div>
				</div>
			</section>
		</form>
		<?php
	}

	private function printMissingModules() {
		if (count($this->modulesBySection) === 0) {
			echo '<p class="empty hiddenInEditMode">';
			$this->text('NO_MODULES_IN_PAGE');
			echo '</p>';
		}
	}

	private function printModuleListAsTable($config, $section) {
		if (!array_key_exists($section, $this->modulesBySection)) {
			echo '<p class="empty">';
			$this->text('NO_MODULES_IN_SECTION');
			echo '</p>';
			return;
		}

		echo '<ul class="tableLike">';
		foreach ($this->modulesBySection[$section] as $module) {
			$moduleInfo = RichModule::getLocalizedModuleInfo($module['definitionId']);
			if ($moduleInfo === false) {
				continue;
			}
			echo '<li class="rowLike">';
			echo '<input type="checkbox" id="module' . $module['mid'] . '"';
			echo ' name="modules[]"';
			echo ' value="' . $module['mid'] . '" />';
			echo '<label for="module' . $module['mid'] . '"';
			echo ' class="checkbox hidden showInEditMode">';
			echo Utils::escapeString($moduleInfo['name']) .' </label>';
			echo '<a href="' . $config->getPublicRoot() . '/admin/module/' . $module['mid'] . '"';
			if (!empty($moduleInfo['description'])) {
				echo ' title="' . Utils::escapeString(
					Utils::internalHtmlToText($moduleInfo['description'])) . '"';
			}
			echo ' class="componentLink"';
			echo '>' . Utils::escapeString($moduleInfo['name']) . '</a>';
			echo '<span class="rowAdditionalInfo">';
			echo Utils::escapeString($module['definitionId']);
			echo '</span>';
			echo '</li>';
		}
		echo '</ul>';
	}

	private function printModuleListAsSelect($section) {
		echo '<select name="operationTarget" class="hidden moduleTarget">';
		$i = 0;
		foreach ($this->modulesBySection[$section] as $module) {
			echo '<option value="' . $i . '">';
			echo Utils::escapeString(RichModule::getLocalizedModuleInfo($module['definitionId'])['name']);
			echo '</option>';
			$i++;
		}
		echo '</select>';
	}

	// --------------------------------------------------------------------------------------------
	// User input handling methods
	// --------------------------------------------------------------------------------------------

	private function handleEditModules() {
		$operation = Utils::getUnmodifiedStringOrEmpty('operation');
		$sectionString = Utils::getUnmodifiedStringOrEmpty('section');
		$operationParameter1 = Utils::getUnmodifiedStringOrEmpty('operationParameter1');

		// check section
		if (!ModuleOperations::isStringValidPageSection($sectionString)) {
			return;
		}
		$section = ModuleOperations::translateSectionString($sectionString);

		// do operation
		switch ($operation) {
			case 'add':
				// check operationParameter1
				if (!RichModule::isValidModuleDefinitionId($operationParameter1)) {
					return;
				}
				// add to database
				$result = $this->moduleOperations->addModule(
					$this->page['pid'],
					$section,
					$operationParameter1);
				if ($result === false) {
					$this->state = false;
					$this->message = 'UNKNOWN_ERROR';
				}
				else {
					$this->state = true;
					$this->message = 'MODULE_ADDED';
				}
				break;
			case 'move':
			case 'copy':
			case 'delete':
				// check selected modules and target
				if (!Utils::isValidFieldIntArray('modules')
						|| !Utils::isValidFieldInt('operationTarget')) {
					return;
				}
				// normalize selected modules
				$moduleIds = array_unique(Utils::getValidFieldArray('modules'));
				$operationTarget = (int) Utils::getUnmodifiedStringOrEmpty('operationTarget');

				// foreach module
				$result = true;
				foreach ($moduleIds as $moduleId) {
					// check if section exists
					if (!array_key_exists($section, $this->modulesBySection)) {
						return;
					}
					$modulesInSection = $this->modulesBySection[$section];
					// check if module exists
					$module = Utils::getColumnWithValue($modulesInSection, 'mid', (int) $moduleId);
					if ($module === false) {
						return;
					}
					// check target position
					if ($operationTarget < 0 || $operationTarget >= count($modulesInSection)) {
						return;
					}

					if ($operation === 'move') {
						// perform move
						$result = $result
							&& $this->moduleOperations->moveModuleWithinSection(
								$module['mid'],
								$operationTarget);
					}
					else if ($operation === 'copy') {
						// perform copy
						$result = $result
							&& $this->moduleOperations->copyModuleWithinSection(
								$module['mid'],
								$operationTarget);
					}
					else if ($operation === 'delete') {
						// perform delete
						$result = $result
							&& $this->moduleOperations->deleteModule($module['mid']);
					}
				}
				if ($result === false) {
					$this->state = false;
					$this->message = 'UNKNOWN_ERROR';
				}
				else if ($operation === 'move') {
					$this->state = true;
					$this->message = 'MODULE_MOVE_SUCCESSFUL';
				}
				else if ($operation === 'copy') {
					$this->state = true;
					$this->message = 'MODULE_COPY_SUCCESSFUL';
				}
				else if ($operation === 'delete') {
					$this->state = true;
					$this->message = 'MODULE_DELETE_SUCCESSFUL';
				}
				break;
			case 'export':
				// check operationParameter1
				if (!ModuleOperations::isStringValidSection($operationParameter1)) {
					return;
				}
				$targetSection = ModuleOperations::translateSectionString($operationParameter1);
				$page = null;
				// check page if non-global section
				if (!ModuleOperations::isGlobalSection($targetSection)) {
					// check operationParameter2
					if (!Utils::isValidFieldInt('operationParameter2')) {
						$this->state = false;
						$this->message = 'PAGE_NEEDED';
					}
					$page = (int) Utils::getUnmodifiedStringOrEmpty('operationParameter2');
					if (!$this->pageOperations->isValidPageId($page)) {
						return;
					}
				}

				// check selected modules
				if (!Utils::isValidFieldIntArray('modules')) {
					return;
				}
				// normalize selected modules
				$moduleIds = array_unique(Utils::getValidFieldArray('modules'));

				// foreach module
				$result = true;
				foreach ($moduleIds as $moduleId) {
					// check if section exists
					if (!array_key_exists($section, $this->modulesBySection)) {
						return;
					}
					$modulesInSection = $this->modulesBySection[$section];
					// check if module exists
					$module = Utils::getColumnWithValue($modulesInSection, 'mid', (int) $moduleId);
					if ($module === false) {
						return;
					}

					// perform export
					$result = $result
							&& $this->moduleOperations->moveModuleBetweenSections(
								$module['mid'],
								$targetSection,
								$page);
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

	private function checkPageFields() {
		if (!Utils::isValidFieldWithContentNoLinebreak('title', 256)) {
			$this->state = false;
			$this->message = 'INVALID_PAGE_TITLE';
			return false;
		}
		if (!Utils::isValidFieldNoLinebreak('hoverTitle', 256)) {
			$this->state = false;
			$this->message = 'INVALID_PAGE_HOVER_TITLE';
			return false;
		}
		if (Utils::isChecked('pageDirectAccess')
			&& !Utils::isValidFieldIdentifier('externalId', 256)) {
			$this->state = false;
			$this->message = 'INVALID_PAGE_EXTERNAL_ID';
			return false;
		}
		if (Utils::isChecked('pageCustomLastChange')
			&& !Utils::isValidFieldDate('externalLastChanged')) {
			$this->state = false;
			$this->message = 'INVALID_PAGE_EXTERNAL_DATE';
			return false;
		}
		return true;
	}

	private function handleNewPage() {
		if ($this->checkPageFields() === false) {
			return;
		}

		// check if externalId already exists
		$externalId = null;
		if (Utils::isChecked('pageDirectAccess')) {
			$externalId = Utils::getValidFieldString('externalId');

			if ($this->menuItemOperations->isValidExternalId($externalId)
				|| $this->pageOperations->isValidExternalId($externalId)) {
				$this->state = false;
				$this->message = 'PAGE_EXTERNALID_EXISTS';
				return;
			}
		}

		$result = $this->pageOperations->addPage(
			Utils::getValidFieldString('title'),
			Utils::getValidFieldStringOrNull('hoverTitle'),
			$externalId,
			Utils::isChecked('pageDeactivated')? PAGES_OPTION_PRIVATE : 0,
			Utils::isChecked('pageCustomLastChange')? 
				Utils::getValidFieldString('externalLastChanged') : null);

		if ($result == false) {
			$this->state = false;
			$this->message = 'UNKNOWN_ERROR';
			return;
		}
		$this->state = true;
		$this->createdPageId = $result;
	}

	private function handleEditPage() {
		if ($this->checkPageFields() === false) {
			return;
		}

		$updateColumns = [];
		// check for updated title
		$title = Utils::getValidFieldString('title');
		if ($title !== $this->page['title']) {
			$updateColumns['title'] = $title;
		}
		// check for updated hoverTitle
		$hoverTitle = Utils::getValidFieldStringOrNull('hoverTitle');
		if ($hoverTitle !== $this->page['hoverTitle']) {
			$updateColumns['hoverTitle'] = $hoverTitle;
		}
		// check for updated externalId
		if (Utils::isChecked('pageDirectAccess')) {
			$externalId = Utils::getValidFieldString('externalId');
			if ($externalId !== $this->page['externalId']) {
				if ($this->menuItemOperations->isValidExternalId($externalId)
					|| $this->pageOperations->isValidExternalId($externalId)) {
					$this->state = false;
					$this->message = 'PAGE_EXTERNALID_EXISTS';
					return;
				}
				$updateColumns['externalId'] = $externalId;
			}
		}
		else if (!Utils::isChecked('pageDirectAccess') && $this->page['externalId'] !== null) {
			$updateColumns['externalId'] = null;
		}
		// check for updated options
		if (Utils::isChecked('pageDeactivated') && !($this->page['options'] & PAGES_OPTION_PRIVATE)) {
			$updateColumns['options'] = $this->page['options'] | PAGES_OPTION_PRIVATE;
		}
		else if (!Utils::isChecked('pageDeactivated') && ($this->page['options'] & PAGES_OPTION_PRIVATE)) {
			$updateColumns['options'] = $this->page['options'] & ~PAGES_OPTION_PRIVATE;
		}
		// check for updated externalLastChanged
		if (Utils::isChecked('pageCustomLastChange')) {
			$externalLastChanged = Utils::getValidFieldString('externalLastChanged');
			if ($externalLastChanged !== $this->page['externalLastChanged']) {
				$updateColumns['externalLastChanged'] = $externalLastChanged;
			}
		}
		else if (!Utils::isChecked('pageCustomLastChange')&& $this->page['externalLastChanged'] !== null) {
			$updateColumns['externalLastChanged'] = null;
		}

		// perform update
		$result = $this->pageOperations->updatePage($this->page['pid'], $updateColumns);

		if ($result == false) {
			$this->state = false;
			$this->message = 'UNKNOWN_ERROR';
			return;
		}
		$this->state = true;
		$this->message = 'PAGE_EDITED';
	}

	// --------------------------------------------------------------------------------------------
	// Database loading methods
	// --------------------------------------------------------------------------------------------

	private function loadPage($pageId) {
		if (!Utils::isValidInt($pageId)) {
			$this->state = false;
			$this->message = 'PAGE_NOT_FOUND';
			return;
		}
		$result = $this->pageOperations->getPage($pageId);
		if ($result === false) {
			$this->state = false;
			$this->message = 'PAGE_NOT_FOUND';
			return;
		}
		$this->page = $result;
	}

	private function loadModulesBySection() {
		$this->modulesBySection = [];
		$sections = $this->moduleOperations->getModuleSections($this->page['pid']);
		if ($sections === false) {
			return;
		}
		foreach ($sections as $section) {
			$modules = $this->moduleOperations->getModules($this->page['pid'], $section['section']);
			if ($modules === false) {
				continue;
			}
			$this->modulesBySection[$section['section']] = $modules;
		}
	}
}

?>