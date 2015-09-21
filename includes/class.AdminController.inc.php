<?php

class AdminController {

	private $config;
	private $fieldOperations;
	private $fieldGroupOperations;
	private $moduleOperations;

	public function __construct() {	
		global $DB, $PUBLIC_ROOT, $CMS_FULLNAME, $CMS_URL;
		$this->config = new Configuration();
		$this->config->setPublicRoot($PUBLIC_ROOT);
		$this->config->setCmsFullname($CMS_FULLNAME);
		$this->config->setCmsUrl($CMS_URL);

		// database operations
		$this->fieldOperations = new FieldOperations($DB);
		$this->fieldGroupOperations = new FieldGroupOperations($DB, $this->fieldOperations);
		$this->moduleOperations = new ModuleOperations($DB, $this->fieldGroupOperations);
	}

	public function layoutDialog(...$contentModules) {
		foreach ($contentModules as $contentModule) {
			$contentModule->printContent($this->config);
		}
	}

	public function layoutContent(...$contentModules) {
		$layoutContext = $this->generateLayoutContext();
		$layoutContext->setContentModules($contentModules);
		$this->layout($layoutContext);
	}

	public function layoutLoggedInContent($currentMenuIndex, $subMenuItems, $asideModules, ...$contentModules) {
		$layoutContext = $this->generateLayoutContext();
		$menuItems = $this->generateMenuItems();
		$menuItems[$currentMenuIndex]->setCurrent(true);
		$layoutContext->setMenuItems($menuItems);
		if ($subMenuItems !== null) {
			$layoutContext->setCurrentSubMenuItems($subMenuItems);
		}
		if ($asideModules !== null) {
			$layoutContext->setAsideHeader($asideModules);
		}
		$layoutContext->setContentModules($contentModules);
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
				CREATE TABLE IF NOT EXISTS `Pages` (
					`pid` INT(10) NOT NULL AUTO_INCREMENT,
					`title` VARCHAR(256) NOT NULL,
					`hoverTitle` VARCHAR(256) NULL,
					`externalId` VARCHAR(256) NULL,
					`options` INT(10) NOT NULL,
					`lastChanged` TIMESTAMP NOT NULL,
					`externalLastChanged` TIMESTAMP NULL,
					PRIMARY KEY (`pid`)
				)
			') && $DB->successQuery('
				CREATE TABLE IF NOT EXISTS `Configuration` (
					`key` VARCHAR(32) NOT NULL,
					`value` VARCHAR(1024) NULL,
					`order` INT(10) NOT NULL,
					PRIMARY KEY (`key`),
					UNIQUE KEY `position` (`key`, `order`)
				)
			') && $DB->successQuery('
				CREATE TABLE IF NOT EXISTS `MenuPaths` (
					`mpid` INT(10) NOT NULL AUTO_INCREMENT,
					`parent` INT(10) NULL,
					`order` INT(10) NOT NULL,
					`title` VARCHAR(256) NOT NULL,
					`hoverTitle` VARCHAR(256) NULL,
					`externalId` VARCHAR(256) NOT NULL,
					`destPage` INT(10) NULL,
					`destLink` VARCHAR(256) NULL,
					`options` INT(10) NOT NULL,
					PRIMARY KEY (`mpid`),
					UNIQUE KEY `position` (`parent`, `order`)
				)
			') && $DB->successQuery('
				CREATE TABLE IF NOT EXISTS `Modules` (
					`mid` INT(10) NOT NULL AUTO_INCREMENT,
					`page` INT(10) NULL,
					`section` INT(10) NOT NULL,
					`order` INT(10) NOT NULL,
					`module` VARCHAR(32) NOT NULL,
					PRIMARY KEY (`mid`),
					UNIQUE KEY `position` (`page`, `section`, `order`)
				)
			') && $DB->successQuery('
				CREATE TABLE IF NOT EXISTS `FieldGroups` (
					`fgid` INT(10) NOT NULL AUTO_INCREMENT,
					`module` INT(10) NOT NULL,
					`key` VARCHAR(32) NULL,
					`order` INT(10) NULL,
					PRIMARY KEY (`fgid`),
					UNIQUE KEY `position` (`module`, `key`, `order`)
				)
			') && $DB->successQuery('
				CREATE TABLE IF NOT EXISTS `Fields` (
					`fid` INT(10) NOT NULL AUTO_INCREMENT,
					`group` INT(10) NOT NULL,
					`key` VARCHAR(32) NOT NULL,
					`type` INT(10) NOT NULL,
					`content` TEXT NOT NULL,
					`metaType` INT(10) NULL,
					`metaContent` TEXT NULL,
					PRIMARY KEY (`fid`),
					UNIQUE KEY `position` (`group`, `key`)
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

	public function getModuleOperations() {
		return $this->moduleOperations;
	}

	// --------------------------------------------------------------------------------------------

	private function generateLayoutContext() {
		global $CMS_FULLNAME, $TR;
		$layoutContext = new LayoutContext($this->config);
		$layoutContext->setTitle($CMS_FULLNAME);
		$layoutContext->setLogo('
			<hgroup>
				<h1>' . $CMS_FULLNAME . '</h1>
				<h2>' . $TR->translate('WEBSITE_ADMINISTRATION') . '</h2>
			</hgroup>
			');
		return $layoutContext;
	}

	private function generateMenuItems() {
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
		global $INCLUDE_DIRECTORY;
		require_once($INCLUDE_DIRECTORY . '/templ.AdminLayout.inc.php');
	}
}

?>