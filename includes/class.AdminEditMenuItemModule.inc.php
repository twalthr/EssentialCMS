<?php

// v1: FEATURE COMPLETE

class AdminEditMenuItemModule extends BasicModule {

	// database operations
	private $menuItemOperations;
	private $globalOperations;

	// UI state
	private $state;
	private $message;

	// member variables
	private $config; // config for page redirect after menu item creation
	private $menu; // complete menu for position selection
	private $menuItem; // current menu item

	public function __construct($config, $menuItemOperations, $globalOperations, $parameters = null) {
		parent::__construct(1, 'admin-edit-menu-item');
		$this->config = $config;
		$this->menuItemOperations = $menuItemOperations;
		$this->globalOperations = $globalOperations;

		// menu item id is present
		if (isset($parameters) && count($parameters) > 0) {
			$this->loadMenuItem($parameters[0]);
		}
		else {
			$this->loadMenu();
		}

		// handle new menu item
		if (!isset($this->menuItem) && isset($this->menu) && Utils::hasFields()) {
			$this->handleNewMenuItem();
		}
		// handle edit menu item
		else if (isset($this->menuItem)
				&& Utils::getUnmodifiedStringOrEmpty('operationSpace') === 'menuItem') {
			$this->handleEditMenuItem();
			// refresh
			$this->loadMenuItem($this->menuItem['miid']);
		}

		// show success message for newly created menu item
		if (!isset($this->state) && count($parameters) > 1 && $parameters[1] === '.success') {
			$this->state = true;
			$this->message = 'MENU_ITEM_CREATED';
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
				<?php if (!isset($this->menuItem)) : ?>
					$('#position').change(function() {
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
					$('#position').trigger('change');
				<?php endif; ?>
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
					<?php if (!isset($this->menuItem)) : ?>
						<div class="field">
							<input type="hidden" id="insertion" name="insertion" value="at" />
							<label for="position"><?php $this->text('MENU_ITEM_POSITION'); ?></label>
							<?php $this->printMenuItemList(); ?>
						</div>
					<?php endif; ?>
					<div class="field">
						<label><?php $this->text('TARGET'); ?></label>
						<div class="checkboxGroup" id="targets">
							<div class="checkboxWrapper">
								<input type="radio" id="target1" name="target"
									value="no" 
									<?php if (Utils::getUnmodifiedStringOrEmpty('target') === 'no'
										|| (!Utils::isValidField('target')
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
								class="pageSelectionId"
								disabled
								value="<?php 
									echo Utils::getEscapedFieldOrVariable('pageSelectionId',
										$this->menuItem['destPage']); ?>" />
							<input type="text" class="large pageSelectionName" disabled
								value="<?php 
									$pid = Utils::getEscapedFieldOrVariable('pageSelectionId',
										$this->menuItem['destPage']);
									if (Utils::isValidInt($pid)) {
										$title = $this->globalOperations->getPageTitle((int) $pid);
										if ($title === false) {
											$this->text('PAGE_ID_INVALID');
										}
										else {
											echo Utils::escapeString($title);
										}
										echo ' / ' . $pid;
									} ?>" />
							<button class="pageSelectionButton"><?php $this->text('SELECT'); ?></button>
						</span>
					</div>
					<div class="field hidden" id="targetWebsite">
						<label for="website"><?php $this->text('MENU_ITEM_WEBSITE'); ?></label>
						<input type="text" name="website" id="website" class="large"
							required maxlength="1024" disabled
							value="<?php 
									echo Utils::getEscapedFieldOrVariable('website',
										$this->menuItem['destLink']); ?>" />
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
							<input type="checkbox" id="visibility" name="visibility"
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
		echo '<select name="position" id="position">';
		foreach ($this->menu as $menuItem) {
			$this->printMenuItemWithinList($menuItem, 0);
		}
		echo '<option value="-1" class="insertAtEnd"';
		if (!Utils::isValidField('position')
				|| (Utils::getUnmodifiedStringOrEmpty('position') == -1
					&& Utils::getUnmodifiedStringOrEmpty('insertion') === 'end')) {
			echo ' selected';
		}
		echo '>';
		$this->text('MENU_ITEM_AT_END');
		echo '</option>';
		echo '</select>';
	}

	private function printMenuItemWithinList($menuItem, $level) {
		echo '<option value="' . $menuItem['miid'] . '"';
		if (Utils::hasStringContent($menuItem['hoverTitle'])) {
			echo ' title="' . Utils::escapeString($menuItem['hoverTitle']) . '"';
		}
		if (Utils::getUnmodifiedStringOrEmpty('position') == $menuItem['miid']
				&& Utils::getUnmodifiedStringOrEmpty('insertion') === 'at') {
			echo ' selected';
		}
		echo '>';
		echo str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level);
		echo Utils::escapeString($menuItem['title']);
		echo '</option>';
		if (count($menuItem['submenu']) === 0) {
			echo '<option value="' . $menuItem['miid'] . '" class="insertAsSubmenu"';
			if (Utils::getUnmodifiedStringOrEmpty('parent') == $menuItem['miid']
					&& Utils::getUnmodifiedStringOrEmpty('insertion') === 'submenu') {
				echo ' selected';
			}
			echo '>';
			echo str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level + 1);
			$this->text('MENU_ITEM_AS_SUBMENU');
			echo '</option>';
		}
		foreach ($menuItem['submenu'] as $item) {
			$this->printMenuItemWithinList($item, $level + 1);
		}
		if (count($menuItem['submenu']) > 0) {
			echo '<option value="' . $menuItem['miid'] . '" class="insertAtEnd"';
			if (Utils::getUnmodifiedStringOrEmpty('position') == $menuItem['miid']
					&& Utils::getUnmodifiedStringOrEmpty('insertion') === 'end') {
				echo ' selected';
			}
			echo '>';
			echo str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level + 1);
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
		if (!Utils::isValidFieldIdentifier('externalId', 256)) {
			$this->state = false;
			$this->message = 'INVALID_MENU_ITEM_EXTERNAL_ID';
			return false;
		}
		// check that if menu item will be created
		if (!isset($this->menuItem)) {
			if (!Utils::isValidFieldInt('position')) {
				$this->state = false;
				$this->message = 'INVALID_MENU_ITEM_POSITION';
				return false;
			}
			$insertion = Utils::getUnmodifiedStringOrEmpty('insertion');
			if ($insertion !== 'end' && $insertion !== 'submenu' && $insertion !== 'at') {
				$this->state = false;
				$this->message = 'INVALID_MENU_ITEM_POSITION';
				return false;
			}
		}
		$target = Utils::getUnmodifiedStringOrEmpty('target');
		if ($target !== 'no' && $target !== 'page' && $target !== 'link') {
			$this->state = false;
			$this->message = 'INVALID_MENU_ITEM_TARGET';
			return false;
		}
		if (($target === 'page' && !Utils::isValidFieldInt('pageSelectionId'))
				|| ($target === 'link' && !Utils::isValidFieldLink('website', 1024))) {
			$this->state = false;
			$this->message = 'INVALID_MENU_ITEM_TARGET';
			return false;
		}
		if (!Utils::isValidFieldNoLinebreak('hoverTitle', 256)) {
			$this->state = false;
			$this->message = 'INVALID_HOVER_TITLE';
			return false;
		}
		return true;
	}

	private function handleNewMenuItem() {
		if ($this->checkFields() === false) {
			return;
		}

		// determine the position
		$position = $this->getMenuItem($this->menu, (int) Utils::getValidFieldString('position'));

		$insertion = Utils::getUnmodifiedStringOrEmpty('insertion');
		$externalId = Utils::getValidFieldString('externalId');
		// check externalId at same level
		if ($insertion === 'end' || $insertion === 'at') {
			// check id of this level
			// if toplevel -> check also page ids
			if ((!isset($position) && $this->menuItemOperations->isValidSiblingExternalId(null, $externalId))
					|| (isset($position) && $this->menuItemOperations->isValidSiblingExternalId(
						$position['miid'], $externalId))
					|| (!isset($position) && $this->globalOperations->isValidPageExternalId($externalId))) {
				$this->state = false;
				$this->message = 'MENU_ITEM_EXTERNAL_ID_EXISTS';
				return;
			}
		}
		// $insertion === 'submenu'
		// check externalId at sublevel
		// position must not be null
		else {
			if (!isset($position) || $this->isValidSubmenuExternalId($position, $externalId)) {
				$this->state = false;
				$this->message = 'MENU_ITEM_EXTERNAL_ID_EXISTS';
				return;
			}
		}

		$options = 0;
		if (Utils::isChecked('deactivated')) {
			$options = Utils::setFlag($options, MenuItemOperations::MENU_ITEMS_OPTION_PRIVATE);
		}
		if (Utils::isChecked('window')) {
			$options = Utils::setFlag($options, MenuItemOperations::MENU_ITEMS_OPTION_BLANK);
		}
		if (Utils::isChecked('visibility')) {
			$options = Utils::setFlag($options, MenuItemOperations::MENU_ITEMS_OPTION_HIDDEN);
		}

		// add menu item
		$result = false;
		$newMenuItemId = null;
		$target = Utils::getUnmodifiedStringOrEmpty('target');
		// add at the end (position can be null if top-level end)
		if ($insertion === 'end') {
			$newMenuItemId = $this->menuItemOperations->addMenuItemAtEnd(
				$position['miid'],
				Utils::getValidFieldString('title'),
				Utils::getValidFieldStringOrNull('hoverTitle'),
				Utils::getValidFieldString('externalId'),
				($target === 'page') ? Utils::getUnmodifiedStringOrEmpty('pageSelectionId') : null,
				($target === 'link') ? Utils::getUnmodifiedStringOrEmpty('website') : null,
				$options);
			if ($newMenuItemId !== false) {
				$result = true;
			}
		}
		// add at position (position must be present)
		else if ($insertion === 'at' && isset($position)) {
			$newMenuItemId = $this->menuItemOperations->addMenuItemAt(
				$position['miid'],
				Utils::getValidFieldString('title'),
				Utils::getValidFieldStringOrNull('hoverTitle'),
				Utils::getValidFieldString('externalId'),
				($target === 'page') ? Utils::getUnmodifiedStringOrEmpty('pageSelectionId') : null,
				($target === 'link') ? Utils::getUnmodifiedStringOrEmpty('website') : null,
				$options);
			if ($newMenuItemId !== false) {
				$result = true;
			}
		}
		// add as submenu at parent position (position must be present and submenu empty)
		else if ($insertion === 'submenu' && isset($position) && count($position['submenu']) === 0) {
			$newMenuItemId = $this->menuItemOperations->addMenuItemSubmenu(
				$position['miid'],
				Utils::getValidFieldString('title'),
				Utils::getValidFieldStringOrNull('hoverTitle'),
				Utils::getValidFieldString('externalId'),
				($target === 'page') ? Utils::getUnmodifiedStringOrEmpty('pageSelectionId') : null,
				($target === 'link') ? Utils::getUnmodifiedStringOrEmpty('website') : null,
				$options);
			if ($newMenuItemId !== false) {
				$result = true;
			}
		}

		if ($result == false) {
			$this->state = false;
			$this->message = 'UNKNOWN_ERROR';
			return;
		}
		$this->state = true;
		$this->message = 'MENU_ITEM_CHANGED';

		// redirect
		if (isset($newMenuItemId)) {
			Utils::redirect($this->config->getPublicRoot() . '/admin/menu-item/' .
				$newMenuItemId . '/.success');
		}
	}

	private function handleEditMenuItem() {
		if ($this->checkFields() === false) {
			return;
		}

		$updateColumns = [];
		// check for updated title
		$title = Utils::getValidFieldString('title');
		if ($title !== $this->menuItem['title']) {
			$updateColumns['title'] = $title;
		}

		// check for updated externalId
		$externalId = Utils::getUnmodifiedStringOrEmpty('externalId');
		$parent = $this->menuItem['parent'];
		if ($externalId !== $this->menuItem['externalId']) {
			if ((!isset($parent) && $this->globalOperations->isValidPageExternalId($externalId))
					|| $this->menuItemOperations->isValidExternalId($parent, $externalId)) {
				$this->state = false;
				$this->message = 'MENU_ITEM_EXTERNAL_ID_EXISTS';
				return;
			}
			$updateColumns['externalId'] = $externalId;
		}

		// check for updated target
		$target = Utils::getUnmodifiedStringOrEmpty('target');
		$pageSelectionId = Utils::getUnmodifiedStringOrEmpty('pageSelectionId');
		$website = Utils::getUnmodifiedStringOrEmpty('website');
		// no target
		if ($target === 'no' && (isset($this->menuItem['destPage']) || isset($this->menuItem['destLink']))) {
			$updateColumns['destPage'] = null;
			$updateColumns['destLink'] = null;
		}
		// page target
		else if ($target === 'page'
				&& $this->menuItem['destPage'] !== $pageSelectionId) {
			$updateColumns['destPage'] = $pageSelectionId;
			$updateColumns['destLink'] = null;
		}
		// link target
		else if ($target === 'link'
				&& $this->menuItem['destLink'] !== $website) {
			$updateColumns['destPage'] = null;
			$updateColumns['destLink'] = $website;
		}

		// check for updated hoverTitle
		$hoverTitle = Utils::getValidFieldStringOrNull('hoverTitle');
		if ($hoverTitle !== $this->menuItem['hoverTitle']) {
			$updateColumns['hoverTitle'] = $hoverTitle;
		}

		$options = $this->menuItem['options'];
		// check for updated visibility
		if (Utils::isChecked('visibility')
				&& !Utils::isFlagged($options, MenuItemOperations::MENU_ITEMS_OPTION_HIDDEN)) {
			$options = Utils::setFlag($options, MenuItemOperations::MENU_ITEMS_OPTION_HIDDEN);
		}
		else if (!Utils::isChecked('visibility')
				&& Utils::isFlagged($options, MenuItemOperations::MENU_ITEMS_OPTION_HIDDEN)) {
			$options = Utils::unsetFlag($options, MenuItemOperations::MENU_ITEMS_OPTION_HIDDEN);
		}
		// check for updated deactivated
		if (Utils::isChecked('deactivated')
				&& !Utils::isFlagged($options, MenuItemOperations::MENU_ITEMS_OPTION_PRIVATE)) {
			$options = Utils::setFlag($options, MenuItemOperations::MENU_ITEMS_OPTION_PRIVATE);
		}
		else if (!Utils::isChecked('deactivated')
				&& Utils::isFlagged($options, MenuItemOperations::MENU_ITEMS_OPTION_PRIVATE)) {
			$options = Utils::unsetFlag($options, MenuItemOperations::MENU_ITEMS_OPTION_PRIVATE);
		}
		// check for updated window
		if (Utils::isChecked('window')
				&& !Utils::isFlagged($options, MenuItemOperations::MENU_ITEMS_OPTION_BLANK)) {
			$options = Utils::setFlag($options, MenuItemOperations::MENU_ITEMS_OPTION_BLANK);
		}
		else if (!Utils::isChecked('window')
				&& Utils::isFlagged($options, MenuItemOperations::MENU_ITEMS_OPTION_BLANK)) {
			$options = Utils::unsetFlag($options, MenuItemOperations::MENU_ITEMS_OPTION_BLANK);
		}

		// update options
		if ($this->menuItem['options'] !== $options) {
			$updateColumns['options'] = $options;
		}

		// perform update
		$result = $this->menuItemOperations->updateMenuItem($this->menuItem['miid'], $updateColumns);

		if ($result == false) {
			$this->state = false;
			$this->message = 'UNKNOWN_ERROR';
			return;
		}
		$this->state = true;
		$this->message = 'MENU_ITEM_CHANGED';
	}

	// --------------------------------------------------------------------------------------------
	// Helper methods
	// --------------------------------------------------------------------------------------------

	private function getMenuItem($menu, $miid) {
		foreach ($menu as $item) {
			// search in items
			if ($item['miid'] === $miid) {
				return $item;
			}
			// search in subitems
			else {
				$result = $this->getMenuItem($item['submenu'], $miid);
				if (isset($result)) {
					return $result;
				}
			}
		}
		// not found
		return null;
	}

	private function isValidSubmenuExternalId($parent, $externalId) {
		$siblings = $parent['submenu'];
		foreach ($siblings as $item) {
			if ($item['externalId'] === $externalId) {
				return true;
			}
		}
		return false;
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
				$result = $this->loadSubmenuForEachItem($item['submenu']);
				if ($result === false) {
					return false;
				}
			}
		}
		return true;
	}
}

?>