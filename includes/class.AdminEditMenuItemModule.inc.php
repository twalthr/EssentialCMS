<?php

class AdminEditMenuItemModule extends BasicModule {

	// database operations
	private $menuItemOperations;
	private $globalOperations;

	// UI state
	private $state;
	private $message;

	// member variables
	private $menu;
	private $menuItem;

	public function __construct($menuItemOperations, $globalOperations, $parameters = null) {
		parent::__construct(1, 'admin-edit-menu-item');
		$this->menuItemOperations = $menuItemOperations;
		$this->globalOperations = $globalOperations;

		$this->loadMenu();
		if (!isset($this->menu)) {
			$this->state = false;
			$this->message = 'UNKNOWN_ERROR';
			return;
		}

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
			$this->loadMenuItem($this->menuItem['miid']);
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
					$('#parent').change(function() {
						var option = $(this).children(':selected');
						if (option.hasClass('insertAtEnd')) {
							$('#insertion').val('end');
						}
						else if (option.hasClass('insertAsSubmenu')) {
							$('#insertion').val('submenu');
						}
						else {
							$('#insertion').val('at');
						}
					});
					$('#parent').trigger('change');
					$('#targets input').change(function() {
						var input = $(this);
						if (input.val() == 'no') {
							$('#targetPage').addClass('hidden');
							$('#targetWebsite').addClass('hidden');
							$('#pageSelectionId').prop('disabled', true);
							$('#website').prop('disabled', true);
						}
						else if (input.val() == 'page') {
							$('#targetPage').removeClass('hidden');
							$('#targetWebsite').addClass('hidden');
							$('#pageSelectionId').prop('disabled', false);
							$('#website').prop('disabled', true);
						}
						else if (input.val() == 'link') {
							$('#targetPage').addClass('hidden');
							$('#targetWebsite').removeClass('hidden');
							$('#pageSelectionId').prop('disabled', true);
							$('#website').prop('disabled', false);
						}
					});
					$('#targets input:checked').trigger('change');
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
								required maxlength="256" />
						</div>
						<div class="field">
							<label for="externalId"><?php $this->text('MENU_ITEM_EXTERNAL_ID'); ?></label>
							<input type="text" name="externalId" id="externalId"  class="large"
								value="<?php echo Utils::getEscapedFieldOrVariable('externalId',
									$this->menuItem['externalId']); ?>"
								required maxlength="256" />
							<span class="hint"><?php $this->text('MENU_ITEM_EXTERNAL_ID_HINT'); ?></span>
						</div>
						<div class="field">
							<input type="hidden" id="insertion" name="insertion" value="at" />
							<label for="parent"><?php $this->text('MENU_ITEM_PARENT'); ?></label>
							<?php $this->printMenuItemList(); ?>
						</div>
						<div class="field">
							<label><?php $this->text('TARGET'); ?></label>
							<div class="checkboxGroup" id="targets">
								<div class="checkboxWrapper">
									<input type="radio" id="target1" name="target"
										value="no" 
										<?php if (Utils::getUnmodifiedStringOrEmpty('target') === 'no'
											|| (Utils::hasFields()
												&& !Utils::isValidField('target')
												&& !isset($this->menuItem['destPage'])
												&& !isset($this->menuItem['destLink']))) : ?>
												checked
										<?php endif; ?> />
									<label for="target1" class="checkbox">
										<?php $this->text('MENU_ITEM_NO_TARGET'); ?>
									</label>
									<?php $this->text('MENU_ITEM_NO_TARGET'); ?>
								</div>
								<div class="checkboxWrapper">
									<input type="radio" id="target2" name="target"
										value="page"
										<?php if (Utils::getUnmodifiedStringOrEmpty('target') === 'page'
											|| (!Utils::isValidField('target')
												&& isset($this->menuItem['destPage'])
												&& !isset($this->menuItem['destLink']))) : ?>
												checked
										<?php endif; ?> />
									<label for="target2" class="checkbox">
										<?php $this->text('MENU_ITEM_TARGET_PAGE'); ?>
									</label>
									<?php $this->text('MENU_ITEM_TARGET_PAGE'); ?>
								</div>
								<div class="checkboxWrapper">
									<input type="radio" id="target3" name="target"
										value="link"
										<?php if (Utils::getUnmodifiedStringOrEmpty('target') === 'link'
											|| (!Utils::isValidField('target')
												&& !isset($this->menuItem['destPage'])
												&& isset($this->menuItem['destLink']))) : ?>
												checked
										<?php endif; ?> />
									<label for="target3" class="checkbox">
										<?php $this->text('MENU_ITEM_TARGET_LINK'); ?>
									</label>
									<?php $this->text('MENU_ITEM_TARGET_LINK'); ?>
								</div>
							</div>
						</div>
						<div class="field hidden" id="targetPage">
							<label for="pageSelectionId"><?php $this->text('MENU_ITEM_PAGE'); ?></label>
							<span class="inputWithOption">
								<input type="hidden" name="pageSelectionId" id="pageSelectionId"
									class="pageSelectionId" disabled />
								<input type="text" class="large pageSelectionName" disabled />
								<button class="pageSelectionButton"><?php $this->text('SELECT'); ?></button>
							</span>
						</div>
						<div class="field hidden" id="targetWebsite">
							<label for="website"><?php $this->text('MENU_ITEM_WEBSITE'); ?></label>
							<input type="text" name="website" id="website" class="large"
								required maxlength="1024" disabled />
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
							<label><?php $this->text('VISIBILITY'); ?></label>
							<div class="checkboxWrapper">
								<input type="checkbox" id="deactivated" name="visibility"
									value="visible"
									<?php echo (Utils::getCheckedFieldOrVariableFlag('visibility',
										$this->menuItem['options'],
										MenuItemOperations::MENU_ITEMS_OPTION_HIDDEN))? 'checked' : ''; ?>
										/>
								<label for="visibility" class="checkbox">
									<?php $this->text('HIDE_MENU_ITEM'); ?>
								</label>
								<?php $this->text('HIDE_MENU_ITEM'); ?>
							</div>
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
	// Printing methods
	// --------------------------------------------------------------------------------------------

