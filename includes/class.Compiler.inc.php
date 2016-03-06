<?php

class Compiler {

	// database operations
	private $configurationOperations;
	private $changelogOperations;
	private $moduleOperations;

	// member variables
	private $config;
	private $maxRuntimeStopFactor;
	private $compilationPath;
	private $layoutPath;
	private $publicPath;

	// results
	private $finished;
	private $success;
	private $processedChanges = 0;

	// TODO add last error to config db

	public function __construct(
			$config,
			$maxRuntimeStopFactor,
			$compilationPath,
			$layoutPath,
			$publicPath,
			$configurationOperations,
			$changelogOperations,
			$pageOperations,
			$moduleOperations) {
		$this->config = $config;
		$this->maxRuntimeStopFactor = $maxRuntimeStopFactor;
		$this->compilationPath = $compilationPath;
		$this->layoutPath = $layoutPath;
		$this->publicPath = $publicPath;
		$this->configurationOperations = $configurationOperations;
		$this->changelogOperations = $changelogOperations;
		$this->pageOperations = $pageOperations;
		$this->moduleOperations = $moduleOperations;
	}

	public function compile($changelog) {
		$startRuntime = time();
		$stopRuntime = time() + ($this->config->getMaxRuntime() * $this->maxRuntimeStopFactor);

		$this->finished = false;
		$this->success = false;
		foreach ($changelog as $change) {
			// check if there are tasks (also from previous compilation)
			$tasks = $this->getInternalTasks();
			// tasks could not be loaded
			if ($tasks === false) {
				$this->saveErrorWithId('UNKNOWN_ERROR');
				$this->finished = true;
				$this->success = false;
				return;
			}
			// no internal tasks -> extract tasks from change
			else if (empty($tasks)) {
				$tasks = $this->splitIntoInternalTasks($change);
				// tasks could not be loaded
				if ($tasks === false) {
					$this->saveErrorWithId('UNKNOWN_ERROR');
					$this->finished = true;
					$this->success = false;
					return;
				}
			}
			// process tasks
			foreach ($tasks as $task) {
				// stop compilation if runtime is about to be exceeded
				if (time() >= $stopRuntime) {
					return;
				}
				// compile task
				$result = $this->compileTask($task);
				// error occurred
				if ($result === false) {
					$this->finished = true;
					$this->success = false;
					return;
				}
			}
			$result = $this->removeChange($change);
			// error occurred
			if ($result === false) {
				$this->saveErrorWithId('UNKNOWN_ERROR');
				$this->finished = true;
				$this->success = false;
			}
			$this->processedChanges++;
		}
		$this->finished = true;
		$this->success = true;
	}

	public function hasFinished() {
		return $this->finished;
	}

	public function wasSuccessful() {
		return $this->success;
	}

	public function getProcessedChanges() {
		return $this->processedChanges;
	}

	public function getErrorReason() {

	}

	// --------------------------------------------------------------------------------------------
	// Helper functions
	// --------------------------------------------------------------------------------------------

	private function getInternalTasks() {
		return $this->changelogOperations->getInternalChanges();
	}

	// --------------------------------------------------------------------------------------------
	// Splitting of change into internal tasks
	// --------------------------------------------------------------------------------------------

	private function splitIntoInternalTasks($change) {
		$recordId = $change['recordId'];
		switch ($change['type']) {
			case ChangelogOperations::CHANGELOG_TYPE_GLOBAL:
				return $this->splitGlobalChange();
			case ChangelogOperations::CHANGELOG_TYPE_PAGE:
				if ($change['operation'] === ChangelogOperations::CHANGELOG_OPERATION_INSERTED) {
					return $this->splitPageInsert($recordId);
				}
				else if ($change['operation'] === ChangelogOperations::CHANGELOG_OPERATION_UPDATED) {
					return $this->splitPageUpdate($recordId);
				}
				else {
					return $this->splitPageDelete($recordId);
				}
			case ChangelogOperations::CHANGELOG_TYPE_MODULE:
				return $this->splitModuleChange($change['operation'], $change['recordId']);
			case ChangelogOperations::CHANGELOG_TYPE_FIELD_GROUP:
				return $this->splitFieldGroupChange($change['operation'], $change['recordId']);
			case ChangelogOperations::CHANGELOG_TYPE_MEDIA_REFERENCE:
				return $this->splitMediaReferenceChange();
		}
		return false;
	}

