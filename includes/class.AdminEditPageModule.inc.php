<?php

class AdminEditPageModule extends BasicModule {

	// database operations
	private $moduleOperations;

	// UI state
	private $state;
	private $message;
	private $createdPageId;

	// member variables
	private $page;
	private $modulesBySection;

	public function __construct($moduleOperations, $parameters = null) {
		global $CMS_VERSION;
		parent::__construct($CMS_VERSION, 'admin-edit-page');
		$this->moduleOperations = $moduleOperations;

		// page id is present
		if (isset($parameters) && count($parameters) > 0) {
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
			$this->loadPage($parameters[0]);
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
		<?php endif; ?>

		<script type="text/javascript">
			$(document).ready(function() {
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
					if (!externalLastChanged.prop('disabled') && externalLastChanged.val().length == 0)  {
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
						$('.lightbox-overlay-dialog #cancel-selection').click(closeLightbox);
						$('.select-module').click(function() {
							form.find('[name="operation"]').val('add');
							form.find('[name="operationParameter"]').val($(this).val());
							form.submit();
						});
					};
					openLightboxWithUrl('<?php echo $config->getPublicRoot(); ?>/admin/select-module-dialog',
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
					// TODO
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
						<h1 class="hiddenInEditMode"><?php echo Utils::escapeString($this->page['title']); ?></h1>
						<h1 class="hidden showInEditMode"><?php $this->text('PAGE_PROPERTIES'); ?></h1>
						<button id="editPage" class="hiddenInEditMode"><?php $this->text('EDIT_PAGE'); ?></button>
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
									<input type="checkbox" id="pageCustomLastChange" name="pageCustomLastChange"
										value="custom-last-change"
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
								<input type="text" name="externalLastChanged" id="externalLastChanged" disabled 
									value="<?php echo Utils::getEscapedFieldOrVariable('externalLastChanged',
										$this->page['externalLastChanged']); ?>" />
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
						'PRE_CONTENT_MODULES',
						'preContent', 
						MODULES_SECTION_PRE_CONTENT); ?>
					<?php $this->printSection(
						$config,
						'CONTENT_MODULES',
						'content', 
						MODULES_SECTION_CONTENT); ?>
					<?php $this->printSection(
						$config,
						'ASIDE_CONTENT_MODULES',
						'asideContent', 
						MODULES_SECTION_ASIDE_CONTENT); ?>
					<?php $this->printSection(
						$config,
						'POST_CONTENT_MODULES',
						'postContent', 
						MODULES_SECTION_POST_CONTENT); ?>
			<?php endif; ?>
		<?php
	}

	// --------------------------------------------------------------------------------------------
	// Printing methods
	// --------------------------------------------------------------------------------------------

	private function printSection($config, $sectionTitle, $sectionString, $section) {
		?>
		<form method="post">
			<input type="hidden" name="operationSpace" value="module" />
			<input type="hidden" name="section" value="<?php echo $sectionString; ?>" />
			<input type="hidden" name="operation" />
			<input type="hidden" name="operationParameter" />
			<section class="<?php echo array_key_exists($section, $this->modulesBySection) ?
				'' : 'hidden showInEditMode'; ?>">
				<h1><?php $this->text($sectionTitle); ?></h1>
				<button class="hidden showInEditMode addButton" value="<?php echo $sectionString; ?>">
					<?php $this->text('ADD_MODULE'); ?></button>
				<div class="moduleList enableButtonsIfChecked">
					<?php $this->printModuleListAsTable($config, $section); ?>
				</div>
				<div class="buttonSet hidden showInEditMode">
					<button class="moveModule disableListIfClicked"
						value="<?php echo $sectionString; ?>" disabled>
						<?php $this->text('MOVE'); ?>
					</button>
					<button class="copyModule disableListIfClicked"
						value="<?php echo $sectionString; ?>" disabled>
						<?php $this->text('COPY'); ?>
					</button>
					<button class="exportModule disableListIfClicked"
						value="<?php echo $sectionString; ?>" disabled>
						<?php $this->text('EXPORT'); ?>
					</button>
					<button class="deleteModule disableListIfClicked"
						value="<?php echo $sectionString; ?>" disabled>
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
			echo $this->text('NO_MODULES_IN_PAGE');
			echo '</p>';
		}
	}

	private function printModuleListAsTable($config, $section) {
		if (!array_key_exists($section, $this->modulesBySection)) {
			echo '<p class="empty">';
			echo $this->text('NO_MODULES_IN_SECTION');
			echo '</p>';
			return;
		}

		echo '<ul class="tableLike">';
		foreach ($this->modulesBySection[$section] as $module) {
			$moduleInfo = RichModule::getLocalizedModuleInfo($module['module']);
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
			echo Utils::escapeString($module['module']);
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
			echo Utils::escapeString(RichModule::getLocalizedModuleInfo($module['module'])['name']);
			echo '</option>';
			$i++;
		}
		echo '<option value="-1" selected>';
		echo $this->text('INSERT_AT_END');
		echo '</option>';
		echo '</select>';
	}

	// --------------------------------------------------------------------------------------------
	// User input handling methods
	// --------------------------------------------------------------------------------------------

	private function isValidSectionString($section) {
		return in_array($section, ['preContent', 'content', 'asideContent', 'postContent'], true);
	}

	private function translateSectionString($section) {
		switch ($section) {
			case 'preContent': return MODULES_SECTION_PRE_CONTENT;
			case 'content': return MODULES_SECTION_CONTENT;
			case 'asideContent': return MODULES_SECTION_ASIDE_CONTENT;
			case 'postContent': return MODULES_SECTION_POST_CONTENT;
		}
	}

	private function handleEditModules() {
		$operation = Utils::getUnmodifiedStringOrEmpty('operation');
		$sectionString = Utils::getUnmodifiedStringOrEmpty('section');
		$operationParameter = Utils::getUnmodifiedStringOrEmpty('operationParameter');

		// check section
		if (!$this->isValidSectionString($sectionString)) {
			return;
		}
		$section = $this->translateSectionString($sectionString);

		// do operation
		switch ($operation) {
			case 'add':
				// check operationParameter
				if (!RichModule::isValidModuleId($operationParameter)) {
					return;
				}
				// add to database
				$result = $this->moduleOperations->addModule(
					$this->page['pid'],
					$section,
					$operationParameter);
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
				// check selected modules and target
				if (!Utils::isValidFieldIntArray('modules')
						|| !Utils::isValidFieldInt('operationTarget')) {
					return;
				}
				// normalize and reverse selected modules
				$moduleIds = array_reverse(array_unique(Utils::getValidFieldArray('modules')));
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
					if ($operationTarget >= count($modulesInSection)) {
						return;
					}

					// perform move
					$result = $result
						&& $this->moduleOperations->moveModuleWithinSection(
							$module['mid'],
							$operationTarget);
				}
				if ($result === false) {
					$this->state = false;
					$this->message = 'UNKNOWN_ERROR';
				}
				else {
					$this->state = true;
					$this->message = 'MODULE_MOVE_SUCCESSFUL';
				}
				break;
		}
	}

