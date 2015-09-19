<?php

class ModuleAdminPages extends BasicModule {

	private $state;
	private $message;
	private $menu;
	private $pages;

	public function __construct(&$controller) {
		global $CMS_VERSION, $DB;
		parent::__construct($CMS_VERSION, 'admin-pages');
		$controller->verifyLogin();

		// handle menu operations
		if (Utils::isValidFieldNotEmpty('operationSpace') && Utils::getValidField('operationSpace') === 'menu') {
			$this->handleMenuOperations();
		}
		// handle page operations
		else if (Utils::isValidFieldNotEmpty('operationSpace') && Utils::getValidField('operationSpace') === 'page') {
			$this->handlePageOperations();
		}

		// get menu from database
		$this->menu = $DB->valuesQuery('
			SELECT `mpid`, `title`, `hoverTitle`, `options`
			FROM `MenuPaths`
			WHERE `parent` IS NULL
			ORDER BY `order` ASC');
		if (empty($this->menu)) {
			$this->menu = false;
		}
		if ($this->menu !== false) {
			$this->addSubMenuForEachItem($this->menu);
		}

		// get pages from database
		$this->pages = $DB->valuesQuery('
			SELECT `pid`, `title`, `hoverTitle`, `options`, `externalId`
			FROM `Pages`
			ORDER BY `pid` ASC');
		if (empty($this->pages)) {
			$this->pages = false;
		}
	}

	public function printContent($config) {
		?>
		<script type="text/javascript">
			$(document).ready(function(){
				$('.siteMap input[type="checkbox"]').change(function() {
					var disabled = $('.siteMap input[type="checkbox"]:checked').length == 0;
					$('#menu-item-public').prop('disabled', disabled);
					$('#menu-item-private').prop('disabled', disabled);
					$('#menu-item-move').prop('disabled', disabled);
					$('#menu-item-copy').prop('disabled', disabled);
					$('#menu-item-delete').prop('disabled', disabled);
				});
				$('#menu-item-public').click(function(e) {
					e.preventDefault();
					$('#menu-item-operation').val('public');
					$('#menuOperations').submit();
				});
				$('#menu-item-private').click(function(e) {
					e.preventDefault();
					$('#menu-item-operation').val('private');
					$('#menuOperations').submit();
				});
				$('#menu-item-move').click(function(e) {
					e.preventDefault();
					$('#menu-item-operation').val('move');
					$('#menuOperations button').addClass('hidden');
					$('#menuOperations .siteMap input[type="checkbox"]').prop('disabled', true);
					$('#menuOperations .dialog-box .dialog-message')
						.text('<?php $this->text('SELECT_MOVE_TARGET'); ?>');
					$('#menuOperations .dialog-box, #menu-item-target, #menu-item-at, #menu-item-into, ' +
						'#menu-item-cancel').removeClass('hidden');
				});
				$('#menu-item-copy').click(function(e) {
					e.preventDefault();
					$('#menu-item-operation').val('copy');
					$('#menuOperations button').addClass('hidden');
					$('#menuOperations .siteMap input[type="checkbox"]').prop('disabled', true);
					$('#menuOperations .dialog-box .dialog-message')
						.text('<?php $this->text('SELECT_COPY_TARGET'); ?>');
					$('#menuOperations .dialog-box, #menu-item-target, #menu-item-at, #menu-item-into, ' +
						'#menu-item-cancel').removeClass('hidden');
				});
				$('#menu-item-at').click(function(e) {
					e.preventDefault();
					$('#menu-item-operationTarget').val('at');
					$('#menuOperations .siteMap input[type="checkbox"]').prop('disabled', false);
					$('#menuOperations').submit();
				});
				$('#menu-item-into').click(function(e) {
					e.preventDefault();
					$('#menu-item-operationTarget').val('into');
					$('#menuOperations .siteMap input[type="checkbox"]').prop('disabled', false);
					$('#menuOperations').submit();
				});
				$('#menu-item-cancel').click(function(e) {
					e.preventDefault();
					$('#menuOperations .siteMap input[type="checkbox"]').prop('disabled', false);
					$('#menuOperations button, #menuOperations .dialog-box').addClass('hidden');
					$('#menuOperations .siteMap, #menu-item-new, #menu-item-public, #menu-item-private, ' +
						'#menu-item-move, #menu-item-copy, #menu-item-delete').removeClass('hidden');
				});
				$('#menu-item-delete').click(function(e) {
					e.preventDefault();
					$('#menu-item-operation').val('delete');
					$('#menuOperations button').addClass('hidden');
					$('#menuOperations .siteMap input[type="checkbox"]').prop('disabled', true);
					$('#menuOperations .dialog-box .dialog-message').text('<?php $this->text('DELETE_QUESTION'); ?>');
					$('#menuOperations .dialog-box, #menu-item-cancel, #menu-item-deleteConfirm').removeClass('hidden');
				});
				$('#menu-item-deleteConfirm').click(function(e) {
					e.preventDefault();
					$('#menuOperations .siteMap input[type="checkbox"]').prop('disabled', false);
					$('#menuOperations').submit();
				});
				$('#menu-item-new').click(function(e) {
					e.preventDefault();
					window.open('<?php echo $config->getPublicRoot()?>/admin/new-menu-item', '_self');
				});
				$('.pageList input[type="checkbox"]').change(function() {
					var disabled = $('.pageList input[type="checkbox"]:checked').length == 0;
					$('#page-public').prop('disabled', disabled);
					$('#page-private').prop('disabled', disabled);
					$('#page-copy').prop('disabled', disabled);
					$('#page-delete').prop('disabled', disabled);
				});
				$('#page-public').click(function(e) {
					e.preventDefault();
					$('#page-operation').val('public');
					$('#pageOperations').submit();
				});
				$('#page-private').click(function(e) {
					e.preventDefault();
					$('#page-operation').val('private');
					$('#pageOperations').submit();
				});
				$('#page-delete').click(function(e) {
					e.preventDefault();
					$('#page-operation').val('delete');
				});
				$('#page-deleteConfirm').click(function(e) {
					e.preventDefault();
				});
				$('#page-new').click(function(e) {
					e.preventDefault();
					window.open('<?php echo $config->getPublicRoot()?>/admin/new-page', '_self');
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
				<button id="menu-item-new"><?php $this->text('NEW_MENU_ITEM'); ?></button>
				<input type="hidden" name="operationSpace" value="menu" />
				<input type="hidden" name="operation" id="menu-item-operation" />
				<input type="hidden" name="operationTarget" id="menu-item-operationTarget" />
				<div class="siteMap">
					<?php $this->printMenu($this->menu, $config, true); ?>
				</div>
				<div class="buttonSet">
					<button id="menu-item-public" disabled><?php $this->text('MAKE_PUBLIC'); ?></button>
					<button id="menu-item-private" disabled><?php $this->text('MAKE_PRIVATE'); ?></button>
					<button id="menu-item-move" disabled><?php $this->text('MOVE'); ?></button>
					<button id="menu-item-copy" disabled><?php $this->text('COPY'); ?></button>
					<button id="menu-item-delete" disabled><?php $this->text('DELETE'); ?></button>
				</div>
				<div class="dialog-box hidden">
					<div class="dialog-message"></div>
					<div class="fields">
						<select name="target" class="hidden" id="menu-item-target">
							<?php $this->printSelect($this->menu, 0); ?>
						</select>
					</div>
					<div class="options">
						<button id="menu-item-at" class="hidden"><?php $this->text('MENU_ITEM_AT'); ?></button>
						<button id="menu-item-into" class="hidden"><?php $this->text('MENU_ITEM_INTO'); ?></button>
						<button id="menu-item-deleteConfirm" class="hidden"><?php $this->text('DELETE'); ?></button>
						<button id="menu-item-cancel" class="hidden"><?php $this->text('CANCEL'); ?></button>
					</div>
				</div>
			</form>
		</section>
		<section>
			<h1><?php $this->text('PAGES'); ?></h1>
			<form method="post" action="<?php echo $config->getPublicRoot()?>/admin/pages" id="pageOperations">
				<button id="page-new"><?php $this->text('NEW_PAGE'); ?></button>
				<input type="hidden" name="operationSpace" value="page" />
				<input type="hidden" name="operation" id="page-operation" />
				<div class="pageList">
					<?php $this->printPages($config); ?>
				</div>
				<div class="buttonSet">
					<button id="page-public" disabled><?php $this->text('MAKE_PUBLIC'); ?></button>
					<button id="page-private" disabled><?php $this->text('MAKE_PRIVATE'); ?></button>
					<button id="page-copy" disabled><?php $this->text('COPY'); ?></button>
					<button id="page-delete" disabled><?php $this->text('DELETE'); ?></button>
				</div>
				<div class="dialog-box hidden">
					<div class="dialog-message"></div>
					<div class="options">
						<button id="page-deleteConfirm" class="hidden"><?php $this->text('DELETE'); ?></button>
						<button id="page-cancel" class="hidden"><?php $this->text('CANCEL'); ?></button>
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
				echo ' class="private"';
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
		echo '<ul class="tableLike">';
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

	private function addSubMenuForEachItem(&$menu) {
		global $DB;
		foreach ($menu as &$item) {
			$item['subMenu'] = $DB->valuesQuery('
				SELECT *
				FROM `MenuPaths`
				WHERE `parent`=?
				ORDER BY `order` ASC', 'i', $item['mpid']);
			if ($item['subMenu'] === false || empty($item['subMenu'])) {
				$item['subMenu'] = false;
			}
			else {
				$this->addSubMenuForEachItem($item['subMenu']);
			}
		}
	}
}

?>