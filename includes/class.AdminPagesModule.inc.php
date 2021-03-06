<?php

// v1: FEATURE COMPLETE

class AdminPagesModule extends BasicModule {

	// database operations
	private $pageOperations;
	private $menuItemOperations;

	// UI state
	private $state;
	private $message;

	// member variables
	private $menu;
	private $pages;

	public function __construct($pageOperations, $menuItemOperations) {
		parent::__construct(1, 'admin-pages');
		$this->pageOperations = $pageOperations;
		$this->menuItemOperations = $menuItemOperations;

		// load menu items
		$this->loadMenu();
		if (!isset($this->menu)) {
			return;
		}

		// load pages
		$this->loadPages();
		if (!isset($this->pages)) {
			return;
		}

		// handle menu operations
		if (Utils::getUnmodifiedStringOrEmpty('operationSpace') === 'menuItem') {
			$this->handleMenuItemOperations();
			// reload menu items
			$this->loadMenu();
		}
		// handle page operations
		else if (Utils::getUnmodifiedStringOrEmpty('operationSpace') === 'page') {
			$this->handlePageOperations();
			// reload pages
			$this->loadPages();
		}
	}

	public function printContent($config) {
		?>
		<script type="text/javascript">
			$(document).ready(function(){
				$('#menuItemPublic').click(function() {
					$('#menuItemOperation').val('public');
					$('#menuOperations').submit();
				});
				$('#menuItemPrivate').click(function() {
					$('#menuItemOperation').val('private');
					$('#menuOperations').submit();
				});
				$('#menuItemMove').click(function() {
					$('#menuItemOperation').val('move');
					openButtonSetDialog($(this),
						'<?php $this->text('SELECT_MOVE_TARGET'); ?>',
						'#menuItemTarget, #menuItemAt, #menuItemInto');
				});
				$('#menuItemCopy').click(function() {
					$('#menuItemOperation').val('copy');
					openButtonSetDialog($(this),
						'<?php $this->text('SELECT_COPY_TARGET'); ?>',
						'#menuItemTarget, #menuItemAt, #menuItemInto');
				});
				$('#menuItemAt').click(function() {
					$('#menuItemOperationTarget').val('at');
					enableList($(this));
					$('#menuOperations').submit();
				});
				$('#menuItemInto').click(function() {
					$('#menuItemOperationTarget').val('into');
					enableList($(this));
					$('#menuOperations').submit();
				});
				$('#menuItemDelete').click(function() {
					$('#menuItemOperation').val('delete');
					openButtonSetDialog($(this),
						'<?php $this->text('DELETE_QUESTION'); ?>',
						'#menuItemDeleteConfirm');
				});
				$('#menuItemDeleteConfirm').click(function() {
					enableList($(this));
					$('#menuOperations').submit();
				});
				$('#menuItemNew').click(function() {
					window.open('<?php echo $config->getPublicRoot(); ?>/admin/new-menu-item', '_self');
				});
				$('#pagePublic').click(function() {
					$('#pageOperation').val('public');
					$('#pageOperations').submit();
				});
				$('#pagePrivate').click(function() {
					$('#pageOperation').val('private');
					$('#pageOperations').submit();
				});
				$('#pageCopy').click(function() {
					$('#pageOperation').val('copy');
					$('#pageOperations').submit();
				});
				$('#pageDelete').click(function() {
					$('#pageOperation').val('delete');
					openButtonSetDialog($(this),
						'<?php $this->text('DELETE_QUESTION'); ?>',
						'#pageDeleteConfirm');
				});
				$('#pageDeleteConfirm').click(function() {
					enableList($(this));
					$('#pageOperations').submit();
				});
				$('#pageNew').click(function() {
					window.open('<?php echo $config->getPublicRoot(); ?>/admin/new-page', '_self');
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
		<section>
			<h1><?php $this->text('MENU'); ?></h1>
			<form method="post" action="<?php echo $config->getPublicRoot(); ?>/admin/pages" id="menuOperations">
				<button id="menuItemNew" class="addButton"><?php $this->text('NEW_MENU_ITEM'); ?></button>
				<input type="hidden" name="operationSpace" value="menuItem" />
				<input type="hidden" name="operation" id="menuItemOperation" />
				<input type="hidden" name="operationTarget" id="menuItemOperationTarget" />
				<div class="siteMap enableButtonsIfChecked">
					<?php $this->printMenu($this->menu, $config, true); ?>
				</div>
				<div class="buttonSet">
					<button id="menuItemPublic" disabled><?php $this->text('MAKE_PUBLIC'); ?></button>
					<button id="menuItemPrivate" disabled><?php $this->text('MAKE_PRIVATE'); ?></button>
					<button id="menuItemMove" class="disableListIfClicked" disabled>
						<?php $this->text('MOVE'); ?></button>
					<button id="menuItemCopy" class="disableListIfClicked" disabled>
						<?php $this->text('COPY'); ?></button>
					<button id="menuItemDelete" class="disableListIfClicked" disabled>
						<?php $this->text('DELETE'); ?></button>
				</div>
				<div class="dialog-box hidden">
					<div class="dialog-message"></div>
					<div class="fields">
						<select name="target" class="hidden" id="menuItemTarget">
							<?php $this->printSelect($this->menu, 0); ?>
						</select>
					</div>
					<div class="options">
						<button id="menuItemAt" class="hidden">
							<?php $this->text('MENU_ITEM_AT'); ?></button>
						<button id="menuItemInto" class="hidden">
							<?php $this->text('MENU_ITEM_INTO'); ?></button>
						<button id="menuItemDeleteConfirm" class="hidden">
							<?php $this->text('DELETE'); ?></button>
						<button class="hidden cancel">
							<?php $this->text('CANCEL'); ?></button>
					</div>
				</div>
			</form>
		</section>
		<section>
			<h1><?php $this->text('PAGES'); ?></h1>
			<form method="post" action="<?php echo $config->getPublicRoot(); ?>/admin/pages"
					id="pageOperations">
				<button id="pageNew" class="addButton"><?php $this->text('NEW_PAGE'); ?></button>
				<input type="hidden" name="operationSpace" value="page" />
				<input type="hidden" name="operation" id="pageOperation" />
				<?php $this->printPages($config); ?>
				<div class="buttonSet">
					<button id="pagePublic" disabled><?php $this->text('MAKE_PUBLIC'); ?></button>
					<button id="pagePrivate" disabled><?php $this->text('MAKE_PRIVATE'); ?></button>
					<button id="pageCopy" class="disableListIfClicked" disabled>
						<?php $this->text('COPY'); ?></button>
					<button id="pageDelete"  class="disableListIfClicked" disabled>
						<?php $this->text('DELETE'); ?></button>
				</div>
				<div class="dialog-box hidden">
					<div class="dialog-message"></div>
					<div class="options">
						<button id="pageDeleteConfirm" class="hidden"><?php $this->text('DELETE'); ?></button>
						<button class="hidden cancel"><?php $this->text('CANCEL'); ?></button>
					</div>
				</div>
			</form>
		</section>
		<?php
	}

	// --------------------------------------------------------------------------------------------
	// Printing methods
	// --------------------------------------------------------------------------------------------

	private function printMenu($menu, $config, $topLevel) {
		if ($topLevel && ($menu === false || count($menu) === 0)) {
			echo '<p class="empty">';
			echo $this->text('NO_MENU_ITEMS');
			echo '</p>';
			return;
		}

		if ($topLevel) {
			echo '<ul class="topLevel">';
		}
		else {
			echo '<ul class="subLevel">';
		}
		foreach ($menu as $item) {
			echo '<li>';
			echo '<input type="checkbox" id="menuitem' . $item['miid'] . '" name="menuitem[]"';
			echo ' value="' . $item['miid'] . '" class="propagateChecked" />';
			echo '<label for="menuitem' . $item['miid'] . '" class="checkbox">';
			echo Utils::escapeString($item['title']) .' </label>';
			echo '<a href="' . $config->getPublicRoot() . '/admin/menu-item/' . $item['miid'] . '"';
			if (Utils::hasStringContent($item['hoverTitle'])) {
				echo ' title="' . Utils::escapeString($item['hoverTitle']) . '"';
			}
			if (Utils::isFlagged($item['options'], MenuItemOperations::MENU_ITEMS_OPTION_PRIVATE)) {
				echo ' class="private componentLink"';
			}
			else {
				echo ' class="componentLink"';
			}
			echo '>' . Utils::escapeString($item['title']) . '</a>';
			if ($item['submenu'] !== false) {
				$this->printMenu($item['submenu'], $config, false);
			}
		}
		echo '</ul>';
	}

	private function printSelect($menu, $level) {
		foreach ($menu as $item) {
			echo '<option value="' . $item['miid']. '">';
			echo str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level);
			echo Utils::escapeString($item['title']) . '</option>';
			if ($item['submenu'] !== false) {
				$this->printSelect($item['submenu'], $level + 1);
			}
		}
	}

	private function printPages($config) {
		if ($this->pages === false || count($this->pages) === 0) {
			echo '<p class="empty">';
			echo $this->text('NO_PAGES');
			echo '</p>';
			return;
		}
		echo '<ul class="tableLike enableButtonsIfChecked">';
		foreach ($this->pages as $page) {
			echo '<li class="rowLike">';
			echo '<input type="checkbox" id="page' . $page['pid'] . '" name="page[]"';
			echo ' value="' . $page['pid'] . '" />';
			echo '<label for="page' . $page['pid'] . '" class="checkbox">';
			echo Utils::escapeString($page['title']) .' </label>';
			echo '<a href="' . $config->getPublicRoot() . '/admin/page/' . $page['pid'] . '"';
			if (Utils::hasStringContent($page['hoverTitle'])) {
				echo ' title="' . Utils::escapeString($page['hoverTitle']) . '"';
			}
			if (Utils::isFlagged($page['options'], PageOperations::PAGES_OPTION_PRIVATE)) {
				echo ' class="private componentLink"';
			}
			else {
				echo ' class="componentLink"';
			}
			echo '>' . Utils::escapeString($page['title']) . '</a>';
			if (Utils::hasStringContent($page['externalId'])) {
				echo '<span class="rowAdditionalInfo">';
				echo Utils::escapeString($page['externalId']);
				echo '</span>';
			}
			echo '</li>';
		}
		echo '</ul>';
	}

	// --------------------------------------------------------------------------------------------
	// User input handling methods
	// --------------------------------------------------------------------------------------------

	private function handleMenuItemOperations() {
		$operation = Utils::getUnmodifiedStringOrEmpty('operation');

		// check for menu items
		if (!Utils::isValidFieldIntArray('menuitem')) {
			return;
		}

		// normalize menu items
		$uniqueMenuitems = array_unique(Utils::getValidFieldArray('menuitem'));
		$alreadyProcessed = [];

		// foreach menu item
		$result = true;
		foreach ($uniqueMenuitems as $menuItem) {
			$menuItem = (int) $menuItem;
			// skip item if already processed
			if (in_array($menuItem, $alreadyProcessed)) {
				continue;
			}
			// check for existence of menu item
			$currentMenuItem = $this->getMenuItem($this->menu, $menuItem);
			if (!isset($currentMenuItem)) {
				continue;
			}

			// collect submenu items
			$subitems = [];
			$this->collectSubmenuItems($currentMenuItem, $subitems);
			$alreadyProcessed = array_merge($alreadyProcessed, $subitems);

			// check target parameters
			if ($operation === 'move' || $operation === 'copy') {
				$operationTarget = Utils::getUnmodifiedStringOrEmpty('operationTarget');
				if ($operationTarget !== 'at' && $operationTarget !== 'into') {
					continue;
				}
				if (!Utils::isValidFieldInt('target')) {
					continue;
				}
				// check for existence of target menu item
				$target = $this->getMenuItem($this->menu,
					(int) Utils::getUnmodifiedStringOrEmpty('target'));
				if (!isset($target)) {
					continue;
				}
				// check for recursive move operation
				if ($operation === 'move' && in_array($target['miid'], $alreadyProcessed)) {
					$this->state = false;
					$this->message = 'MENU_ITEMS_NO_RECURSIVE_MOVE';
					return;
				}
			}

			foreach ($subitems as $subitem) {
				// do operation
				switch ($operation) {
					case 'delete':
						$result = $result && $this->menuItemOperations->deleteMenuItem($subitem);
						break;
					case 'public':
						$result = $result && $this->menuItemOperations->makeMenuItemPublic($subitem);
						break;
					case 'private':
						$result = $result && $this->menuItemOperations->makeMenuItemPrivate($subitem);
						break;
					case 'move':
						// copy
						if ($operationTarget === 'at') {
							$result = $result && $this->menuItemOperations->copyMenuItemAt($target['miid'], $subitem);
						}
						else {
							$result = $result && $this->menuItemOperations->copyMenuItemSubmenu($target['miid'], $subitem);
						}
						// delete
						$result = $result && $this->menuItemOperations->deleteMenuItem($subitem);
					case 'copy':
						if ($operationTarget === 'at') {
							$result = $result && $this->menuItemOperations->copyMenuItemAt($target['miid'], $subitem);
						}
						else {
							$result = $result && $this->menuItemOperations->copyMenuItemSubmenu($target['miid'], $subitem);
						}
						break;
					default:
						$result = false;
					break;
				}
			}
		}

		// set state
		$this->state = $result;
		if ($result === true) {
			switch ($operation) {
				case 'delete':
					$this->message = 'MENU_ITEMS_DELETED';
					break;
				case 'public':
				case 'private':
					$this->message = 'MENU_ITEMS_VISIBILITY_CHANGED';
					break;
				case 'move':
				case 'copy':
					$this->message = 'MENU_ITEMS_COPY_MOVE_SUCCESSFUL';
					break;
			}
		}
		else {
			$this->message = 'UNKNOWN_ERROR';
		}
	}

	private function handlePageOperations() {
		$operation = Utils::getUnmodifiedStringOrEmpty('operation');

		// check for pages
		if (!Utils::isValidFieldIntArray('page')) {
			return;
		}

		// normalize pages
		$uniquePageIds = array_unique(Utils::getValidFieldArray('page'));

		// foreach page
		$result = true;
		foreach ($uniquePageIds as $pageId) {
			// check if page exists
			$page = Utils::getColumnWithValue($this->pages, 'pid', (int) $pageId);
			if ($page === false) {
				continue;
			}

			// do operation
			switch ($operation) {
				case 'delete':
					$result = $result && $this->pageOperations->deletePage($page['pid']);
					break;
				case 'public':
					$result = $result && $this->pageOperations->makePagePublic($page['pid']);
					break;
				case 'private':
					$result = $result && $this->pageOperations->makePagePrivate($page['pid']);
					break;
				case 'copy':
					$result = $result && $this->pageOperations->copyPage($page['pid']);
					break;
				default:
					$result = false;
				break;
			}
		}

		// set state
		$this->state = $result;
		if ($result === true) {
			switch ($operation) {
				case 'delete':
					$this->message = 'PAGES_DELETED';
					break;
				case 'public':
				case 'private':
					$this->message = 'PAGES_VISIBILITY_CHANGED';
					break;
				case 'move':
				case 'copy':
					$this->message = 'PAGES_COPIED';
					break;
			}
		}
		else {
			$this->message = 'UNKNOWN_ERROR';
		}
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

	private function collectSubmenuItems($menuItem, &$collection) {
		$collection[] = $menuItem['miid'];
		foreach ($menuItem['submenu'] as $submenuItem) {
			$this->collectSubmenuItems($submenuItem, $collection);
		}
	}

	// --------------------------------------------------------------------------------------------
	// Database loading methods
	// --------------------------------------------------------------------------------------------

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

	private function loadPages() {
		$pages = $this->pageOperations->getPages();
		if ($pages === false) {
			$this->state = false;
			$this->message = 'UNKNOWN_ERROR';
		}
		$this->pages = $pages;
	}
}

?>