	private function splitGlobalChange() {
		return [];
	}

	private function splitPageInsert($pid) {
		$tasks = [];

		// add global modules if not yet compiled
		$result = $this->ensureGlobalModulesTasks();
		if ($result === false) {
			return false;
		}
		else {
			$tasks += $result;
		}

		// compile page
		$result = $this->addPageCompilationTask($pid);
		if ($result === false) {
			return false;
		}
		else {
			$tasks += $result;
		}

		return $tasks;
	}

	private function splitPageUpdate($pid) {
		$tasks = [];

		// add global modules if not yet compiled
		$result = $this->ensureGlobalModulesTasks();
		if ($result === false) {
			return false;
		}
		else {
			$tasks += $result;
		}

		// add pre-content modules
		$result = $this->ensureModulesTasks($pid, ModuleOperations::MODULES_SECTION_PRE_CONTENT);
		if ($result === false) {
			return false;
		}
		else {
			$tasks += $result;
		}

		// add content modules
		$result = $this->ensureModulesTasks($pid, ModuleOperations::MODULES_SECTION_CONTENT);
		if ($result === false) {
			return false;
		}
		else {
			$tasks += $result;
		}

		// add aside content modules
		$result = $this->ensureModulesTasks($pid, ModuleOperations::MODULES_SECTION_ASIDE_CONTENT);
		if ($result === false) {
			return false;
		}
		else {
			$tasks += $result;
		}

		// add post-content modules
		$result = $this->ensureModulesTasks($pid, ModuleOperations::MODULES_SECTION_POST_CONTENT);
		if ($result === false) {
			return false;
		}
		else {
			$tasks += $result;
		}

		// compile page
		$result = $this->addPageCompilationTask($pid);
		if ($result === false) {
			return false;
		}
		else {
			$tasks += $result;
		}

		return $tasks;
	}

	private function splitPageDelete($pid) {
		return false; // TODO !!!!!!!!!!!!!!!
	}

	private function splitModuleChange($operation, $mid) {

	}

	private function splitFieldGroupChange($operation, $fgid) {

	}

	private function splitMediaReferenceChange() {

	}

	private function ensureGlobalModulesTasks() {
		$tasks = [];

		// add global pre-content modules
		$result = $this->ensureModulesTasks(null, ModuleOperations::MODULES_SECTION_GLOBAL_PRE_CONTENT);
		if ($result === false) {
			return false;
		}
		else {
			$tasks += $result;
		}

		// add global content modules
		$result = $this->ensureModulesTasks(null, ModuleOperations::MODULES_SECTION_GLOBAL_CONTENT);
		if ($result === false) {
			return false;
		}
		else {
			$tasks += $result;
		}

		// add global aside content modules
		$result = $this->ensureModulesTasks(null, ModuleOperations::MODULES_SECTION_GLOBAL_ASIDE_CONTENT);
		if ($result === false) {
			return false;
		}
		else {
			$tasks += $result;
		}

		// add global post-content modules
		$result = $this->ensureModulesTasks(null, ModuleOperations::MODULES_SECTION_GLOBAL_POST_CONTENT);
		if ($result === false) {
			return false;
		}
		else {
			$tasks += $result;
		}

		// add global logo modules
		$result = $this->ensureModulesTasks(null, ModuleOperations::MODULES_SECTION_GLOBAL_LOGO);
		if ($result === false) {
			return false;
		}
		else {
			$tasks += $result;
		}

		// add global aside header modules
		$result = $this->ensureModulesTasks(null, ModuleOperations::MODULES_SECTION_GLOBAL_ASIDE_HEADER);
		if ($result === false) {
			return false;
		}
		else {
			$tasks += $result;
		}

		// add global footer modules
		$result = $this->ensureModulesTasks(null, ModuleOperations::MODULES_SECTION_GLOBAL_FOOTER);
		if ($result === false) {
			return false;
		}
		else {
			$tasks += $result;
		}

		return $tasks;
	}

