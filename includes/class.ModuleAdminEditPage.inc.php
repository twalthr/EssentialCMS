<?php

class ModuleAdminEditPage extends BasicModule {

	private $state;
	private $message;
	private $page;
	private $createdPageId;

	public function __construct(&$controller, $pageId = null) {
		global $CMS_VERSION;
		parent::__construct($CMS_VERSION, 'admin-edit-page');
		$controller->verifyLogin();

		// page is present
		if (isset($pageId)) {
			$this->loadPage($pageId);
		}

		// handle new page
		if (Utils::hasFields() && !isset($this->page)) {
			$this->handleNewPage();
		}
		// handle edit page
		else if (Utils::hasFields() && isset($this->page)) {
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
			$(document).ready(function(){
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
				action="<?php echo $config->getPublicRoot()?>/admin/page/<?php echo $this->page['pid']?>">
		<?php else : ?>
			<form method="post" action="<?php echo $config->getPublicRoot()?>/admin/new-page">
		<?php endif; ?>
			<section>
				<?php if (isset($this->page)) : ?>
					<h1><?php $this->text('PAGE_PROPERTIES'); ?></h1>
				<?php else : ?>
					<h1><?php $this->text('NEW_PAGE'); ?></h1>
				<?php endif; ?>
				<div class="fields">
					<div class="field">
						<label for="title"><?php $this->text('PAGE_TITLE'); ?></label>
						<input type="text" name="title" id="title" class="large"
							value="<?php echo Utils::getEscapedFieldOrVariable('title', $this->page['title']); ?>"
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
							<input type="checkbox" id="pageDirectAccess" name="pageDirectAccess" value="direct-access"
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
						<label for="externalLastChanged"><?php $this->text('PAGE_EXTERNAL_LAST_CHANGED'); ?></label>
						<input type="text" name="externalLastChanged" id="externalLastChanged" disabled 
						value="<?php echo Utils::getEscapedFieldOrVariable('externalLastChanged',
								$this->page['externalLastChanged']); ?>" />
					</div>
					<div class="field">
						<label><?php $this->text('PUBLICATION'); ?></label>
						<div class="checkboxWrapper">
							<input type="checkbox" id="pageDeactivated" name="pageDeactivated" value="deactivated"
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
					<input type="submit" value="<?php $this->text('CREATE_PAGE'); ?>" />
				</div>
			</section>
			<?php if (isset($this->page)) : ?>
				<section>
					<h1><?php $this->text('HEADER_MODULES'); ?></h1>
				</section>
				<section>
					<h1><?php $this->text('CONTENT_MODULES'); ?></h1>
				</section>
				<section>
					<h1><?php $this->text('ASIDE_CONTENT_MODULES'); ?></h1>
				</section>
				<section>
					<h1><?php $this->text('FOOTER_MODULES'); ?></h1>
				</section>
			<?php endif; ?>
		</form>
		<?php
	}

	// --------------------------------------------------------------------------------------------

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

	private function handleCreatePage() {

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