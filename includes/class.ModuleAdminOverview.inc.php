<?php

class ModuleAdminOverview extends BasicModule {

	public function __construct(&$controller) {
		global $CMS_VERSION;
		parent::__construct($CMS_VERSION, 'admin-overview');
		$controller->verifyLogin();
	}

	public function printContent($config) {
		?>

		Overview

		<?php
	}

}

?>