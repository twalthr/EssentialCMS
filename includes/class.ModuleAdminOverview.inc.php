<?php

class ModuleAdminOverview extends BasicModule {

	private $controller;

	public function __construct($controller) {
		global $CMS_VERSION;
		parent::__construct($CMS_VERSION, "admin-overview");
		$this->controller = $controller;
	}

	public function getContent($config) {
		?>

		Overview

		<?php
	}

}

?>