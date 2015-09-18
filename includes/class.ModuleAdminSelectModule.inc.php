<?php

class ModuleAdminSelectModule extends BasicModule {

	private $modules;

	public function __construct(&$controller) {
		global $CMS_VERSION;
		parent::__construct($CMS_VERSION, 'admin-select-module');
		$controller->verifyLogin();

		$this->loadModules();
	}

	public function printContent($config) {
		?>

		<?php echo var_dump($this->modules); ?>

		<?php
	}

	// --------------------------------------------------------------------------------------------

	private function loadModules() {
		$this->modules = RichModule::getLocalizedModulesList();
	}
}

?>