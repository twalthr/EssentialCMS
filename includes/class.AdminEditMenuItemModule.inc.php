<?php

class AdminEditMenuItemModule extends BasicModule {

	// database operations
	private $pageOperations;
	private $menuItemOperations;

	// UI state
	private $state;
	private $message;

	// member variables
	private $menuItem;

	public function __construct($pageOperations, $menuItemOperations, $parameters = null) {
		parent::__construct(1, 'admin-edit-menu-item');
		$this->pageOperations = $pageOperations;
		$this->menuItemOperations = $menuItemOperations;

		// menu item id is present
		if (isset($parameters) && count($parameters) > 0) {
			$this->loadMenuItem($parameters[0]);
		}

		// handle new menu item
		if (!isset($this->menuItem) && Utils::hasFields()) {
			$this->handleNewMenuItem();
		}
		// handle edit menu item
		else if (isset($this->menuItem)
				&& Utils::getUnmodifiedStringOrEmpty('operationSpace') === 'menuItem') {
			$this->handleEditMenuItem();
			// refresh
			$this->loadPage($this->page['pid']);
		}
	}

	public function printContent($config) {
		?>
			<script type="text/javascript">
				$(document).ready(function() {
					$('#cancel').click(function() {
						window.open('<?php echo $config->getPublicRoot(); ?>/admin/pages', '_self');
					});
					$('#externalId').focus(function() {
						var externalId = $('#externalId');
						if (externalId.val().length == 0) {
							externalId.val(generateIdentifierFromString($('#title').val()));
						}
					});
					$('#targets input').change(function() {
						var input = $(this);
						if (input.val() == 'no') {
							$('#targetPage').addClass('hidden');
							$('#targetWebsite').addClass('hidden');
						}
						else if (input.val() == 'page') {
							$('#targetPage').removeClass('hidden');
							$('#targetWebsite').addClass('hidden');
						}
						else if (input.val() == 'link') {
							$('#targetPage').addClass('hidden');
							$('#targetWebsite').removeClass('hidden');
						}
					});
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
			<form method="post"
				<?php if (!isset($this->menuItem)) : ?>
					action="<?php echo $config->getPublicRoot(); ?>/admin/new-menu-item"
				<?php endif; ?>>
			<input type="hidden" name="operationSpace" value="menuItem" />
				<section>
					<h1>
						<?php if (isset($this->menuItem)) : ?>
							<?php $this->text('MENU_ITEM_PROPERTIES'); ?>: 
								<?php echo Utils::escapeString($this->menuItem['title']); ?>
						<?php else : ?>
							<?php $this->text('NEW_MENU_ITEM'); ?>
						<?php endif; ?>
					</h1>
					<div class="buttonSet general">
						<?php if (isset($this->menuItem)) : ?>
							<input type="submit" value="<?php $this->text('SAVE'); ?>" />
						<?php else: ?>
							<input type="submit" value="<?php $this->text('CREATE'); ?>" />
						<?php endif; ?>
						<button id="cancel"><?php $this->text('CANCEL'); ?></button>
					</div>
					<div class="fields">
						<div class="field">
							<label for="title"><?php $this->text('MENU_ITEM_TITLE'); ?></label>
							<input type="text" name="title" id="title" class="large"
								value="<?php echo Utils::getEscapedFieldOrVariable('title',
									$this->menuItem['title']); ?>"
								required />
						</div>
						<div class="field">
							<label for="externalId"><?php $this->text('MENU_ITEM_EXTERNAL_ID'); ?></label>
							<input type="text" name="externalId" id="externalId"  class="large"
								value="<?php echo Utils::getEscapedFieldOrVariable('externalId',
									$this->menuItem['externalId']); ?>"/>
							<span class="hint"><?php $this->text('MENU_ITEM_EXTERNAL_ID_HINT'); ?></span>
						</div>
						<div class="field">
							<label><?php $this->text('TARGET'); ?></label>
							<div class="checkboxGroup" id="targets">
								<div class="checkboxWrapper">
									<input type="radio" id="target1" name="target"
										value="no" />
									<label for="target1" class="checkbox">
										<?php $this->text('MENU_ITEM_NO_TARGET'); ?>
									</label>
									<?php $this->text('MENU_ITEM_NO_TARGET'); ?>
								</div>
								<div class="checkboxWrapper">
									<input type="radio" id="target2" name="target"
										value="page" />
									<label for="target2" class="checkbox">
										<?php $this->text('MENU_ITEM_TARGET_PAGE'); ?>
									</label>
									<?php $this->text('MENU_ITEM_TARGET_PAGE'); ?>
								</div>
								<div class="checkboxWrapper">
									<input type="radio" id="target3" name="target"
										value="link" />
									<label for="target3" class="checkbox">
										<?php $this->text('MENU_ITEM_TARGET_LINK'); ?>
									</label>
									<?php $this->text('MENU_ITEM_TARGET_LINK'); ?>
								</div>
							</div>
						</div>
						<div class="field hidden" id="targetPage">
							<label for="pageSelect"><?php $this->text('MENU_ITEM_PAGE'); ?></label>
							<input type="text" name="pageName" class="large selectedPageName"
								disabled />
							<span class="inputOption">
								<input type="hidden" name="pageId" id="pageId" class="selectedPageId" />
								<button class="pageSelect"><?php $this->text('SELECT'); ?></button>
							</span>
						</div>
						<div class="field hidden" id="targetWebsite">
							<label for="website"><?php $this->text('MENU_ITEM_WEBSITE'); ?></label>
							<input type="text" name="page" id="website" class="large" />
							<span class="hint"><?php $this->text('WEBSITE_HINT'); ?></span>
						</div>
						<div class="field">
							<label for="hoverTitle"><?php $this->text('HOVER_TITLE'); ?></label>
							<input type="text" name="hoverTitle" id="hoverTitle"  class="large"
								value="<?php echo Utils::getEscapedFieldOrVariable('hoverTitle',
									$this->menuItem['hoverTitle']); ?>"
								/>
							<span class="hint"><?php $this->text('HOVER_TITLE_HINT'); ?></span>
						</div>
						<div class="field">
							<label><?php $this->text('PUBLICATION'); ?></label>
							<div class="checkboxWrapper">
								<input type="checkbox" id="deactivated" name="deactivated"
									value="deactivated"
									<?php echo (Utils::getCheckedFieldOrVariableFlag('deactivated',
										$this->menuItem['options'],
										MenuItemOperations::MENU_ITEMS_OPTION_PRIVATE))? 'checked' : ''; ?>
										/>
								<label for="deactivated" class="checkbox">
									<?php $this->text('DEACTIVATE_MENU_ITEM'); ?>
								</label>
								<?php $this->text('DEACTIVATE_MENU_ITEM'); ?>
							</div>
						</div>
						<div class="field">
							<label><?php $this->text('WINDOW'); ?></label>
							<div class="checkboxWrapper">
								<input type="checkbox" id="window" name="window"
									value="new"
									<?php echo (Utils::getCheckedFieldOrVariableFlag('window',
										$this->menuItem['options'],
										MenuItemOperations::MENU_ITEMS_OPTION_BLANK))? 'checked' : ''; ?>
										/>
								<label for="window" class="checkbox">
									<?php $this->text('OPEN_IN_NEW_WINDOW'); ?>
								</label>
								<?php $this->text('OPEN_IN_NEW_WINDOW'); ?>
							</div>
						</div>
					</div>
					<div class="fieldsRequired">
						<?php $this->text('REQUIRED'); ?>
					</div>
				</section>
			</form>
		<?php
	}

	// --------------------------------------------------------------------------------------------
	// User input handling methods
	// --------------------------------------------------------------------------------------------

	private function checkFields() {
		if (!Utils::isValidFieldWithContentNoLinebreak('title', 256)) {
			$this->state = false;
			$this->message = 'INVALID_MENU_ITEM_TITLE';
			return false;
		}
		if (!Utils::isValidFieldNoLinebreak('hoverTitle', 256)) {
			$this->state = false;
			$this->message = 'INVALID_HOVER_TITLE';
			return false;
		}
		if (!Utils::isValidFieldWithContentNoLinebreak('externalId', 256)
				|| !Utils::isValidFieldIdentifier('externalId', 256)) {
			$this->state = false;
			$this->message = 'INVALID_MENU_ITEM_EXTERNAL_ID';
			return false;
		}
		return true;
	}

	private function handleNewMenuItem() {
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
				$this->message = 'PAGE_EXTERNAL_ID_EXISTS';
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

	private function handleEditMenuItem() {
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
					$this->message = 'PAGE_EXTERNAL_ID_EXISTS';
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

	private function loadMenuItem($menuItemId) {
		if (!Utils::isValidInt($menuItemId)) {
			$this->state = false;
			$this->message = 'MENU_ITEM_NOT_FOUND';
			return;
		}
		$menuItem = $this->menuItemOperations->getMenuItem($menuItemId);
		if ($menuItem === false) {
			$this->state = false;
			$this->message = 'MENU_ITEM_NOT_FOUND';
			return;
		}
		$this->menuItem = $menuItem;
	}
}

?>