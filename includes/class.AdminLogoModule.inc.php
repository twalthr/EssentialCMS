<?php

class AdminLogoModule extends BasicModule {

	public function __construct() {
		parent::__construct(1, 'admin-logo');
	}

	public function printContent($config) {
		?>
		<hgroup>
			<h1><?php echo $config->getCmsFullname(); ?></h1>
			<h2><?php $this->text('WEBSITE_ADMINISTRATION'); ?></h2>
		</hgroup>
		<?php
	}
}

?>