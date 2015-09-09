<?php

class AdminController {

	private $config;

	public function __construct() {	
		global $PUBLIC_ROOT, $CMS_FULLNAME, $CMS_URL;
		$this->config = new Configuration();
		$this->config->setPublicRoot($PUBLIC_ROOT);
		$this->config->setCmsFullname($CMS_FULLNAME);
		$this->config->setCmsUrl($CMS_URL);
	}

	public function layoutContent(...$contentModules) {
		$layoutContext = $this->generateLayoutContext();
		$layoutContext->setContentModules($contentModules);
		$this->layout($layoutContext);
	}

	public function isInstalled() {
		global $DB;
		return $DB->existsQuery('
			SELECT `key` 
			FROM `Configuration` 
			WHERE `key`="username" AND `value`="admin" AND `order`="0"
			');
	}

	public function install() {
		global $DB;
		if (!isset($_POST['password'])
			|| !isset($_POST['password2'])
			|| strlen($_POST['password']) > 64
			|| strlen($_POST['password2']) > 64) {
			return "PASSWORD_MAXLENGTH";
		}
		if (strcmp($_POST['password'], $_POST['password2']) !== 0) {
			return "PASSWORDS_NOT_EQUAL";
		}
		/ TODO PASSWORD HASHEN UND SALTEN
		$result = $DB->successQuery('
				CREATE TABLE IF NOT EXISTS `Pages` (
					`pid` INT(10) NOT NULL,
					`title` VARCHAR(128) NULL,
					`externalId` VARCHAR(64) NOT NULL,
					`mainMenu` INT(10) NULL,
					`options` INT(10) NOT NULL,
					`lastChanged` TIMESTAMP NOT NULL,
					`externalLastChanged` TIMESTAMP NULL,
					PRIMARY KEY (`pid`)
				)
			') && $DB->successQuery('
				CREATE TABLE IF NOT EXISTS `Configuration` (
					`key` VARCHAR(32) NOT NULL,
					`value` VARCHAR(1024) NOT NULL,
					`order` INT(10) NOT NULL,
					PRIMARY KEY (`key`)
				)
			');

	}

	// --------------------------------------------------------------------------------------------

	private function generateLayoutContext() {
		$layoutContext = new LayoutContext($this->config);
		return $layoutContext;
	}

	private function layout($layoutContext) {
		global $INCLUDE_DIRECTORY;
		require_once($INCLUDE_DIRECTORY . '/templ.AdminLayout.inc.php');
	}
}

?>