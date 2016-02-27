<?php

class Compiler {

	// database operations
	private $changelogOperations;
	private $moduleOperations;

	// member variables
	private $config;
	private $maxRuntimeStopFactor;
	private $compilationPath;

	// results
	private $finished;
	private $success;
	private $processedChanges = 0;

	// TODO add last error to config db

	public function __construct(
			$config,
			$maxRuntimeStopFactor,
			$compilationPath,
			$changelogOperations,
			$moduleOperations) {
		$this->config = $config;
		$this->maxRuntimeStopFactor = $maxRuntimeStopFactor;
		$this->compilationPath = $compilationPath;
		$this->changelogOperations = $changelogOperations;
		$this->moduleOperations = $moduleOperations;
	}

	public function compile($changelog) {
		$startRuntime = time();
		$stopRuntime = time() + ($this->config->getMaxRuntime() * $maxRuntimeStopFactor);

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
			if (empty($tasks)) {
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
			$this->removeChange($change);
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
					return $this->splitPageUpdate($recordId);
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
			// check if module file exists
			if (!file_exists($this->compilationPath . '/module_' .
					$module['page'] . '_' . $module['mid'])) {
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
			}
		}
		return $tasks;
	}

	private function addPageCompilationTask($pid) {
		return $this->addTask(
			ChangelogOperations::CHANGELOG_TYPE_PAGE,
			ChangelogOperations::CHANGELOG_OPERATION_INSERTED,
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

	private function compileTask($task) {

	}

	private function removeChange($change) {

	}

	private function saveErrorWithId($message) {

	}
}

?>