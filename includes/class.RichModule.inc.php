<?php

abstract class RichModule extends BasicModule {

	const MODULE_PRIORITY_LOW = 0;
	const MODULE_PRIORITY_MEDIUM = 1;
	const MODULE_PRIORITY_HIGH = 2;

	private $module;
	private $currentCompilationPage;
	private $intermoduleProperties;

	public function __construct($cmsVersion, $name) {
		parent::__construct($cmsVersion, $name);
	}

	// the priority determines which module can define intermodule properties
	public function getPriority() {
		return MODULE_PRIORITY_LOW;
	}

	// array of FieldInfo
	public function getConfigFieldInfo() {
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
	// Module
	// --------------------------------------------------------------------------------------------

	final public function setModule($module) {
		if (isset($this->module)) {
			throw new Exception('Module is already set.');
		}
		$this->module = $module;
	}

	final public function verifyModule() {
		if (!isset($this->module)) {
			throw new Exception('Module is missing.');
		}
	}

	// --------------------------------------------------------------------------------------------
	// Compilation methods
	// --------------------------------------------------------------------------------------------

	// 1 = module does only provide content for one subpage
	// 2 = module provides content for two subpages, etc.
	public function getNumberOfSubpages() {
		return 1;
	}

	// number can be higher than number of subpages
	// e.g. for comment module of blog posts number of subpages is 1 but current compilation page
	// depends on number of blog posts
	final public function setCurrentCompilationPage($number) {
		$this->currentCompilationPage = $number;
	}

	final public function getCurrentCompilationPage() {
		return $this->currentCompilationPage;
	}

	public function getContent($config) {
		return parent::getContent($config);
	}

	public function getExternalId() {
		if ($this->currentCompilationPage === 0) {
			throw new Exception('External ID of first page can not be defined by module.');
		}
		return null;
	}

	public function usesIntermoduleProperties() {
		return false;
	}

	public function definesIntermoduleProperties() {
		return false;
	}

	// function with higher priority can set intermodule properties
	// e.g. overview -> no rating on an overview page
	public function defineIntermoduleProperties() {
		return [];
	}

	final public function getIntermoduleProperties() {
		return $this->intermoduleProperties;
	}

	final public function setIntermoduleProperties($intermoduleProperties) {
		$this->intermoduleProperties = $intermoduleProperties;
	}

	// --------------------------------------------------------------------------------------------
	// Data load/store methods
	// --------------------------------------------------------------------------------------------

	final public function getNumberOfFieldGroups($key) {
		$this->verifyModule();
		$fieldGroupOperations = $this->controller->getFieldGroupOperations();
		return $fieldGroupOperations->getNumberOfFieldGroups($this->inPageModuleId, $key);
	}

	final public function getFieldGroups($key) {
		$this->verifyModule();
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
		$this->verifyModule();
		$fieldGroupOperations = $this->controller->getFieldGroupOperations();
		$fieldGroupId = $fieldGroupOperations->getConfigFieldGroupId($this->inPageModuleId);
		if ($fieldGroupId === false) {
			$result = $fieldGroupOperations->addFieldGroup($this->inPageModuleId, null);
			if ($result === false) {
				return false;
			}
		}
		return new FieldGroup($controller, $fieldGroupId);
	}

	final public function getFieldGroup($key, $order) {
		$this->verifyModule();
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
		$this->verifyModule();
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
		$this->verifyModule();
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
	// Helper methods
	// --------------------------------------------------------------------------------------------

	final public function getFieldGroupInfoOfKey($key) {
		foreach ($this->getFieldGroupInfo() as $fieldGroupInfo) {
			if ($fieldGroupInfo->getKey() === $key) {
				return $fieldGroupInfo;
			}
		}
		return false;
	}

	final public function getConfigAsFieldGroupInfo() {
		return new FieldGroupInfo('config', '', '', $this->getConfigFieldInfo());
	}

	// --------------------------------------------------------------------------------------------
	// Static helper methods
	// --------------------------------------------------------------------------------------------

	public static function getLocalizedModuleInfo($moduleDefId) {
		global $ROOT_DIRECTORY;
		global $TR;
		$module = [];
		$module['definitionId'] = $moduleDefId;
		$module['name'] = $moduleDefId;
		$module['description'] = null;
		// check for locale information
		$localeDir = $ROOT_DIRECTORY . '/modules/' . $moduleDefId . '/locales';
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

	public static function isValidModuleDefinitionId($moduleDefId) {
		return in_array($moduleDefId, RichModule::getModulesList(), true);
	}

	public static function getLocalizedModulesList() {
		$localizedList = [];
		$moduleList = RichModule::getModulesList();
		foreach ($moduleList as $moduleDefId) {
			$localizedList[] = RichModule::getLocalizedModuleInfo($moduleDefId);
		}
		// sort list
		function cmp($a, $b) {
			return strcmp(strtolower($a['name']), strtolower($b['name']));
		}
		usort($localizedList, 'cmp');

		return $localizedList;
	}

	private static $loadedModuleDefs = [];

	public static function loadModuleDefinition($moduleDefId) {
		if (array_key_exists($moduleDefId, RichModule::$loadedModuleDefs)) {
			return RichModule::$loadedModuleDefs[$moduleDefId];
		}
		else {
			global $ROOT_DIRECTORY;
			if (!file_exists($ROOT_DIRECTORY . '/modules/' . $moduleDefId . '/module.php')) {
				return false;
			}
			$module = include $ROOT_DIRECTORY . '/modules/' . $moduleDefId . '/module.php';
			if (!is_object($module) || !($module instanceof RichModule)) {
				return false;
			}
			RichModule::$loadedModuleDefs[$moduleDefId] = $module;
			return $module;
		}
	}
}

?>