	private function ensureModulesTasks($pid, $section) {
		$modules = $this->moduleOperations->getModules($pid, $section);
		if ($modules === false) {
			return false;
		}
		$tasks = [];
		foreach ($modules as $module) {
			// check if first module file exists
			// we can however assume that a module has at least a page 0
			if (!file_exists($this->compilationPath . '/module_' .
					$module['page'] . '_' . $module['mid'] . '_0')) {
				// add module insertion task
				$result = $this->addTask(
					ChangelogOperations::CHANGELOG_TYPE_MODULE,
					ChangelogOperations::CHANGELOG_OPERATION_INSERTED,
					$module['mid']);
				if ($result === false) {
					return false;
				}
				else {
					$tasks[] = $result;
				}

				// TODO load definition and add field group tasks
			}
		}
		return $tasks;
	}

	private function addPageCompilationTask($pid) {
		return $this->addTask(
			ChangelogOperations::CHANGELOG_TYPE_PAGE,
			ChangelogOperations::CHANGELOG_OPERATION_UPDATED,
			$pid);
	}

	private function addTask($type, $operation, $recordId) {
		$result = $this->changelogOperations->addInternalChange($type, $operation, $recordId);
		if ($result === false) {
			return false;
		}
		return array('type' => $type, 'operation' => $operation, 'recordId' => $recordId);
	}

	// --------------------------------------------------------------------------------------------
	// Compilation of tasks
	// --------------------------------------------------------------------------------------------

	private function compileTask($task) {
		switch ($task['type']) {
			case ChangelogOperations::CHANGELOG_TYPE_GLOBAL:
			case ChangelogOperations::CHANGELOG_TYPE_PAGE:
				if ($task['operation'] === ChangelogOperations::CHANGELOG_OPERATION_UPDATED) {
					return $this->compilePageUpdate($task['recordId']);
				}
				else {
					return $this->compilePageDelete($task['recordId']);
				}
			case ChangelogOperations::CHANGELOG_TYPE_MODULE:
			case ChangelogOperations::CHANGELOG_TYPE_FIELD_GROUP:
			case ChangelogOperations::CHANGELOG_TYPE_MEDIA_REFERENCE:
		}
	}

