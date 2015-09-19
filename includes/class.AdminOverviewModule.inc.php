<?php

class AdminOverviewModule extends BasicModule {

	public function __construct(&$controller) {
		global $CMS_VERSION;
		parent::__construct($CMS_VERSION, 'admin-overview');
	}

	public function printContent($config) {
		?>

		Overview

		<?php
	}

}

?>