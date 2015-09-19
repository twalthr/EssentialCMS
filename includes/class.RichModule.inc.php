<?php

abstract class RichModule extends BasicModule {

	const MODULE_PRIORITY_LOW = 0;
	const MODULE_PRIORITY_MEDIUM = 1;
	const MODULE_PRIORITY_HIGH = 2;

	private $controller;

	private $inPageModuleId;
	private $inPagePageId;
	private $inPageSection;
	private $inPageOrder;

	private $currentCompilationPage;

	public function __construct($cmsVersion, $name) {
		parent::__construct($cmsVersion, $name);
	}

	final public function setController($controller) {
		$this->$controller = $controller;
	}

	// the priority determines which module can define page properties
	public function getPriority() {
		return MODULE_PRIORITY_LOW;
	}

	// array of FieldInfo
	public function getConfigFieldGroupInfo() {
		return [];
	}

	// array of FieldGroupInfo
	public function getFieldGroupInfo() {
		return [];
	}

	public function servesDynamicContent() {
		return false;
	}

	// --------------------------------------------------------------------------------------------
	// In page module properties
	// --------------------------------------------------------------------------------------------

	final public function setInPageProperties($moduleId, $pageId, $pageSection, $pageOrder) {
		if (isset($this->inPageModuleId)) {
			throw new Exception('In page properties are already set.');
		}
		$this->inPageModuleId = $moduleId;
		$this->inPagePageId = $pageId;
		$this->inPageSection = $pageSection;
		$this->inPageOrder = $pageOrder;
	}

	final public function verifyInPageProperties() {
		if (!isset($this->inPageModuleId)
			|| !isset($this->inPagePageId)
			|| !isset($this->inPageSection)
			|| !isset($this->inPageOrder)) {
			throw new Exception('In page properties missing.');
		}
	}

	// --------------------------------------------------------------------------------------------
	// Compilation methods
	// --------------------------------------------------------------------------------------------

	// 1 = module does only provide content for one page
	// 2 = module provides content for two pages thus it
	//     must define properties such as externalId and title
	//     for the second page
	public function getNumberOfPages() {
		return 1;
	}

	public function setCurrentCompilationPage($number) {
		$this->currentCompilationPage = $number;
	}

	public function getCurrentCompilationPage() {
		return $this->currentCompilationPage;
	}

	public function getContent($config) {
		return parent::getContent($config);
	}

	public function getTitle($currentTitle) {
		return null;
	}

	public function getExternalId() {
		if ($this->currentCompilationPage === 0) {
			throw new Exception('External ID of first page can not be defined by module.');
		}
		return null;
	}

	public function getStyleFiles() {
		return [];
	}

	public function getScriptFiles() {
		return [];
	}

	// --------------------------------------------------------------------------------------------
	// Data load/store methods
	// --------------------------------------------------------------------------------------------

	final public function getNumberOfFieldGroups($key) {
		$this->verifyInPageProperties();
		$db = $this->controller->getDB();
		$result = $db->valueQuery('
			SELECT COUNT(*) AS `count`
			FROM `FieldGroups`
			WHERE `module`=? AND `key`=?',
			'is', $this->inPageModuleId, $key);
		if ($result === false) {
			return false;
		}
		return intval($result['count']);
	}

	final public function getFieldGroups($key) {
		$this->verifyInPageProperties();
		$db = $this->controller->getDB();
		$fieldGroupIds = $db->valuesQuery('
			SELECT `fgid`
			FROM `FieldGroups`
			WHERE `module`=? AND `key`=?
			ORDER BY `order` ASC',
			'is', $this->inPageModuleId, $key);
		if ($fieldGroupIds === false) {
			return false;
		}
		$result = [];
		foreach ($fieldGroupIds as $fieldGroupId) {
			$result[] = new FieldGroup($controller, $fieldGroupId);
		}
		return $result;
	}

	final public function getConfigGroup() {
		$this->verifyInPageProperties();
		$db = $this->controller->getDB();
		$fieldGroupId = $db->valueQuery('
			SELECT `fgid`
			FROM `FieldGroups`
			WHERE `module`=? AND `key` IS NULL AND `order` IS NULL',
			'i', $this->inPageModuleId);

		// insert empty config
		if ($fieldGroupId === false) {
			$fieldGroupId = $db->impactQueryWithId('
			INSERT INTO `FieldGroups`
			(`module`, `key`, `order`)
			VALUES
			(?, NULL, NULL)',
			'i', $this->inPageModuleId);

			if ($fieldGroupId === false) {
				return false;
			}
		}
		return new FieldGroup($controller, $fieldGroupId);
	}