	private function handleNewPage() {
		global $DB;
		if (!Utils::isValidFieldWithContentNoLinebreak('title', 256)) {
			$this->state = false;
			$this->message = 'INVALID_PAGE_TITLE';
			return;
		}
		if (!Utils::isValidFieldNoLinebreak('hoverTitle', 256)) {
			$this->state = false;
			$this->message = 'INVALID_PAGE_HOVER_TITLE';
			return;
		}
		if (Utils::isChecked('pageDirectAccess')
			&& !Utils::isValidFieldIdentifier('externalId', 256)) {
			$this->state = false;
			$this->message = 'INVALID_PAGE_EXTERNAL_ID';
			return;
		}
		if (Utils::isChecked('pageCustomLastChange')
			&& !Utils::isValidFieldDate('externalLastChanged')) {
			$this->state = false;
			$this->message = 'INVALID_PAGE_EXTERNAL_DATE';
			return;
		}

		// check if externalId already exists
		$externalId = null;
		if (Utils::isChecked('pageDirectAccess')) {
			$externalId = Utils::getValidFieldString('externalId');

			$menuItemExists = $DB->resultQuery('SELECT `mpid` FROM `MenuPaths` WHERE `externalId`=?', 's', $externalId);
			$pageExists = $DB->resultQuery('SELECT `pid` FROM `Pages` WHERE `externalId`=?', 's', $externalId);

			if ($menuItemExists || $pageExists) {
				$this->state = false;
				$this->message = 'PAGE_EXTERNALID_EXISTS';
				return;
			}
		}

		$result = $DB->impactQueryWithId('
			INSERT INTO `Pages`
			(`title`, `hoverTitle`, `externalId`, `options`, `lastChanged`, `externalLastChanged`)
			VALUES
			(?,?,?,?,NOW(),?)',
			'sssis',
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

	}

	// --------------------------------------------------------------------------------------------
	// Database loading methods
	// --------------------------------------------------------------------------------------------

	private function loadPage($pageId) {
		global $DB;
		$result = ctype_digit($pageId)
			&& ($resultValue = $DB->valueQuery('
				SELECT `pid`, `title`, `hoverTitle`, `externalId`, `options`, `lastChanged`, `externalLastChanged`
				FROM `Pages`
				WHERE `pid`=?', 'i', $pageId));
		if ($result === false) {
			$this->state = false;
			$this->message = 'PAGE_NOT_FOUND';
			return;
		}
		$this->page = $resultValue;
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