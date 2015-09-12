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
		<section>
			<h1><?php $this->text('MENU'); ?></h1>
		</section>
		<section>
			<h1><?php $this->text('ALL_PAGES'); ?></h1>
		</section>

		Pages

		<?php
	}

}

?>