	final public function getFieldGroup($key, $order) {
		$this->verifyInPageProperties();
		$db = $this->controller->getDB();
		$fieldGroupId = $db->valueQuery('
			SELECT `fgid`
			FROM `FieldGroups`
			WHERE `module`=? AND `key`=? AND `order`=?
			ORDER BY `order` ASC',
			'isi', $this->inPageModuleId, $key, $order);
		if ($fieldGroupId === false) {
			return false;
		}
		return new FieldGroup($controller, $fieldGroupId);
	}

	final public function newFieldGroup($key) {
		$this->verifyInPageProperties();
		$db = $this->controller->getDB();
		$fieldGroupId = $db->impactQueryWithId('
			INSERT INTO `FieldGroups`
			(`module`, `key`, `order`)
			VALUES
			(?,?, (SELECT COALESCE(MAX(`order`), -1) + 1 FROM `FieldGroups` WHERE `module`=? AND `key`=?))',
			'isis', $this->inPageModuleId, $key, $this->inPageModuleId, $key);
		if ($fieldGroupId === false) {
			return false;
		}
		return new FieldGroup($controller, $fieldGroupId);
	}

	final public function newFieldGroupAt($key, $order) {
		$this->verifyInPageProperties();
		$db = $this->controller->getDB();
		$result = $db->valueQuery('
			SELECT COUNT(*) AS `count`
			FROM `FieldGroups`
			WHERE `module`=? AND `key`=?',
			'is', $this->inPageModuleId, $key);
		if ($result === false || $result['count'] <= $order) {
			return false;
		}
		$result = $db->impactQuery('
			UPDATE `FieldGroups`
			SET `order` = `order` + 1
			WHERE `module`=? AND `key`=? AND `order`>=?',
			'isi', $this->inPageModuleId, $key, $order);
		if ($result === false) {
			return false;
		}
		$fieldGroupId = $db->impactQueryWithId('
			INSERT INTO `FieldGroups`
			(`module`, `key`, `order`)
			VALUES
			(?,?,?)',
			'isi', $this->inPageModuleId, $key, $order);
		if ($fieldGroupId === false) {
			return false;
		}
		return new FieldGroup($controller, $fieldGroupId);
	}

	// --------------------------------------------------------------------------------------------
	// Static helper methods
	// --------------------------------------------------------------------------------------------

	public static function getLocalizedModuleInfo($moduleId) {
		global $ROOT_DIRECTORY;
		global $TR;
		$module = [];
		$module['id'] = $moduleId;
		$module['name'] = $moduleId;
		$module['description'] = null;
		// check for locale information
		$localeDir = $ROOT_DIRECTORY . '/modules/' . $moduleId . '/locales';
		if (file_exists($localeDir) && is_dir($localeDir)) {
			// check of current locale is supported
			$supportedLocale = $TR->getSupportedLocaleFromDirectory($localeDir);
			if ($supportedLocale !== false) {
				$header = Translator::readHeaderFromLocaleFile($localeDir . '/' . 
					$supportedLocale . '.locale');
				// get translated module information from header
				if (count($header) > 0) {
					$module['name'] = $header[0];
				}
				if (count($header) > 1) {
					$module['description'] = $header[1];
				}
			}
		}
		return $module;
	}

	public static function getModulesList() {
		global $ROOT_DIRECTORY;
		$moduleList = array();
		// open modules directory
		if ($handle = opendir($ROOT_DIRECTORY . '/modules')) {
			while (false !== ($entry = readdir($handle))) {
				// for each directory
				if ($entry !== '.'
					&& $entry !== '..'
					&& is_dir($ROOT_DIRECTORY . '/modules/' . $entry)
					&& file_exists($ROOT_DIRECTORY . '/modules/' . $entry . '/module.php')) {
					$moduleList[] = $entry;
				}
			}
			closedir($handle);
		}
		return $moduleList;
	}

	public static function isValidModuleId($moduleId) {
		return in_array($moduleId, RichModule::getModulesList(), true);
	}

	public static function getLocalizedModulesList() {
		$localizedList = [];
		$moduleList = RichModule::getModulesList();
		foreach ($moduleList as $moduleId) {
			$localizedList[] = RichModule::getLocalizedModuleInfo($moduleId);
		}
		// sort list
		function cmp($a, $b) {
			return strcmp(strtolower($a['name']), strtolower($b['name']));
		}
		usort($localizedList, 'cmp');

		return $localizedList;
	}
}

?>