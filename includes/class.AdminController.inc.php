<?php

class AdminController {

	private $config;
	private $configurationOperations;
	private $changelogOperations;
	private $menuItemOperations;
	private $fieldOperations;
	private $fieldGroupOperations;
	private $moduleOperations;
	private $pageOperations;
	private $mediaGroupOperations;
	private $mediaOperations;
	private $imageOperations;
	private $globalOperations;

	public function __construct() {
		global $DB, $PUBLIC_ROOT, $CMS_FULLNAME, $CMS_URL, $MAX_RUNTIME;
		$this->config = new Configuration();
		$this->config->setPublicRoot($PUBLIC_ROOT);
		$this->config->setCmsFullname($CMS_FULLNAME);
		$this->config->setCmsUrl($CMS_URL);
		$this->config->setMaxRuntime($MAX_RUNTIME);

		// database operations
		$this->configurationOperations = new ConfigurationOperations($DB);
		$this->changelogOperations = new ChangelogOperations($DB);
		$this->menuItemOperations = new MenuItemOperations($DB);
		$this->fieldOperations = new FieldOperations($DB);
		$this->fieldGroupOperations = new FieldGroupOperations($DB, $this->fieldOperations);
		$this->moduleOperations = new ModuleOperations($DB, $this->fieldGroupOperations,
			$this->changelogOperations);
		$this->pageOperations = new PageOperations($DB, $this->moduleOperations, $this->changelogOperations);
		$this->mediaGroupOperations = new MediaGroupOperations($DB);
		$this->mediaOperations = new MediaOperations($DB);
		$this->imageOperations = new ImageOperations($DB);
		$this->globalOperations = new GlobalOperations($this->menuItemOperations,
			$this->pageOperations);
	}

	public function layoutDialog(...$modules) {
		foreach ($modules as $module) {
			$module->printContent($this->config);
		}
	}

	public function layoutContent(...$modules) {
		$layoutContext = $this->createLayoutContext();
		$layoutContext->setContentModules($this->createContentFromModules($modules));
		$this->layout($layoutContext);
	}

	public function layoutLoggedInContent($currentMenuIndex, $subMenuItems, $asideModules,
			...$contentModules) {
		$layoutContext = $this->createLayoutContext();
		// add menu
		$menuItems = $this->createMenuItems();
		if ($currentMenuIndex !== null && $currentMenuIndex >= 0) {
			$menuItems[$currentMenuIndex]->setCurrent(true);
		}
		$layoutContext->setMenuItems($menuItems);
		if ($subMenuItems !== null) {
			$layoutContext->setCurrentSubMenuItems($subMenuItems);
		}
		// add changelog status
		$numberOfChanges = $this->changelogOperations->getNumberOfChanges();
		$changelogShown = false;
		foreach ($contentModules as $contentModule) {
			if ($contentModule instanceof AdminChangelogModule) {
				$changelogShown = true;
			}
		}
		if (!$changelogShown && $numberOfChanges !== false && $numberOfChanges > 0 && $contentModules) {
			$module = new AdminChangelogStatusModule($numberOfChanges);
			$layoutContext->setPreContentModules([$this->createContentFromModule($module)]);
		}

		// add content
		if (isset($asideModules)) {
			$layoutContext->setAsideContentModules($this->createContentFromModules($asideModules));
		}
		$layoutContext->setContentModules($this->createContentFromModules($contentModules));
		$this->layout($layoutContext);
	}

