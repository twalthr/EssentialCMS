<?php

class AdminEditPageModule extends BasicModule {

	// database operations
	private $modulesOperations;

	// UI state
	private $state;
	private $message;
	private $createdPageId;

	// member variables
	private $page;

	public function __construct($modulesOperations, $parameters) {
		global $CMS_VERSION;
		parent::__construct($CMS_VERSION, 'admin-edit-page');
		$this->modulesOperations = $modulesOperations;

		// page is present
		if (count($parameters) > 0) {
			$this->loadPage($parameters[0]);
		}

		// handle new page
		if (Utils::hasFields() && !isset($this->page)) {
			$this->handleNewPage();
		}
		// handle edit modules
		else if (Utils::hasFields()
			&& isset($this->page)
			&& Utils::getUnmodifiedStringOrEmpty('operationSpace') === 'module') {
			$this->handleEditModules();
		}
		// handle edit page
		else if (Utils::hasFields()
			&& isset($this->page)
			&& Utils::getUnmodifiedStringOrEmpty('operationSpace') === 'page') {
			$this->handleEditPage();
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
				$('.moduleList').each(function() {
					var moduleList = $(this);
					moduleList
						.find('input[type="checkbox"]')
						.change(function() {
							var disabled = moduleList.find('input[type="checkbox"]:checked').length == 0;
							moduleList.next().find('button').prop('disabled', disabled);
						});
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
				$('.addModule').click(function(e) {
					var sectionId = $(this).val();
					var lightboxOpened = function() {
						$('.lightbox-overlay-dialog #cancel-selection').click(closeLightbox);
						$('.select-module').click(function() {
							$('#moduleOperation').val('add');
							$('#moduleSection').val(sectionId);
							$('#moduleParameter1').val($(this).val());
							$('#moduleOperations').submit();
						});
					};
					openLightboxWithUrl('<?php echo $config->getPublicRoot(); ?>/admin/select-module-dialog',
						true,
						lightboxOpened);
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
			<form method="post"
				action="<?php echo $config->getPublicRoot(); ?>/admin/page/<?php echo $this->page['pid']; ?>">
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
				<form method="post" id="moduleOperations"
					action="<?php echo $config->getPublicRoot(); ?>/admin/page/<?php echo $this->page['pid']; ?>">
					<input type="hidden" name="operationSpace" value="module" />
					<input type="hidden" name="operation" id="moduleOperation" />
					<input type="hidden" name="section" id="moduleSection" />
					<input type="hidden" name="operationParameter1" id="moduleParameter1" />
					<input type="hidden" name="operationParameter2" id="moduleParameter2" />
					<section <?php $this->printHiddenClass(MODULES_SECTION_PRE_CONTENT); ?>>
						<h1><?php $this->text('PRE_CONTENT_MODULES'); ?></h1>
						<button class="hidden showInEditMode addModule" value="preContent">
							<?php $this->text('ADD_MODULE'); ?></button>
						<div class="moduleList">
							<?php $this->printModuleList($config, MODULES_SECTION_PRE_CONTENT, 'preContent'); ?>
						</div>
						<div class="buttonSet hidden showInEditMode">
							<button class="moveModule" value="preContent" disabled>
								<?php $this->text('MOVE'); ?></button>
							<button class="copyModule" value="preContent" disabled>
								<?php $this->text('COPY'); ?></button>
							<button class="importModule" value="preContent" disabled>
								<?php $this->text('IMPORT'); ?></button>
							<button class="deleteModule" value="preContent" disabled>
								<?php $this->text('DELETE'); ?></button>
						</div>
					</section>
					<section <?php $this->printHiddenClass(MODULES_SECTION_CONTENT); ?>>
						<h1><?php $this->text('CONTENT_MODULES'); ?></h1>
						<button class="hidden showInEditMode addModule" value="content">
							<?php $this->text('ADD_MODULE'); ?></button>
						<div class="moduleList">
							<?php $this->printModuleList($config, MODULES_SECTION_CONTENT, 'content'); ?>
						</div>
						<div class="buttonSet hidden showInEditMode">
							<button class="moveModule" value="content" disabled>
								<?php $this->text('MOVE'); ?></button>
							<button class="copyModule" value="content" disabled>
								<?php $this->text('COPY'); ?></button>
							<button class="importModule" value="content" disabled>
								<?php $this->text('IMPORT'); ?></button>
							<button class="deleteModule" value="content" disabled>
								<?php $this->text('DELETE'); ?></button>
						</div>
					</section>
					<section <?php $this->printHiddenClass(MODULES_SECTION_ASIDE_CONTENT); ?>>
						<h1><?php $this->text('ASIDE_CONTENT_MODULES'); ?></h1>
						<button class="hidden showInEditMode addModule" value="asideContent">
							<?php $this->text('ADD_MODULE'); ?></button>
						<div class="moduleList">
							<?php $this->printModuleList($config, MODULES_SECTION_ASIDE_CONTENT, 'asideContent'); ?>
						</div>
						<div class="buttonSet hidden showInEditMode">
							<button class="moveModule" value="asideContent" disabled>
								<?php $this->text('MOVE'); ?></button>
							<button class="copyModule" value="asideContent" disabled>
								<?php $this->text('COPY'); ?></button>
							<button class="importModule" value="asideContent" disabled>
								<?php $this->text('IMPORT'); ?></button>
							<button class="deleteModule" value="asideContent" disabled>
								<?php $this->text('DELETE'); ?></button>
						</div>
					</section>
					<section <?php $this->printHiddenClass(MODULES_SECTION_POST_CONTENT); ?>>
						<h1><?php $this->text('POST_CONTENT_MODULES'); ?></h1>
						<button class="hidden showInEditMode addModule" value="postContent">
							<?php $this->text('ADD_MODULE'); ?></button>
						<div class="moduleList">
							<?php $this->printModuleList($config, MODULES_SECTION_POST_CONTENT, 'postContent'); ?>
						</div>
						<div class="buttonSet hidden showInEditMode">
							<button class="moveModule" value="postContent" disabled>
								<?php $this->text('MOVE'); ?></button>
							<button class="copyModule" value="postContent" disabled>
								<?php $this->text('COPY'); ?></button>
							<button class="importModule" value="postContent" disabled>
								<?php $this->text('IMPORT'); ?></button>
							<button class="deleteModule" value="postContent" disabled>
								<?php $this->text('DELETE'); ?></button>
						</div>
					</section>
				</form>
			<?php endif; ?>
		<?php
	}

	// --------------------------------------------------------------------------------------------
	// Printing methods
	// --------------------------------------------------------------------------------------------

	private function printMissingModules() {
		global $DB;
		$moduleCount = $DB->valueQuery('
			SELECT COUNT(*) AS `count`
			FROM `Modules`
			WHERE `page`=?',
			'i', $this->page['pid']);
		if ($moduleCount === false || $moduleCount['count'] === 0) {
			echo '<p class="empty hiddenInEditMode">';
			echo $this->text('NO_MODULES_IN_PAGE');
			echo '</p>';
		}
	}

	private function printHiddenClass($section) {
		global $DB;
		$moduleCount = $DB->valueQuery('
			SELECT COUNT(*) AS `count`
			FROM `Modules`
			WHERE `page`=? AND `section`=?',
			'ii', $this->page['pid'], $section);
		if ($moduleCount === false || $moduleCount['count'] === 0) {
			echo 'class="hidden showInEditMode"';
		}
	}

	private function printModuleList($config, $section, $sectionString) {
		global $DB;
		$modules = $DB->valuesQuery('
			SELECT `mid`, `module`
			FROM `Modules`
			WHERE `page`=? AND `section`=?
			ORDER BY `order` ASC',
			'ii', $this->page['pid'], $section);

		if ($modules === false || empty($modules)) {
			echo '<p class="empty">';
			echo $this->text('NO_MODULES_IN_SECTION');
			echo '</p>';
			return;
		}

		echo '<ul class="tableLike">';
		foreach ($modules as $module) {
			$moduleInfo = RichModule::getLocalizedModuleInfo($module['module']);
			if ($moduleInfo === false) {
				continue;
			}
			echo '<li class="rowLike">';
			echo '<input type="checkbox" id="' . $sectionString . 'Module' . $module['mid'] . '"';
			echo ' name="' . $sectionString . '[]"';
			echo ' value="' . $module['mid'] . '" />';
			echo '<label for="' . $sectionString . 'Module' . $module['mid'] . '"';
			echo ' class="checkbox hidden showInEditMode">';
			echo Utils::escapeString($moduleInfo['name']) .' </label>';
			echo '<a href="' . $config->getPublicRoot() . '/admin/module/' . $module['mid'] . '"';
			if (!empty($moduleInfo['description'])) {
				echo ' title="' . Utils::escapeString(Utils::internalHtmlToText($moduleInfo['description'])) . '"';
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
		$section = Utils::getUnmodifiedStringOrEmpty('section');
		$operationParameter1 = Utils::getUnmodifiedStringOrEmpty('operationParameter1');
		$operationParameter2 = Utils::getUnmodifiedStringOrEmpty('operationParameter2');
		switch ($operation) {
			case 'add':
				// check section
				if (!$this->isValidSectionString($section)) {
					return;
				}
				// check operationParameter1
				if (!RichModule::isValidModuleId($operationParameter1)) {
					return;
				}
				// add to database
				$result = $this->modulesOperations->addModule(
					$this->page['pid'],
					$this->translateSectionString($section),
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

}

?>