<?php

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
		$this->loadMenuItems();
		if (!isset($this->menu)) {
			return;
		}

		// load pages
		$this->loadPages();
		if (!isset($this->pages)) {
			return;
		}

		// handle menu operations
		if (Utils::getUnmodifiedStringOrEmpty('operationSpace') === 'menu') {
			$this->handleMenuOperations();
			// reload menu items
			$this->loadMenuItems();
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
				$('#pageDelete').click(function() {
					$('#pageOperation').val('delete');
				});
				$('#pageDeleteConfirm').click(function() {
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
			<form method="post" action="<?php echo $config->getPublicRoot()?>/admin/pages" id="menuOperations">
				<button id="menuItemNew" class="addButton"><?php $this->text('NEW_MENU_ITEM'); ?></button>
				<input type="hidden" name="operationSpace" value="menu" />
				<input type="hidden" name="operation" id="menuItemOperation" />
				<input type="hidden" name="operationTarget" id="menuItemOperationTarget" />
				<div class="siteMap enableButtonsIfChecked">
					<?php $this->printMenu($this->menu, $config, true); ?>
				</div>
				<div class="buttonSet">
					<button id="menuItemPublic" disabled><?php $this->text('MAKE_PUBLIC'); ?></button>
					<button id="menuItemPrivate" disabled><?php $this->text('MAKE_PRIVATE'); ?></button>
					<button id="menuItemMove" class="disableListIfClicked" disabled><?php $this->text('MOVE'); ?></button>
					<button id="menuItemCopy" class="disableListIfClicked" disabled><?php $this->text('COPY'); ?></button>
					<button id="menuItemDelete" class="disableListIfClicked" disabled><?php $this->text('DELETE'); ?></button>
				</div>
				<div class="dialog-box hidden">
					<div class="dialog-message"></div>
					<div class="fields">
						<select name="target" class="hidden" id="menuItemTarget">
							<?php $this->printSelect($this->menu, 0); ?>
						</select>
					</div>
					<div class="options">
						<button id="menuItemAt" class="hidden"><?php $this->text('MENU_ITEM_AT'); ?></button>
						<button id="menuItemInto" class="hidden"><?php $this->text('MENU_ITEM_INTO'); ?></button>
						<button id="menuItemDeleteConfirm" class="hidden"><?php $this->text('DELETE'); ?></button>
						<button class="hidden cancel"><?php $this->text('CANCEL'); ?></button>
					</div>
				</div>
			</form>
		</section>
		<section>
			<h1><?php $this->text('PAGES'); ?></h1>
			<form method="post" action="<?php echo $config->getPublicRoot()?>/admin/pages" id="pageOperations">
				<button id="pageNew" class="addButton"><?php $this->text('NEW_PAGE'); ?></button>
				<input type="hidden" name="operationSpace" value="page" />
				<input type="hidden" name="operation" id="pageOperation" />
				<?php $this->printPages($config); ?>
				<div class="buttonSet">
					<button id="pagePublic" disabled><?php $this->text('MAKE_PUBLIC'); ?></button>
					<button id="pagePrivate" disabled><?php $this->text('MAKE_PRIVATE'); ?></button>
					<button id="page-copy" class="disableListIfClicked" disabled><?php $this->text('COPY'); ?></button>
					<button id="pageDelete"  class="disableListIfClicked" disabled><?php $this->text('DELETE'); ?></button>
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

	private function printMenu(&$menu, &$config, $topLevel) {
		if ($topLevel && ($menu === false || empty($menu))) {
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
		foreach ($menu as &$item) {
			echo '<li>';
			echo '<input type="checkbox" id="menuitem' . $item['mpid'] . '" name="menuitem[]"';
			echo ' value="' . $item['mpid'] . '" class="propagateChecked" />';
			echo '<label for="menuitem' . $item['mpid'] . '" class="checkbox">';
			echo Utils::escapeString($item['title']) .' </label>';
			echo '<a href="' . $config->getPublicRoot() . '/admin/menu-item/' . $item['mpid'] . '"';
			if (Utils::hasStringContent($item['hoverTitle'])) {
				echo ' title="' . Utils::escapeString($item['hoverTitle']) . '"';
			}
			if ($item['options'] & MENUPATHS_OPTION_PRIVATE) {
				echo ' class="private componentLink"';
			}
			else {
				echo ' class="componentLink"';
			}
			echo '>' . Utils::escapeString($item['title']) . '</a>';
			if ($item['subMenu'] !== false) {
				$this->printMenu($item['subMenu'], $config, false);
			}
		}
		echo '</ul>';
	}

	private function printSelect(&$menu, $level) {
		foreach ($menu as &$item) {
			echo '<option value="' . $item['mpid']. '">';
			echo str_repeat('&nbsp;', $level);
			echo Utils::escapeString($item['title']) . '</option>';
			if ($item['subMenu'] !== false) {
				$this->printSelect($item['subMenu'], $level + 1);
			}
		}
	}

	private function printPages(&$config) {
		if ($this->pages === false) {
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
			if ($page['options'] & PAGES_OPTION_PRIVATE) {
				echo ' class="private componentLink"';
			}
			else {
				echo ' class="componentLink"';
			}
			echo '>' . Utils::escapeString($page['title']) . '</a>';
			if (Utils::hasStringContent($page['externalId'])) {
				echo '<span class="rowAdditionalInfo">';
				echo Utils::escapeString($page['hoverTitle']);
				echo '</span>';
			}
			echo '</li>';
		}
		echo '</ul>';
	}

	// --------------------------------------------------------------------------------------------
	// User input handling methods
	// --------------------------------------------------------------------------------------------

	private function handlePageOperations() {
		global $DB;
		if (!Utils::isValidFieldNotEmpty('operation')
			|| !Utils::isValidFieldArrayWithContent('page')) {
			return;
		}

		// normalize pages
		$uniquePages = array_unique(Utils::getValidFieldArray('page'));
		// check for existence of all pages
		foreach ($uniquePages as $page) {
			if(!$DB->resultQuery('SELECT `pid` FROM `Pages` WHERE `pid`=?', 'i', $page)) {
				return;
			}
		}

		// execute operation
		$operation = Utils::getValidFieldString('operation');
		switch ($operation) {
			case 'public':
				$result = true;
				foreach ($uniquePages as $page) {
					$result &= $DB->impactQuery('
						UPDATE `Pages`
						SET `options` = `options` & ~' . PAGES_OPTION_PRIVATE . '
						WHERE `pid`=?', 'i', $page);
				}

				if ($result) {
					$this->state = true;
					$this->message = 'PAGES_VISIBILITY_CHANGED';
				}
				break;
			case 'private':
				$result = true;
				foreach ($uniquePages as $page) {
					$result &= $DB->impactQuery('
						UPDATE `Pages`
						SET `options` = `options` | ' . PAGES_OPTION_PRIVATE . '
						WHERE `pid`=?', 'i', $page);
				}

				if ($result) {
					$this->state = true;
					$this->message = 'PAGES_VISIBILITY_CHANGED';
				}
				break;
		}
	}

	private function handleMenuOperations() {
		global $DB;
		if (!Utils::isValidFieldNotEmpty('operation')
			|| !Utils::isValidFieldArrayWithContent('menuitem')) {
			return;
		}

		// normalize menuitems
		$uniqueMenuitems = array_unique(Utils::getValidFieldArray('menuitem'));
		// check for existence of all menuitems
		foreach ($uniqueMenuitems as $menuitem) {
			if(!$DB->resultQuery('SELECT `mpid` FROM `MenuPaths` WHERE `mpid`=?', 'i', $menuitem)) {
				return;
			}
		}

		// execute operation
		$operation = Utils::getValidFieldString('operation');
		switch ($operation) {
			case 'public':
				$result = true;
				foreach ($uniqueMenuitems as $menuitem) {
					$result &= $DB->impactQuery('
						UPDATE `MenuPaths`
						SET `options` = `options` & ~' . MENUPATHS_OPTION_PRIVATE . '
						WHERE `mpid`=?', 'i', $menuitem);
				}

				if ($result) {
					$this->state = true;
					$this->message = 'MENU_ITEMS_VISIBILITY_CHANGED';
				}
				break;
			case 'private':
				$result = true;
				foreach ($uniqueMenuitems as $menuitem) {
					$result &= $DB->impactQuery('
						UPDATE `MenuPaths`
						SET `options` = `options` | ' . MENUPATHS_OPTION_PRIVATE . '
						WHERE `mpid`=?', 'i', $menuitem);
				}

				if ($result) {
					$this->state = true;
					$this->message = 'MENU_ITEMS_VISIBILITY_CHANGED';
				}
				break;
			case 'move':
			case 'copy':
				if (!Utils::isValidFieldNotEmpty('operationTarget')
					|| !Utils::isValidFieldNotEmpty('target')) {
					return;
				}
				$operationTarget = Utils::getValidFieldString('operationTarget');

				// normalize target
				$target = $DB->valueQuery('
					SELECT `mpid`, `parent`, `order`
					FROM `MenuPaths`
					WHERE `mpid`=?',
					'i', Utils::getValidFieldString('target'));
				// check for existence of target
				if ($target === false) {
					return;
				}

				// process one item after the other
				$processed = [];
				foreach ($uniqueMenuitems as $menuitem) {
					// skip already processed items
					if (in_array($menuitem, $processed)) {
						continue;
					}

					$group = [];
					$group[] = intval($menuitem);
					$this->addIdsOfSubMenuItems($group, $menuitem);

					// check that target is not part of the group
					if ($operation === 'move') {
						if (in_array($target['mpid'], $group)) {
							$this->state = false;
							$this->message = 'MENU_ITEMS_NO_RECURSIVE_MOVE';
							return;
						}
					}

					// if single element or entire group (parent with children) is selected
					$result = true;
					if (count(array_intersect($group, $uniqueMenuitems)) == count($group)) {
						// copy
						if ($operation === 'copy') {
							$result &= $this->copyGroup($group, $target, $operationTarget);
						}
						// move
						else {
							$result &= $this->moveGroup($group, $target, $operationTarget);
						}
						$processed = array_merge($processed, $group);
					}
					// partial group selected
					else {
						$this->state = false;
						$this->message = 'MENU_ITEMS_NO_MULTILEVEL_MOVE';
						return;
					}
				}
				if ($result) {
					$this->state = true;
					$this->message = 'MENU_ITEMS_COPY_MOVE_SUCCESSFUL';
				}
				break;
			case 'delete':
				$result = true;
				$processed = [];
				// process one item after the other
				foreach ($uniqueMenuitems as $menuitem) {
					// skip already processed items
					if (in_array($menuitem, $processed)) {
						continue;
					}
					$group = [];
					$group[] = intval($menuitem);
					$this->addIdsOfSubMenuItems($group, $menuitem);
					$result &= $this->deleteGroup($group);
				}
				if ($result) {
					$this->state = true;
					$this->message = 'MENU_ITEMS_DELETED';
				}
				break;
		}
	}

	private function moveGroup(&$group, &$target, $mode) {
		$result = true;
		$result &= $this->copyGroup($group, $target, $mode);
		$result &= $this->deleteGroup($group);
		return $result;
	}

	private function deleteGroup(&$group) {
		global $DB;
		$topElement = $DB->valueQuery('
			SELECT `mpid`, `parent`, `order`
			FROM `MenuPaths` WHERE `mpid`=?',
			'i', $group[0]);
		if ($topElement === false) {
			return false;
		}
		
		$result = true;
		// delete the group
		foreach ($group as $mpid) {
			$result &= $DB->impactQuery('DELETE FROM `MenuPaths` WHERE `mpid`=?', 'i', $mpid);
		}
		// refresh the order of neighbours
		if ($topElement['parent'] == null) {
			$DB->impactQuery('
				UPDATE `MenuPaths`
				SET `order` = `order` - 1
				WHERE `parent` IS NULL AND `order`>?', 'i', $topElement['order']);
		}
		else {
			$DB->impactQuery('
				UPDATE `MenuPaths`
				SET `order` = `order` - 1
				WHERE `parent`=? AND `order`>?', 'ii', $topElement['parent'], $topElement['order']);
		}
		return $result;
	}

	private function copyGroup(&$group, &$target, $mode) {
		global $DB;
		// load group
		$topElement = $DB->valueQuery('SELECT * FROM `MenuPaths` WHERE `mpid`=?', 'i', $group[0]);
		if ($topElement === false) {
			return false;
		}
		$menu = [];
		$menu[] = &$topElement;
		$this->addSubMenuForEachItem($menu);

		if ($mode === 'at') {
			// refresh the order of the target neighbours
			if ($target['parent'] === null) {
				$DB->impactQuery('
					UPDATE `MenuPaths`
					SET `order` = `order` + 1
					WHERE `parent` IS NULL AND `order`>=?',
					'i', $target['order']);
			}
			else {
				$DB->impactQuery('
					UPDATE `MenuPaths`
					SET `order` = `order` + 1
					WHERE `parent`=? AND `order`>=?',
					'ii', $target['parent'], $target['order']);
			}

			$newId = $DB->impactQueryWithId('
				INSERT INTO `MenuPaths`
				(`parent`, `order`, `title`, `hoverTitle`, `externalId`, `destPage`, `destLink`, `options`)
				VALUES (?,?,?,?,?,?,?,?)',
				'iisssisi',
				$target['parent'], $target['order'],
				$topElement['title'], $topElement['hoverTitle'], $topElement['externalId'], $topElement['destPage'],
				$topElement['destLink'], $topElement['options']);
			if ($newId === false) {
				return false;
			}
			if ($topElement['subMenu'] !== false
				&& $this->insertSubMenu($topElement['subMenu'], $newId) === false) {
				return false;
			}
		}
		else if ($mode === 'into') {
			$targetMax = $DB ->valuesQuery('
				SELECT COUNT(*) AS count
				FROM `MenuPaths`
				WHERE `parent`=?',
				'i', $target['mpid'])[0]['count'];

			$newId = $DB->impactQueryWithId('
				INSERT INTO `MenuPaths`
				(`parent`, `order`, `title`, `hoverTitle`, `externalId`, `destPage`, `destLink`, `options`)
				VALUES (?,?,?,?,?,?,?,?)',
				'iisssisi',
				$target['mpid'], $targetMax,
				$topElement['title'], $topElement['hoverTitle'], $topElement['externalId'], $topElement['destPage'],
				$topElement['destLink'], $topElement['options']);
			if ($newId === false) {
				return false;
			}
			if ($topElement['subMenu'] !== false
				&& $this->insertSubMenu($topElement['subMenu'], $newId) === false) {
				return false;
			}
		}
		return true;
	}

	private function insertSubMenu(&$menu, $parent) {
		global $DB;
		$order = 0;
		foreach ($menu as &$item) {
			$newId = $DB->impactQueryWithId('
				INSERT INTO `MenuPaths`
				(`parent`, `order`, `title`, `hoverTitle`, `externalId`, `destPage`, `destLink`, `options`)
				VALUES (?,?,?,?,?,?,?,?)',
				'iisssisi',
				$parent, $order,
				$item['title'], $item['hoverTitle'], $item['externalId'], $item['destPage'],
				$item['destLink'], $item['options']);
			if ($newId === false) {
				return false;
			}
			$order++;
			if ($item['subMenu'] !== false && !empty($item['subMenu'])) {
				if ($this->insertSubMenu($item['subMenu'], $newId) === false) {
					return false;
				}
			}
		}
		return true;
	}

	private function addIdsOfSubMenuItems(&$array, $parent) {
		global $DB;
		$submenuItems = $DB->valuesQuery('SELECT `mpid` FROM `MenuPaths` WHERE `parent`=?', 'i', $parent);
		if ($submenuItems === false) {
			return;
		}
		foreach ($submenuItems as $submenuItem) {
			$array[] = $submenuItem['mpid'];
			$this->addIdsOfSubMenuItems($array, $submenuItem['mpid']);
		}
	}

	// --------------------------------------------------------------------------------------------
	// Database loading methods
	// --------------------------------------------------------------------------------------------

	private function loadMenuItems() {
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
			$submenu = $this->menuItemOperations->getSubmenuItems($menu['mpid']);
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