<?php

class ModuleAdminPages extends BasicModule {

	private $menu;

	public function __construct(&$controller) {
		global $CMS_VERSION, $DB;
		parent::__construct($CMS_VERSION, 'admin-pages');
		$controller->verifyLogin();

		$this->menu = $DB->valuesQuery('
			SELECT `mpid`, `title`, `hoverTitle`, `options`
			FROM `MenuPaths`
			WHERE `parent` IS NULL');
		if (empty($this->menu)) {
			$this->menu = false;
		}
		if ($this->menu !== false) {
			$this->addSubMenu($this->menu);
		}
	}

	private function addSubMenu(&$menu) {
		global $DB;
		foreach ($menu as &$item) {
			$item['subMenu'] = $DB->valuesQuery('
				SELECT `mpid`, `title`, `hoverTitle`, `options`
				FROM `MenuPaths`
				WHERE `parent`=?', 'i', $item['mpid']);
			if ($item['subMenu'] === false || empty($item['subMenu'])) {
				$item['subMenu'] = false;
			}
			else {
				$this->addSubMenu($item['subMenu']);
			}
		}
	}

	private function printMenu(&$menu, &$config, $topLevel) {
		if ($topLevel && ($menu === false || empty($menu))) {
			echo '<p>' . $this->text('NO_MENU_ITEMS') . '</p>';
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
			echo '<input type="checkbox" id="menuitem' . $item['mpid'] . '" name="menuitem"';
			echo ' value="' . $item['mpid'] . '" class="propagateChecked" />';
			echo '<label for="menuitem' . $item['mpid'] . '" class="checkbox">';
			echo htmlspecialchars($item['title']) .' </label>';
			echo '<a href="' . $config->getPublicRoot() . '/admin/menu-item/' . $item['mpid'] . '"';
			if (Utils::hasStringContent($item['hoverTitle'])) {
				echo ' title="' . htmlspecialchars($item['hoverTitle']) . '"';
			}
			if ($item['options'] & MENUPATHS_OPTION_PRIVATE) {
				echo ' class="private"';
			}
			echo '>' . htmlspecialchars($item['title']) . '</a>';
			if ($item['subMenu'] !== false) {
				$this->printMenu($item['subMenu'], $config, false);
			}
		}
		echo '</ul>';
	}

	public function getContent($config) {
		?>
		<section>
			<h1><?php $this->text('MENU'); ?></h1>
			<div class="siteMap">
				<?php $this->printMenu($this->menu, $config, true); ?>
			</div>
			<div class="buttonSet">
			</div>
		</section>
		<section>
			<h1><?php $this->text('ALL_PAGES'); ?></h1>
		</section>

		Pages

		<?php
	}

}

?>