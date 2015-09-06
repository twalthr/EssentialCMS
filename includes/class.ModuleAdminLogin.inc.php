<?php

class ModuleAdminLogin extends BasicModule {

	public function __construct() {
		global $CMS_VERSION;
		parent::__construct($CMS_VERSION, "admin-login");
	}

	public function getContent() {
		?>

		Hello World

		<?php
	}

}

?>