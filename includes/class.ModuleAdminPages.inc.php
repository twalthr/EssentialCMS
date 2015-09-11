<?php

class ModuleAdminPages extends BasicModule {

	private $controller;

	public function __construct($controller) {
		global $CMS_VERSION;
		parent::__construct($CMS_VERSION, "admin-pages");
		$this->controller = $controller;
	}

	public function getContent($config) {
		?>

		Pages

		<?php
	}

}

?>