	public function isInstalled() {
		global $DB;
		return $DB->resultQuery('
			SELECT `key` 
			FROM `Configuration` 
			WHERE `key`="user" AND `value`="admin" AND `order`="0"
			');
	}

	public function install() {
		global $DB;
		// already installed
		if ($this->isInstalled()) {
			return true;
		}

		// not installed, no installation attempt
		if (!isset($_POST['password'])) {
			return false;
		}

		// installation attempt
		if (!isset($_POST['password2'])
			|| strlen($_POST['password']) > 64
			|| strlen($_POST['password2']) > 64) {
			return 'PASSWORD_MAXLENGTH';
		}
		if (strlen($_POST['password']) < 8
			|| strlen($_POST['password2']) < 8) {
			return 'PASSWORD_MINLENGTH';
		}
		if (strcmp($_POST['password'], $_POST['password2']) !== 0) {
			return 'PASSWORDS_NOT_EQUAL';
		}
		$hashedAndSalted = password_hash($_POST['password'], PASSWORD_DEFAULT);

		$result = $DB->successQuery('
				CREATE TABLE IF NOT EXISTS `Changelog` (
					`clid` INT NOT NULL AUTO_INCREMENT,
					`internal` TINYINT NOT NULL,
					`type` TINYINT NOT NULL,
					`operation` TINYINT NOT NULL,
					`recordId` INT NOT NULL,
					`time` TIMESTAMP NOT NULL,
					`description` TEXT NULL,
					PRIMARY KEY (`clid`)
				)
			') && $DB->successQuery('
				CREATE TABLE IF NOT EXISTS `Images` (
					`iid` INT NOT NULL AUTO_INCREMENT,
					`file` INT NOT NULL,
					`externalId` VARCHAR(256) NULL,
					`hoverTitle` VARCHAR(256) NULL,
					`altTitle` VARCHAR(256) NULL,
					`topCornerX` INT NOT NULL,
					`topCornerY` INT NOT NULL,
					`bottomCornerX` INT NOT NULL,
					`bottomCornerY` INT NOT NULL,
					`width` INT NOT NULL,
					`height` INT NOT NULL,
					`format` INT NOT NULL,
					PRIMARY KEY (`iid`)
				)
			') && $DB->successQuery('
				CREATE TABLE IF NOT EXISTS `Media` (
					`mid` INT NOT NULL AUTO_INCREMENT,
					`group` INT NOT NULL,
					`originalName` VARCHAR(512) NOT NULL,
					`internalName` VARCHAR(512) NOT NULL,
					`description` TEXT NULL,
					`tags` TEXT NULL,
					`checksum` CHAR(40) NULL,
					`size` BIGINT NULL,
					`externalId` VARCHAR(256) NULL,
					`options` INT NOT NULL,
					`lastChanged` TIMESTAMP NOT NULL,
					`externalLastChanged` TIMESTAMP NULL,
					PRIMARY KEY (`mid`)
				)
			') && $DB->successQuery('
				CREATE TABLE IF NOT EXISTS `Pages` (
					`pid` INT NOT NULL AUTO_INCREMENT,
					`title` VARCHAR(256) NOT NULL,
					`hoverTitle` VARCHAR(256) NULL,
					`externalId` VARCHAR(256) NULL,
					`options` INT NOT NULL,
					`lastChanged` TIMESTAMP NOT NULL,
					`externalLastChanged` TIMESTAMP NULL,
					PRIMARY KEY (`pid`)
				)
			') && $DB->successQuery('
				CREATE TABLE IF NOT EXISTS `Configuration` (
					`key` VARCHAR(32) NOT NULL,
					`value` VARCHAR(1024) NULL,
					`order` INT NOT NULL,
					PRIMARY KEY (`key`),
					UNIQUE KEY `position` (`key`, `order`)
				)
			') && $DB->successQuery('
				CREATE TABLE IF NOT EXISTS `MenuItems` (
					`miid` INT NOT NULL AUTO_INCREMENT,
					`parent` INT NULL,
					`order` INT NOT NULL,
					`title` VARCHAR(256) NOT NULL,
					`hoverTitle` VARCHAR(256) NULL,
					`externalId` VARCHAR(256) NOT NULL,
					`destPage` INT NULL,
					`destLink` VARCHAR(1024) NULL,
					`options` INT NOT NULL,
					PRIMARY KEY (`miid`),
					UNIQUE KEY `position` (`parent`, `order`),
					UNIQUE `external` (`parent`, `externalId`)
				)
			') && $DB->successQuery('
				CREATE TABLE IF NOT EXISTS `Modules` (
					`mid` INT NOT NULL AUTO_INCREMENT,
					`page` INT NULL,
					`section` INT NOT NULL,
					`order` INT NOT NULL,
					`definitionId` VARCHAR(32) NOT NULL,
					PRIMARY KEY (`mid`),
					UNIQUE KEY `position` (`page`, `section`, `order`)
				)
			') && $DB->successQuery('
				CREATE TABLE IF NOT EXISTS `FieldGroups` (
					`fgid` INT NOT NULL AUTO_INCREMENT,
					`module` INT NOT NULL,
					`key` VARCHAR(32) NULL,
					`order` INT NULL,
					PRIMARY KEY (`fgid`),
					UNIQUE KEY `position` (`module`, `key`, `order`)
				)
			') && $DB->successQuery('
				CREATE TABLE IF NOT EXISTS `MediaReferences` (
					`mrid` INT NOT NULL AUTO_INCREMENT,
					`file` INT NOT NULL,
					`fieldGroup` INT NOT NULL,
					PRIMARY KEY (`mrid`)
				)
			') && $DB->successQuery('
				CREATE TABLE IF NOT EXISTS `Fields` (
					`fid` INT NOT NULL AUTO_INCREMENT,
					`group` INT NOT NULL,
					`key` VARCHAR(32) NOT NULL,
					`type` INT NOT NULL,
					`content` TEXT NOT NULL,
					PRIMARY KEY (`fid`),
					UNIQUE KEY `position` (`group`, `key`, `fid`)
				)
			') && $DB->successQuery('
				CREATE TABLE IF NOT EXISTS `MediaGroups` (
					`mgid` INT NOT NULL AUTO_INCREMENT,
					`title` VARCHAR(256) NOT NULL,
					`description` TEXT NULL,
					`tags` TEXT NULL,
					`checksum` CHAR(40) NULL,
					`options` INT NOT NULL,
					PRIMARY KEY (`mgid`)
				)
			') && $DB->impactQuery('
				INSERT INTO `Configuration` (`key`, `value`, `order`) 
				VALUES ("user", "admin", 0)
			') && $DB->impactQuery('
				INSERT INTO `Configuration` (`key`, `value`, `order`)
				VALUES ("password", ?, 0)
			', 's', $hashedAndSalted)
				&& $DB->impactQuery('
				INSERT INTO `Configuration` (`key`, `value`, `order`) 
				VALUES ("session", NULL, 0)
			');

		if ($result) {
			return true;
		}
		else {
			return 'UNKNOWN_ERROR';
		}
	}

	public function login() {
		global $DB;
		session_start();
		$currentSid = session_id();

		// check for session (already prepared for multi-user support)
		$sessionInfos = $DB->valuesQuery('SELECT `value`, `order` FROM `Configuration` WHERE `key`="session"');
		if ($sessionInfos === false) {
			return 'UNKNOWN_ERROR';
		}
		foreach ($sessionInfos as $sessionInfo) {
			if ($sessionInfo['value'] !== null
				&& strlen($sessionInfo['value']) == strlen($currentSid)
				&& hash_equals($sessionInfo['value'], $currentSid)) {
				$this->config->setUserId($sessionInfo['order']);
				return true;
			}
		}

		// no password attempt
		if (!isset($_POST['password'])) {
			return false;
		}

		// check for password to login (no multi-user support yet)		
		$saltedHash = $DB->valuesQuery('SELECT `value` FROM `Configuration` WHERE `key`="password" AND `order`=0');
		if ($saltedHash === false || count($saltedHash) != 1) {
			return 'UNKNOWN_ERROR';
		}
		$currentPassword = $_POST['password'];
		if (strlen($currentPassword) >= 8
			&& strlen($currentPassword) < 64
			&& password_verify($currentPassword, $saltedHash[0]['value'])) {
			$result = $DB->impactQuery('UPDATE `Configuration` SET `value`=? WHERE `key`="session" AND `order`=0',
				's', $currentSid);
			if ($result === false) {
				return 'UNKNOWN_ERROR';
			}
			return true;
		}
		return 'WRONG_PASSWORD';
	}

	public function verifyLogin() {
		global $PUBLIC_ROOT;
		if ($this->login() !== true) {
			header('Location: ' . $PUBLIC_ROOT . '/admin');
			exit;
		}
	}

	public function getDB() {
		global $DB;
		return $DB;
	}

	public function getConfig() {
		return $this->config;
	}

	public function getGlobalOperations() {
		return $this->globalOperations;
	}

	public function getMenuItemOperations() {
		return $this->menuItemOperations;
	}

	public function getFieldOperations() {
		return $this->fieldOperations;
	}

	public function getFieldGroupOperations() {
		return $this->fieldGroupOperations;
	}

	public function getModuleOperations() {
		return $this->moduleOperations;
	}

	public function getPageOperations() {
		return $this->pageOperations;
	}

	public function getChangelogOperations() {
		return $this->changelogOperations;
	}

	public function getMediaGroupOperations() {
		return $this->mediaGroupOperations;
	}

	public function getMediaOperations() {
		return $this->mediaOperations;
	}

	public function getImageOperations() {
		return $this->imageOperations;
	}

	// --------------------------------------------------------------------------------------------

	private function createContentFromModule($module) {
		$content = [];
		$content['name'] = $module->getName();
		$content['content'] = $module->getContent($this->config);
		return $content;
	}

	private function createContentFromModules($modules) {
		$content = [];
		foreach ($modules as $module) {
			$content[] = $this->createContentFromModule($module);
		}
		return $content;
	}

	private function createLayoutContext() {
		$layoutContext = new LayoutContext($this->config);
		$layoutContext->setTitle($this->config->getCmsFullname());
		$layoutContext->setLogoModules([$this->createContentFromModule(new AdminLogoModule())]);
		return $layoutContext;
	}

	private function createMenuItems() {
		global $PUBLIC_ROOT, $TR;
		$menuItems = array();
		$menuItems[] = new MenuItem($PUBLIC_ROOT . '/admin/overview', null, false, 
			$TR->translate('MENU_OVERVIEW'), null);
		$menuItems[] = new MenuItem($PUBLIC_ROOT . '/admin/pages', null, false, 
			$TR->translate('MENU_PAGES'), null);
		$menuItems[] = new MenuItem($PUBLIC_ROOT . '/admin/feedback', null, false, 
			$TR->translate('MENU_FEEDBACK'), null);
		$menuItems[] = new MenuItem($PUBLIC_ROOT . '/admin/media', null, false, 
			$TR->translate('MENU_MEDIA'), null);
		$menuItems[] = new MenuItem($PUBLIC_ROOT . '/admin/settings', null, false, 
			$TR->translate('MENU_SETTINGS'), null);
		$menuItems[] = new MenuItem($PUBLIC_ROOT . '/admin/logout', null, false, 
			$TR->translate('MENU_LOGOUT'), null);
		return $menuItems;
	}

	private function layout($layoutContext) {
		global $ROOT_DIRECTORY;
		require_once($ROOT_DIRECTORY . '/layouts/templ.AdminLayout.inc.php');
	}

	// --------------------------------------------------------------------------------------------

	public function getCompiler() {
		global $ROOT_DIRECTORY, $MAX_RUNTIME_STOP_FACTOR;
		return new Compiler(
			$this->config,
			$MAX_RUNTIME_STOP_FACTOR,
			$ROOT_DIRECTORY . '/compiled',
			$ROOT_DIRECTORY . '/layouts',
			$ROOT_DIRECTORY . '/public',
			$this->configurationOperations,
			$this->changelogOperations,
			$this->pageOperations,
			$this->moduleOperations);
	}
}

?>