	private function compilePageUpdate($pid) {
		$layoutContext = new LayoutContext($this->config);

		// load page properties
		$page = $this->pageOperations->getPage($pid);
		if ($page === false) {
			return false;
		}

		// load global properties
		$title = $this->configurationOperations->getSingleValue(
			ConfigurationOperations::CONFIGURATION_TITLE);
		if ($title === false || !Utils::hasStringContent($title)) {
			$layoutContext->setTitle(Utils::escapeString($page['title']));
		}
		else {
			$layoutContext->setTitle(Utils::escapeString($page['title']) . ' - ' .
				Utils::escapeString($title));
		}
		$description = $this->configurationOperations->getSingleValue(
			ConfigurationOperations::CONFIGURATION_DESCRIPTION);
		if ($description !== false) {
			$layoutContext->setDescription(Utils::escapeString($description));
		}
		$customHeader = $this->configurationOperations->getSingleValue(
			ConfigurationOperations::CONFIGURATION_CUSTOM_HEADER);
		if ($customHeader !== false) {
			$layoutContext->setCustomHeader($customHeader);
		}

		// load global modules (with one/first subpage)
		$globalPreContentModules = $this->readSubpageOfModules(
			null,
			ModuleOperations::MODULES_SECTION_GLOBAL_PRE_CONTENT,
			0);

		$globalContentModules = $this->readSubpageOfModules(
			null,
			ModuleOperations::MODULES_SECTION_GLOBAL_CONTENT,
			0);

		$globalAsideContentModules = $this->readSubpageOfModules(
			null,
			ModuleOperations::MODULES_SECTION_GLOBAL_ASIDE_CONTENT,
			0);

		$globalPostContentModules = $this->readSubpageOfModules(
			null,
			ModuleOperations::MODULES_SECTION_GLOBAL_POST_CONTENT);

		$globalLogoModules = $this->readSubpageOfModules(
			null,
			ModuleOperations::MODULES_SECTION_GLOBAL_LOGO,
			0);

		$globalAsideHeaderModules = $this->readSubpageOfModules(
			null,
			ModuleOperations::MODULES_SECTION_GLOBAL_ASIDE_HEADER,
			0);

		$globalFooterModules = $this->readSubpageOfModules(
			null,
			ModuleOperations::MODULES_SECTION_GLOBAL_FOOTER,
			0);

		if ($globalPreContentModules === false
				|| $globalContentModules === false
				|| $globalAsideContentModules === false
				|| $globalPostContentModules === false
				|| $globalLogoModules === false
				|| $globalAsideHeaderModules === false
				|| $globalFooterModules === false) {
			return false;
		}

		// add logo, aside header and footer to context
		$layoutContext->setLogoModules($globalLogoModules);
		$layoutContext->setAsideHeaderModules($globalAsideHeaderModules);
		$layoutContext->setFooterModules($globalFooterModules);

		// determine maximum number of subpages
		$numberOfSubpages = $this->readSubpageMaximumOfPage($pid);
		if ($numberOfSubpages === false) {
			return false;
		}

		// compile subpages
		for ($i = 0; $i < $numberOfSubpages; $i++) { 
			// pre content
			$preContentModules = $this->readSubpageOfModules(
				$pid,
				ModuleOperations::MODULES_SECTION_PRE_CONTENT,
				$i);

			// content
			$contentModules = $this->readSubpageOfModules(
				$pid,
				ModuleOperations::MODULES_SECTION_CONTENT,
				$i);

			// aside content
			$asideContentModules = $this->readSubpageOfModules(
				$pid,
				ModuleOperations::MODULES_SECTION_ASIDE_CONTENT,
				$i);

			// post content
			$postContentModules = $this->readSubpageOfModules(
				$pid,
				ModuleOperations::MODULES_SECTION_POST_CONTENT,
				$i);

			if ($preContentModules === false
					|| $contentModules === false
					|| $asideContentModules === false
					|| $postContentModules === false) {
				return false;
			}

			// add to context
			$allPreContentModules = array_merge($globalPreContentModules, $preContentModules);
			$layoutContext->setPreContentModules($allPreContentModules);
			$allContentModules = array_merge($globalContentModules, $contentModules);
			$layoutContext->setContentModules($allContentModules);
			$allAsideContentModules = array_merge($globalAsideContentModules, $asideContentModules);
			$layoutContext->setAsideContentModules($allAsideContentModules);
			$allPostContentModules = array_merge($globalPostContentModules, $postContentModules);
			$layoutContext->setPostContentModules($allPostContentModules);

			// determine subpage targets
			$targetPaths = [ $this->compilationPath . '/' . $pid . '_' . $i . '.page' ];
			if (isset($page['externalId'])) {
				if ($i == 0) {
					$targetPaths[] = $this->publicPath . '/' . $page['externalId'];
				}
				else if ($i > 0) {
					$allSubpageModules = array_merge(
						$allPreContentModules,
						$allContentModules,
						$allAsideContentModules,
						$allPostContentModules);
					$subPageTitle = $this->determinePageTitle($i, $allSubpageModules);
					$targetPaths[] = $this->publicPath . '/' . $page['externalId'] . '/' . $subPageTitle;
				}
			}

			// compile
			$layout = $this->configurationOperations->getSingleValue(
				ConfigurationOperations::CONFIGURATION_LAYOUT);
			if ($layout === false) {
				$layout = 'DefaultLayout';
			}
			$path = $this->layoutPath . '/templ.' . $layout . '.inc.php';
			if (file_exists($path) === false) {
				$this->saveErrorWithId('COMPILATION_LAYOUT_NOT_FOUND');
				return false;
			}
			ob_start();
			require($path);
			$pageContent = ob_get_contents();
			ob_end_clean();

			// save to file
			foreach ($targetPaths as $path) {
				$fileWrite = file_put_contents($path, $pageContent);
				if ($fileWrite === false) {
					$this->saveErrorWithId('COMPILATION_PAGE_SAVING_FAILED');
					return false;
				}
			}
		}
		return true;
	}