	private function printMenuItemList() {
		echo '<select name="parent" id="parent">';
		foreach ($this->menu as $menuItem) {
			$this->printMenuItemWithinList($menuItem, 0);
		}
		echo '<option value="' . $menuItem['miid'] . '" class="insertAtEnd" selected>';
		$this->text('MENU_ITEM_AT_END');
		echo '</option>';
		echo '</select>';
	}

	private function printMenuItemWithinList($menuItem, $level) {
		echo '<option value="' . $menuItem['miid'] . '"';
		if (Utils::hasStringContent($menuItem['hoverTitle'])) {
			echo ' title="' . Utils::escapeString($menuItem['hoverTitle']) . '"';
		}
		echo '>';
		echo str_repeat('&nbsp;&nbsp;', $level);
		echo Utils::escapeString($menuItem['title']);
		echo '</option>';
		if (count($menuItem['submenu']) === 0) {
			echo '<option value="' . $menuItem['miid'] . '" class="insertAsSubmenu">';
			echo str_repeat('&nbsp;&nbsp;', $level + 1);
			$this->text('MENU_ITEM_AS_SUBMENU');
			echo '</option>';
		}
		foreach ($menuItem['submenu'] as $menuItem) {
			$this->printMenuItemWithinList($menuItem, $level + 1);
		}
		if (count($menuItem['submenu']) > 0) {
			echo '<option value="' . $menuItem['miid'] . '" class="insertAtEnd">';
			echo str_repeat('&nbsp;&nbsp;', $level + 1);
			$this->text('MENU_ITEM_AT_END');
			echo '</option>';
		}
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
		if (!Utils::isValidFieldWithContentNoLinebreak('externalId', 256)
				|| !Utils::isValidFieldIdentifier('externalId', 256)) {
			$this->state = false;
			$this->message = 'INVALID_MENU_ITEM_EXTERNAL_ID';
			return false;
		}
		if (!Utils::isValidFieldInt('parent')) {
			$this->state = false;
			$this->message = 'INVALID_MENU_ITEM_PARENT';
			return false;
		}
		$target = Utils::getUnmodifiedStringOrEmpty('target');
		if ($target !== 'no' && $target !== 'page' && $target !== 'link') {
			$this->state = false;
			$this->message = 'INVALID_MENU_ITEM_TARGET';
			return false;
		}
		if (($target === 'page' && !Utils::isValidFieldInt('pageSelectionId'))
				|| ($target === 'link' && !Utils::isValidFieldLink('website'))) {
			$this->state = false;
			$this->message = 'INVALID_MENU_ITEM_TARGET';
			return false;
		}
		if (!Utils::isValidFieldNoLinebreak('hoverTitle', 256)) {
			$this->state = false;
			$this->message = 'INVALID_HOVER_TITLE';
			return false;
		}
		if (!Utils::isValidField('deactivated') || !Utils::isValidField('window')
				|| !Utils::isValidField('visibility')) {
			$this->state = false;
			$this->message = 'UNKNOWN_ERROR';
			return false;
		}
		return true;
	}

	private function handleNewMenuItem() {
		if ($this->checkFields() === false) {
			return;
		}

		// determine the parent
		$parent = $this->getMenuItem($this->menuItem, (int) Utils::getValidFieldString('parent'));

		// check if externalId already exists
		$externalId = Utils::getValidFieldString('externalId');
		if ($this->globalOperations->isValidPageExternalId($externalId)
				|| $this->isValidSubmenuExternalId($parent, $externalId)) {
			$this->state = false;
			$this->message = 'MENU_ITEM_EXTERNAL_ID_EXISTS';
			return;
		}

		$result = $this->menuItemOperations->addMenuItem(
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

	private function getMenuItem($menu, $miid) {
		foreach ($menu as $item) {
			// search in items
			if ($item['miid'] === $miid) {
				return $item;
			}
			// search in subitems
			else {
				$result = $this->getMenuItem($item['submenu'], $miid);
				if ($result !== null) {
					return $result;
				}
			}
		}
		// not found
		return null;
	}

	private function isValidSubmenuExternalId($parent, $externalId) {
		if ($parent === null) {
			$siblings = $this->menu;
		}
		else {
			$siblings = $parent['submenu'];
		}
		foreach ($siblings as $item) {
			if ($item['externalId'] === $externalId) {
				return true;
			}
		}
		return false;
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

	private function loadMenu() {
		$menu = $this->menuItemOperations->getParentMenuItems();
		if ($menu === false) {
			$this->state = false;
			$this->message = 'UNKNOWN_ERROR';
			return;
		}
		$result = $this->loadSubmenuForEachItem($menu);
		if ($result === false) {
			$this->state = false;
			$this->message = 'UNKNOWN_ERROR';
			return;
		}
		$this->menu = $menu;
	}

	private function loadSubmenuForEachItem(&$menu) {
		foreach ($menu as &$item) {
			$submenu = $this->menuItemOperations->getSubmenuItems($item['miid']);
			if ($submenu === false) {
				$this->state = false;
				$this->message = 'UNKNOWN_ERROR';
				return false;
			}
			else if (count($submenu) === 0) {
				$item['submenu'] = [];
			}
			else {
				$item['submenu'] = $submenu;
				$result = $this->addSubMenuForEachItem($item['submenu']);
				if ($result === false) {
					return false;
				}
			}
		}
		return true;
	}
}

?>