<?php

class ModuleAdminOverview extends BasicModule {

	public function __construct() {
		global $CMS_VERSION;
		parent::__construct($CMS_VERSION, "admin-overview");
	}

	public function getContent($config) {
		?>

		Overview

		<?php
	}

}

?>