	private function compilePageDelete($pid) {

	}

	private function determinePageTitle($defaultTitle, $modules) {
		foreach ($modules as $module) {
			if (Utils::hasStringContent($module['title'])) {
				return $module['title'];
			}
		}
		return $defaultTitle;
	}

	private function readSubpageMaximumOfPage($pid) {
		$preContentMax = $this->readSubpageMaximumOfPageSection(
			$pid, ModuleOperations::MODULES_SECTION_PRE_CONTENT);
		$contentMax = $this->readSubpageMaximumOfPageSection(
			$pid, ModuleOperations::MODULES_SECTION_CONTENT);
		$asideContentMax = $this->readSubpageMaximumOfPageSection(
			$pid, ModuleOperations::MODULES_SECTION_ASIDE_CONTENT);
		$postContentMax = $this->readSubpageMaximumOfPageSection(
			$pid, ModuleOperations::MODULES_SECTION_POST_CONTENT);
		if ($preContentMax === false
				|| $contentMax === false
				|| $asideContentMax === false
				|| $postContentMax === false) {
			return false;
		}
		return max(1, $preContentMax, $contentMax, $asideContentMax, $postContentMax);
	}

	private function readSubpageMaximumOfPageSection($pid, $section) {
		$modules = $this->moduleOperations->getModules($pid, $section);
		if ($modules === false) {
			return false;
		}

		$moduleFileList = Utils::getFileList($this->compilationPath, '.module');

		$maximum = 0;
		// for each module in section
		foreach ($modules as $module) {
			// collect number of subpages
			$numberOfSubpages = 0;
			foreach ($moduleFileList as $moduleFile) {
				$prefix = $module['page'] . '_' . $module['mid'] . '_';
				if (Utils::stringStartsWith($moduleFile, $prefix)) {
					$numberOfSubpages++;
				}
			}
			// error if no subpage exists
			// a module must have at least 1 subpage.
			if ($numberOfSubpages === 0) {
				$this->saveErrorWithId('COMPILATION_MODULE_NOT_FOUND');
				return false;
			}
			$maximum = max($maximum, $numberOfSubpages);
		}
		return $maximum;
	}

	private function readSubpageOfModules($pid, $section, $subpage) {
		$modules = $this->moduleOperations->getModules($pid, $section);
		if ($modules === false) {
			return false;
		}
		$modulesWithSubpage = [];
		// for each module in section
		foreach ($modules as $module) {
			// collect subpage of module
			$path = $this->compilationPath . '/' . $module['page'] . '_' . $module['mid'] . '_' .
				$subpage . '.module';
			$fileContent = file_get_contents($path);
			// subpage does not exist, fallback to subpage 0
			if ($fileContent === false && $subpage !== 0) {
				$path = $this->compilationPath . '/' . $module['page'] . '_' . $module['mid'] . '_0' .
					'.module';
				$fileContent = file_get_contents($path);
			}
			if ($fileContent === false) {
				$this->saveErrorWithId('COMPILATION_MODULE_NOT_FOUND');
				return false;
			}
			$fileTitle = Utils::readFirstLine($fileContent);
			$fileContent = Utils::deleteFirstLine($fileContent);
			$modulesWithSubpage[] = [
				'name' => $module['definitionId'],
				'title' => $fileTitle,
				'content' => $fileContent];
		}
		return $modulesWithSubpage;
	}

	// --------------------------------------------------------------------------------------------

	private function removeChange($change) {
		return $this->changelogOperations->removeChange($change['clid']);
	}

	private function saveErrorWithId($message) {
		file_put_contents($this->compilationPath . '/error', $message);
	}
}

?>